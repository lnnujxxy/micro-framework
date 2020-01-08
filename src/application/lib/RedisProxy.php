<?php
namespace Pepper\Framework\Lib;

use Pepper\Lib\SimpleConfig;

class RedisProxy
{
    private $_redis = null;

    public function __construct($config = null)
    {
        $this->_redis = $this->getRedis($config);
    }

    /**
     * @param null $config
     * @return \Redis
     * @throws
     */
    public static function getInstance($config = null)
    {
        static $redis;

        if ($config) {
            $key = md5(serialize($config));
        } else {
            $key = 'def';
        }

        try {
            if (isset($redis[$key]) && $redis[$key] instanceof \Redis && $redis[$key]->ping() == '+PONG') {
                return $redis[$key];
            }
        } catch (\RuntimeException $e) {
        }
        $redis[$key] = new self($config);

        return $redis[$key];
    }

    private function getRedis($config = null)
    { 
        if (!$config) {
            $config = SimpleConfig::get('REDIS_CONF')[0];
        }
        try {
            $redis = new \Redis();
            $redis->connect($config['host'], $config['port'], $config['timeout'] ? $config['timeout'] : 3);
            if ($config['password']) {
                $redis->auth($config['password']);
            }
        } catch (\RuntimeException $e) {
            Logger::fatal($e->getMessage(), array('host' => $config['host'], 'port' => $config['port']), $e->getCode());
            throw new BizException(ERROR_SYS_REDIS);
        }
        return $redis;
    }

    public function __call($name, $arguments)
    {
        try {
            return call_user_func_array(array($this->_redis, $name), $arguments);
        } catch (\RuntimeException $e) {
            Logger::fatal($e->getMessage(), ['name' => $name, 'arguments' => json_encode($arguments)], $e->getCode());
            throw new BizException(ERROR_SYS_REDIS);
        }
    }

    public function __destruct()
    {
        $this->_redis = null;
    }
}
