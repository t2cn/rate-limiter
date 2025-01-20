<?php

namespace T2\RateLimiter;

use T2\Contract\BootstrapInterface;
use Workerman\Worker;
use RedisException;

class Bootstrap implements BootstrapInterface
{
    /**
     * @param Worker|null $worker
     * @return void
     * @throws RedisException
     */
    public static function start(?Worker $worker): void
    {
        Limiter::init($worker);
    }
}