<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/20
 * Time: 下午3:30
 */

namespace App\Utility;

use EasySwoole\EasySwoole\Logger;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\Http\Request;

class Pub
{
    static function getRunMode(): string
    {
        return Config::getInstance()->getConf('RUN_MODE');
    }

    static function isDev(): bool
    {
        return Config::getInstance()->getConf('RUN_MODE') == AppConst::RM_DEV;
    }

    static function getProcessFullName(string $processName): string
    {
        return Config::getInstance()->getConf('SERVER_NAME') . '.Process.' . $processName;
    }

    static function udate(string $format = 'Y-m-d H:i:s.u', ?float $utimestamp = null): ?string
    {
        if (is_null($utimestamp)) {
            $utimestamp = microtime(true);
        }
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);
        $res = date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
        return $res ? $res : null;
    }

    static function clientIp(Request $request, $headerName = 'x-real-ip')
    {
        $server = ServerManager::getInstance()->getSwooleServer();
        $client = $server->getClientInfo($request->getSwooleRequest()->fd);
        $clientAddress = $client['remote_ip'];
        $xri = $request->getHeader($headerName);
        $xff = $request->getHeader('x-forwarded-for');
        if ($clientAddress === '127.0.0.1') {
            if (!empty($xri)) {  // 如果有xri 则判定为前端有NGINX等代理
                $clientAddress = $xri[0];
            } elseif (!empty($xff)) {  // 如果不存在xri 则继续判断xff
                $list = explode(',', $xff[0]);
                if (isset($list[0])) {
                    $clientAddress = $list[0];
                }
            }
        }
        return $clientAddress;
    }

    static function versionCompare(string $inputVersion, ?string $requestVersion = null): bool
    {
        if (empty($requestVersion)) {
            $version = Config::getInstance()->getConf('app.version');
        }
        $return = false;
        // 先获取 inputVersion 支持的版本，然后遍历
        foreach (explode('|', $inputVersion) as $item) {
            // 判断是否包含'+'支持以上版本
            if (strpos($item, '+') !== false) {
                // 判断 version 是否大于等于要求的版本
                if (version_compare($version, substr($item, 0, -1), '>=')) {
                    $return = true;
                    break;
                }
            } else {
                // 判断 version 是否等于要求的版本
                if ($version == $item) {
                    $return = true;
                    break;
                }
            }
        }
        return $return;
    }

    static function parseUriPath(Request $request): ?string
    {
        $msg = null;
        $path = substr($request->getUri()->getPath(), 1);
        $routers = Config::getInstance()->getConf('router');
        if (isset($routers[$path])) {
            $router = $routers[$path];
            if (in_array(strtolower($request->getMethod()), explode(',', $router['method']))) {
                $version = $request->getRequestParam('version');
                // 如果没传，默认获取当前最新版本
                if (empty($version)) {
                    $version = Config::getInstance()->getConf('app.version');
                }

                $flag = false;
                // 先获取 router 支持的版本，然后遍历
                foreach (explode('|', $router['version']) as $item) {
                    // 判断是否包含'+'支持以上版本
                    if (strpos($item, '+') !== false) {
                        // 判断 version 是否大于等于要求的 $vs 版本
                        $vs = substr($item, 0, -1);
                        if (version_compare($version, $vs, '>=')) {
                            $version = $vs;
                            $flag = true;
                            break;
                        }
                    } else {
                        // 判断 version 是否等于要求的版本
                        if ($version == $item) {
                            $flag = true;
                            break;
                        }
                    }
                }

                if ($flag) {
                    $module = join('', explode('.', $version));
                    // 配置了forward的用forward的，否则延用Path
                    if (isset($router['forward']) && !empty($router['forward'])) {
                        $path = $router['forward'];
                    }
                    $forward = "/v{$module}/{$path}";
                    $request->getUri()->withPath($forward);
                } else {
                    $msg = 'Version does not exist';
                }
            } else {
                $msg = 'Method not allowed';
            }
        } else {
            $msg = 'Uri not found';
        }
        return $msg;
    }

    static function saveSlowLog(Request $request): void
    {
        $nowTime = microtime(true);
        $reqTime = $request->getAttribute('request_time');
        $second = Config::getInstance()->getConf('app.slow_log.second');
        if (($nowTime - $reqTime) > $second) {
            // 计算一下运行时间
            $runTime = round($nowTime - $reqTime, 6) . 's';
            // 获取用户IP地址
            $ip = $request->getAttribute('remote_ip');
            // 拼接日志内容
            $data = ['ip' => $ip, 'time' => date('Y.m.d H:i:s', $reqTime), 'runtime' => $runTime, 'uri' => $request->getUri()->__toString()];
            $userAgent = $request->getHeader('user-agent');
            if (is_array($userAgent) && count($userAgent) > 0) {
                $data['user_agent'] = $userAgent[0];
            }
            Logger::getInstance()->log(var_export($data, true), 'slow');
        }
    }

    static function pushDingtalkMsg(string $msg, string $type = 'debug', int $time = null, string $file = null
        , int $line = null, string $ip = null, string $uri = null, string $userAgent = null): void
    {
        $cf = Config::getInstance();
        $env = $cf->getConf('RUN_MODE');
        $appName = $cf->getConf('app.name');
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
        HttpClient::getInstance()->post($cf->getConf('app.dingtalk.uri'), ['body' => json_encode($body),
            'timeout' => 3, 'headers' => ['Content-Type' => 'application/json; charset=utf-8']]);
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
     * @param string $url
     * @param array|null $params
     * @param string $tokenKey
     * @return array|null
     */
    static function requestWithSign(string $method, string $url, array $params = null, string $tokenKey = null): ?array
    {
        $now = time();
        if (is_null($tokenKey)) {
            $tokenKey = Config::getInstance()->getConf('app.token.key');
        }
        $method = strtolower($method);
        if ($method == 'post') {
            $params['form']['utime'] = $now;
            $params['form']['from_where'] = 'admin';
            $params['form']['sign'] = base64_encode(md5($now . $tokenKey));
            if (isset($_REQUEST['sess_token'])) {
                $params['form']['sess_token'] = $_REQUEST['sess_token'];
            }
            $body = HttpClient::getInstance()->post($url, $params)->getBody();
        } else {
            $params['query']['utime'] = $now;
            $params['query']['from_where'] = 'admin';
            $params['query']['sign'] = base64_encode(md5($now . $tokenKey));
            if (isset($_REQUEST['sess_token'])) {
                $params['query']['sess_token'] = $_REQUEST['sess_token'];
            }
            $body = HttpClient::getInstance()->get($url, $params)->getBody();
        }
        return empty($body) ? null : json_decode($body, true);
    }
}