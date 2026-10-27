<?php
namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Uploads;
use App\Services\Learning;

class ProfileController extends Controller {
    public function show() {
        $user = Auth::user();
        return view('student/profile', ['user' => $user, 'enrollments' => Learning::enrollments((int)$user['id'])]);}

    public function update() {
        $d = $this->validate(['name' => 'required|max:150', 'phone' => 'max:40', 'bio' => 'max:1000']);
        DB::update('users', ['name' => $d['name'], 'phone' => $d['phone'] ?: null, 'bio' => $d['bio'] ?: null, 'updated_at' => now()], 'id = ?', [Auth::id()]);
        Audit::log('profile.updated', 'user', Auth::id());
        $this->ok('Profile updated.');}

    public function password() {
        $user = Auth::user();
        if ($user['password_hash'] && !password_verify((string)($_POST['current_password'] ?? ''), $user['password_hash'])) {
            flash('error', 'Your current password is incorrect.');
            back(['current_password' => 'Incorrect password.']);}
        $this->validate(['password' => 'required|password|confirmed']);
        \App\Services\Users::setPassword((int)$user['id'], (string)$_POST['password']);
        $this->ok('Password changed. Other devices have been signed out.');}

    public function avatar() {
        $f = $this->req->file('avatar');
        if ($this->req->str('remove') === '1') {
            Uploads::deletePublic(Auth::user()['avatar']);
            DB::update('users', ['avatar' => null], 'id = ?', [Auth::id()]);
            $this->ok('Photo removed.');}
        if (!$f) {
            back(['avatar' => 'Choose an image.']);}
        try {
            $path = Uploads::image($f, 'avatars', 2048);}
        catch (\Throwable $e) {
            flash('error', $e->getMessage());
            back();}
        Uploads::deletePublic(Auth::user()['avatar']);
        DB::update('users', ['avatar' => $path, 'updated_at' => now()], 'id = ?', [Auth::id()]);
        $this->ok('Photo updated.');}}
