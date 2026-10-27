<?php
namespace App\Core;

/**
 * Event + filter bus used by core and modules.
 * Core events: app.booting, app.routes, auth.login, user.created, enrollment.created, enrollment.updated,
 * lesson.viewed, lesson.completed, course.completed, cron.run, admin.nav (filter), student.nav (filter),
 * lesson.types (filter), lesson.content (filter), auth.permissions (filter), dashboard.widgets (filter).
 */
class Hooks {
    private static array $listeners = [];

    public static function on(string $event, callable $cb, int $priority = 10): void {
        self::$listeners[$event][$priority][] = $cb;}

    public static function fire(string $event, ...$args): void {
        foreach (self::sorted($event) as $cb) {
            $cb(...$args);}}

    public static function filter(string $name, $value, ...$args) {
        foreach (self::sorted($name) as $cb) {
            $value = $cb($value, ...$args);}
        return $value;}

    public static function has(string $event): bool {
        return !empty(self::$listeners[$event]);}

    private static function sorted(string $event): array {
        if (empty(self::$listeners[$event])) {
            return [];}
        ksort(self::$listeners[$event]);
        return array_merge(...array_values(self::$listeners[$event]));}}
