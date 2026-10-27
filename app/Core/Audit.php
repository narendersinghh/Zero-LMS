<?php
namespace App\Core;

class Audit {
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, array $details = [], ?int $userId = null): void {
        try {
            $req = App::instance()->request();
            DB::insert('audit_logs', [
                'user_id' => $userId ?? (Auth::id() ?: null), 'action' => substr($action, 0, 80), 'entity_type' => $entityType, 'entity_id' => $entityId,
                'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'ip' => PHP_SAPI === 'cli' ? 'cli' : $req->ip(), 'user_agent' => PHP_SAPI === 'cli' ? 'cli' : $req->userAgent(), 'created_at' => now(),]);}
        catch (\Throwable $e) {
            Logger::error('Audit failed: ' . $e->getMessage());}}}
