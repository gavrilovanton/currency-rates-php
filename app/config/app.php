<?php

declare(strict_types = 1);

set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, $severity, $severity, $file, $line);
});

$config = [
    "REDIS_HOST" => "cache",
    "REDIS_PORT" => 6379,
    "LOG_PATH" => '/var/www/public/app.log'
];

return $config;