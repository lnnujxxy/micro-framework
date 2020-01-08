<?php
namespace Pepper\Lib;

class Cache
{

    private static $_cache = array();
    private $masterConfKey = null;
    private $slaveConfKey = null;

    const DEFAULT_EXPIRE = 86400;

    /**
     * @return Cache
     */
    public static function getInstance($masterConfKey, $slaveConfKey)
    {
        $key = md5(json_encode($masterConfKey) . json_encode($slaveConfKey));
        if(!isset(self::$_cache[$key])) {
            self::$_cache[$key] = new self($masterConfKey, $slaveConfKey);
        }
        return self::$_cache[$key];
    }

    protected function __construct($masterConfKey, $slaveConfKey)
    {
        $this->masterConfKey = $masterConfKey;
        $this->slaveConfKey = $slaveConfKey;
    }


    private function getCacheConf($key, $conf)
    {
        $hash_key = sprintf("%u", crc32($key));
        $order = intval(fmod($hash_key, count($conf)));
        return $conf[$order];
    }

    // 获取key对应的配置索引
    private function getCacheConfIndex($key, $master){
        $confKey = $master ? $this->masterConfKey : $this->slaveConfKey;
        $conf = SimpleConfig::get($confKey);
        $hash_key = sprintf("%u", crc32($key));
        $order = intval(fmod($hash_key, count($conf)));
        return $order;
    }

    public function set($key, $value, $flag = 0, $expire = 0)
    {
        $redis = $this->getRedis($key, true);
        $set_value = array(
            "value" => $value,
        );
        $expire && $set_value['time'] = time() + $expire;
        $set_value = serialize($set_value);
        return $expire > 0 ? $redis->setex($key, $expire, $set_value) : $redis->set($key, $set_value);
    }

    public function get($key)
    {
        $redis = $this->getRedis($key, false);
        $value = $redis->get($key);
        if (!$value){
            return false;
        }
        $get_value = @unserialize($value);
        if (!$get_value){
            return false;
        }
        if (isset($get_value['time']) && $get_value["time"] < time()){
            return false;
        }
        return $get_value['value'];
    }

    public function mget($keys)
    {
        $group = array();
        foreach($keys as $key) {
            $index = $this->getCacheConfIndex($key, false);
            if (empty($group[$index])) {
                $group[$index] = array(
                    "redis" => $this->getRedis($key, false),
                    "keys" => array($key),
                );
            }else{
                $group[$index]["keys"][] = $key;
            }
        }
        $result = array();
        foreach($group as $v){
            $values = $v["redis"]->mget($v["keys"]);
            foreach($v["keys"] as $i => $key){
                if (!$values[$i]){
                    $result[$key] = false;
                    continue;
                }
                $get_value = @unserialize($values[$i]);
                if (!$get_value){
                    $result[$key] = false;
                    continue;
                }
                if (isset($get_value['time']) && $get_value["time"] < time()){
                    $result[$key] = false;
                    continue;
                }
                $result[$key] = $get_value['value'];
            }
        }
        return $result;
    }

    public function del($key)
    {
        $redis = $this->getRedis($key, true);
        return $redis->del($key);
    }

    private function getRedis($key, $master = true)
    {
        $confKey = $master ? $this->masterConfKey : $this->slaveConfKey;
        return RealRedisProxy::getInstance($this->getCacheConf($key, SimpleConfig::get($confKey)));
    }

    public function delete($key)
    {
        return $this->del($key);
    }

    /**
     * @param $key string 锁定key
     * @param $random int 锁定值随机数
     * @param int $ttl 锁超时
     * @param null $config 锁定 redis 配置
     * @return bool 是否锁成功
     */
    public function lock($key, $random, $ttl = 3, $config = null)
    {
        if (!$config) {
            $config = SimpleConfig::get("REDIS_CONF");
        }
        $redis = RealRedisProxy::getInstance($config);
        return $redis->set($key, $random, array("nx", "ex" => $ttl));
    }

    public function unlock($key, $random, $config = null)
    {
        if (!$config) {
            $config = SimpleConfig::get("REDIS_CONF");
        }

        $redis = RealRedisProxy::getInstance($config);
        if ($redis->get($key) == $random) {
            $redis->del($key);
        }
    }

    /**
     * setPro ，拿锁更新缓存
     * @param $key
     * @param $data
     * @param $expire
     * @return bool
     */
    public function setWithFakeExpire($key, $data, $expire)
    {
        $key = $this->getFakeExpireKey($key);
        $handler = $this->getRedis($key, true);
        $result = $handler->setex($key, self::DEFAULT_EXPIRE, serialize(array('data' => $data,'expire' => $expire,'time' => time())));
        $handler->setex($this->getFakeExpireLockKey($key), self::DEFAULT_EXPIRE, 0);
        return $result;
    }

    /**
     * getPro 拿锁更新缓存
     * @param $key
     * @param null $expiredData
     * @return bool|mixed
     */
    public function getWithFakeExpire($key, &$expiredData = null)
    {
        $key = $this->getFakeExpireKey($key);
        $handler = $this->getRedis($key, false);
        $masterRedis = $this->getRedis($key);
        $result = $handler->get($key);

        // 没命中缓存
        if (!$result){
            return false;
        }

        $result = unserialize($result);
        if (!$result || !is_array($result)){
            return false;
        }

        if (time() - $result['time'] > $result['expire']){
            // 数据过期，拿锁更新数据
            if ($masterRedis->incr($this->getFakeExpireLockKey($key)) == 1){
                $expiredData = $result['data'];
                return false;
            }
            // 30s timeout reset lock
            if (time() - $result['time'] > 30){
                $masterRedis->set($this->getFakeExpireLockKey($key), 0);
            }
        }
        return $result['data'];
    }

    public function delWithFakeExpire($key)
    {
        $key = $this->getFakeExpireKey($key);
        $handler = $this->getRedis($key, true);
        return $handler->del($key);
    }

    private function  getFakeExpireKey($key)
    {
        return $key . '_pro';
    }

    private function getFakeExpireLockKey($key)
    {
        return $key . "_lock";
    }
}
