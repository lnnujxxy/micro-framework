<?php

use PHPUnit\Framework\TestCase;

class priorityTest extends TestCase
{
    function testPriority()
    {
        \Pepper\Lib\SimpleConfig::loadStandardConfigFile(dirname(__DIR__) . '/config', 'process', 'process_conf.php', 'test');

        $product = "test";
        $job = "test_job4";
        $queue = new \Pepper\Process\Base\Queue($product);

        for ($i = 0; $i <= 5; $i++) {
            $value = 5 - $i;
            $queue->addPriorityQueue($job, $value, $i);
        }

        for ($j = 0; $j < 5; $j ++) {
            $data[$j] =  $queue->getPriorityQueue($job);
        }

        $asc = function($arr) {
            $isAsc = true;
            foreach ($arr as $key => $value) {
                if (isset($arr[$key + 1]) && $arr[$key] > $arr[$key + 1]) {
                    $isAsc = false;
                }
            }
            return $isAsc;
        };

        $this->assertTrue($asc($data));
    }
}