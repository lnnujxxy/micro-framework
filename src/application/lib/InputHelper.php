<?php
namespace Pepper\Framework\Lib;

class InputHelper {
    public static function checkInt($num, $err) {
        Interceptor::ensureNotFalse(is_numeric($num) ,ERROR_PARAM_INVALID_FORMAT, $err);
        return intval($num);
    }

    public static function checkGender($gender, $err='gender') {
        Interceptor::ensureNotFalse(in_array($gender, ['male', 'female', 'all']), ERROR_PARAM_INVALID_FORMAT, $err);
        return $gender;
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
}