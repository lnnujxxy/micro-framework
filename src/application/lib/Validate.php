<?php
namespace Pepper\Framework\Lib;

use Pepper\Lib\SimpleConfig;

class Validate
{
    private static $_inner_secret_key = '5ff10ecc78ada17c37b96fdf1ecb0c9e';
    private static $_outer_secret_key = 'eac63e66d8c4a6f0303f00bc76d0217c';

    public static function is_valid_client($request)
    {
        if (!isset($request['time']) || !$request['time']) {
            return false;
        }

        $guid = $request['guid'];
        if (empty($guid)) {
            return false;
        }

        $conf = SimpleConfig::get('REDIS_CONF');
        $hash_key = sprintf('%u', crc32($guid));
        $order = intval(fmod($hash_key, count($conf)));
        $redis = RedisProxy::getInstance($conf[$order]);
        if (false !== $redis->get($guid)) {
            Interceptor::ensureNotFalse(false, ERROR_PARAM_FLOOD_REQUEST);
        }

        $redis->setex($guid, 7200, 1);
        unset($request['guid']);

        ksort($request);
        $str = '';
        foreach ($request as $k => $v) {
            if (in_array($k, array('userid', 'rand', 'time'))) {
                $str .= $k . '=' . rawurldecode(urlencode($v)).'&';
            }
        }
        $sign = md5($str . self::$_outer_secret_key);

        return $guid == $sign ? true : false;
    }

    public static function is_valid_server($request)
    {
        if ($request['inner_secret'] != self::$_inner_secret_key) {
            return false;
        }
        return true;
    }

    public static function checkIp($ip, $allow_ip_list)
    {
        $y = explode('.', $ip);
        foreach ($allow_ip_list as $allow) {
            $x = count(explode('.', $allow));
            if (implode('.', array_slice($y, 0, $x)) == $allow) {
                return true;
            }
        }
        return false;
    }

    public static function isNumeric($value)
    {
        return is_numeric($value);
    }

    public static function isInts($nums)
    {
        return preg_match("/^(\\d+,?)+$/", $nums) != 0;
    }

    public static function isMobile($mobile)
    {
        return 0 != preg_match("/^1[3456789]{1}\d{9}$/", $mobile);
    }

    public static function isValidUrl($url)
    {
        return strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0;
    }

    public static function isPositiveNumber($value)
    {
        return $value > 0;
    }

    public static function isStr($value)
    {
        return strlen($value) > 0;
    }
}
