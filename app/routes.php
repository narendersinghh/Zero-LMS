<?php
/** @var \App\Core\Router $r */
$r = app()->router();

$r->get('/', function () {
    if (!auth() && setting('store_enabled') && setting('store_home', true)) {
        return (new \App\Controllers\StoreController())->catalog();}
    redirect(auth() ? (can('admin.access') ? '/admin' : '/dashboard') : '/login');});

// Authentication
$r->get('/login', 'AuthController@showLogin', ['guest']);
$r->post('/login', 'AuthController@login', ['guest']);
$r->post('/logout', 'AuthController@logout');
$r->get('/password/forgot', 'AuthController@showForgot', ['guest']);
$r->post('/password/forgot', 'AuthController@forgot', ['guest']);
$r->get('/password/reset/{token}', 'AuthController@showReset');
$r->post('/password/reset/{token}', 'AuthController@reset');
$r->get('/password/setup/{token}', 'AuthController@showReset');
$r->post('/password/setup/{token}', 'AuthController@reset');
$r->get('/cron/run', 'SystemCronController@run');
$r->get('/register', 'StoreController@showRegister', ['guest']);
$r->post('/register', 'StoreController@register', ['guest']);

// Public catalog & shareable course links (anyone can buy + create an account)
$r->get('/store', 'StoreController@catalog');
$r->get('/explore', function () {
    redirect('/store');});
$r->get('/c/{slug}', 'StoreController@show');
$r->get('/c/{slug}/checkout', 'StoreController@checkout');
$r->post('/c/{slug}/checkout', 'StoreController@placeOrder');
$r->get('/c/{slug}/quote', 'StoreController@quote');
$r->get('/m/{module}/{file}', 'StoreController@moduleAsset');

// Student area
$r->group('', ['auth'], function ($r) {
    $r->get('/dashboard', 'DashboardController@index');
    $r->get('/courses', 'CourseController@index');
    $r->get('/courses/{slug}', 'CourseController@show');
    $r->get('/courses/{slug}/lessons/{id}', 'LessonController@show');
    $r->post('/lessons/{id}/progress', 'LessonController@progress');
    $r->post('/lessons/{id}/complete', 'LessonController@complete');
    $r->get('/media/stream/{token}', 'MediaController@stream');
    $r->get('/lessons/{id}/source', 'LessonController@source');
    $r->get('/files/{name}', 'FileController@show');
    $r->get('/orders', 'StoreController@orders');
    $r->get('/orders/{ref}/pay', 'StoreController@pay');
    $r->post('/orders/{ref}/confirm', 'StoreController@confirm');
    $r->post('/orders/{ref}/cancel', 'StoreController@cancel');
    $r->get('/notifications', 'NotificationController@index');
    $r->post('/notifications/read-all', 'NotificationController@readAll');
    $r->get('/notifications/{id}/open', 'NotificationController@open');
    $r->get('/profile', 'ProfileController@show');
    $r->post('/profile', 'ProfileController@update');
    $r->post('/profile/password', 'ProfileController@password');
    $r->post('/profile/avatar', 'ProfileController@avatar');});

// Admin panel
$r->group('/admin', ['auth', 'staff', 'can:admin.access'], function ($r) {
    $r->get('', 'Admin\DashboardController@index');

    $r->group('', ['can:courses.manage'], function ($r) {
        $r->get('/courses', 'Admin\CourseController@index');
        $r->get('/courses/create', 'Admin\CourseController@create');
        $r->post('/courses', 'Admin\CourseController@store');
        $r->get('/courses/{id}/edit', 'Admin\CourseController@edit');
        $r->post('/courses/{id}', 'Admin\CourseController@update');
        $r->post('/courses/{id}/duplicate', 'Admin\CourseController@duplicate');
        $r->post('/courses/{id}/status', 'Admin\CourseController@status');
        $r->post('/courses/{id}/delete', 'Admin\CourseController@destroy', ['can:courses.delete']);
        $r->get('/courses/{id}/curriculum', 'Admin\CurriculumController@index');
        $r->post('/courses/{id}/modules', 'Admin\CurriculumController@storeModule');
        $r->post('/modules/{id}', 'Admin\CurriculumController@updateModule');
        $r->post('/modules/{id}/delete', 'Admin\CurriculumController@deleteModule');
        $r->post('/courses/{id}/reorder', 'Admin\CurriculumController@reorder');
        $r->post('/editor/upload', 'FileController@upload');
        $r->get('/courses/{id}/lessons/create', 'Admin\LessonController@create');
        $r->post('/courses/{id}/lessons', 'Admin\LessonController@store');
        $r->get('/lessons/{id}/edit', 'Admin\LessonController@edit');
        $r->post('/lessons/{id}', 'Admin\LessonController@update');
        $r->post('/lessons/{id}/delete', 'Admin\LessonController@destroy');
        $r->post('/lessons/{id}/duplicate', 'Admin\LessonController@duplicate');});

    $r->group('/videos', ['can:videos.manage'], function ($r) {
        $r->get('', 'Admin\VideoController@index');
        $r->get('/search', 'Admin\VideoController@search');
        $r->post('/upload-chunk', 'Admin\VideoController@uploadChunk');
        $r->post('/external', 'Admin\VideoController@external');
        $r->post('/scan', 'Admin\VideoController@scan');
        $r->get('/{id}/stream', 'Admin\VideoController@stream');
        $r->post('/{id}', 'Admin\VideoController@update');
        $r->post('/{id}/delete', 'Admin\VideoController@destroy');});

    $r->group('/students', ['can:students.manage'], function ($r) {
        $r->get('', 'Admin\StudentController@index');
        $r->get('/export', 'Admin\StudentController@export');
        $r->get('/create', 'Admin\StudentController@create');
        $r->post('', 'Admin\StudentController@store');
        $r->post('/bulk', 'Admin\StudentController@bulk');
        $r->get('/{id}', 'Admin\StudentController@show');
        $r->post('/{id}', 'Admin\StudentController@update');
        $r->post('/{id}/enroll', 'Admin\StudentController@enroll', ['can:enrollments.manage']);
        $r->post('/{id}/invite', 'Admin\StudentController@invite');
        $r->post('/{id}/status', 'Admin\StudentController@status');
        $r->post('/{id}/delete', 'Admin\StudentController@destroy');});

    $r->group('/orders', ['can:orders.manage'], function ($r) {
        $r->get('', 'Admin\\OrderController@index');
        $r->get('/export', 'Admin\\OrderController@export');
        $r->post('/{id}/approve', 'Admin\\OrderController@approve');
        $r->post('/{id}/reject', 'Admin\\OrderController@reject');
        $r->get('/{id}/proof', 'Admin\\OrderController@proof');});

    $r->group('/enrollments', ['can:enrollments.manage'], function ($r) {
        $r->get('', 'Admin\EnrollmentController@index');
        $r->post('/bulk', 'Admin\EnrollmentController@bulk');
        $r->post('/{id}', 'Admin\EnrollmentController@update');
        $r->post('/{id}/delete', 'Admin\EnrollmentController@destroy');});

    $r->group('/import', ['can:imports.run'], function ($r) {
        $r->get('', 'Admin\ImportController@index');
        $r->post('', 'Admin\ImportController@store');
        $r->get('/sample', 'Admin\ImportController@sample');
        $r->get('/{id}', 'Admin\ImportController@show');});

    $r->group('/reports', ['can:reports.view'], function ($r) {
        $r->get('', 'Admin\ReportController@index');
        $r->get('/courses/{id}', 'Admin\ReportController@course');
        $r->get('/courses/{id}/export', 'Admin\ReportController@export');});

    $r->group('', ['can:emails.manage'], function ($r) {
        $r->get('/emails', 'Admin\EmailController@index');
        $r->get('/emails/{key}/edit', 'Admin\EmailController@edit');
        $r->get('/emails/{key}/preview', 'Admin\EmailController@preview');
        $r->post('/emails/{key}', 'Admin\EmailController@update');
        $r->post('/emails/{key}/test', 'Admin\EmailController@test');
        $r->get('/mail-log', 'Admin\EmailController@log');
        $r->post('/mail-log/process', 'Admin\EmailController@process');
        $r->post('/mail-log/{id}/retry', 'Admin\EmailController@retry');});

    $r->get('/settings', function () {
        redirect('/admin/settings/general');});
    $r->get('/settings/mail', 'Admin\MailSettingsController@show', ['can:mail.configure']);
    $r->post('/settings/mail', 'Admin\MailSettingsController@save', ['can:mail.configure']);
    $r->post('/settings/mail/test', 'Admin\MailSettingsController@test', ['can:mail.configure']);
    $r->get('/modules', 'Admin\ModulesController@index', ['can:modules.manage']);
    $r->post('/modules', 'Admin\ModulesController@save', ['can:modules.manage']);
    $r->get('/settings/{tab}', 'Admin\SettingsController@show', ['can:branding.manage']);
    $r->post('/settings/{tab}', 'Admin\SettingsController@save', ['can:branding.manage']);
    $r->get('/logs', 'Admin\LogController@index', ['can:logs.view']);

    $r->group('/team', ['can:admins.manage'], function ($r) {
        $r->get('', 'Admin\TeamController@index');
        $r->post('', 'Admin\TeamController@store');
        $r->post('/{id}', 'Admin\TeamController@update');});

    $r->group('/system', ['can:settings.system'], function ($r) {
        $r->get('', 'Admin\SystemController@index');
        $r->post('/update-check', 'Admin\SystemController@check', ['can:updates.run']);
        $r->post('/update', 'Admin\SystemController@update', ['can:updates.run']);
        $r->post('/rollback', 'Admin\SystemController@rollback', ['can:updates.run']);
        $r->post('/updater', 'Admin\SystemController@saveUpdater', ['can:updates.run']);
        $r->post('/modules', 'Admin\SystemController@modules', ['can:modules.manage']);
        $r->post('/maintenance', 'Admin\SystemController@maintenance');
        $r->post('/cron', 'Admin\SystemController@cron');
        $r->post('/cache', 'Admin\SystemController@cache');
        $r->post('/migrate', 'Admin\SystemController@migrate');});});
