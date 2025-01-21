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

use App\Exception\BusinessException;
use Workerman\Worker;

class Apcu implements DriverInterface
{
    /**
     * 构造方法
     * @param Worker|null $worker
     */
    public function __construct(?Worker $worker)
    {
        if (!extension_loaded('apcu')) {
            throw new BusinessException('APCu extension is not loaded');
        }

        if (!apcu_enabled()) {
            throw new BusinessException('APCu is not enabled. Please set apc.enable_cli=1 in php.ini to enable APCu.');
        }
    }

    /**
     * @param string $key
     * @param int $ttl
     * @param int $step
     * @return int
     */
    public function increase(string $key, int $ttl = 24 * 60 * 60, int $step = 1): int
    {
        return apcu_inc("$key-" . $this->getExpireTime($ttl) . '-' . $ttl, $step, $success, $ttl) ?: 0;
    }

    /**
     * @param $ttl
     * @return int
     */
    protected function getExpireTime($ttl): int
    {
        return ceil(time() / $ttl) * $ttl;
    }
}