<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/25
 * Time: 下午3:35
 */

namespace App\Throwable;

use App\Utility\Curl;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class Handler
{
    public static function pushDingtalkMsg(string $msg, string $type = 'debug', int $time = null, string $file = null
        , int $line = null, string $ip = null, string $uri = null, string $userAgent = null)
    {
        $Conf = Config::getInstance();
        $env = $Conf->getConf('RUN_MODE');
        $appName = $Conf->getConf('app.name');
        $title = "[{$appName}/{$env}: {$type}] {$msg}";
        $text = ["### {$msg}", '> `App:` ' . $appName, '`Env:` ' . $env, '`Type:` ' . $type,
            '`Time:` ' . date('Y.n.j H:i:s', isset($time) ? $time : time())];
        if (isset($file)) {
            $text[] = '`File:` ' . $file;
        }
        if (isset($line)) {
            $text[] = '`Line:` ' . $line;
        }
        if (isset($ip)) {
            $text[] = '`IP:` ' . $ip;
        }
        if (isset($uri)) {
            $text[] = '`Uri:` ' . $uri;
        }
        if (isset($userAgent)) {
            $text[] = '`UserAgent:` ' . $userAgent;
        }
        $body = ['msgtype' => 'markdown', 'markdown' => ['title' => $title, 'text' => join('  ' . PHP_EOL, $text)]];
        Curl::getInstance()->request('post', $Conf->getConf('app.dingtalk.uri'), ['body' => json_encode($body)]);
    }

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
                self::pushDingtalkMsg($error['message'], 'shut_down', null, $error['file'], $error['line']);
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
                self::pushDingtalkMsg($description, 'error', null, $file, $line);
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
                self::pushDingtalkMsg($throwable->getMessage(), 'http_exception', null, $throwable->getFile(),
                    $throwable->getLine(), $ip, $request->getUri()->__toString(), $userAgent);
            }
        });
    }
}