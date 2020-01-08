<?php

class CachedTest extends \PHPUnit\Framework\TestCase
{
    use \Pepper\Lib\Cached;
    protected $callbackTimes = 0;
    protected static $val = null;

    function testSessionCache()
    {
        $var = 1;
        $key = 'a';

        // 方法级缓存可以正常拿到结果
        $result = $this->_getVal4TestSessionCache($key, $var);
        $this->assertEquals($var, $result);
        $this->assertEquals(1, $this->callbackTimes);

        // 方法级缓存可以正常拿到结果
        $result = $this->_getVal4TestSessionCache($key, $var);
        $this->assertEquals($var, $result);
        $this->assertEquals(1, $this->callbackTimes);

        // 模拟过期，可以再次调用获取值函数，拿到正确结果（每次调用_getKey4TestCore，返回的值都会+1）
        sleep(3);
        $result = $this->_getVal4TestSessionCache($key, $var);
        $this->assertEquals($var + 2, $result);
        $this->assertEquals(2, $this->callbackTimes);

        // 模拟过期，可以再次调用获取值函数，拿到正确结果（每次调用_getKey4TestCore，返回的值都会+1）
        sleep(3);
        $result = $this->_getVal4TestSessionCache($key, $var);
        $this->assertEquals($var + 3, $result);
        $this->assertEquals(3, $this->callbackTimes);
    }

    private function _getVal4TestSessionCache($key, $var, $expire = 2, $expireOnFailed = 1)
    {
        $val = self::$val;
        $r = self::sessionCached($key, function () use ($val, $var) {
            ++$this->callbackTimes;
            if ($val === null) {
                return $var;
            }
            return $var + $val;
        }, $expire, $expireOnFailed);
        ++self::$val;
        return $r;
    }


    function testRemoteCache()
    {
        $key = 'b';
        $var = 1;
        $expire = 2;
        $expireOnFailed = 1;
        $this->callbackTimes = 0;
        self::$val = null;

        $r = $this->_getVal4TestRemoteCache($key, $var, new TestCache(), $expire, $expireOnFailed);
        $this->assertEquals($var, $r);
        $this->assertEquals(1, $this->callbackTimes);

        $r = $this->_getVal4TestRemoteCache($key, $var, new TestCache(), $expire, $expireOnFailed);
        $this->assertEquals($var, $r);
        $this->assertEquals(1, $this->callbackTimes);

        sleep(3);

        $r = $this->_getVal4TestRemoteCache($key, $var, new TestCache(), $expire, $expireOnFailed);
        $this->assertEquals($var + 2, $r);
        $this->assertEquals(2, $this->callbackTimes);

        sleep(3);

        $r = $this->_getVal4TestRemoteCache($key, $var, new TestCache(), $expire, $expireOnFailed);
        $this->assertEquals($var + 3, $r);
        $this->assertEquals(3, $this->callbackTimes);
    }

    function testRemoteCacheUseExpiredData(){
        $key = 'c';
        $var = 1;
        $expire = 2;
        $expireOnFailed = 1;
        $this->callbackTimes = 0;
        self::$val = null;

        $r = $this->_getVal4TestRemoteCache($key, $var, new TestCache(), $expire, $expireOnFailed, true);
        $this->assertEquals($var, $r);
        $this->assertEquals(1, $this->callbackTimes);

        $r = $this->_getVal4TestRemoteCache($key, $var, new TestCache(), $expire, $expireOnFailed, true);
        $this->assertEquals($var, $r);
        $this->assertEquals(1, $this->callbackTimes);

        sleep(3);

        $r = $this->_getVal4TestRemoteCache($key, $var, new TestCache(), $expire, $expireOnFailed, true);
        $this->assertEquals($var + 2, $r);
        $this->assertEquals(2, $this->callbackTimes);

        sleep(3);

        $r = $this->_getVal4TestRemoteCache($key, $var, new TestCache(), $expire, $expireOnFailed, true);
        $this->assertEquals($var + 3, $r);
        $this->assertEquals(3, $this->callbackTimes);
    }

    private function _getVal4TestRemoteCache($key, $var, \Pepper\Lib\ICache $cache, $expire = 2, $expireOnFailed = 1, $useExpiredData = false)
    {
        $val = self::$val;
        $r = self::remoteCached($key, function () use ($val, $var) {
            ++$this->callbackTimes;
            if ($val === null) {
                return $var;
            }
            return $var + $val;
        }, $cache, $expire, $expireOnFailed, $useExpiredData);
        ++self::$val;
        return $r;
    }

}

class TestCache implements \Pepper\Lib\ICache
{

    protected static $cache = [];

    public function get($key)
    {
        $r = isset(self::$cache[$key]) ? self::$cache[$key] : null;
        if (!$r){
            return false;
        }
        if ($r['expire'] < time()){
            return false;
        }
        return $r['val'];
    }

    public function set($key, $val, $expire)
    {
        self::$cache[$key] = ['val' => $val, 'expire' => time() + $expire];
        return true;
    }

    public function getpro($key, &$expiredData)
    {
        $key .= 'pro';
        $r = isset(self::$cache[$key]) ? self::$cache[$key] : null;
        if (!$r){
            return false;
        }
        if ($r['expire'] < time()){
            $expiredData = $r['val'];
            return false;
        }
        return $r['val'];
    }

    public function setpro($key, $val, $expire)
    {
        $key .= 'pro';
        self::$cache[$key] = ['val' => $val, 'expire' => time() + $expire];
        return true;
    }
}

