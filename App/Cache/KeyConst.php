<?php
/**
 * Created by PhpStorm.
 * User: zhongyongbiao
 * Date: 2018/11/12
 * Time: 下午4:35
 */

namespace App\Cache;


class KeyConst
{
    const SESSION_KEY_PREFIX = 'ci_session:';
    const USER_INFO_PREFIX = 'account@user:info:';
    const USER_PASSPORT_INFO_PREFIX = 'account@user:passport:info;';
    const ADVISER_INFO_PREFIX = 'account@adviser:info:';
    const INNER_VIP_INFO_PREFIX = 'account@inner:vip:info:';
}