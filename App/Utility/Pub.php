<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/20
 * Time: 下午3:30
 */

namespace App\Utility;


use EasySwoole\EasySwoole\Config;

class Pub
{
    static function pushDingtalkMsg(string $msg, string $type = 'debug', int $time = null, string $file = null
        , int $line = null, string $ip = null, string $uri = null, string $userAgent = null): void
    {
        $Conf = Config::getInstance();
        $env = $Conf->getConf('RUN_MODE');
        $appName = $Conf->getConf('app.name');
        $title = "[{$appName}/{$env}: {$type}] {$msg}";
        $text = ["### {$msg}", '> `App:` ' . $appName, '`Env:` ' . $env, '`Type:` ' . $type,
            '`Time:` ' . date('Y.n.j H:i:s', isset($time) ? $time : time())];
        if (isset($file)) {
            $text[] = '`File:` ' . $file;
        }
        if (isset($line)) {
            $text[] = '`Line:` ' . $line;
        }
        if (isset($ip)) {
            $text[] = '`IP:` ' . $ip;
        }
        if (isset($uri)) {
            $text[] = '`Uri:` ' . $uri;
        }
        if (isset($userAgent)) {
            $text[] = '`UserAgent:` ' . $userAgent;
        }
        $body = ['msgtype' => 'markdown', 'markdown' => ['title' => $title, 'text' => join('  ' . PHP_EOL, $text)]];
        Curl::getInstance()->request('post', $Conf->getConf('app.dingtalk.uri'), ['body' => json_encode($body)]);
    }

    /**
     * getOssUri
     * @param string $uri
     * @param string $type
     * @param null|string $bucket
     * @return string
     */
    static function getOssUri(string $uri, string $type = 'thumb', ?string $bucket = null): string
    {
        // 默认是上传域那个bucket
        if (!isset($bucket)) {
            $bucket = Config::getInstance()->getConf('oss.upload_bucket');
        }
        // 如果已经带域名的把域名去掉
        if (strpos($uri, 'oss.hxsapp.com') !== false) {
            $uri = explode('?', $uri)[0];
        } else {
            $uri = 'https://' . $bucket . '-oss.hxsapp.com/' . $uri;
        }
        if ($type == 'thumb') {
            return $uri . '?x-oss-process=style/thumb';
        } else if ($type == 'medium') {
            return $uri . '?x-oss-process=style/medium';
        } else {
            return $uri;
        }
    }

    /**
     * 根据生日算年龄
     * @param string $birthday
     * @return int|null
     */
    static function birthdayToAge(string $birthday): ?int
    {
        $age = strtotime($birthday);
        if ($age === false) {
            return null;
        }
        list($y1, $m1, $d1) = explode('-', date('Y-m-d', $age));
        $now = strtotime('now');
        list($y2, $m2, $d2) = explode('-', date('Y-m-d', $now));
        $age = $y2 - $y1;
        if ((int)($m2 . $d2) < (int)($m1 . $d1)) {
            $age -= 1;
        }
        return $age;
    }

    /**
     * 带md5验证的curl请求
     * @param string $method
     * @param string $uri
     * @param array|null $params
     * @param string $tokenKey
     * @return array|null
     */
    static function requestWithSign(string $method, string $uri, array $params = null, string $tokenKey = null): ?array
    {
        $now = time();
        if (is_null($tokenKey)) {
            $tokenKey = Config::getInstance()->getConf('app.token.key');
        }
        if (strtoupper($method) == 'POST') {
            $params['form_params']['utime'] = $now;
            $params['form_params']['from_where'] = 'admin';
            $params['form_params']['sign'] = base64_encode(md5($now . $tokenKey));
            if (isset($_REQUEST['sess_token'])) {
                $params['form_params']['sess_token'] = $_REQUEST['sess_token'];
            }
        } else {
            $params['query']['utime'] = $now;
            $params['query']['from_where'] = 'admin';
            $params['query']['sign'] = base64_encode(md5($now . $tokenKey));
            if (isset($_REQUEST['sess_token'])) {
                $params['query']['sess_token'] = $_REQUEST['sess_token'];
            }
        }
        $body = Curl::getInstance()->request($method, $uri, $params)->getBody();
        return !empty($body) ? json_decode($body, true) : null;
    }
}