<?php

use Monolog\Handler\NullHandler;

return [
    'default' => env('LOG_CHANNEL', 'null'),
    'channels' => [
        'null' => ['driver' => 'monolog', 'handler' => NullHandler::class],
        'single' => ['driver' => 'single', 'path' => storage_path('logs/laravel.log'), 'level' => 'debug'],
    ],
];
