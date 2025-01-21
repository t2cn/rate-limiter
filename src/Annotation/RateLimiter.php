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