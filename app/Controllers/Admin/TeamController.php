<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Services\Users;

/** Staff accounts (Super Admin only). */
class TeamController extends Controller {
    public function index() {
        return view('admin/team/index', ['rows' => DB::all("SELECT * FROM {users} WHERE role IN ('super_admin', 'admin') ORDER BY role DESC, name")]);}

    public function store() {
        $d = $this->validate(['name' => 'required|max:150', 'email' => 'required|email', 'role' => 'required|in:admin,super_admin']);
        $u = Users::byEmail($d['email']);
        if ($u) {
            DB::update('users', ['role' => $d['role'], 'updated_at' => now()], 'id = ?', [$u['id']]);
            Audit::log('team.promoted', 'user', (int)$u['id'], ['role' => $d['role']]);
            $this->ok($u['name'] . ' is now ' . \App\Core\Auth::ROLES[$d['role']] . '.');}
        $id = Users::create(['name' => $d['name'], 'email' => $d['email'], 'role' => $d['role']]);
        Users::sendWelcome(Users::find($id));
        $this->ok('Team member invited. They will receive an account setup email.');}

    public function update(string $id) {
        $u = Users::find((int)$id) ?? abort(404);
        if ((int)$u['id'] === Auth::id()) {
            flash('error', 'You cannot change your own role or status.');
            back();}
        $role = $this->req->str('role');
        $status = $this->req->str('status');
        $row = ['updated_at' => now()];
        if (isset(Auth::ROLES[$role])) {
            if ($u['role'] === 'super_admin' && $role !== 'super_admin' && (int)DB::val("SELECT COUNT(*) FROM {users} WHERE role = 'super_admin' AND status = 'active'") <= 1) {
                flash('error', 'At least one active Super Admin is required.');
                back();}
            $row['role'] = $role;}
        if (in_array($status, ['active', 'suspended'], true)) {
            $row['status'] = $status;}
        DB::update('users', $row, 'id = ?', [$u['id']]);
        Auth::bumpSessionVersion((int)$u['id']);
        Audit::log('team.updated', 'user', (int)$u['id'], $row);
        $this->ok('Team member updated.');}}
