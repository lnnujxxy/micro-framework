<?php

use Pepper\Process\ProcessClient;
use PHPUnit\Runner\Exception;

class TestWorker
{
    public function execute1($v)
    {
        sleep(1);
        ProcessClient::addLog(array("job" => $v));
        ProcessClient::addLog(array("a" => 1));
        ProcessClient::addLog(array("a" => 1));
        ProcessClient::addLog(array("b" => 1));
        ProcessClient::addLog(array("c" => array(1, 1)));
        ProcessClient::addLog("just for test!");
        return true;
    }

    public static function execute2($v)
    {
        sleep(1);
        ProcessClient::addLog(array("job" => $v));
        ProcessClient::addLog(array("a" => 2));
        ProcessClient::addLog(array("a" => 2));
        ProcessClient::addLog(array("b" => 2));
        ProcessClient::addLog(array("c" => array(2, 2)));
        ProcessClient::addLog("just for test!");
        return true;
    }

    public function execute3($v)
    {
        sleep(1);
        ProcessClient::addLog(array("job" => $v));
        ProcessClient::addLog(array("a" => 2));
        ProcessClient::addLog(array("a" => 2));
        ProcessClient::addLog(array("b" => 2));
        ProcessClient::addLog(array("c" => array(2, 2)));
        ProcessClient::addLog("sdsdfsdf");
        throw new Exception("just for test! exception!!!!!!!");

    }
}