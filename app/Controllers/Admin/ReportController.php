<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csv;
use App\Core\DB;
use App\Core\Paginator;
use App\Services\Courses;
use App\Services\Payments\Payments;

/** Reports: platform overview (period based) and per-course drill-down. All aggregates use grouped queries. */
class ReportController extends Controller {
    public const PERIODS = [7 => '7 days', 30 => '30 days', 90 => '90 days'];

    /** Seconds to add to UTC timestamps so daily buckets follow the site timezone. */
    private function tzOffset(): int {
        return (new \DateTime('now', site_tz()))->getOffset();}

    /** Day keys (site timezone) for the last $days days, oldest first. */
    private function dayKeys(int $days): array {
        $keys = [];
        $d = (new \DateTime('today', site_tz()))->modify('-' . ($days - 1) . ' days');
        for ($i = 0; $i < $days; $i++) {
            $keys[$d->format('Y-m-d')] = $d->format('M j');
            $d->modify('+1 day');}
        return $keys;}

    /** Daily grouped values for a timestamp column. $expr is the aggregate (COUNT(*), SUM(amount)…). */
    private function daily(string $table, string $col, string $expr, string $since, string $where = '1=1', array $p = []): array {
        $off = $this->tzOffset();
        return DB::pairs("SELECT DATE(DATE_ADD($col, INTERVAL $off SECOND)) d, $expr FROM {" . $table . "} WHERE $col >= ? AND $where GROUP BY d", array_merge([$since], $p));}

    private function fill(array $keys, array $vals): array {
        return array_map(fn($k) => (float)($vals[$k] ?? 0), array_keys($keys));}

    private static function delta(float $cur, float $prev): ?float {
        if ($prev <= 0) {
            return $cur > 0 ? 100.0 : null;}
        return round(($cur - $prev) / $prev * 100, 1);}

    /** Per-enrollment progress rows (enrollment id, course, done, started, total) as a derived table SQL. */
    private function enrollmentProgressSql(string $where = '1=1'): string {
        return "SELECT e.id, e.course_id, e.user_id, e.created_at, e.completed_at, COUNT(p.id) AS started, COUNT(p.completed_at) AS done, COALESCE(t.total, 0) AS total
            FROM {enrollments} e
            LEFT JOIN {lesson_progress} p ON p.user_id = e.user_id AND p.course_id = e.course_id
            LEFT JOIN (SELECT course_id, COUNT(*) total FROM {lessons} WHERE status = 'published' GROUP BY course_id) t ON t.course_id = e.course_id
            WHERE $where GROUP BY e.id";}

    private function funnel(string $where = '1=1', array $p = []): array {
        $r = DB::one('SELECT COUNT(*) enrolled, SUM(started > 0) started, SUM(total > 0 AND done / total >= .5) halfway, SUM(completed_at IS NOT NULL OR (total > 0 AND done >= total)) completed, COALESCE(AVG(IF(total > 0, LEAST(done / total, 1), 0)) * 100, 0) avg_progress FROM (' . $this->enrollmentProgressSql($where) . ') x', $p);
        return array_map(fn($v) => (float)$v, $r ?: []);}

    public function index() {
        $days = (int)$this->req->query('days', 30);
        $days = isset(self::PERIODS[$days]) ? $days : 30;
        $now = time();
        $from = gmdate('Y-m-d H:i:s', $now - $days * 86400);
        $prevFrom = gmdate('Y-m-d H:i:s', $now - 2 * $days * 86400);
        $keys = $this->dayKeys($days);
        $since = to_utc(array_key_first($keys) . ' 00:00:00');
        // Daily series
        $enrSeries = $this->fill($keys, $this->daily('enrollments', 'created_at', 'COUNT(*)', $since));
        $revSeries = $this->fill($keys, $this->daily('orders', 'paid_at', 'SUM(amount)', $since, "status = 'paid'"));
        $actSeries = $this->fill($keys, $this->daily('lesson_progress', 'last_viewed_at', 'COUNT(DISTINCT user_id)', $since));
        $cmpSeries = $this->fill($keys, $this->daily('lesson_progress', 'completed_at', 'COUNT(*)', $since));
        // KPI totals: current vs previous period, one query per source
        $e = DB::one('SELECT SUM(created_at >= ?) cur, SUM(created_at >= ? AND created_at < ?) prev FROM {enrollments} WHERE created_at >= ?', [$from, $prevFrom, $from, $prevFrom]);
        $o = DB::one("SELECT COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount END), 0) cur, COALESCE(SUM(CASE WHEN paid_at >= ? AND paid_at < ? THEN amount END), 0) prev, SUM(paid_at >= ?) orders FROM {orders} WHERE status = 'paid' AND paid_at >= ?", [$from, $prevFrom, $from, $from, $prevFrom]);
        $a = DB::one('SELECT COUNT(DISTINCT CASE WHEN last_viewed_at >= ? THEN user_id END) cur, COUNT(DISTINCT CASE WHEN last_viewed_at >= ? AND last_viewed_at < ? THEN user_id END) prev FROM {lesson_progress} WHERE last_viewed_at >= ?', [$from, $prevFrom, $from, $prevFrom]);
        $c = DB::one('SELECT SUM(completed_at >= ?) cur, SUM(completed_at >= ? AND completed_at < ?) prev FROM {lesson_progress} WHERE completed_at >= ?', [$from, $prevFrom, $from, $prevFrom]);
        $fCur = $this->funnel('e.created_at >= ?', [$from]);
        $fPrev = $this->funnel('e.created_at >= ? AND e.created_at < ?', [$prevFrom, $from]);
        $kpis = [
            ['New enrollments', number_format((int)$e['cur']), self::delta((float)$e['cur'], (float)$e['prev']), $enrSeries, 'users', 'yellow'],
            ['Revenue', money((float)$o['cur']), self::delta((float)$o['cur'], (float)$o['prev']), $revSeries, 'key', 'green', (int)$o['orders'] . ' paid orders'],
            ['Active learners', number_format((int)$a['cur']), self::delta((float)$a['cur'], (float)$a['prev']), $actSeries, 'activity', 'blue'],
            ['Lessons completed', number_format((int)$c['cur']), self::delta((float)$c['cur'], (float)$c['prev']), $cmpSeries, 'check-circle', 'purple'],
            ['Avg. progress (new students)', round($fCur['avg_progress'] ?? 0) . '%', isset($fPrev['enrolled']) && $fPrev['enrolled'] > 0 ? round(($fCur['avg_progress'] ?? 0) - $fPrev['avg_progress'], 1) : null, [], 'target', 'orange', 'Students enrolled in this period', true],];
        $funnel = $this->funnel();
        // Top courses (lifetime) with revenue and completion
        $courses = DB::all("SELECT c.id, c.title, c.slug, c.status, c.cover_image, c.color, c.illustration, COALESCE(t.total, 0) lessons, COALESCE(x.students, 0) students, COALESCE(x.avg_progress, 0) avg_progress, COALESCE(x.completed, 0) completed, COALESCE(r.revenue, 0) revenue, COALESCE(r.orders, 0) orders
            FROM {courses} c
            LEFT JOIN (SELECT course_id, COUNT(*) total FROM {lessons} WHERE status = 'published' GROUP BY course_id) t ON t.course_id = c.id
            LEFT JOIN (SELECT course_id, COUNT(*) students, AVG(IF(total > 0, LEAST(done / total, 1), 0)) * 100 avg_progress, SUM(completed_at IS NOT NULL OR (total > 0 AND done >= total)) completed FROM (" . $this->enrollmentProgressSql() . ") ep GROUP BY course_id) x ON x.course_id = c.id
            LEFT JOIN (SELECT course_id, SUM(amount) revenue, COUNT(*) orders FROM {orders} WHERE status = 'paid' GROUP BY course_id) r ON r.course_id = c.id
            ORDER BY c.status = 'archived', students DESC, c.id DESC");
        // Lessons: views for "most watched" and drop-off (computed in PHP in curriculum order)
        $lessons = DB::all("SELECT l.id, l.title, l.course_id, c.title course_title, COALESCE(v.viewers, 0) viewers, COALESCE(v.completions, 0) completions, COALESCE(v.avg_pct, 0) avg_pct
            FROM {lessons} l JOIN {courses} c ON c.id = l.course_id LEFT JOIN {modules} m ON m.id = l.module_id
            LEFT JOIN (SELECT lesson_id, COUNT(*) viewers, COUNT(completed_at) completions, AVG(percent) avg_pct FROM {lesson_progress} GROUP BY lesson_id) v ON v.lesson_id = l.id
            WHERE l.status = 'published' AND c.status <> 'archived' ORDER BY l.course_id, COALESCE(m.sort_order, 999999), l.sort_order, l.id");
        $watched = array_values(array_filter($lessons, fn($l) => $l['viewers'] > 0));
        usort($watched, fn($x, $y) => [$y['viewers'], $y['completions']] <=> [$x['viewers'], $x['completions']]);
        $drops = [];
        $prev = null;
        foreach ($lessons as $l) {
            if ($prev && $prev['course_id'] === $l['course_id'] && $prev['viewers'] > 0 && $l['viewers'] < $prev['viewers']) {
                $drops[] = $l + ['from' => $prev['title'], 'prev_viewers' => (int)$prev['viewers'], 'drop' => (int)$prev['viewers'] - (int)$l['viewers'], 'drop_pct' => round(($prev['viewers'] - $l['viewers']) / $prev['viewers'] * 100)];}
            $prev = $l;}
        usort($drops, fn($x, $y) => [$y['drop'], $y['drop_pct']] <=> [$x['drop'], $x['drop_pct']]);
        return view('admin/reports/index', ['days' => $days, 'keys' => $keys, 'kpis' => $kpis, 'enrSeries' => $enrSeries, 'revSeries' => $revSeries, 'actSeries' => $actSeries, 'cmpSeries' => $cmpSeries,
            'funnel' => $funnel, 'courses' => $courses, 'watched' => array_slice($watched, 0, 6), 'drops' => array_slice($drops, 0, 5), 'tzName' => site_tz()->getName()]);}

    /** Students of a course with progress; $where/$having/$order are built from whitelisted options only. */
    private function studentsQuery(int $courseId, string $having = '1=1', string $order = 'done DESC, u.name', string $search = ''): array {
        $p = [$courseId];
        $w = 'e.course_id = ?';
        if ($search !== '') {
            $w .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
            array_push($p, DB::like($search), DB::like($search));}
        $sql = "SELECT u.id, u.name, u.email, u.avatar, e.id enrollment_id, e.starts_at, e.expires_at, e.status, e.completed_at, e.created_at enrolled_at,
            COUNT(p.id) AS viewed, COUNT(p.completed_at) AS done, MAX(p.last_viewed_at) AS last_view, COALESCE(SUM(p.position), 0) AS watched
            FROM {enrollments} e JOIN {users} u ON u.id = e.user_id
            LEFT JOIN {lesson_progress} p ON p.user_id = e.user_id AND p.course_id = e.course_id
            WHERE $w GROUP BY e.id HAVING $having ORDER BY $order";
        return [$sql, $p];}

    private function courseRows(int $courseId, string $limit = ''): array {
        [$sql, $p] = $this->studentsQuery($courseId);
        return DB::all($sql . $limit, $p);}

    public function course(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        $cid = (int)$course['id'];
        $total = (int)DB::val("SELECT COUNT(*) FROM {lessons} WHERE course_id = ? AND status = 'published'", [$cid]);
        $week = gmdate('Y-m-d H:i:s', time() - 7 * 86400);
        // Students table: filters, search, sort (whitelisted)
        $filter = $this->req->str('filter', 'all');
        $filters = ['all' => 'All', 'not_started' => 'Not started', 'in_progress' => 'In progress', 'completed' => 'Completed', 'inactive7' => 'Inactive 7d+'];
        $filter = isset($filters[$filter]) ? $filter : 'all';
        $done = $total > 0 ? "(e.completed_at IS NOT NULL OR COUNT(p.completed_at) >= $total)" : 'e.completed_at IS NOT NULL';
        $having = ['all' => '1=1', 'not_started' => 'COUNT(p.id) = 0', 'in_progress' => "COUNT(p.id) > 0 AND NOT $done", 'completed' => $done,
            'inactive7' => "(MAX(p.last_viewed_at) IS NULL OR MAX(p.last_viewed_at) < '$week')"][$filter];
        $sorts = ['progress' => 'done DESC, last_view DESC', 'activity' => '(MAX(p.last_viewed_at) IS NULL), last_view DESC', 'name' => 'u.name', 'newest' => 'e.created_at DESC'];
        $sort = $this->req->str('sort', 'progress');
        $sort = isset($sorts[$sort]) ? $sort : 'progress';
        $q = $this->req->str('q');
        [$sql, $p] = $this->studentsQuery($cid, $having, $sorts[$sort], $q);
        $pg = new Paginator((int)DB::val("SELECT COUNT(*) FROM ($sql) t", $p), 25);
        $rows = DB::all($sql . $pg->limitSql(), $p);
        // KPIs + funnel
        $f = $this->funnel('e.course_id = ?', [$cid]);
        $active7 = (int)DB::val('SELECT COUNT(DISTINCT user_id) FROM {lesson_progress} WHERE course_id = ? AND last_viewed_at >= ?', [$cid, $week]);
        $rev = DB::one("SELECT COALESCE(SUM(amount), 0) revenue, COUNT(*) orders FROM {orders} WHERE course_id = ? AND status = 'paid'", [$cid]);
        // Daily activity (30 days)
        $keys = $this->dayKeys(30);
        $since = to_utc(array_key_first($keys) . ' 00:00:00');
        $series = [
            'enrolled' => $this->fill($keys, $this->daily('enrollments', 'created_at', 'COUNT(*)', $since, 'course_id = ?', [$cid])),
            'active' => $this->fill($keys, $this->daily('lesson_progress', 'last_viewed_at', 'COUNT(DISTINCT user_id)', $since, 'course_id = ?', [$cid])),
            'completed' => $this->fill($keys, $this->daily('lesson_progress', 'completed_at', 'COUNT(*)', $since, 'course_id = ?', [$cid])),];
        // Lesson engagement in curriculum order
        $lessonStats = DB::all("SELECT l.id, l.title, l.release_day, m.title module_title, COALESCE(v.viewers, 0) viewers, COALESCE(v.completions, 0) completions, COALESCE(v.avg_pct, 0) avg_pct
            FROM {lessons} l LEFT JOIN {modules} m ON m.id = l.module_id
            LEFT JOIN (SELECT lesson_id, COUNT(*) viewers, COUNT(completed_at) completions, AVG(percent) avg_pct FROM {lesson_progress} WHERE course_id = ? GROUP BY lesson_id) v ON v.lesson_id = l.id
            WHERE l.course_id = ? AND l.status = 'published' ORDER BY COALESCE(m.sort_order, 999999), l.sort_order, l.id", [$cid, $cid]);
        $dist = DB::one('SELECT SUM(pct = 0) a, SUM(pct > 0 AND pct < 25) b, SUM(pct >= 25 AND pct < 50) c, SUM(pct >= 50 AND pct < 75) d, SUM(pct >= 75 AND pct < 100) e, SUM(pct >= 100) f FROM (SELECT IF(total > 0, LEAST(done / total, 1) * 100, 0) pct FROM (' . $this->enrollmentProgressSql('e.course_id = ?') . ') x) y', [$cid]) ?: [];
        return view('admin/reports/course', ['course' => $course, 'rows' => $rows, 'pg' => $pg, 'total' => $total, 'lessonStats' => $lessonStats, 'dist' => $dist, 'f' => $f, 'active7' => $active7,
            'rev' => $rev, 'keys' => $keys, 'series' => $series, 'filters' => $filters, 'filter' => $filter, 'sort' => $sort, 'q' => $q, 'price' => Payments::price($course), 'week' => $week]);}

    public function export(string $id) {
        $course = Courses::find((int)$id) ?? abort(404);
        $total = (int)DB::val("SELECT COUNT(*) FROM {lessons} WHERE course_id = ? AND status = 'published'", [$course['id']]);
        $out = [];
        foreach ($this->courseRows((int)$course['id']) as $r) {
            $out[] = [$r['name'], $r['email'], $r['status'], $r['starts_at'], $r['expires_at'] ?: 'Lifetime', $r['done'] . '/' . $total, $total ? min(100, floor($r['done'] / $total * 100)) . '%' : '0%', $r['completed_at'], $r['last_view']];}
        \App\Core\Audit::log('report.exported', 'course', (int)$course['id']);
        Csv::download(slugify($course['title']) . '-progress-' . gmdate('Y-m-d') . '.csv', ['Name', 'Email', 'Enrollment status', 'Starts (UTC)', 'Expires (UTC)', 'Lessons completed', 'Progress', 'Course completed (UTC)', 'Last activity (UTC)'], $out);}}
