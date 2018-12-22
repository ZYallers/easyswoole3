<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/25
 * Time: 下午3:35
 */

namespace App\Throwable;

use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\FastCache\Cache;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class Handler
{
    const PUSHMSG_QUEUE_KEY = 'throwable.handler.pushmsg.queue';

    public static function shutDownHandler()
    {
        $error = error_get_last();
        go(function () use ($error) {
            if (!empty($error)) {
                Logger::getInstance()->log(var_export($error, true), 'shut_down');
            }
        });
        go(function () use ($error) {
            if (!empty($error) && Config::getInstance()->getConf('app.dingtalk.enable')) {
                Cache::getInstance()->enQueue(self::PUSHMSG_QUEUE_KEY, [$error['message'], 'shut_down', time(),
                    $error['file'], $error['line']]);
            }
        });
    }

    public static function errorHandler($errorCode, $description, $file = null, $line = null)
    {
        go(function () use ($description, $file, $line) {
            $data = ['description' => $description, 'file' => $file, 'line' => $line];
            Logger::getInstance()->log(var_export($data, true), 'error');
        });
        go(function () use ($description, $file, $line) {
            if (Config::getInstance()->getConf('app.dingtalk.enable')) {
                Cache::getInstance()->enQueue(self::PUSHMSG_QUEUE_KEY, [$description, 'error', time(), $file, $line]);
            }
        });
    }

    public static function httpExceptionhandler(\Throwable $throwable, Request $request, Response $response)
    {
        go(function () use ($throwable) {
            Logger::getInstance()->log($throwable->__toString(), 'http_exception');
        });
        go(function () use ($throwable, $request) {
            if (Config::getInstance()->getConf('app.dingtalk.enable')) {
                $ip = $request->getAttribute('remote_ip');
                $tmp = $request->getHeader('user-agent');
                $userAgent = (is_array($tmp) && count($tmp) > 0) ? $tmp[0] : '';
                Cache::getInstance()->enQueue(self::PUSHMSG_QUEUE_KEY, [$throwable->getMessage(), 'http_exception',
                    time(), $throwable->getFile(), $throwable->getLine(), $ip, $request->getUri()->__toString(), $userAgent]);
            }
        });
    }
}