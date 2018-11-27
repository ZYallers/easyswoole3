<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/26
 * Time: 下午6:20
 */

namespace App\Utility;


use EasySwoole\EasySwoole\Config;
use EasySwoole\Trace\AbstractInterface\LoggerWriterInterface;

class Logger implements LoggerWriterInterface
{
    public function writeLog($obj, $logCategory, $timeStamp)
    {
        // TODO: Implement writeLog() method.
        $appName = Config::getInstance()->getConf('app.name');
        $logDir = Config::getInstance()->getConf('LOG_DIR');
        $dir = $logDir . DIRECTORY_SEPARATOR . date('Ymd', $timeStamp);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $str = '[' . date('Y/m/d H:i:s', $timeStamp) . ']: ' . $obj . PHP_EOL;
        $filePath = $dir . DIRECTORY_SEPARATOR . $appName . '.' . $logCategory . '.log';
        file_put_contents($filePath, $str, FILE_APPEND | LOCK_EX);
    }
}