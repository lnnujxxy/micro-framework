<?php
namespace Pepper\Framework\Lib;

use Pepper\Framework\Traits\RedisTrait;
use Pepper\Lib\SimpleConfig;

class Restrict
{
    use RedisTrait;
    private static $_config = array();

    private static $_whites = array(//'13426096953',//tianzuo
    );

    private static function _getConfig($rule)
    {
        self::$_config = SimpleConfig::get('REQUEST_SPEED_LIMIT');
        return isset(self::$_config[$rule]) ? self::$_config[$rule] : array('prefix' => 'restrict_' . md5($rule) . '_', 'frequency' => 120, 'interval' => 60, 'text' => 0);
    }

    /**
     * 封禁检查
     * @param $rule string 规则名
     * @param $uniqid string 封禁检查值
     * @param bool $update 是否检查同时更新次数值，默认只检查是否触犯封禁次数
     * @return bool true 达到封禁次数 false 未达到封禁次数
     */
    public static function check($rule, $uniqid, $update = false)
    {
        foreach (self::$_whites as $white) {
            if (strpos($uniqid, $white) !== false) {
                return false;
            }
        }

        $config = self::_getConfig($rule);

        $key = $config['prefix'] . $uniqid;
        $limit = $config['frequency'];
        $interval = $config['interval'];
        $redis = self::getRedis();
        $record = $redis->get($key);

        $record = empty($record) ? '0_' . time() : $record;
        list($times, $starttime) = explode('_', $record);

        $times = empty($times) ? '0' : $times;
        $starttime = !empty($starttime) ? $starttime : time();

        if (time() <= ($starttime + $interval)) {
            if ($times >= $limit) {
                return true;
            }
        } else {
            $times = 0;
            $starttime = time();
        }

        if ($update) {
            $record = ($times + 1) . '_' . $starttime;
            $redis->setex($key, $interval, $record);
        }

        return false;
    }

    /**
     * 封禁触犯次数增加
     * @param $rule
     * @param $uniqid
     * @return bool
     */
    public static function add($rule, $uniqid)
    {
        return self::check($rule, $uniqid, true);
    }

    const KEY_PAY_GOODS_CASH = 'key:pay:goods:cash';
    public static function addItem($key, $item) {
        $redis = self::getRedis();
        $redis->sAdd($key, $item);
    }

    public static function checkItem($key, $item) {
        $redis = self::getRedis();
        return $redis->sIsMember($key, $item);
    }

    public static function clearItem($key, $item) {
        $redis = self::getRedis();
        $redis->sRem($key, $item);
    }
}
