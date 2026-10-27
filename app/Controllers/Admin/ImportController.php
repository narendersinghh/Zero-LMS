<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csv;
use App\Core\DB;
use App\Services\Access;
use App\Services\Enrollments;
use App\Services\Users;

/** CSV bulk user creation + enrollment. Batched queries; emails are queued and sent in the background. */
class ImportController extends Controller {
    public function index() {
        return view('admin/import/index', ['courses' => DB::all("SELECT id, title, access_days, end_date, start_date FROM {courses} WHERE status <> 'archived' ORDER BY title"),
            'history' => DB::all('SELECT i.*, u.name AS by_name FROM {imports} i LEFT JOIN {users} u ON u.id = i.user_id ORDER BY i.id DESC LIMIT 10')]);}

    public function sample() {
        Csv::download('zeroes-import-sample.csv', ['email', 'name', 'phone'], [['jane@example.com', 'Jane Doe', '+1 555 0100'], ['raj@example.com', 'Raj Patel', '']]);}

    public function show(string $id) {
        $imp = DB::one('SELECT * FROM {imports} WHERE id = ?', [(int)$id]) ?? abort(404);
        return view('admin/import/show', ['imp' => $imp, 'errors' => json_decode((string)$imp['errors'], true) ?: []]);}

    public function store() {
        @set_time_limit(300);
        $f = $this->req->file('csv');
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Please choose a CSV file.');
            back();}
        if (!preg_match('/\.(csv|txt)$/i', $f['name']) || $f['size'] > 10 * 1048576) {
            flash('error', 'Upload a .csv file up to 10 MB.');
            back();}
        $courseIds = array_values(array_filter(array_map('intval', $this->req->arr('courses'))));
        $courses = $courseIds ? DB::all('SELECT * FROM {courses} WHERE id IN (' . DB::in($courseIds) . ')', $courseIds) : [];
        $start = $this->req->str('starts_at') ? to_utc($this->req->str('starts_at')) : now();
        $expires = $this->req->str('expires_at') ? to_utc($this->req->str('expires_at')) : null;
        $sendNew = $this->req->bool('email_new');
        $sendExisting = $this->req->bool('email_existing');
        $rows = Csv::read($f['tmp_name']);
        $errors = [];
        $valid = [];
        foreach ($rows as $i => $r) {
            $email = strtolower(trim($r['email'] ?? $r['e_mail'] ?? $r['email_address'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['row' => $i + 2, 'value' => $email, 'error' => 'Invalid email'];
                continue;}
            if (isset($valid[$email])) {
                $errors[] = ['row' => $i + 2, 'value' => $email, 'error' => 'Duplicate in file'];
                continue;}
            $name = trim($r['name'] ?? $r['full_name'] ?? trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')));
            $valid[$email] = ['email' => $email, 'name' => mb_substr($name, 0, 150), 'phone' => mb_substr($r['phone'] ?? $r['mobile'] ?? '', 0, 40)];}
        $stats = ['created' => 0, 'existing' => 0, 'enrolled' => 0];
        $titles = array_column($courses, 'title');
        $emails = array_keys($valid);
        DB::transaction(function () use (&$stats, $valid, $emails, $courses, $start, $expires) {
            $existing = [];
            foreach (array_chunk($emails, 500) as $chunk) {
                foreach (DB::all('SELECT id, email, name, password_hash, role, status FROM {users} WHERE email IN (' . DB::in($chunk) . ')', $chunk) as $u) {
                    $existing[$u['email']] = $u;}}
            $new = [];
            foreach ($valid as $email => $v) {
                if (!isset($existing[$email])) {
                    $new[] = ['role' => 'student', 'name' => $v['name'] ?: Users::nameFromEmail($email), 'email' => $email, 'phone' => $v['phone'] ?: null, 'status' => 'active', 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()];}}
            DB::insertMany('users', $new);
            $stats['created'] = count($new);
            $stats['existing'] = count($existing);
            $ids = [];
            foreach (array_chunk($emails, 500) as $chunk) {
                $ids += DB::pairs('SELECT email, id FROM {users} WHERE email IN (' . DB::in($chunk) . ')', $chunk);}
            foreach ($courses as $c) {
                $exp = Access::computeExpiry($c, (int)strtotime($start . ' UTC'), $expires);
                $erows = [];
                foreach ($ids as $uid) {
                    $erows[] = ['user_id' => (int)$uid, 'course_id' => (int)$c['id'], 'status' => 'active', 'starts_at' => $start, 'expires_at' => $exp, 'source' => 'csv', 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()];}
                // Existing enrollments are re-activated with the new dates.
                $stats['enrolled'] += DB::insertMany('enrollments', $erows, 'ON DUPLICATE KEY UPDATE status = VALUES(status), starts_at = VALUES(starts_at), expires_at = VALUES(expires_at), updated_at = VALUES(updated_at)');
                $notes = array_map(fn($uid) => ['user_id' => (int)$uid, 'type' => 'enrollment', 'title' => 'You have access to ' . $c['title'], 'body' => 'Your course is ready. Jump in and start learning.', 'url' => '/courses/' . $c['slug'], 'dedupe_key' => 'enroll-' . $c['id'] . '-csv-' . gmdate('YmdH')], array_values($ids));
                \App\Services\Notifier::many($notes);}});
        // Queue emails outside the transaction.
        $queued = 0;
        if ($sendNew || $sendExisting) {
            $all = [];
            foreach (array_chunk($emails, 500) as $chunk) {
                foreach (DB::all('SELECT * FROM {users} WHERE email IN (' . DB::in($chunk) . ')', $chunk) as $u) {
                    $all[] = $u;}}
            foreach ($all as $u) {
                if ($u['status'] !== 'active') {
                    continue;}
                if (!$u['password_hash'] && $sendNew) {
                    Users::sendWelcome($u, $titles);
                    $queued++;}
                elseif ($u['password_hash'] && $sendExisting) {
                    foreach ($courses as $c) {
                        Enrollments::sendEnrolledEmail($u, $c, Access::computeExpiry($c, (int)strtotime($start . ' UTC'), $expires), $start);
                        $queued++;}}}}
        $impId = DB::insert('imports', ['user_id' => Auth::id(), 'filename' => mb_substr($f['name'], 0, 255), 'course_ids' => implode(',', $courseIds), 'total' => count($rows),
            'created' => $stats['created'], 'existing' => $stats['existing'], 'enrolled' => count($courses) * count($valid), 'skipped' => count($errors), 'errors' => json_encode(array_slice($errors, 0, 1000)), 'created_at' => now()]);
        Audit::log('import.completed', 'import', $impId, $stats + ['emails' => $queued, 'courses' => $courseIds]);
        flash('success', sprintf('Import finished: %d new accounts, %d existing, %d skipped. %d emails queued.', $stats['created'], $stats['existing'], count($errors), $queued));
        redirect('/admin/import/' . $impId);}}
