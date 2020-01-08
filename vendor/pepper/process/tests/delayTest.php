<?php

use PHPUnit\Framework\TestCase;

class delayTest extends TestCase
{
    function testDelay()
    {
        \Pepper\Lib\SimpleConfig::loadStandardConfigFile(dirname(__DIR__) . '/config', 'process', 'process_conf.php', 'test');

        $product = "test";
        $job = "test_job3";
        $queue = new \Pepper\Process\Base\Queue($product);
        $value = "test";
        $queue->addDelayQueue($job, $value, 1);
        $this->assertFalse($queue->getDelayQueue($job));
        sleep(2);
        $this->assertTrue($queue->getDelayQueue($job) == $value);
    }
}