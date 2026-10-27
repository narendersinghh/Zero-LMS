<?php
namespace App\Services;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\DB;
use App\Core\Hooks;
use App\Core\Mailer;

class Users {
    public const SETUP_HOURS = 168;
    public const RESET_HOURS = 1;

    public static function find(int $id): ?array {
        return DB::one('SELECT * FROM {users} WHERE id = ?', [$id]);}

    public static function byEmail(string $email): ?array {
        return DB::one('SELECT * FROM {users} WHERE email = ?', [strtolower(trim($email))]);}

    /** Create a user. Without a password, the account waits for the setup link. */
    public static function create(array $data, bool $audit = true): int {
        $id = DB::insert('users', [
            'role' => $data['role'] ?? 'student', 'name' => trim($data['name'] ?? '') ?: self::nameFromEmail($data['email']), 'email' => strtolower(trim($data['email'])),
            'password_hash' => !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null,
            'password_set_at' => !empty($data['password']) ? now() : null, 'status' => $data['status'] ?? 'active', 'phone' => $data['phone'] ?? null,
            'created_by' => Auth::id() ?: null, 'created_at' => now(), 'updated_at' => now(),]);
        if ($audit) {
            Audit::log('user.created', 'user', $id, ['email' => $data['email'], 'role' => $data['role'] ?? 'student']);}
        Hooks::fire('user.created', $id, $data);
        return $id;}

    public static function nameFromEmail(string $email): string {
        $local = explode('@', $email)[0];
        return ucwords(trim(preg_replace('/[._\-+]+|\d+/', ' ', $local))) ?: $local;}

    public static function createToken(int $userId, string $type, int $hours): string {
        $token = bin2hex(random_bytes(32));
        DB::run('DELETE FROM {password_tokens} WHERE user_id = ? AND type = ?', [$userId, $type]);
        DB::insert('password_tokens', ['user_id' => $userId, 'type' => $type, 'token_hash' => hash('sha256', $token), 'expires_at' => gmdate('Y-m-d H:i:s', time() + $hours * 3600), 'created_at' => now()]);
        return $token;}

    public static function findToken(string $token): ?array {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;}
        return DB::one('SELECT t.*, u.email, u.name, u.status FROM {password_tokens} t JOIN {users} u ON u.id = t.user_id WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > ? LIMIT 1', [hash('sha256', $token), now()]);}

    /** Queue the welcome email containing a password setup link. */
    public static function sendWelcome(array $user, array $courseTitles = []): void {
        $token = self::createToken((int)$user['id'], 'setup', self::SETUP_HOURS);
        $line = $courseTitles ? ' with access to <strong>' . implode('</strong>, <strong>', array_map('e', $courseTitles)) . '</strong>' : '';
        Mailer::queueTemplate('welcome', $user['email'], $user['name'], ['setup_link' => url('/password/setup/' . $token, [], true), 'course_line' => $line, 'link_hours' => self::SETUP_HOURS]);}

    public static function sendReset(array $user): void {
        $token = self::createToken((int)$user['id'], 'reset', self::RESET_HOURS);
        Mailer::queueTemplate('password_reset', $user['email'], $user['name'], ['reset_link' => url('/password/reset/' . $token, [], true), 'link_hours' => self::RESET_HOURS]);}

    public static function setPassword(int $userId, string $password): void {
        DB::update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'password_set_at' => now(), 'updated_at' => now(), 'email_verified_at' => now()], 'id = ?', [$userId]);
        Auth::bumpSessionVersion($userId);
        Audit::log('user.password_set', 'user', $userId, [], $userId);}}
