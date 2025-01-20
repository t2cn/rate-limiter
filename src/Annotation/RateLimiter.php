<?php

namespace T2\RateLimiter\Annotation;

use Attribute;
use T2\RateLimiter\RateLimitException;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class RateLimiter
{
    /**
     * IP
     * @var string
     */
    const string IP = 'ip';

    /**
     * UID
     * @var string
     */
    const string UID = 'uid';

    /**
     * SID
     * @var string
     */
    const string SID = 'sid';

    /**
     * RateLimiter constructor.
     * @param int $limit
     * @param int $ttl
     * @param mixed $key
     * @param string $message
     * @param string $exception
     */
    public function __construct(public int $limit, public int $ttl = 1, public mixed $key = 'ip', public string $message = 'Too Many Requests', public string $exception = RateLimitException::class)
    {

    }
}