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

    function __construct()
    {
        $this->logDir = Config::getInstance()->getConf('LOG_DIR');
        $this->serverName = Config::getInstance()->getConf('SERVER_NAME');
        if (empty($this->logDir)) {
            $this->logDir = EASYSWOOLE_ROOT . '/Log';;
        }
    }

    public function log(string $str, $logCategory = null, int $timestamp = null): ?string
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
        $content = "[{$date}][{$logCategory}]: {$str}";
        file_put_contents($filePath, "{$content}\n", FILE_APPEND | LOCK_EX);
        return $content;
    }

    public function console(string $str, $category = null, $saveLog = false): ?string
    {
        // TODO: Implement console() method.
        if (empty($category)) {
            $category = 'console';
        }
        $time = time();
        $date = date('Y/m/d H:i:s', $time);
        $content = "[{$date}][{$category}]: {$str}";
        echo "{$content}\n";
        if ($saveLog) {
            $this->log($str, $category, $time);
        }
        return $content;
    }
}