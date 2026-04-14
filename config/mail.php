<?php

return [
    'default' => env('MAIL_MAILER', 'array'),
    'mailers' => [
        'array' => ['transport' => 'array'],
    ],
    'from' => ['address' => 'noreply@example.com', 'name' => 'Repro'],
];
