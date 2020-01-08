<?php

namespace Pepper\Lib;

trait Cached
{
    protected static $_methodCached = [];

    /**
     * 方法级缓存
     * @param string $key 缓存key
     * @param callable $resultCallback 返回内容的callback，如果失败，可抛异常或者返回false
     * @param int $expire 缓存时长，单位秒
     * @param int $expireOnFailed 如果获取内容失败，则失败的结果被缓存的时间
     * @return mixed|null
     */
    public static function sessionCached($key, $resultCallback, $expire = 3, $expireOnFailed = 1)
    {
        $cached = self::_get($key);
        if ($cached === null) {
            try {
                $result = call_user_func($resultCallback);
                self::_set($key, $result, $expire);
            } catch (\Exception $e) {
                $result = null;
                $expireOnFailed && self::_set($key, $result, $expireOnFailed);
            }

            $cached = $result;
        }
        return $cached;
    }

    /**
     * 远程缓存
     * @param string $key
     * @param callable $resultCallback 返回内容的callback，如果失败，可抛异常或者返回false
     * @param ICache $cacheObject
     * @param int $expire 缓存时长，单位秒
     * @param int $expireOnFailed 如果获取内容失败，则失败的结果被缓存的时间
     * @param bool $useExpiredData 是否使用过期数据进行返回
     * @return mixed|false
     */
    public static function remoteCached($key, $resultCallback, ICache $cacheObject, $expire = 10, $expireOnFailed = 1, $useExpiredData = true)
    {
        if ($useExpiredData){
            $result = $cacheObject->getpro($key, $expiredData);
        }else{
            $result = $cacheObject->get($key);
        }
        // 缓存不可用
        if ($result === false){
            try{
                $result = call_user_func($resultCallback);
            }catch (\Exception $e){
                // 如果获取内容失败，则按照失败缓存时间设置缓存
                $expire = $expireOnFailed;
            }
            // 如果允许使用过期数据，并且存在过期数据，则使用过期数据
            if (!$result && $useExpiredData && isset($expiredData) && $expiredData){
                $result = $expiredData;
            }

            // 如果有内容，并且有过期时间（当失败时，可能指定过期时间为0）
            if ($expire && $result){
                if ($useExpiredData){
                    $cacheObject->setpro($key, $result, $expire);
                }else{
                    $cacheObject->set($key, $result, $expire);
                }
            }
        }
        return $result;
    }

    /**
     * 读取一个方法级缓存
     * @param string $key
     * @return null|mixed
     */
    protected static function _get($key)
    {
        if (!isset(self::$_methodCached[$key])) {
            return null;
        }

        if (self::$_methodCached[$key]['expired'] < time()) {
            return null;
        }

        return self::$_methodCached[$key]['val'];

    }

    /**
     * 设置一个方法级缓存
     * @param string $key
     * @param mixed $val
     * @param int $expire 过期时间
     */
    protected static function _set($key, $val, $expire)
    {
        self::$_methodCached[$key] = ['val' => $val, 'expired' => time() + $expire];
    }

    /**
     * 清除一个方法级缓存
     * @param string $key
     */
    protected static function _clear($key){
        unset(self::$_methodCached[$key]);
    }

    /**
     * 按前缀清除方法级缓存
     * @param $prefix
     */
    protected static function _clearPrefix($prefix){
        foreach (self::$_methodCached as $k => $_){
            if (strncmp($k, $prefix, strlen($prefix)) === 0){
                unset(self::$_methodCached[$k]);
            }
        }
    }

    /**
     * 清空方法级缓存，慎用
     */
    protected static function _clearAll(){
        self::$_methodCached = [];
    }
}