<?php
/**
 * Created by PhpStorm.
 * User: wangtonghe-hj
 * Date: 2017/10/12
 * Time: 15:23
 */

use PHPUnit\Framework\TestCase;
use Pepper\Process\ProcessClient;


class getTaskCountTest extends TestCase
{
    function testAddTask()
    {
        $num = 50;
        $product = "test";
        $job1 = "test_job3";
        $job2 = "test_job4";
        $process = ProcessClient::getInstance($product);
        for ($i = 0; $i < $num; $i++) {
            $job1_traceid = $process->addTask($job1, array("time" => time() . '-' . rand(0, 9999)));
            $job2_traceid = $process->addTask($job2, array("time" => time() . '-' . rand(0, 9999)));
            self::assertNotEmpty($job1_traceid);
            self::assertNotEmpty($job2_traceid);
        }
    }
    /**
     * @depends testAddTask
     */
    function testGetTaskCount()
    {

        $product = "test";
        $job1 = "test_job3";
        $process = ProcessClient::getInstance($product);
        $len = $process->getTaskCount($job1);
        self::assertNotEmpty($len);
    }

    function testIsAlive()
    {
        $product = "test";
        $job1 = "test_job3";
        $process = ProcessClient::getInstance($product);
        $len = $process->isAlive($job1);
        self::assertTrue($len);
    }
}