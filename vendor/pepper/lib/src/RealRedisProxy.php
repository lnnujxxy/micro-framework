<?php
namespace Pepper\Lib;


class RealRedisProxy
{
    private $_config = null;
    private $_redis = null;
    private $_rescued = 0;
    private $_needRescue = true; //是否需要补刀

    private static $RESCUE_REDIS_CONF = array(
        array(
            "host" => "154.8.195.226",
            "port" => 3306,
            "timeout" => 3,
            "password" => "test123"
        )
    );


    const KEY_EXPIRE = 15;


    protected function __construct($config = null, $needRescue = true)
    {
        $this->_config = $config;
        $this->_needRescue = $needRescue;
        $this->_redis = $this->_getRedis($config);
    }

    /**
     * @param null $config
     * @param $needRescue bool 是否需要自动挽救，建议对于cache类的最好挽救，存储类型的挽救只能保证前端不出错，有可能有脏数据
     * @param $reconnect bool 为了兼容cachePro, 加入 reconnect
     * @return \Redis 为了 IDE 友好，注释为 return Redis
     */
    public static function getInstance($config = null, $needRescue = true, $reconnect = false)
    {
        static $redis;

        if ($config) {
            $key = md5(serialize($config));
        } else {
            $key = "def";
        }

        if (isset($redis[$key]) && !$reconnect) {
            return $redis[$key];
        }
        $redis[$key] = new self($config, $needRescue);
        return $redis[$key];
    }

    public static function getShardingInstance($config, $shardingKey, $needRescue=true)
    {
        $count = count($config);
        if(!is_array($config) || $count == 0) {
            throw new \Exception("null sharding redis config");
        }
        $idx = abs(crc32($shardingKey)) % $count;
        return self::getInstance($config[$idx], $needRescue);
    }

    private function _getRedis($config = null)
    {
        if (!$config) {
            $config = SimpleConfig::get("REDIS_CONF");
        }

        try {
            $redis = new \Redis();
            $retryInterval = isset($config['retry_interval']) ? $config['retry_interval'] : 800;
            $timeout = isset($config['timeout']) ? $config['timeout'] : 3;
            $redis->connect($config["host"], $config["port"], $timeout, null, $retryInterval);
            $redis->auth($config["password"]);
        } catch (\RedisException $e) {
            $msg = $e->getMessage();
            $configJson = json_encode($config);

            Logger::warning("redis-exception-msg: {$msg}, redis-config: {$configJson}");
            if ($this->_needRescue) {
                $redis = $this->rescue('connect', array($config["host"], $config["port"], $config['timeout'] ? $config['timeout'] : 3));
            } else {
                throw new \Exception(ERROR_SYS_REDIS);
            }
        }

        return $redis;
    }

    public function __call($name, $arguments)
    {
        try {

            return call_user_func_array(array($this->_redis, $name), $arguments);
        } catch (\RedisException $e) {
            $msg = $e->getMessage();
            $configJson = json_encode($this->_config);
            Logger::warning("redis-exception-msg: {$msg}, redis-config: {$configJson}");
            if ($this->_needRescue) {
                $this->rescue($name, $arguments);
            } else {
                throw new \Exception(ERROR_SYS_REDIS);
            }
        }
    }

    public function sScan($key, &$it, $pattern="", $count=0)
    {
        return $this->_redis->sScan($key, $it, $pattern, $count);
    }

    public function scan(&$it, $pattern="", $count=0)
    {
        return $this->_redis->scan($it, $pattern, $count);
    }

    public function zScan($key, &$it, $pattern="", $count=0)
    {
        return $this->_redis->zScan($key, $it, $pattern, $count);
    }

    public function hScan($key, &$it, $pattern="", $count=0)
    {
        return $this->_redis->hScan($key, $it, $pattern, $count);
    }

    public function __destruct()
    {
        $this->_redis = null;
    }

    private function rescue($name, $arguments)
    {
        if ($this->_rescued > 0) {
            //顶多挽救一次
            Logger::warning('rescue redis down, conf: ' . json_encode($this->_config));
            throw new \Exception('rescue redis down');
        }

        $newConf = $this->getRescueConf();
        $redis = $this->_getRedis($newConf);
        $this->_rescued++;
        $this->_config = $newConf;
        $this->_redis = $redis;
        Logger::warning('redis rescue conf:' . json_encode($newConf) . " redis call : {$name}");
        if (!in_array($name, array('connect', 'auth'))) {
            $ret = call_user_func_array(array($this->_redis, $name), $arguments);
            $this->_redis->expire($arguments[0], self::KEY_EXPIRE); //挽救的结果 15s 有效
            return $ret;
        } else {
            return $redis;
        }
    }

    private function getRescueConf()
    {
        $conf = self::$RESCUE_REDIS_CONF;
        $count = count($conf);
        $configStr = serialize($this->_config);
        if ($count > 0) {
            return $conf[abs(crc32($configStr)) % $count];
        } else {
            throw new \Exception(ERROR_SYS_REDIS);
        }
    }
}
