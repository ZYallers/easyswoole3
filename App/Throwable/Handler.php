<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/25
 * Time: 下午3:35
 */

namespace App\Throwable;

use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\FastCache\Cache;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class Handler
{
    const PUSHMSG_QUEUE_KEY = 'throwable.handler.pushmsg.queue';

    public static function shutDownHandler()
    {
        $error = error_get_last();
        if (!empty($error)) {
            Logger::getInstance()->log(var_export($error, true), 'shut_down');
            if (Config::getInstance()->getConf('app.dingtalk.enable')) {
                Cache::getInstance()->enQueue(self::PUSHMSG_QUEUE_KEY, [$error['message'], 'shut_down', time(), $error['file'], $error['line']]);
            }
        }
    }

    public static function errorHandler($errorCode, $description, $file = null, $line = null)
    {
        Logger::getInstance()->log(var_export(['description' => $description, 'file' => $file, 'line' => $line], true), 'error');
        if (Config::getInstance()->getConf('app.dingtalk.enable')) {
            Cache::getInstance()->enQueue(self::PUSHMSG_QUEUE_KEY, [$description, 'error', time(), $file, $line]);
        }
    }

    public static function httpExceptionHandler(\Throwable $throwable, Request $request, Response $response)
    {
        Logger::getInstance()->log($throwable->__toString(), 'http_exception');
        if (Config::getInstance()->getConf('app.dingtalk.enable')) {
            $ip = $request->getAttribute('remote_ip');
            $tmp = $request->getHeader('user-agent');
            $userAgent = (is_array($tmp) && count($tmp) > 0) ? $tmp[0] : '';
            Cache::getInstance()->enQueue(self::PUSHMSG_QUEUE_KEY, [$throwable->getMessage(), 'http_exception',
                time(), $throwable->getFile(), $throwable->getLine(), $ip, $request->getUri()->__toString(), $userAgent]);
        }
    }
}