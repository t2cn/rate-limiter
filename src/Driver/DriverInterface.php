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

namespace T2\RateLimiter\Driver;

interface DriverInterface
{
    /**
     * @param string $key
     * @param int $ttl
     * @param int $step
     * @return int
     */
    public function increase(string $key, int $ttl = 24 * 60 * 60, int $step = 1): int;
}