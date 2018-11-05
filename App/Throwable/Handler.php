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
use EasySwoole\EasySwoole\Swoole\Memory\AtomicManager;
use EasySwoole\EasySwoole\Swoole\Time\Timer;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class Handler
{
    private final static function pushDtMsg(\Throwable $throwable, Request $request)
    {
        $env = Config::getInstance()->getConf('app.debug') ? 'dev' : 'produce';
        $appname = Config::getInstance()->getConf('app.name');
        $title = '[' . $appname . '/' . $env . '] ' . $throwable->getMessage();
        $temp = $request->getHeader('user-agent');
        $clientInfo = $request->getAttribute('client_info');
        $pushUri = Config::getInstance()->getConf('dingtalk.push_uri');
        $token = Config::getInstance()->getConf('dingtalk.access_token');

        $body = ['msgtype' => 'markdown', 'markdown' => [
            'title' => $title,
            'text' => join("  \n", [
                '### ' . $title,
                '> `IP:` ' . $clientInfo['remote_ip'],
                '`Time:` ' . date('Y.n.j H:i:s', $clientInfo['connect_time']),
                '`Url:` ' . $request->getUri()->__toString(),
                '`File:` ' . $throwable->getFile(),
                '`Line:` ' . $throwable->getLine(),
                '`UserAgent:` ' . ((is_array($temp) && count($temp) > 0) ? $temp[0] : 'NULL')
            ])
        ]];

        Curl::getInstance()->request('post', "{$pushUri}?access_token={$token}", ['body' => json_encode($body)]);
    }

    public static function handle(\Throwable $throwable, Request $request, Response $response)
    {
        $trace = $throwable->__toString() . "\n";
        Logger::getInstance()->log($trace, 'exception');
        if (Config::getInstance()->getConf('app.debug')) {
            echo $trace;
        }
        // 钉钉群消息推送
        if (Config::getInstance()->getConf('dingtalk.enable')) {
            $timestamp = AtomicManager::getInstance()->get('dingtalk.timestamp')->get();
            $delay = Config::getInstance()->getConf('dingtalk.delay');
            if ((time() - $timestamp) > $delay) {
                AtomicManager::getInstance()->get('dingtalk.timestamp')->set(time());
                // 本想用async方法，但async不能use资源类型的变量，所有只好用delay，延迟个1毫秒再执行
                Timer::delay(1, function () use ($throwable, $request) {
                    self::pushDtMsg($throwable, $request);
                });
            }
        }
    }
}