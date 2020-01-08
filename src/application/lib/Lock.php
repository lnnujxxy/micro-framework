<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/4/18
 * Time: 下午12:03
 */
namespace Pepper\Framework\Lib;

use Pepper\Lib\SimpleConfig;

class Lock
{
    /**
     * 加锁
     * @param $key
     * @param int $ttl
     * @return bool|string
     */
    public static function lock($key, $ttl = 3)
    {
        try {
            $ticket = uniqid('lock_ticket_' . $key . '_', true);
            $redis = RedisProxy::getInstance(SimpleConfig::get('REDIS_LOCK'));
            if ($redis->set($key, $ticket, array('nx', 'ex' => $ttl)) === true) {
                return $ticket;
            }
        } catch (\Exception $e) {
        }
        return false;
    }

    /**
     * 解锁
     * @param $key
     * @param $ticket
     * @return bool
     */
    public static function unlock($key, $ticket)
    {
        try {
            $redis = RedisProxy::getInstance(SimpleConfig::get('REDIS_LOCK'));
            if ($redis->get($key) == $ticket) {
                return $redis->del($key) !== false;
            }
        } catch (\Exception $e) {
        }
        return false;
    }
}
