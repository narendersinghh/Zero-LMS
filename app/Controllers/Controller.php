<?php
namespace App\Controllers;

use App\Core\App;
use App\Core\Request;
use App\Core\Validator;

abstract class Controller {
    protected Request $req;

    public function __construct() {
        $this->req = App::instance()->request();}

    /** Validate request input; on failure redirect back (or JSON 422) with errors. */
    protected function validate(array $rules, array $labels = []): array {
        $data = [];
        foreach (array_keys($rules) as $k) {
            $v = $this->req->input($k);
            $data[$k] = is_string($v) ? trim($v) : $v;
            if (array_key_exists($k . '_confirmation', $_POST)) {
                $data[$k . '_confirmation'] = $_POST[$k . '_confirmation'];}}
        $errors = Validator::make($data, $rules, $labels);
        if ($errors) {
            if ($this->req->isAjax()) {
                json_out(['error' => reset($errors), 'errors' => $errors], 422);}
            flash('error', reset($errors));
            back($errors);}
        return $data;}

    protected function ok(string $msg, string $to = ''): void {
        flash('success', $msg);
        $to ? redirect($to) : back([], false);}}
