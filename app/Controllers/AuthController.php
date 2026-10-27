<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Throttle;
use App\Services\Users;

class AuthController extends Controller {
    public function showLogin() {
        return view('auth/login');}

    public function login() {
        $data = $this->validate(['email' => 'required|email', 'password' => 'required']);
        $email = strtolower($data['email']);
        $ipKey = 'login-ip:' . $this->req->ip();
        $emKey = 'login-em:' . $email;
        if (Throttle::tooMany($ipKey, 20, 15) || Throttle::tooMany($emKey, 6, 15)) {
            Audit::log('auth.throttled', null, null, ['email' => $email]);
            flash('error', 'Too many sign-in attempts. Please wait 15 minutes and try again.');
            back();}
        $user = Auth::attempt($email, (string)$_POST['password']);
        if (!$user) {
            Throttle::hit($ipKey);
            Throttle::hit($emKey);
            Audit::log('auth.failed', null, null, ['email' => $email]);
            flash('error', 'Those credentials do not match our records.');
            back(['email' => 'Invalid email or password.']);}
        if ($user['status'] !== 'active') {
            flash('error', 'Your account is suspended. Please contact support.');
            back();}
        Throttle::clear($emKey);
        Auth::login($user);
        $to = (string)Session::pull('_intended', '');
        $isStaff = in_array($user['role'], ['super_admin', 'admin'], true);
        if ($to && strpos($to, '/') === 0 && strpos($to, '//') !== 0 && strpos($to, '/media/') === false) {
            header('Location: ' . $to);
            exit;}
        redirect($isStaff ? '/admin' : '/dashboard');}

    public function logout() {
        Auth::logout();
        redirect('/login');}

    public function showForgot() {
        return view('auth/forgot');}

    public function forgot() {
        $data = $this->validate(['email' => 'required|email']);
        $key = 'forgot:' . $this->req->ip();
        if (Throttle::tooMany($key, 5, 30)) {
            flash('error', 'Too many requests. Please try again later.');
            back();}
        Throttle::hit($key);
        $user = Users::byEmail($data['email']);
        if ($user && $user['status'] === 'active') {
            $user['password_hash'] ? Users::sendReset($user) : Users::sendWelcome($user);
            Audit::log('auth.reset_requested', 'user', (int)$user['id'], [], (int)$user['id']);}
        // Same response either way to avoid account enumeration.
        flash('success', 'If an account exists for that email, a link has been sent. Please check your inbox.');
        redirect('/login');}

    public function showReset(string $token) {
        $t = Users::findToken($token);
        $setup = strpos($this->req->path(), '/password/setup') === 0;
        return view('auth/reset', ['token' => $token, 'valid' => (bool)$t, 'email' => $t['email'] ?? '', 'name' => $t['name'] ?? '', 'setup' => $setup]);}

    public function reset(string $token) {
        $t = Users::findToken($token);
        if (!$t || $t['status'] !== 'active') {
            flash('error', 'This link is invalid or has expired. Request a new one below.');
            redirect('/password/forgot');}
        $this->validate(['password' => 'required|password|confirmed']);
        Users::setPassword((int)$t['user_id'], (string)$_POST['password']);
        \App\Core\DB::update('password_tokens', ['used_at' => now()], 'id = ?', [$t['id']]);
        $user = Users::find((int)$t['user_id']);
        Auth::login($user);
        flash('success', $t['type'] === 'setup' ? 'Your account is ready. Welcome aboard!' : 'Your password has been updated.');
        redirect(in_array($user['role'], ['super_admin', 'admin'], true) ? '/admin' : '/dashboard');}}
