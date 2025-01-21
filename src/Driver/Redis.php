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

use RedisException;
use T2\Redis as RedisClient;
use Workerman\Worker;
use Workerman\Timer;

class Redis implements DriverInterface
{
    /**
     * 构造方法
     * @param Worker|null $worker
     * @param string $connection
     * @throws RedisException
     */
    public function __construct(?Worker $worker, protected string $connection)
    {
        if ($worker) {
            Timer::add(24 * 60 * 60, function () {
                $this->clearExpire();
            });
        }

        $this->clearExpire();
    }

    /**
     * @param string $key
     * @param int $ttl
     * @param $step
     * @return int
     * @throws RedisException
     */
    public function increase(string $key, int $ttl = 24 * 60 * 60, $step = 1): int
    {
        return RedisClient::connection($this->connection)->hIncrBy('rate-limiter-' . date('Y-m-d'), "$key-" . $this->getExpireTime($ttl) . '-' . $ttl, $step) ?: 0;
    }

    /**
     * @param $ttl
     * @return int
     */
    protected function getExpireTime($ttl): int
    {
        return ceil(time() / $ttl) * $ttl;
    }

    /**
     * @return void
     * @throws RedisException
     */
    protected function clearExpire(): void
    {
        $keys = RedisClient::connection($this->connection)->keys('rate-limiter-*');
        foreach ($keys as $key) {
            if (!str_contains($key, 'rate-limiter-' . date('Y-m-d'))) {
                $key = str_replace(config('redis.' . $this->connection . '.prefix', ''), '', $key);
                RedisClient::connection($this->connection)->del($key);
            }
        }
    }

}