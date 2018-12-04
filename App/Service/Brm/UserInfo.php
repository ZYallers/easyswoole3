<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/10/31
 * Time: 下午2:57
 */

namespace App\Service\Brm;

use App\Service\User\UserPassport;
use App\Utility\Abst\Service;
use App\Utility\Pub;

class UserInfo extends Service
{
    use \EasySwoole\Component\Singleton;
    
    public function getByUserId($userId): ?array
    {
        $up = (new UserPassport())->getByUserId($userId);
        if (is_null($up) || !isset($up['encode_phone']) || empty($up['encode_phone'])) {
            return null;
        }
        $info = $this->getByEncodePhone($up['encode_phone']);
        if (is_null($info) || !isset($info['data'][$userId])) {
            return null;
        }
        return $info['data'][$userId];
    }

    public function getByEncodePhone(string $encodePhone): ?array
    {
        $item = Pub::requestWithSign('get', 'https://im.hxsapp.com/api/Brm/getBrmUserInfoByEncodePhone', ['query' => ['encode_phone' => $encodePhone]]);
        return $item;
    }
}