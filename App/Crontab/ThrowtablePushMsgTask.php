<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/12/20
 * Time: 下午3:21
 */

namespace App\Crontab;

use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\FastCache\Cache;

class ThrowtablePushMsgTask extends AbstractCronTask
{
    /**
     * @var int $rate 检查心率
     */
    private static $rate;

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

    private static function pushMsg(): void
    {
        $data = Cache::getInstance()->deQueue(\App\Throwable\Handler::PUSHMSG_QUEUE_KEY);
        //echo date('Y/m/d H:i:s') . ': Throwable pushmsg, data: ' . var_export($data, true) . ".\n";
        if (!empty($data) && is_array($data)) {
            call_user_func_array([\App\Utility\Pub::class, 'pushDingtalkMsg'], $data);
        }
    }

    private static function getRate()
    {
        if (!isset(self::$rate)) {
            $rate = Config::getInstance()->getConf('app.throw_check_rate');
            if (!intval($rate) > 0) {
                $rate = 10; //检测异常并推送消息定时任务的检测频率，默认10秒
            }
            self::$rate = $rate;
        }
        return self::$rate;
    }

    public static function run(\swoole_server $server, int $taskId, int $fromWorkerId, $flags = null)
    {
        // TODO: Implement run() method.
        $rate = self::getRate();
        for ($second = $rate; $second <= 60; $second += $rate) {
            $server->after($second * 1000, function () {
                self::pushMsg();
            });
        }
    }
}