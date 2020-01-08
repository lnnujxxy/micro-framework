<?php
namespace Pepper\Framework\Lib;

class Context
{
    private static $_container;
    private static $_lastError;

    public static function add($key, $value)
    {
        Interceptor::ensureFalse(isset(self::$_container[$key]), ERROR_PARAM_KEY_EXISTS, $key);
        self::$_container[$key] = $value;
        return true;
    }

    public static function set($key, $value)
    {
        self::$_container[$key] = $value;
        return true;
    }

    public static function get($key)
    {
        return self::$_container[$key];
    }

    public static function getLastError()
    {
        return self::$_lastError;
    }

    public static function setLastError($e)
    {
        self::$_lastError = $e;
    }

    public static function cleanLastError()
    {
        self::$_lastError = null;
    }
}