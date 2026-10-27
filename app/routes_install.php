<?php
$r = app()->router();
$r->get('/install', 'InstallController@index');
$r->post('/install/database', 'InstallController@database');
$r->post('/install/finish', 'InstallController@finish');
