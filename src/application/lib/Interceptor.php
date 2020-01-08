<?php
namespace Pepper\Framework\Lib;

class Interceptor
{
    public static function ensureNull($result, $error, $args = array())
    {
        if (!is_null($result)) {
            throw new BizException($error, $args);
        }

        return $result;
    }

    public static function ensureNotNull($result, $error, $args = array())
    {
        if (is_null($result)) {
            throw new BizException($error, $args);
        }

        return $result;
    }

    public static function ensureNotEmpty($result, $error, $args = array())
    {
        if (empty($result)) {
            throw new BizException($error, $args);
        }

        return $result;
    }

    public static function ensureEmpty($result, $error, $args = array())
    {
        if (!empty($result)) {
            throw new BizException($error, $args);
        }

        return $result;
    }

    public static function ensureNotFalse($result, $error, $args = array())
    {
        if ($result === false) {
            throw new BizException($error, $args);
        }

        return $result;
    }

    public static function ensureFalse($result, $error, $args = array())
    {
        if ($result !== false) {
            throw new BizException($error, $args);
        }

        return $result;
    }
}