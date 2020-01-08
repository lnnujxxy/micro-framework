<?php
namespace Pepper\Process\Base;

use Pepper\Lib\SimpleConfig;
use const Pepper\Process\QUEUE_TYPE_DELAY;
use const Pepper\Process\QUEUE_TYPE_NORMAL;
use const Pepper\Process\QUEUE_TYPE_PRIORITY;
use const Pepper\Process\QUEUE_TYPE_ROUTE;


class Queue
{
    private $_product = "";
    private $_config = array();

    private $priorityLua = <<<'LUA'
local val = redis.call('zrevrangebyscore', KEYS[1], '+inf', '-inf', 'LIMIT', '0', '1')
if(val[1] ~= nil) then
    redis.call('zrem', KEYS[1], 0, val[1])
end
return val[1]
LUA;

    private $delayLua = <<<'LUA'
local val = redis.call('zrangebyscore', KEYS[1], '-inf', KEYS[2], 'LIMIT', '0', '1')
if(val[1] ~= nil) then
    redis.call('zrem', KEYS[1], 0, val[1])
end
return val[1]
LUA;

    public function __construct($product)
    {
        $this->_product = $product;
        $this->_config = SimpleConfig::get('QUEUE_CONF','process');
    }

    private function getRedis($name)
    {
        static $redises;
        $config_key = 0;
        if (!isset($this->_config[$this->_product]) || empty($this->_config[$this->_product])) {
            throw new QueueException("product not support", QueueException::CODE_PRODUCT_NOT_SUPPORT);
        }

        if (!isset($this->_config[$this->_product]["queue"][$name])) {
            throw new QueueException("queue name not support", QueueException::CODE_QUEUE_NAME_INVALID);
        }

        try {
            $serverid = $this->_config[$this->_product]["queue"][$name]["server"];

            $config = $this->_config[$this->_product]["servers"][$serverid];
            if (!$config || !is_array($config) || empty($config)) {
                throw new QueueException("server not configuration", QueueException::CODE_SERVER_NOT_CONFIGURATION);
            }

            if (count($config) > 1) {
                $config_key = array_rand($config);
            } else {
                $config_key = 0;
            }

            if (isset($redises[$serverid . "_" . $config_key]) && $redises[$serverid . "_" . $config_key] instanceof \Redis && $redises[$serverid . "_" . $config_key]->ping() == "+PONG") {
                return $redises[$serverid . "_" . $config_key];
            }
        } catch (\Exception $e) {
            ;
        }

        $this_config = $config[$config_key];

        $redises[$serverid . "_" . $config_key] = new \Redis();
        $redises[$serverid . "_" . $config_key]->connect($this_config["host"], $this_config["port"], $this_config["timeout"]);
        $redises[$serverid . "_" . $config_key]->auth($this_config["password"]);

        return $redises[$serverid . "_" . $config_key];
    }

    public function isAlive()
    {
        foreach ($this->_config[$this->_product]["servers"] as $config_arr) {
            foreach ($config_arr as $config) {
                $redis = new \Redis();
                $redis->connect($config["host"], $config["port"], $config["timeout"]);
                $redis->auth($config["password"]);

                if ($redis->ping() != "+PONG") {
                    return false;
                }

                $redis->close();
            }
        }

        return true;
    }

    public function getTraceId()
    {
        $arr = gettimeofday();
        $utime = $arr['sec'] * 1000000 + $arr['usec'];

        return (crc32(gethostname()) & 0x7FFFFFF | 0x8000000) . ($utime & 0x7FFFFFF | 0x8000000);
    }

    public function getKey($name)
    {
        return $this->_product . "_" . $name;
    }

    public function getType($name)
    {
        return $this->_config[$this->_product]["queue"][$name]["type"];
    }

    public static function getInstance($product)
    {
        static $queue;

        if ($queue == null || !($queue instanceof Queue)) {
            $queue = new Queue($product);
        }

        return $queue;
    }

    public function addRouteQueue($name, $data, $rank)
    {
        $queues = $this->_config[$this->_product]["queue"][$name]["queues"];

        foreach ($queues as $name) {
            switch ($this->getType($name)) {
                case QUEUE_TYPE_NORMAL:
                    $this->addNormalQueue($name, $data);
                    break;
                case QUEUE_TYPE_PRIORITY:
                    $this->addPriorityQueue($name, $data, $rank);
                    break;
                case QUEUE_TYPE_DELAY:
                    $this->addDelayQueue($name, $data, $rank);
                    break;
            }
        }

        return true;
    }

    public function addPriorityQueue($name, $data, $rank)
    {
        $redis = $this->getRedis($name);
        $key   = $this->getKey($name);
        return $redis->zadd($key, $rank, $data);
    }

    public function getPriorityQueue($name)
    {
        $redis = $this->getRedis($name);
        $key   = $this->getKey($name);

        $value = $redis->eval($this->priorityLua, [$key], 1);
        return $value;
    }

    public function addDelayQueue($name, $data, $rank)
    {
        // 兼容延时时间格式处理
        if ($rank < 86400) {
            $score = time() + $rank;
        } else {
            $score = $rank;            
        }

        $redis = $this->getRedis($name);
        $key   = $this->getKey($name);
        return $redis->zadd($key, $score, $data);
    }

    public function getDelayQueue($name)
    {
        $redis = $this->getRedis($name);
        $key   = $this->getKey($name);
        $value = $redis->eval($this->delayLua, [$key, time()], 2);
        return $value;
    }

    public function addNormalQueue($name, $data)
    {
        $redis = $this->getRedis($name);
        $key = $this->getKey($name);
        return $redis->lPush($key, $data);
    }

    public function getNormalQueue($name)
    {
        $redis = $this->getRedis($name);
        $key = $this->getKey($name);
        $data = $redis->rpop($key);

        return $data;
    }

    public function addRescueQueue($name, $data)
    {
        $redis = $this->getRedis($name);
        $key = $this->getKey($name);

        $bak_key = $key . "_bak" . date("ymd");
        $redis->lPush($bak_key, json_encode($data));
        $redis->expire($bak_key, 172800);

        return true;
    }

    public function addQueue($name, $params, $rank = 0)
    {
        $traceid = $this->getTraceId();
        $ret = 0;
        $message = array(
            "traceid" => $traceid,
            "addtime" => time(),
            "retry" => 0,
            "rank" => $rank,
            "params" => $params
        );
        $data = json_encode($message);

        switch ($this->getType($name)) {
            case QUEUE_TYPE_NORMAL:
                $ret = $this->addNormalQueue($name, $data);
                break;
            case QUEUE_TYPE_PRIORITY:
                $ret = $this->addPriorityQueue($name, $data, $rank);
                break;
            case QUEUE_TYPE_DELAY:
                $ret = $this->addDelayQueue($name, $data, $rank);
                break;
            case QUEUE_TYPE_ROUTE:
                $ret = $this->addRouteQueue($name, $data, $rank);
                break;
            default:
                break;
        }

        return $ret ? $traceid : 0;
    }

    public function getQueue($name)
    {
        switch ($this->getType($name)) {
            case QUEUE_TYPE_NORMAL:
                $data = $this->getNormalQueue($name);
                break;
            case QUEUE_TYPE_PRIORITY:
                $data = $this->getPriorityQueue($name);
                break;
            case QUEUE_TYPE_DELAY:
                $data = $this->getDelayQueue($name);
                break;
            default:
                break;
        }

        if (!empty($data)) {
            return json_decode($data, true);
        }

        return false;
    }

    private function _resetQueue($name, $params)
    {
        $type = $this->getType($name);
        $params["retry"]++;
        $params["resetime"] = time();
        $data = json_encode($params);

        switch ($type) {
            case QUEUE_TYPE_NORMAL:
                return $this->addNormalQueue($name, $data);
                break;
            case QUEUE_TYPE_PRIORITY:
                return $this->addPriorityQueue($name, $data, $params["rank"]);
                break;
            case QUEUE_TYPE_DELAY:
                return $this->addDelayQueue($name, $data, $params["rank"]);
                break;
            default:
                break;
        }

        return false;
    }

    public function rescueQueue($name, $date)
    {
        $serverid = $this->_config[$this->_product]["queue"][$name]["server"];
        $config_arr = $this->_config[$this->_product]["servers"][$serverid];

        $key = $this->getKey($name);
        $bak_key = $key . "_bak" . $date;

        $retry = $this->_config[$this->_product]["queue"][$name]["retry"];

        foreach ($config_arr as $config) {
            $redis = new \Redis();
            $redis->connect($config["host"], $config["port"], $config["timeout"]);
            $redis->auth($config["password"]);

            print "Host: {$config["host"]} Port: {$config["port"]} \n";

            while (true) {
                $data = $redis->rpop($bak_key);
                print "Rpop Data " . date("Y-m-d H:i:s") . " : " . var_export($data, true) . " \n";
                if (!$data) {
                    break;
                }
                $data = json_decode($data, true);
                print "Unserialize Data" . date("Y-m-d H:i:s") . " : " . var_export($data, true) . " \n";

                if ($data["params"] && ($retry && $data["retry"] < $retry || !$retry)) {
                    return $this->_resetQueue($name, $data);
                }
                usleep(10);
            }

            $redis->close();
        }
    }

    public function getLength($name)
    {
        $redis = $this->getRedis($name);
        $len = 0;
        $key = $this->getKey($name);

        switch ($this->getType($name)) {
            case QUEUE_TYPE_NORMAL:
                $len = $redis->lSize($key);
                break;
            case QUEUE_TYPE_PRIORITY:
            case QUEUE_TYPE_DELAY:
                $len = $redis->zCard($key);
                break;
        }

        return $len ? $len : 0;
    }

    public function getBakLength($name, $date = "")
    {
        $redis = $this->getRedis($name);
        $len = 0;
        $key = $this->getKey($name);
        $date || $date = date("ymd");
        $bak_key = $key . "_bak" . $date;

        switch ($this->getType($name)) {
            case QUEUE_TYPE_NORMAL:
                $len = $redis->lSize($bak_key);
                break;
            case QUEUE_TYPE_PRIORITY:
            case QUEUE_TYPE_DELAY:
                $len = $redis->zCard($bak_key);
                break;
        }

        return $len ? $len : 0;
    }
}

?>