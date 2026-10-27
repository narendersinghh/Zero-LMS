<?php
namespace App\Controllers;

use App\Core\Media;

class MediaController extends Controller {
    public function stream(string $token) {
        Media::stream($token);}}
