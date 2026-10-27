<?php
namespace App\Modules\Certificates;

use App\Core\App;
use App\Core\Auth;
use App\Core\Hooks;
use App\Core\Module as BaseModule;
use App\Core\View;

/** Certificates of completion: auto-issued on course completion, printable, publicly verifiable. */
class Module extends BaseModule {
    public function register(App $app): void {
        $this->permissions(['super_admin' => ['certificates.manage'], 'admin' => ['certificates.manage']]);
        $this->adminNav('Certificates', '/admin/certificates', 'trophy', 'certificates.manage', 40);
        $this->studentNav('Certificates', '/certificates', 'trophy', 30);
        $c = 'App\\Modules\\Certificates\\Controller@';
        Hooks::on('app.routes', function ($r) use ($c) {
            $r->get('/verify', $c . 'verifyForm');
            $r->get('/verify/{code}', $c . 'verify');
            $r->group('', ['auth'], function ($r) use ($c) {
                $r->get('/certificates', $c . 'mine');
                $r->get('/certificates/{code}', $c . 'show');});
            $r->group('/admin/certificates', ['auth', 'staff', 'can:certificates.manage'], function ($r) use ($c) {
                $r->get('', $c . 'adminIndex');
                $r->post('', $c . 'adminIssue');
                $r->post('/{id}/revoke', $c . 'adminRevoke');
                $r->post('/{id}/restore', $c . 'adminRestore');});});
        Hooks::on('course.completed', function (int $userId, int $courseId) {
            Certificates::issue($userId, $courseId);});
        Hooks::on('course.header_actions', function (array $items, array $course, $enr, $user) {
            if ($user && ($cert = Certificates::forUserCourse((int)$user['id'], (int)$course['id']))) {
                $items[] = '<a class="btn btn-dark mt-4" href="' . e(url('/certificates/' . $cert['code'])) . '">' . icon('trophy') . 'View certificate</a> ';}
            return $items;});
        Hooks::on('profile.panels', function (array $items, array $user) {
            $items[] = View::render('certificates/_panel', ['certs' => Certificates::forUser((int)$user['id'])]);
            return $items;});
        Hooks::on('dashboard.side', function (array $items, array $user) {
            $certs = Certificates::forUser((int)$user['id']);
            if ($certs) {
                $items[] = View::render('certificates/_panel', ['certs' => array_slice($certs, 0, 3)]);}
            return $items;});}}
