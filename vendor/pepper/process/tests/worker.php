<?php
date_default_timezone_set('Asia/Harbin');

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once "TestWorker.php";
use Pepper\Lib\SimpleConfig;
/**
 * 脚本需手动执行，参数1 start|stop|restart
 */
use Pepper\Process\ProcessClient;
try{
    $product = "test";
    $job1 = "test_job3";
    $job2 = "test_job4";
    $max_thread = 3;
    $max_task   = 1000;
    SimpleConfig::loadConfigVarsFile(dirname(__DIR__) . '/config/process_conf.php.test');
    $worker1 = array("TestWorker", "execute1");
    $worker2 = array("TestWorker", "execute2");
    $worker3 = array("TestWorker", "execute3");
    $process = ProcessClient::getInstance($product);
    $process->addWorker($job1, $worker1, $max_thread, $max_task);
    $process->addWorker($job2, $worker2, $max_thread, $max_task);
    $process->addWorker($job1, $worker3, 1, $max_task);
    $process->run();
} catch (Exception $e) {

}