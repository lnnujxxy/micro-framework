<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/12/16
 * Time: 下午10:42
 */
namespace Pepper\Framework\Traits;
use Pepper\Framework\Lib\RedisProxy;
use Pepper\Lib\SimpleConfig;

trait RedisTrait {
    public static function getRedis($key = "") {
        $config = SimpleConfig::get('REDIS_CONF');
        $count = count($config);
        if (!is_array($config) || $count == 0) {
            throw new \RuntimeException('null sharding redis config');
        }
        $idx = abs(crc32($key)) % $count;
        return RedisProxy::getInstance($config[$idx]);
    }
}