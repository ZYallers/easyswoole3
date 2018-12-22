<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/12/20
 * Time: 下午3:21
 */

namespace App\Crontab;

use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\FastCache\Cache;
use EasySwoole\EasySwoole\Swoole\Time\Timer;

class ThrowtablePushMsgTask extends AbstractCronTask
{
    public static function getRule(): string
    {
        // TODO: Implement getRule() method.
        // 每分钟执行一次
        return '* * * * *';
    }

    public static function getTaskName(): string
    {
        // TODO: Implement getTaskName() method.
        return 'ThrowtablePushMsgTask';
    }

    public static function run(\swoole_server $server, int $taskId, int $fromWorkerId)
    {
        // TODO: Implement run() method.
        for ($second = 0; $second <= 60; $second += 10) {
            //echo date('Y/m/d H:i:s') . ": Second: {$second}.\n";
            Timer::delay(($second * 1000) + 1, function () {
                $data = Cache::getInstance()->deQueue(\App\Throwable\Handler::PUSHMSG_QUEUE_KEY);
                echo date('Y/m/d H:i:s') . ': Throwable pushmsg, data: ' . var_export($data, true) . ".\n";
                if (!empty($data) && is_array($data)) {
                    call_user_func_array([\App\Utility\Pub::class, 'pushDingtalkMsg'], $data);
                }
            });
        }
    }
}