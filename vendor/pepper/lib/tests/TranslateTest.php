<?php
require_once __DIR__ . "/../vendor/autoload.php";

use Pepper\Lib\Translate;

class TranslateTest extends \PHPUnit\Framework\TestCase {
    public function testSingleton(){
        $this->assertTrue(Translate::initSingleton(__DIR__ . "/resources/lang", "zh_cn", "en"), "initSingleton returns not true");
        $this->assertFalse(Translate::initSingleton(__DIR__ . "/resources/lang", "zh_cn", "en"), "initSingleton call twice returns not false");
        $this->assertEquals("接受", __("live/link.accept"));
    }

    /**
     * @depends testSingleton
     */
    public function testTrans(){
        $this->assertEquals("accept", __("live/link.accept", "en"));
        $this->assertTrue(Translate::getInstance()->setThrowException(), "default throw is not true");
        Translate::getInstance()->setThrowException(false);
        $this->assertEquals("live/link.accept1", __("live/link.accept1", "en"));
        $this->assertEquals("操作成功", __("common.err_0"));
        $this->assertEquals("failed", __("common.err_1"));
        $this->assertEquals("嘉宾准备好了", __("live/link.guest.ready"));
        $this->assertEquals("guest is ready", __("live/link.guest.ready", "en"));
    }

    public function testMultiTrans(){
        $tr1 = new Translate(__DIR__ . "/resources/lang", "zh_cn", "en");
        $tr2 = new Translate(__DIR__ . "/resources/lang", "en", "en");
        $this->assertEquals("操作成功", $tr1->get("common.err_0"));
        $this->assertEquals("success", $tr2->get("common.err_0"));
    }
}
