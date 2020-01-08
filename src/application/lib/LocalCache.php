<?php
namespace Pepper\Framework\Lib;

class LocalCache
{
    public static function set($key, $data, $expire)
    {
        if (!function_exists('apcu_store')) {
            return false;
        }
        return apcu_store($key, $data, $expire);
    }

    public static function get($key)
    {
        if (!function_exists('apcu_fetch')) {
            return false;
        }
        $result = apcu_fetch($key);
        return $result;
    }
}
