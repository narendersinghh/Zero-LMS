<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Installer;
use App\Core\Session;
use App\Core\Validator;
use App\Services\Users;

/** 3-step web installer: requirements -> database -> site & admin account. */
class InstallController extends Controller {
    public function __construct() {
        parent::__construct();
        if (App::installed()) {
            redirect('/login');}}

    public function index() {
        $step = max(1, min(3, $this->req->int('step', 1)));
        if ($step >= 2 && !Installer::requirementsMet()) {
            $step = 1;}
        if ($step === 3 && !Session::get('install_db')) {
            $step = 2;}
        $guess = (($this->req->isSecure() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path());
        return view('install/index', ['step' => $step, 'reqs' => Installer::requirements(), 'ok' => Installer::requirementsMet(), 'db' => Session::get('install_db', []), 'guessUrl' => $guess]);}

    public function database() {
        $c = ['host' => $this->req->str('db_host', 'localhost'), 'port' => $this->req->int('db_port', 3306), 'name' => $this->req->str('db_name'), 'user' => $this->req->str('db_user'), 'pass' => (string)($_POST['db_pass'] ?? ''), 'prefix' => preg_replace('/[^a-z0-9_]/i', '', $this->req->str('db_prefix', 'zx_'))];
        if ($c['name'] === '' || $c['user'] === '') {
            flash('error', 'Database name and user are required.');
            back();}
        if ($err = Installer::testDb($c)) {
            flash('error', 'Could not connect: ' . $err);
            back();}
        Session::set('install_db', $c);
        flash('success', 'Database connection successful.');
        redirect('/install?step=3');}

    public function finish() {
        $db = Session::get('install_db');
        if (!$db) {
            redirect('/install?step=2');}
        $data = $this->req->only(['site_name', 'site_url', 'timezone', 'admin_name', 'admin_email', 'password', 'mail_from']);
        $data['password_confirmation'] = $_POST['password_confirmation'] ?? '';
        $data['password'] = (string)($_POST['password'] ?? '');
        $errors = Validator::make($data, ['site_name' => 'required|max:80', 'site_url' => 'required|url', 'admin_name' => 'required|max:150', 'admin_email' => 'required|email', 'password' => 'required|password|confirmed', 'mail_from' => 'email']);
        if ($errors) {
            flash('error', reset($errors));
            back($errors);}
        try {
            \App\Core\DB::connect($db);
            if (!Installer::writeEnv($db, $data['site_url'])) {
                throw new \RuntimeException('Could not write the .env file. Make sure the install folder is writable.');}
            Installer::install(['name' => $data['admin_name'], 'email' => $data['admin_email'], 'password' => $data['password']], ['site_name' => $data['site_name'], 'timezone' => $data['timezone'], 'mail_from' => $data['mail_from']], $this->req->bool('demo'));}
        catch (\Throwable $e) {
            @unlink(BASE_PATH . '/.env');
            flash('error', 'Installation failed: ' . $e->getMessage());
            back();}
        Session::forget('install_db');
        $u = Users::byEmail($data['admin_email']);
        Auth::login($u);
        flash('success', 'Zeroes is installed. Welcome to your admin panel!');
        redirect('/admin');}}
