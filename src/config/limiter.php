<?php

return [
    'enable'       => true,
    'driver'       => 'auto', // auto, apcu, memory, redis
    'stores'       => [
        'redis' => [
            'connection' => 'default',
        ]
    ],

    // 这些ip的请求不做频率限制 例如 '127.0.0.1','212.122.1.23'
    'ip_whitelist' => [],
];