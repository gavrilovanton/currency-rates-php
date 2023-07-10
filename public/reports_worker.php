<?php

require_once __DIR__ . '/../vendor/autoload.php';

$config = require_once __DIR__ . '/../app/config/app.php';

App\Application::init($config)->reportsWorker();