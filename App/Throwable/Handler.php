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
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use EasySwoole\EasySwoole\Swoole\Time\Timer;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class Handler
{
    public static function pushDingtalkMsg(string $type, int $time, string $message, string $file, int $line, string $ip = '', string $uri = '', string $userAgent = '')
    {
        $Conf = Config::getInstance();
        $env = $Conf->getConf('app.debug') ? 'develop' : 'produce';
        $appName = $Conf->getConf('app.name');
        $title = "[{$appName}/{$env}: {$type}] {$message}";
        $text = ["### {$message}", '> `App:` ' . $appName, '`Env:` ' . $env, '`Type:` ' . $type,
            '`Time:` ' . date('Y.n.j H:i:s', $time), '`File:` ' . $file, '`Line:` ' . $line];
        if (!empty($ip)) {
            $text[] = '`IP:` ' . $ip;
        }
        if (!empty($uri)) {
            $text[] = '`Uri:` ' . $uri;
        }
        if (!empty($userAgent)) {
            $text[] = '`UserAgent:` ' . $userAgent;
        }
        $body = ['msgtype' => 'markdown', 'markdown' => ['title' => $title, 'text' => join('  ' . PHP_EOL, $text)]];
        $pushUri = $Conf->getConf('dingtalk.push_uri');
        $token = $Conf->getConf('dingtalk.access_token');
        Curl::getInstance()->request('post', "{$pushUri}?access_token={$token}", ['body' => json_encode($body)]);
    }

    public static function shutDownHandler()
    {
        //TODO::这里的异步不能用Timer::delay()方法
        $error = error_get_last();
        if (!empty($error)) {
            TaskManager::async(function () use ($error) {
                Logger::getInstance()->log(var_export($error, true), 'shut_down');
            });
            if (Config::getInstance()->getConf('dingtalk.enable')) {
                $time = time();
                TaskManager::async(function () use ($time, $error) {
                    Handler::pushDingtalkMsg('shut_down', $time, $error['message'], $error['file'], $error['line']);
                });
            }
        }
    }

    public static function errorHandler($errorCode, $description, $file = null, $line = null)
    {
        $time = time();
        Timer::delay(2000, function () use ($time, $description, $file, $line) {
            $data = ['description' => $description, 'file' => $file, 'line' => $line];
            Logger::getInstance()->log(var_export($data, true), 'error');
        });
        if (Config::getInstance()->getConf('dingtalk.enable')) {
            Timer::delay(5000, function () use ($time, $description, $file, $line) {
                Handler::pushDingtalkMsg('error', $time, $description, $file, $line);
            });
        }
    }

    public static function httpExceptionhandler(\Throwable $throwable, Request $request, Response $response)
    {
        Timer::delay(2000, function () use ($throwable) {
            Logger::getInstance()->log($throwable->getTraceAsString(), 'http_exception');
        });
        if (Config::getInstance()->getConf('dingtalk.enable')) {
            $time = time();
            Timer::delay(5000, function () use ($time, $throwable, $request) {
                $ip = ServerManager::getInstance()->getSwooleServer()->connection_info($request->getSwooleRequest()->fd);
                $ip = isset($ip['remote_ip']) ? $ip['remote_ip'] : 'unknow';
                $tmp = $request->getHeader('user-agent');
                $userAgent = (is_array($tmp) && count($tmp) > 0) ? $tmp[0] : '';
                Handler::pushDingtalkMsg('http_exception', $time, $throwable->getMessage(), $throwable->getFile(),
                    $throwable->getLine(), $ip, $request->getUri()->__toString(), $userAgent);
            });
        }
    }
}