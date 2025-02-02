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

use Workerman\Timer;
use Workerman\Worker;

class Memory implements DriverInterface
{
    protected array $data = [];
    protected array $expire = [];

    /**
     * 构造方法
     * @param Worker|null $worker
     */
    public function __construct(?Worker $worker)
    {
        if (!$worker) {
            return;
        }

        Timer::add(60, function () {
            $this->clearExpire();
        });
    }

    /**
     * @param string $key
     * @param int $ttl
     * @param $step
     * @return int
     */
    public function increase(string $key, int $ttl = 24 * 60 * 60, $step = 1): int
    {
        $expireTime = $this->getExpireTime($ttl);
        $key        = "$key-$expireTime-$ttl";
        if (!isset($this->data[$key])) {
            $this->data[$key]                = 0;
            $this->expire[$expireTime][$key] = time();
        }
        $this->data[$key] += $step;
        return $this->data[$key];
    }

    /**
     * @param $ttl
     * @return float
     */
    protected function getExpireTime($ttl): float
    {
        return ceil(time() / $ttl) * $ttl;
    }

    /**
     * @return void
     */
    protected function clearExpire(): void
    {
        foreach ($this->expire as $expireTime => $keys) {
            if ($expireTime < time()) {
                foreach ($keys as $key => $time) {
                    unset($this->data[$key]);
                }

                unset($this->expire[$expireTime]);
            }
        }
    }
}