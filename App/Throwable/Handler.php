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
use EasySwoole\EasySwoole\Swoole\Memory\TableManager;
use EasySwoole\EasySwoole\Swoole\Task\TaskManager;
use EasySwoole\EasySwoole\Swoole\Time\Timer;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class Handler
{
    private final static function pushDtMsg(\Throwable $throwable, Request $request)
    {
        $env = Config::getInstance()->getConf('APP.debug') ? 'dev' : 'produce';
        $appname = Config::getInstance()->getConf('APP.name');
        $title = '[' . $appname . '/' . $env . '] ' . $throwable->getMessage();
        $temp = $request->getHeader('user-agent');
        $clientInfo = $request->getAttribute('client_info');
        $pushUri = Config::getInstance()->getConf('DINGTALK.push_uri');
        $token = Config::getInstance()->getConf('DINGTALK.access_token');

        $body = ['msgtype' => 'markdown', 'markdown' => [
            'title' => $title,
            'text' => join("  \n", [
                '### ' . $title,
                '> `IP:` ' . $clientInfo['remote_ip'],
                '`Time:` ' . date('Y.n.j H:i:s'),
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
        $trace = $throwable->__toString() . "\n\n";
        Logger::getInstance()->log($trace, 'exception');
        if (Config::getInstance()->getConf('APP.debug')) {
            echo $trace;
        }

        if (Config::getInstance()->getConf('DINGTALK.enable')) {
            if (!is_null(TableManager::getInstance()->get('share_table'))) {
                $arr = TableManager::getInstance()->get('share_table')->get('dingtalk.timestamp');
                $timestamp = (is_array($arr) && isset($arr['timestamp'])) ? intval($arr['timestamp']) : 0;
                $delay = intval(Config::getInstance()->getConf('DINGTALK.delay'));
                if ((time() - $timestamp) > $delay) {
                    TableManager::getInstance()->get('share_table')->set('dingtalk.timestamp', ['timestamp' => time()]);
                    Timer::delay(3000, function () use ($throwable, $request) {
                        self::pushDtMsg($throwable, $request);
                    });
                }
            }
        }
    }
}