<?php
/**
 * bak队列重跑脚本
 * Usage：php script.php job_name [date:070310]
 */
ini_set('memory_limit','3072M');
date_default_timezone_set('Asia/Harbin');

require_once dirname(__DIR__) . '/vendor/autoload.php';
use Pepper\Lib\SimpleConfig;
use Pepper\Process\ProcessClient;

$job = $argv[1];
$date = isset($argv[2]) ? $argv[2] : date('ymd');
SimpleConfig::loadConfigVarsFile(dirname(__DIR__) . '/config/process_conf.php.test');
try{
    $product = "xxxxx";
    $process = ProcessClient::getInstance($product);
    $process->rescue($job, $date);
} catch (Exception $e) {
    var_dump($e);
}
