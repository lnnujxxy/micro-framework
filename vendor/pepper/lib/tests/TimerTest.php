<?php

use \Pepper\Lib\Timer;

class TimerTest extends \PHPUnit\Framework\TestCase
{

    public function test1()
    {
        Timer::start(__FUNCTION__, [1,2,3]);
        usleep(10000);
        Timer::end(__FUNCTION__);

        Timer::start('test', ['hehehe']);
        usleep(10000);
        Timer::start('test2', ['he2hehe']);
        usleep(10000);
        Timer::start('xxx');
        usleep(10000);
        Timer::end('xxx');
        usleep(10000);
        Timer::end('test2');
        usleep(10000);
        Timer::tick('tick1');
        usleep(10000);
        Timer::tick('tick2');
        usleep(10000);
        Timer::end('test');
        $result = Timer::result();
        echo $result;

        $this->assertNotFalse(strpos($result, __FUNCTION__));
        $this->assertNotFalse(strpos($result, 'test'));
        $this->assertNotFalse(strpos($result, 'test2'));
        $this->assertNotFalse(strpos($result, 'tick'));
        $this->assertNotFalse(strpos($result, 'tick2'));
        $this->assertNotFalse(strpos($result, 'cost'));
        $this->assertNotFalse(strpos($result, 'memory'));
    }
}