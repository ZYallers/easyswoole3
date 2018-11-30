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
        $logDir = Config::getInstance()->getConf('LOG_DIR');
        $baseDir = $logDir . '/' . date('Ymd', $timeStamp);
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0777, true);
        }
        $str = '[' . date('Y/m/d H:i:s', $timeStamp) . ']: ' . $obj . PHP_EOL;
        $filePath = $baseDir . '/' . Config::getInstance()->getConf('SERVER_NAME') . '.' . $logCategory . '.log';
        file_put_contents($filePath, $str, FILE_APPEND | LOCK_EX);
    }
}