<?php

use Pepper\Lib\ES;

class ESTest extends \PHPUnit\Framework\TestCase
{
    function testES() {
        $host = \Pepper\Lib\SimpleConfig::get("TEST_ES_HOSTS");
        $index = "test";
        $type = "test";
        $id = mt_rand(1, 100000000);
        $body = array("test" => 1);
        $es = ES::getInstance($host);
        $es->add($index, $type, $id, $body);
        $value = $es->get($index, $type, $id);
        $this->assertTrue($value["found"]);
        $value = $es->search($index, $type, ["query"=>["term" => ["_id" => $id]]]);
        $this->assertTrue(count($value["hits"]["hits"]) > 0);
        $es->delete($index, $type, $id);
    }
}