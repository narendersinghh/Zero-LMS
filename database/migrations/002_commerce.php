<?php
/** Pricing, public course pages, orders/payments and self-registration. */
use App\Core\DB;

return function () {
    $o = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $cols = DB::col('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [DB::prefix() . 'courses']);
    $add = [
        'price' => 'DECIMAL(10,2) NULL', 'sale_price' => 'DECIMAL(10,2) NULL', 'currency' => "CHAR(3) NOT NULL DEFAULT 'INR'",
        'is_public' => 'TINYINT(1) NOT NULL DEFAULT 0', 'highlights' => 'TEXT NULL', 'color' => 'VARCHAR(20) NULL', 'illustration' => 'VARCHAR(40) NULL',];
    foreach ($add as $c => $def) {
        if (!in_array($c, $cols, true)) {
            DB::run("ALTER TABLE {courses} ADD COLUMN `$c` $def");}}
    $ucols = DB::col('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [DB::prefix() . 'users']);
    if (!in_array('signup_source', $ucols, true)) {
        DB::run('ALTER TABLE {users} ADD COLUMN signup_source VARCHAR(30) NULL');}
    DB::run("CREATE TABLE IF NOT EXISTS {orders} (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      ref VARCHAR(24) NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      course_id INT UNSIGNED NOT NULL,
      amount DECIMAL(10,2) NOT NULL DEFAULT 0,
      original_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
      discount DECIMAL(10,2) NOT NULL DEFAULT 0,
      coupon_code VARCHAR(40) NULL,
      currency CHAR(3) NOT NULL DEFAULT 'INR',
      gateway VARCHAR(30) NOT NULL DEFAULT 'upi',
      status VARCHAR(20) NOT NULL DEFAULT 'pending',
      gateway_ref VARCHAR(120) NULL,
      utr VARCHAR(60) NULL,
      proof VARCHAR(255) NULL,
      payer_note VARCHAR(255) NULL,
      admin_note VARCHAR(255) NULL,
      meta TEXT NULL,
      submitted_at DATETIME NULL,
      paid_at DATETIME NULL,
      verified_by INT UNSIGNED NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NULL,
      UNIQUE KEY uq_order_ref (ref),
      KEY ix_order_status (status, created_at),
      KEY ix_order_user (user_id),
      KEY ix_order_course (course_id),
      KEY ix_order_utr (utr),
      CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES {users}(id) ON DELETE CASCADE,
      CONSTRAINT fk_order_course FOREIGN KEY (course_id) REFERENCES {courses}(id) ON DELETE CASCADE) $o");
    $tpl = [
        'order_received' => ['Payment received, verifying', 'We received your payment for {{course_title}}', "<h1>Thanks, {{first_name}}!</h1><p>We received your payment details for <strong>{{course_title}}</strong> (order <strong>{{order_ref}}</strong>, {{amount}}).</p><p>Our team will verify it shortly and you'll get another email as soon as your access is active.</p><p class=\"btn-wrap\"><a class=\"btn\" href=\"{{orders_url}}\">View my orders</a></p>"],
        'order_paid' => ['Payment confirmed, access granted', "You're in! Access to {{course_title}} is active", "<h1>Payment confirmed 🎉</h1><p>Hi {{first_name}}, your payment for <strong>{{course_title}}</strong> (order {{order_ref}}, {{amount}}) is confirmed and your access is now active.</p><p class=\"btn-wrap\"><a class=\"btn\" href=\"{{course_url}}\">Start learning</a></p><p>Sign in anytime at <a href=\"{{login_url}}\">{{login_url}}</a> with <strong>{{email}}</strong>.</p>"],
        'order_rejected' => ['Payment could not be verified', 'Action needed: payment for {{course_title}}', "<h1>We couldn't verify your payment</h1><p>Hi {{first_name}}, we couldn't match your payment for <strong>{{course_title}}</strong> (order {{order_ref}}).</p><p><strong>Reason:</strong> {{reason}}</p><p class=\"btn-wrap\"><a class=\"btn\" href=\"{{pay_url}}\">Review payment</a></p><p>Reply to this email if you believe this is a mistake.</p>"],];
    foreach ($tpl as $k => $t) {
        DB::run('INSERT IGNORE INTO {email_templates} (`key`, name, subject, body, enabled, updated_at) VALUES (?,?,?,?,1,?)', [$k, $t[0], $t[1], $t[2], now()]);}};
