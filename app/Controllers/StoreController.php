<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Hooks;
use App\Core\Mailer;
use App\Core\Settings;
use App\Core\Throttle;
use App\Core\Uploads;
use App\Core\Validator;
use App\Services\Access;
use App\Services\Courses;
use App\Services\Enrollments;
use App\Services\Notifier;
use App\Services\Payments\Payments;
use App\Services\Payments\UpiGateway;
use App\Services\Users;

/** Public catalog, shareable course pages, checkout, UPI payment and self-registration. */
class StoreController extends Controller {
    private function publicCourse(string $slug): array {
        $c = Courses::bySlug($slug);
        if (!$c || ($c['status'] !== 'published' && !Auth::isStaff()) || (!(int)$c['is_public'] && !Auth::isStaff())) {
            abort(404, 'This course is not available.');}
        return $c;}

    /** Public storefront: every published course marked "show in store". Disabled stores are visible to staff only (preview). */
    public function catalog() {
        if (!Settings::get('store_enabled') && !Auth::isStaff()) {
            abort(404);}
        $q = $this->req->str('q');
        $cat = $this->req->str('category');
        $sort = $this->req->str('sort', 'featured');
        // One query: lesson count and total length per course, filtered in PHP (stores are small).
        $all = DB::all("SELECT c.*, COUNT(l.id) AS lesson_count, COALESCE(SUM(COALESCE(NULLIF(l.duration, 0), v.duration, 0)), 0) AS total_secs FROM {courses} c LEFT JOIN {lessons} l ON l.course_id = c.id AND l.status = 'published' LEFT JOIN {videos} v ON v.id = l.video_id WHERE c.status = 'published' AND c.is_public = 1 GROUP BY c.id ORDER BY c.sort_order, c.id DESC");
        $cats = array_values(array_unique(array_filter(array_column($all, 'category'))));
        sort($cats);
        $courses = array_values(array_filter($all, fn($c) => ($cat === '' || $c['category'] === $cat) && ($q === '' || stripos($c['title'] . ' ' . $c['subtitle'] . ' ' . $c['category'] . ' ' . $c['instructor_name'], $q) !== false)));
        foreach ($courses as &$c) {
            $c['_price'] = Payments::price($c);}
        unset($c);
        if ($sort === 'price_low' || $sort === 'price_high') {
            usort($courses, fn($a, $b) => ($sort === 'price_low' ? 1 : -1) * ($a['_price']['final'] <=> $b['_price']['final']));}
        elseif ($sort === 'new') {
            usort($courses, fn($a, $b) => $b['id'] <=> $a['id']);}
        $mine = Auth::check() ? DB::pairs('SELECT course_id, id FROM {enrollments} WHERE user_id = ?', [Auth::id()]) : [];
        return view('store/catalog', ['courses' => $courses, 'mine' => $mine, 'q' => $q, 'cats' => $cats, 'cat' => $cat, 'sort' => $sort, 'count' => count($all)]);}

    public function show(string $slug) {
        $course = $this->publicCourse($slug);
        $curr = Courses::curriculum((int)$course['id']);
        $enr = Auth::check() ? Access::enrollment(Auth::id(), (int)$course['id']) : null;
        $active = $enr && Access::enrollmentState($enr, $course) === 'active';
        $total = array_sum(array_map(fn($l) => (int)($l['duration'] ?: $l['video_duration']), $curr['lessons']));
        return view('store/course', ['course' => $course, 'modules' => $curr['modules'], 'lessonCount' => count($curr['lessons']), 'totalSecs' => $total, 'price' => Payments::price($course), 'active' => $active,
            'students' => (int)DB::val('SELECT COUNT(*) FROM {enrollments} WHERE course_id = ?', [$course['id']])]);}

    public function checkout(string $slug) {
        $course = $this->publicCourse($slug);
        if (Auth::check() && ($e = Access::enrollment(Auth::id(), (int)$course['id'])) && Access::enrollmentState($e, $course) === 'active') {
            redirect('/courses/' . $course['slug']);}
        $price = Payments::price($course, ['coupon' => $this->req->str('coupon'), 'preview' => true]);
        return view('store/checkout', ['course' => $course, 'price' => $price, 'gateways' => Payments::gateways(), 'user' => Auth::user()]);}

    /** JSON price preview (used when a coupon is entered). */
    public function quote(string $slug) {
        // Throttled so coupon codes cannot be guessed by brute force.
        $key = 'quote:' . $this->req->ip();
        if (Throttle::tooMany($key, 40, 10)) {
            json_out(['error' => 'Too many attempts. Please wait a few minutes.'], 429);}
        Throttle::hit($key);
        $course = $this->publicCourse($slug);
        $p = Payments::price($course, ['coupon' => $this->req->str('coupon'), 'preview' => true]);
        json_out(['final' => $p['final'], 'final_h' => money($p['final'], $p['currency']), 'discount' => $p['discount'], 'discount_h' => money($p['discount'], $p['currency']), 'label' => $p['label'], 'free' => $p['free'], 'error' => $p['error'] ?? null]);}

    public function placeOrder(string $slug) {
        $course = $this->publicCourse($slug);
        $user = Auth::user();
        if (!$user) {
            $key = 'signup:' . $this->req->ip();
            if (Throttle::tooMany($key, 10, 60)) {
                flash('error', 'Too many sign-ups from your network. Please try again later.');
                back();}
            $user = $this->createAccount('checkout');
            Throttle::hit($key);}
        $price = Payments::price($course, ['coupon' => $this->req->str('coupon'), 'user' => $user]);
        if (!empty($price['error'])) {
            flash('error', $price['error']);
            back();}
        if ($price['free']) {
            Enrollments::enroll((int)$user['id'], $course, ['source' => $price['coupon'] ? 'coupon' : 'free']);
            if ($price['coupon']) {
                Hooks::fire('coupon.used', $price['coupon'], $user, $course, null);}
            flash('success', 'You\'re enrolled in ' . $course['title'] . '. Enjoy!');
            redirect('/courses/' . $course['slug']);}
        $gws = Payments::gateways();
        $gk = $this->req->str('gateway') ?: (string)array_key_first($gws);
        if (!isset($gws[$gk])) {
            flash('error', 'Online payment is not configured yet. Please contact support.');
            back();}
        $order = Payments::create($user, $course, $price, $gk);
        return $gws[$gk]->begin($order, $course, $user);}

    /** Create + sign in a student from the signup form. */
    private function createAccount(string $source): array {
        $d = $this->validate(['name' => 'required|max:150', 'email' => 'required|email', 'phone' => 'max:40', 'password' => 'required|password']);
        if (Users::byEmail($d['email'])) {
            flash('error', 'An account with this email already exists. Please sign in to continue.');
            \App\Core\Session::set('_intended', $_SERVER['HTTP_REFERER'] ?? '');
            back(['email' => 'Account exists. Sign in instead.']);}
        $id = Users::create(['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?: null, 'password' => $d['password']]);
        DB::update('users', ['signup_source' => $source, 'email_verified_at' => null], 'id = ?', [$id]);
        $u = Users::find($id);
        Auth::login($u);
        Notifier::notify($id, 'Welcome to ' . Settings::get('site_name') . '!', 'Your account is ready.', '/dashboard', 'info');
        return $u;}

    public function showRegister() {
        if (!Settings::get('allow_registration', true)) {
            abort(404);}
        if (Auth::check()) {
            redirect('/dashboard');}
        return view('auth/register');}

    public function register() {
        if (!Settings::get('allow_registration', true)) {
            abort(404);}
        $key = 'signup:' . $this->req->ip();
        if (Throttle::tooMany($key, 10, 60)) {
            flash('error', 'Too many sign-ups from your network. Please try again later.');
            back();}
        $this->createAccount('register');
        Throttle::hit($key);
        flash('success', 'Welcome aboard! Your account is ready.');
        redirect(Settings::get('store_enabled') ? '/store' : '/dashboard');}

    private function ownOrder(string $ref): array {
        $o = Payments::find($ref);
        if (!$o || ((int)$o['user_id'] !== Auth::id() && !Auth::isStaff())) {
            abort(404);}
        return $o;}

    public function pay(string $ref) {
        $o = $this->ownOrder($ref);
        if ($o['status'] === 'paid') {
            flash('success', 'Payment confirmed. Your access is active.');
            redirect('/courses/' . $o['course_slug']);}
        if ($o['gateway'] !== 'upi') {
            $gw = Payments::gateways()[$o['gateway']] ?? null;
            if ($gw && $o['status'] === 'pending') {
                return $gw->begin($o, \App\Services\Courses::find((int)$o['course_id']), Auth::user());}}
        $uri = UpiGateway::uri($o, $o['course_title']);
        return view('store/pay', ['o' => $o, 'uri' => $uri, 'qr' => \App\Core\QrCode::svg($uri, 220)]);}

    public function confirm(string $ref) {
        $o = $this->ownOrder($ref);
        if (!in_array($o['status'], ['pending', 'rejected'], true)) {
            back([], false);}
        $utr = strtoupper(preg_replace('/\s+/', '', $this->req->str('utr')));
        if (!preg_match('/^[A-Z0-9]{6,30}$/', $utr)) {
            flash('error', 'Enter the UPI transaction ID / UTR (usually a 12-digit number) shown in your payment app.');
            back(['utr' => 'Invalid reference']);}
        if (DB::val("SELECT id FROM {orders} WHERE utr = ? AND id <> ? AND status IN ('submitted','paid')", [$utr, $o['id']])) {
            flash('error', 'This transaction reference was already used for another order.');
            back(['utr' => 'Already used']);}
        $proof = $o['proof'];
        if ($f = $this->req->file('proof')) {
            try {
                $proof = $this->storeProof($f);}
            catch (\Throwable $e) {
                flash('error', 'Screenshot: ' . $e->getMessage());
                back();}}
        if (Settings::get('upi_require_proof') && !$proof) {
            flash('error', 'Please attach a screenshot of the successful payment.');
            back();}
        DB::update('orders', ['status' => 'submitted', 'utr' => $utr, 'proof' => $proof, 'payer_note' => mb_substr($this->req->str('note'), 0, 255) ?: null, 'submitted_at' => now(), 'updated_at' => now()], 'id = ?', [$o['id']]);
        $course = Courses::find((int)$o['course_id']);
        $user = Users::find((int)$o['user_id']);
        if (Settings::get('upi_provisional_access')) {
            Enrollments::enroll((int)$user['id'], $course, ['source' => 'provisional', 'notes' => 'Pending verification of ' . $o['ref']]);}
        Mailer::queueTemplate('order_received', $user['email'], $user['name'], Payments::vars($o, $course));
        $staff = DB::col("SELECT id FROM {users} WHERE role IN ('super_admin','admin') AND status = 'active'");
        Notifier::many(array_map(fn($sid) => ['user_id' => (int)$sid, 'type' => 'order', 'title' => 'Payment to verify: ' . money($o['amount'], $o['currency']), 'body' => $user['name'] . ' · ' . $course['title'] . ' · UTR ' . $utr, 'url' => '/admin/orders?status=submitted', 'dedupe_key' => 'order-' . $o['id'] . '-' . substr(md5($utr), 0, 6)], $staff));
        Audit::log('order.submitted', 'order', (int)$o['id'], ['utr' => $utr]);
        Hooks::fire('order.submitted', $o);
        flash('success', Settings::get('upi_provisional_access') ? 'Thanks! You can start learning now while we verify your payment.' : 'Thanks! We\'re verifying your payment and will email you shortly.');
        redirect(Settings::get('upi_provisional_access') ? '/courses/' . $course['slug'] : '/orders/' . $o['ref'] . '/pay');}

    private function storeProof(array $f): string {
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1048576) {
            throw new \RuntimeException('Upload an image up to 5 MB.');}
        $mime = Uploads::mime($f['tmp_name']);
        $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$mime] ?? null;
        if (!$ext || !@getimagesize($f['tmp_name'])) {
            throw new \RuntimeException('Upload a PNG, JPG or WebP image.');}
        $dir = STORAGE_PATH . '/private/proofs';
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);}
        $name = gmdate('Ym') . '-' . bin2hex(random_bytes(10)) . '.' . $ext;
        move_uploaded_file($f['tmp_name'], $dir . '/' . $name);
        return $name;}

    public function orders() {
        $rows = DB::all('SELECT o.*, c.title AS course_title, c.slug AS course_slug FROM {orders} o JOIN {courses} c ON c.id = o.course_id WHERE o.user_id = ? ORDER BY o.id DESC', [Auth::id()]);
        return view('store/orders', ['rows' => $rows]);}

    public function cancel(string $ref) {
        $o = $this->ownOrder($ref);
        if ($o['status'] === 'pending') {
            DB::update('orders', ['status' => 'cancelled', 'updated_at' => now()], 'id = ?', [$o['id']]);}
        $this->ok('Order cancelled.', '/orders');}

    /** Serve module assets from app/Modules/<M>/assets (enabled modules only). */
    public function moduleAsset(string $module, string $file) {
        $module = basename($module);
        $file = basename($file);
        $path = APP_PATH . '/Modules/' . $module . '/assets/' . $file;
        $types = ['css' => 'text/css', 'js' => 'application/javascript', 'svg' => 'image/svg+xml', 'png' => 'image/png', 'webp' => 'image/webp', 'jpg' => 'image/jpeg'];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!isset($types[$ext]) || !is_file($path) || !in_array($module, \App\Core\ModuleManager::enabled(), true)) {
            abort(404);}
        \App\Core\Session::close();
        header('Content-Type: ' . $types[$ext] . '; charset=utf-8');
        header('Cache-Control: public, max-age=2592000');
        readfile($path);
        exit;}}
