<?php
namespace App\Core;

class HttpException extends \RuntimeException {
    public function __construct(int $code = 404, string $message = '') {
        parent::__construct($message, $code);}}
