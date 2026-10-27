<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Services\Access;
use App\Services\Courses;
use App\Services\Learning;
use App\Services\Progress;

class CourseController extends Controller {
    public function index() {
        $uid = Auth::id();
        $enrollments = Learning::enrollments($uid);
        $progress = Progress::forCourses($uid, array_column($enrollments, 'id'));
        $filter = $this->req->str('filter', 'all');
        return view('student/courses', ['enrollments' => $enrollments, 'progress' => $progress, 'filter' => $filter]);}

    public function show(string $slug) {
        $user = Auth::user();
        $course = Courses::bySlug($slug);
        $preview = Auth::can('courses.preview');
        if (!$course || ($course['status'] !== 'published' && !$preview)) {
            abort(404);}
        $enr = Access::enrollment((int)$user['id'], (int)$course['id']);
        if (!$enr && !$preview) {
            abort(403, 'You are not enrolled in this course.');}
        $data = Learning::annotate(Courses::curriculum((int)$course['id']), $enr, $course, Progress::lessonMap((int)$user['id'], (int)$course['id']), $preview && !$enr);
        if ($preview && $enr) {
            $data['state'] = 'active';}
        return view('student/course', ['course' => $course, 'enr' => $enr, 'c' => $data, 'preview' => $preview, 'quote' => Courses::quote($course)]);}}
