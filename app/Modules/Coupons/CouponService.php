<?php
namespace App\Modules\Coupons;

use App\Core\DB;

/** Coupon lookup, validation and redemption bookkeeping. */
class CouponService {
    public static function normalize(string $code): string {
        return strtoupper(preg_replace('/\s+/', '', $code));}

    public static function find(string $code): ?array {
        $code = self::normalize($code);
        return $code === '' ? null : DB::one('SELECT * FROM {coupons} WHERE code = ? LIMIT 1', [$code]);}

    /** @return array{0:?array, 1:?string} [coupon, error] */
    public static function validate(string $code, array $course, ?array $user): array {
        $c = self::find($code);
        if (!$c || !(int)$c['active']) {
            return [null, 'This coupon code is not valid.'];}
        $now = time();
        if ($c['starts_at'] && strtotime($c['starts_at'] . ' UTC') > $now) {
            return [null, 'This coupon is not active yet.'];}
        if ($c['expires_at'] && strtotime($c['expires_at'] . ' UTC') <= $now) {
            return [null, 'This coupon has expired.'];}
        if ($c['course_id'] && (int)$c['course_id'] !== (int)$course['id']) {
            return [null, 'This coupon is not valid for this course.'];}
        if ($c['max_uses'] !== null && (int)$c['used_count'] >= (int)$c['max_uses']) {
            return [null, 'This coupon has reached its usage limit.'];}
        if ($user && (int)$c['per_user_limit'] > 0) {
            $n = (int)DB::val('SELECT COUNT(*) FROM {coupon_redemptions} WHERE coupon_id = ? AND user_id = ?', [$c['id'], $user['id']]);
            if ($n >= (int)$c['per_user_limit']) {
                return [null, 'You have already used this coupon.'];}}
        return [$c, null];}

    public static function discount(array $c, float $amount): float {
        $d = $c['type'] === 'fixed' ? (float)$c['value'] : $amount * (float)$c['value'] / 100;
        return round(min(max(0, $d), $amount), 2);}

    public static function label(array $c): string {
        return $c['code'] . ' (' . ($c['type'] === 'fixed' ? money($c['value']) . ' off' : rtrim(rtrim(number_format((float)$c['value'], 2), '0'), '.') . '% off') . ')';}

    /** Record one use (idempotent per order). */
    public static function redeem(string $code, int $userId, ?int $orderId, ?int $courseId, float $discount): void {
        $c = self::find($code);
        if (!$c) {
            return;}
        if ($orderId && DB::val('SELECT id FROM {coupon_redemptions} WHERE order_id = ?', [$orderId])) {
            return;}
        DB::insert('coupon_redemptions', ['coupon_id' => $c['id'], 'user_id' => $userId, 'order_id' => $orderId, 'course_id' => $courseId, 'discount' => $discount, 'created_at' => now()]);
        DB::run('UPDATE {coupons} SET used_count = used_count + 1 WHERE id = ?', [$c['id']]);}}
