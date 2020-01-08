<?php

use Pepper\Process\ProcessClient;
use PHPUnit\Framework\TestCase;

require_once "TestWorker.php";

/**
 * Class rescueTest
 * 该test依赖于worker，需要手动执行worker
 */
class rescueTest extends TestCase
{
    function testAddTask()
    {
        $num = 3;
        $product = "test";
        $job1 = "test_job3";
        $process = ProcessClient::getInstance($product);
        for ($i = 0; $i < $num; $i++) {
            $job1_traceid = $process->addTask($job1, array("time" => time() . '-' . rand(0, 9999)));
            self::assertNotEmpty($job1_traceid);
        }
    }

//    /**
//     * @depends testAddTask
//     */
//    public function testAddWorker()
//    {
//        $worker = array("TestWorker", "execute3");
//        $param = "test";
//        if ($worker) {
//            $method = new \ReflectionMethod($worker[0], $worker[1]);
//            if (!$method->isStatic()) {
//                $class = new \ReflectionClass($worker[0]);
//                $worker[0] = $class->newInstance();
//            }
//        }
//
//        call_user_func($worker, $param);
//    }

    /**
     * @depends testAddTask
     */
    function testGetBakCount()
    {
        $product = "test";
        $job1 = "test_job3";
        $date = date("ymd");
        $process = ProcessClient::getInstance($product);
        $len = $process->getTaskBakCount($job1, $date);
        self::assertNotEmpty($len);
    }


    function testRescue()
    {
        $product = "test";
        $job1 = "test_job3";
        $date = date("ymd");
        $process = ProcessClient::getInstance($product);
        $traceid1 = $process->rescue($job1, $date);
        self::assertNotEmpty($traceid1);
    }
}