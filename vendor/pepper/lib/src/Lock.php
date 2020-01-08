<?php

namespace Pepper\Lib;

class Lock
{

    /**
     * 高速自旋，最大每秒10次
     */
    const SPIN_INTERVAL_FAST = 100;

    /**
     * 中速自旋，最大每秒2次
     */
    const SPIN_INTERVAL_MEDIUM = 500;

    /**
     * 低速自旋，最大每秒一次
     */
    const SPIN_INTERVAL_SLOW = 1000;

    /**
     * Redis cluster config
     * @var array
     */
    protected $config;

    protected $redis;

    protected $tickets = [];

    private function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 获取一个分布式锁实例
     * @param array $redisClusterMasterConfig Redis master集群配置数组（多个会根据key进行hash使用，如有热key，可自行在key上做文章）
     * @return Lock
     */
    public static function getInstance($redisClusterMasterConfig)
    {
        static $instance = [];
        $k = md5(json_encode($redisClusterMasterConfig));
        isset($instance[$k]) || $instance[$k] = new self($redisClusterMasterConfig);
        return $instance[$k];
    }

    /**
     * 加锁
     * @param string $key 锁标识，同一个标识使用同一个锁
     * @param int $expire 过期时间，单位秒
     * @return bool
     */
    public function lock($key, $expire)
    {
        if ($expire <= 0) {
            return false;
        }

        try {
            $ticket = uniqid('lock_ticket_' . $key . '_', true);
            $redis = $this->getRedis($key);
            $r = $redis->set($key, $ticket, array("nx", "ex" => $expire));
            if ($r === true) {
                $this->tickets[$ticket] = $key;
                return $ticket;
            }
        } catch (\Exception $e) {
        }
        return false;
    }

    /**
     * 解锁
     * @param string $ticket 加锁后得到的ticket
     * @return bool
     */
    public function unlock($ticket)
    {
        if (!isset($this->tickets[$ticket])) {
            return false;
        }
        $key = $this->tickets[$ticket];
        try {
            $redis = $this->getRedis($key);
            if ($redis->get($key) == $ticket) {
                unset($this->tickets[$ticket]);
                return $redis->del($key) !== false;
            }
        } catch (\Exception $e) {
        }
        return false;
    }

    /**
     * 自旋加锁
     * @param string $key 锁标识，同一个标识使用同一个锁
     * @param int $expire 过期时间，单位秒（可以使用小数）
     * @param int $timeout 如果未拿到锁，自旋等待时间
     * @param int $interval 自旋频率，可选值：Lock::SPIN_INTERVAL_FAST, Lock::SPIN_INTERVAL_MEDIUM, Lock::SPIN_INTERVAL_SLOW
     * @return bool
     */
    public function spinlock($key, $expire, $timeout, $interval = self::SPIN_INTERVAL_MEDIUM)
    {
        if ($timeout <= 0) {
            return false;
        }

        if (!in_array($interval, [self::SPIN_INTERVAL_FAST, self::SPIN_INTERVAL_MEDIUM, self::SPIN_INTERVAL_SLOW])) {
            return false;
        }

        $i = 0;
        do {
            $ticket = $this->lock($key, $expire);
            if ($ticket !== false) {
                return $ticket;
            }
            if ($i * $interval >= $timeout * 1000) {
                break;
            }
            usleep($interval * 1000);
            ++$i;
        } while (true);

        return false;
    }

    protected function getRedis($key)
    {
        static $redis = [];
        (!isset($redis[$key]) || !$redis[$key]) && $redis[$key] = RealRedisProxy::getShardingInstance($this->config, $key);
        return $redis[$key];
    }
}