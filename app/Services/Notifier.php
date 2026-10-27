<?php
namespace App\Services;

use App\Core\DB;

class Notifier {
    public static function notify(int $userId, string $title, string $body = '', string $url = '', string $type = 'info', ?string $dedupe = null): void {
        DB::run('INSERT IGNORE INTO {notifications} (user_id, type, title, body, url, dedupe_key, created_at) VALUES (?,?,?,?,?,?,?)', [$userId, $type, mb_substr($title, 0, 200), mb_substr($body, 0, 500), $url ?: null, $dedupe, now()]);}

    /** Bulk insert (rows: user_id,type,title,body,url,dedupe_key). Duplicates (same dedupe key) are ignored. */
    public static function many(array $rows): int {
        foreach ($rows as &$r) {
            $r['created_at'] = now();}
        return DB::insertMany('notifications', $rows, 'IGNORE');}

    public static function unreadCount(int $userId): int {
        return (int)DB::val('SELECT COUNT(*) FROM {notifications} WHERE user_id = ? AND read_at IS NULL', [$userId]);}

    public static function recent(int $userId, int $limit = 8): array {
        return DB::all('SELECT * FROM {notifications} WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int)$limit, [$userId]);}}
