<?php
namespace Pepper\Framework\Lib;

/*
 * 支持的降级
 * define('LOG_DEGRADED', false); // 日志降级
 * define('DOWNGRADE', 0); // 全站降级等级
 * define('SLIENT_USERIDS', array('123')); // 禁言直播间名单
 * define('DOWNGRAGE_NAMELIST', array('123' => array('level' => 1))); // 热门主播降级名单
*/
class Degraded
{
    static $map = array();

    public static function getAll()
    {
        if (empty($_SERVER['HTTP_DEGRADED'])) {
            return array();
        } else {
            $result = json_decode($_SERVER['HTTP_DEGRADED'], true);
            return $result;
        }
    }

    public static function raw()
    {
        return $_SERVER['HTTP_DEGRADED'];
    }

    /**
     * 需要在开始的时候设置一下。
     */
    public static function set()
    {
        self::$map = self::getAll();
    }

    /**
     * 但实际是返回配置的降级值
     * @param $name string
     * @return bool|mixed
     */
    public static function get($name)
    {
        if (isset(self::$map[$name])) {
            return self::$map[$name];
        } else {
            return false;
        }
    }
}