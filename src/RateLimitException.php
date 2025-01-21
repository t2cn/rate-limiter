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

use App\Exception\BusinessException;
use T2\Request;
use T2\Response;
use Throwable;

class RateLimitException extends BusinessException
{
    /**
     * @param Request $request
     * @return Response|null
     */
    public function render(Request $request): ?Response
    {
        $code    = $this->getCode() ?: 429;
        $data    = $this->data ?? [];
        $message = $this->trans($this->getMessage(), $data);
        if ($request->expectsJson()) {
            $json = ['code' => $code, 'msg' => $message, 'data' => $data];

            return new Response(200, ['Content-Type' => 'application/json'],
                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return new Response($code, [], $message);
    }

    /**
     * Translate message.
     * @param string $message
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    protected function trans(string $message, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $args = [];
        foreach ($parameters as $key => $parameter) {
            $args[":$key"] = $parameter;
        }

        try {
            $message = trans($message, $args, $domain, $locale);
        } catch (Throwable $e) {

        }

        foreach ($parameters as $key => $value) {
            $message = str_replace(":$key", $value, $message);
        }

        return $message;
    }
}