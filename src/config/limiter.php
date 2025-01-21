<?php
/**
 * This file is part of T2-Engine.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    Tony<dev@t2engine.cn>
 * @copyright Tony<dev@t2engine.cn>
 * @link      https://www.t2engine.cn/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);

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