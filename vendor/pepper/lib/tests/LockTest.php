<?php

use PHPUnit\Framework\TestCase;

class LockTest extends TestCase
{
    /**
     * @var \Pepper\Lib\Lock
     */
    protected $lock;

    function setup()
    {
        $config = \Pepper\Lib\SimpleConfig::get('REDIS_CLUSTER_CONF');
        $this->lock = \Pepper\Lib\Lock::getInstance($config);
    }

    function testNormal()
    {
        $key = 'test_lock' . time();
        $ticket1 = $this->lock->lock($key, 3);
        $this->assertNotFalse($ticket1, 'get key lock failed');

        $result = $this->lock->lock($key, 3);
        $this->assertFalse($result, 'repeat locked key');

        $key2 = 'test_lock2' . time();
        $ticket2 = $this->lock->lock($key2, 3);
        $this->assertNotFalse($ticket2, 'get key2 lock failed');

        $result = $this->lock->lock($key2, 3);
        $this->assertFalse($result, 'repeat locked key2');

        $result = $this->lock->unlock($ticket1);
        $this->assertTrue($result, 'unlock key failed');

        $result = $this->lock->unlock($ticket2);
        $this->assertTrue($result, 'unlock key2 failed');
    }

    function testExpired()
    {
        $key = 'test_lock_expired' . time();
        $result = $this->lock->lock($key, 1);
        $this->assertNotFalse($result, 'get key lock failed');

        sleep(1);
        $result = $this->lock->lock($key, 1);
        $this->assertNotFalse($result, 'get key lock failed after expired');
    }

    function testSpinlock()
    {
        $key = 'test_lock_spinlock' . time();
        $ticket1 = $this->lock->lock($key, 1);
        $this->assertNotFalse($ticket1, 'get key lock failed');

        $time = microtime(true);
        $ticket2 = $this->lock->spinlock($key, 1, 2);
        $over = microtime(true);

        $this->assertNotFalse($ticket2, 'get spinlock failed');
        $this->assertTrue($over - $time > 1, 'spinlock cost lt 1s');
        $this->assertTrue($over - $time < 2, 'spinlock cost gt 2s');

        $result = $this->lock->unlock($ticket2);
        $this->assertTrue($result, 'unlock failed');

    }

    function testTimeoutSpinlock()
    {
        $key = 'test_lock_spinlock_timeout' . time();
        $ticket1 = $this->lock->lock($key, 30);
        $this->assertNotFalse($ticket1, 'get key lock failed');
        $time = microtime(true);
        $result = $this->lock->spinlock($key, 1, 2);
        $over = microtime(true);

        $this->assertFalse($result, 'get spinlock failed');
        $this->assertTrue($over - $time > 2, 'spinlock cost lt 2s');
        $this->assertTrue($over - $time < 3, 'spinlock cost gt 3s');

        $result = $this->lock->unlock($ticket1);
        $this->assertTrue($result, 'unlock failed');
    }
}