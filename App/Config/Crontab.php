<?php
/**
 * 定时任务配置文件
 */

return [
    /** 异常消息推送定时任务 */
    [
        'runmode' => null,
        'version' => '1.0.0+',
        'class' => \App\Crontab\ThrowtablePushMsgTask::class,
    ]
];