<?php
/**
 * App 配置文件
 */

return [
    'name' => 'easyswoole3',
    'version' => '4.1.0',
    'token' => [
        'key' => '',
        'timeout' => 60
    ],
    'slow_log' => [
        'enable' => true,
        'second' => 3
    ],
    'dingtalk' => [
        'enable' => true,
        'uri' => 'https://oapi.dingtalk.com/robot/send?access_token=9c12b1dfc3a5749ed318f83c220f8f5248cdb7c3508a18016c8134f876840ec3'
    ],
    'throw_check_rate' => 20, //单位秒，检测异常并推送消息定时任务的检测频率
];