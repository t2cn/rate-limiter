<?php

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