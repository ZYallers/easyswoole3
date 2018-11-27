<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/25
 * Time: 下午3:35
 */

namespace App\Throwable;

use App\Utility\Pub;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class Handler
{
    public static function shutDownHandler()
    {
        $error = error_get_last();
        \go(function () use ($error) {
            if (!empty($error)) {
                Logger::getInstance()->log(var_export($error, true), 'shut_down');
            }
        });
        \go(function () use ($error) {
            if (!empty($error) && Config::getInstance()->getConf('app.dingtalk.enable')) {
                Pub::pushDingtalkMsg($error['message'], 'shut_down', null, $error['file'], $error['line']);
            }
        });
    }

    public static function errorHandler($errorCode, $description, $file = null, $line = null)
    {
        \go(function () use ($description, $file, $line) {
            $data = ['description' => $description, 'file' => $file, 'line' => $line];
            Logger::getInstance()->log(var_export($data, true), 'error');
        });
        \go(function () use ($description, $file, $line) {
            if (Config::getInstance()->getConf('app.dingtalk.enable')) {
                Pub::pushDingtalkMsg($description, 'error', null, $file, $line);
            }
        });
    }

    public static function httpExceptionhandler(\Throwable $throwable, Request $request, Response $response)
    {
        \go(function () use ($throwable) {
            Logger::getInstance()->log($throwable->getTraceAsString(), 'http_exception');
        });
        \go(function () use ($throwable, $request) {
            if (Config::getInstance()->getConf('app.dingtalk.enable')) {
                $ip = $request->getAttribute('remote_ip');
                $tmp = $request->getHeader('user-agent');
                $userAgent = (is_array($tmp) && count($tmp) > 0) ? $tmp[0] : '';
                Pub::pushDingtalkMsg($throwable->getMessage(), 'http_exception', null, $throwable->getFile(),
                    $throwable->getLine(), $ip, $request->getUri()->__toString(), $userAgent);
            }
        });
    }
}