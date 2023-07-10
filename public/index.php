<?php

require_once __DIR__ . '/../vendor/autoload.php';

$config = require_once __DIR__ . '/../app/config/app.php';
$request = (object) $_GET;

App\Application::init($config)->ratesController($request)->render();