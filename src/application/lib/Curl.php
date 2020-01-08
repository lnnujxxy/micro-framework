<?php
namespace Pepper\Framework\Lib;

class Curl
{
    static $timeout = 3000; //毫秒

    public static function get($url, $port = 0, $timeout = 3000, $connectTimeout = 1000)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, ceil(self::$timeout / 1000));
        $port && curl_setopt($ch, CURLOPT_PORT, $port);

        //兼容秒级和毫秒级超时， cURL 版本 >= libcurl/7.21.0 版本，毫秒级超时是一定生效的
        if (defined('CURLOPT_NOSIGNAL') && defined('CURLOPT_CONNECTTIMEOUT_MS') && defined('CURLOPT_TIMEOUT_MS')) {
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeout ? $connectTimeout : self::$timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout ? $timeout : self::$timeout);
        }

        $content = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        Interceptor::ensureNotFalse(false !== $content && 0 === $errno, ERROR_CURL_URL_SYSTEM_ERROR, array('get', $url, $errno, $error));
        Interceptor::ensureNotFalse($httpcode == 200, ERROR_CURL_URL_HTTP_ERROR, array('get', $url, $httpcode, 'httpcode return $httpcode'));

        curl_close($ch);

        return $content;
    }

    public static function post($url, $params, $port = 0, $header = array())
    {
        if (empty($header)) {
            $header = array(
                'Content-Type: application/x-www-form-urlencoded',
            );
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout / 1000);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $port && curl_setopt($ch, CURLOPT_PORT, $port);

        //兼容秒级和毫秒级超时， cURL 版本 >= libcurl/7.21.0 版本，毫秒级超时是一定生效的
        if (defined('CURLOPT_NOSIGNAL') && defined('CURLOPT_CONNECTTIMEOUT_MS') && defined('CURLOPT_TIMEOUT_MS')) {
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::$timeout);
        }

        $content = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        Interceptor::ensureNotFalse(false !== $content && 0 === $errno, ERROR_CURL_URL_SYSTEM_ERROR, array('post', $url, $errno, $error));
        Interceptor::ensureNotFalse($httpcode == 200, ERROR_CURL_URL_HTTP_ERROR, array('post', $url, $httpcode, 'httpcode return $httpcode'));

        curl_close($ch);

        return $content;
    }
}