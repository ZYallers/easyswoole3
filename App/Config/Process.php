<?php
/**
 * 自定义进程配置文件
 */

return [
    /** 暴力热加载 */
    [
        'runmode' => 'develop',
        'version' => '1.0.0+',
        'class' => \App\Process\HotReload::class,
        'name' => 'HotReload',
        'args' => null,
    ]
];