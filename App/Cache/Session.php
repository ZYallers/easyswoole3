<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/12
 * Time: 下午4:29
 */

namespace App\Cache;


use App\Utility\Abst\Cache;
use EasySwoole\EasySwoole\Config;

class Session extends Cache
{
    public function __construct(string $className = null)
    {
        parent::__construct(\App\Utility\Pool\Redis\Session::class);
    }

    private function phpUnserialize(string $str): ?array
    {
        $vars = [];
        $offset = 0;
        $strlen = strlen($str);
        while ($offset < $strlen) {
            if (!strstr(substr($str, $offset), '|')) {
                //throw new \Exception('Invalid data, remaining: ' . substr($str, $offset));
                break;
            }
            $pos = strpos($str, '|', $offset);
            $num = $pos - $offset;
            $varname = substr($str, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($str, $offset));
            $vars[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return count($vars) > 0 ? $vars : null;
    }

    private function phpSerialize(array $vars): string
    {
        $ret = '';
        foreach ($vars as $key => $value) {
            $ret .= $key . '|' . serialize($value);
        }
        return $ret;
    }

    public function getData(?string $sid, string $key = null)
    {
        $return = null;
        $resp = $this->getCache()->get(KeyConst::SESSION_KEY_PREFIX . $sid);
        if (!empty($resp)) {
            $vars = $this->phpUnserialize($resp);
            if (!is_null($vars)) {
                if (is_null($key)) {
                    $return = $vars;
                } else {
                    if (isset($vars[$key])) {
                        $return = $vars[$key];
                    }
                }
            }
        }
        return $return;
    }

    public function refreshExpireTime(string $sid): void
    {
        $vars = $this->getData($sid);
        if (!is_null($vars)) {
            $nowTime = time();
            $modRate = Config::getInstance()->getConf('redis.session.mod_rate');
            $lastRegen = isset($vars['__ci_last_regenerate']) ? $vars['__ci_last_regenerate'] : 0;
            if (($nowTime - $lastRegen) > $modRate) {
                $vars['__ci_last_regenerate'] = $nowTime;
                $expireTime = Config::getInstance()->getConf('redis.session.expire_time');
                if (isset($vars['__ci_vars']) && is_array($vars['__ci_vars'])) {
                    foreach ($vars['__ci_vars'] as $key => $value) {
                        $vars['__ci_vars'][$key] = $nowTime + $expireTime;
                    }
                }
                $newVal = $this->phpSerialize($vars);
                $this->getCache()->setex(KeyConst::SESSION_KEY_PREFIX . $sid, $expireTime, $newVal);
            }
        }
    }
}