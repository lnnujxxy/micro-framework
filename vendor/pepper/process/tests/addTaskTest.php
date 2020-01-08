<?php

use Pepper\Process\ProcessClient;
use PHPUnit\Framework\TestCase;

class addTaskTest extends TestCase
{

    function testAddTask()
    {
        $num = 50;
        $product = "test";
        $job1 = "test_job1";
        $job2 = "test_job2";
        $process = ProcessClient::getInstance($product);
        for ($i = 0; $i < $num; $i++) {
            $job1_traceid = $process->addTask($job1, array("time" => time() . '-' . rand(0, 9999)));
            $job2_traceid = $process->addTask($job2, array("time" => time() . '-' . rand(0, 9999)), 60);
            self::assertNotEmpty($job1_traceid);
            self::assertNotEmpty($job2_traceid);
        }
    }
}