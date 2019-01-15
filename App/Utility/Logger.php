<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/26
 * Time: 下午6:20
 */

namespace App\Utility;

use EasySwoole\EasySwoole\Config;
use EasySwoole\Trace\AbstractInterface\LoggerInterface;

class Logger implements LoggerInterface
{
    private $logDir;
    private $serverName;

    function __construct(string $logDir = null)
    {
        $this->serverName = Config::getInstance()->getConf('SERVER_NAME');
        if (empty($logDir)) {
            $logDir = EASYSWOOLE_ROOT . '/Log';
        }
        $this->logDir = $logDir;

    }

    public function log(string $str, $logCategory, int $timestamp = null)
    {
        // TODO: Implement log() method.
        if (is_null($timestamp)) {
            $timestamp = time();
        }
        $dir = $this->logDir . '/' . date('Ymd', $timestamp);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filePath = $dir . '/' . $this->serverName . '.' . $logCategory . '.log';
        $date = date('Y/m/d H:i:s', $timestamp);
        $content = "[{$date}][{$logCategory}]: {$str}\n";
        file_put_contents($filePath, $content, FILE_APPEND | LOCK_EX);
    }

    public function console(string $str, $category = null, $saveLog = false)
    {
        // TODO: Implement console() method.
        if (empty($category)) {
            $category = 'console';
        }
        $time = time();
        $date = date('Y/m/d H:i:s', $time);
        echo "[{$date}][{$category}]: {$str}\n";
        if ($saveLog) {
            $this->log($str, $category, $time);
        }
    }
}