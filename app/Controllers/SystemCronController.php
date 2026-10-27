<?php
namespace App\Controllers;

use App\Core\Scheduler;
use App\Core\Settings;

/** URL-triggered cron for hosts without CLI cron: /cron/run?key=SECRET */
class SystemCronController extends Controller {
    public function run() {
        $secret = (string)Settings::get('cron_secret', '');
        if ($secret === '' || !hash_equals($secret, (string)$this->req->query('key', ''))) {
            abort(403);}
        json_out(['ok' => true, 'result' => Scheduler::runRequest($this->req->query('only', '') === 'mail' ? 'mail' : '')]);}}
