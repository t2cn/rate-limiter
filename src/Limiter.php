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

namespace T2\RateLimiter;

use T2\Contract\MiddlewareInterface;
use T2\RateLimiter\Driver\Apcu;
use T2\RateLimiter\Driver\DriverInterface;
use T2\RateLimiter\Driver\Memory;
use T2\RateLimiter\Driver\Redis;
use Exception;
use RedisException;
use ReflectionException;
use ReflectionMethod;
use T2\Response;
use T2\Request;
use T2\RateLimiter\Annotation\RateLimiter as RateLimiterAnnotation;
use Workerman\Worker;

class Limiter implements MiddlewareInterface
{
    /**
     * @var array
     */
    protected static array $ipWhiteList = [];

    /**
     * @var DriverInterface
     */
    protected static DriverInterface $driver;

    /**
     * @var string
     */
    protected static string $redisConnection = 'default';

    /**
     * @var bool
     */
    protected static bool $initialized = false;

    /**
     * @var string
     */
    protected static string $prefix = 'rate-limiter';

    /**
     * 中间件逻辑
     * @param Request $request
     * @param callable $handler
     * @return Response
     * @throws ReflectionException|Exception
     */
    public function process(Request $request, callable $handler): Response
    {
        if (!$request->controller || !method_exists($request->controller, $request->action)) {
            return $handler($request);
        }

        $reflectionMethod = new ReflectionMethod($request->controller, $request->action);
        $attributes       = $reflectionMethod->getAttributes(RateLimiterAnnotation::class);

        $prefix = static::$prefix;
        foreach ($attributes as $attribute) {
            $annotation = $attribute->newInstance();
            switch ($annotation->key) {
                case RateLimiterAnnotation::UID:
                    $uid = session('user.id', session()->getId());
                    $key = "$prefix-$request->controller-$request->action-$annotation->key-$uid";
                    break;
                case RateLimiterAnnotation::SID:
                    $key = "$prefix-$request->controller-$request->action-$annotation->key-" . session()->getId();
                    break;
                case RateLimiterAnnotation::IP:
                    $ip = $request->getRealIp();
                    if (in_array($ip, static::$ipWhiteList)) {
                        continue 2;
                    }
                    $key = "$prefix-$request->controller-$request->action-$annotation->key-$ip";
                    break;
                default:
                    if (is_array($annotation->key)) {
                        $key = $prefix . '-' . ($annotation->key)();
                    } else {
                        $key = "$prefix-$annotation->key";
                    }
            }
            if (static::$driver->increase($key, $annotation->ttl) > $annotation->limit) {
                $exceptionClass = $annotation->exception;
                throw new $exceptionClass($annotation->message);
            }
        }

        return $handler($request);
    }

    /**
     * @param Worker|null $worker
     * @return void
     * @throws RedisException
     */
    public static function init(?Worker $worker): void
    {
        static::$initialized     = true;
        static::$ipWhiteList     = config('limiter.ip_whitelist', []);
        static::$redisConnection = config('limiter.stores.redis.connection', 'default');
        $driver                  = config('limiter.driver');
        if ($driver === 'auto') {
            if (function_exists('apcu_enabled') && apcu_enabled()) {
                $driver = 'apcu';
            } else {
                $driver = 'memory';
            }
        }
        static::$driver = match ($driver) {
            'apcu'  => new Apcu($worker),
            'redis' => new Redis($worker, static::$redisConnection),
            default => new Memory($worker),
        };
    }

    /**
     * Check rate limit
     * @param string $key
     * @param int $limit
     * @param int $ttl
     * @param string $message
     * @return void
     * @throws RateLimitException
     */
    public static function check(string $key, int $limit, int $ttl, string $message = 'Too Many Requests'): void
    {
        $key = static::$prefix . '-' . $key;
        if (static::$driver->increase($key, $ttl) > $limit) {
            throw new RateLimitException($message);
        }
    }
}