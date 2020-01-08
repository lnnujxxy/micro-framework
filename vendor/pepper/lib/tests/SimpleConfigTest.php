<?php
/**
 * Created by PhpStorm.
 * User: wangchenglong
 * Date: 2017/7/7
 * Time: 20:52
 */

use Pepper\Lib\SimpleConfig;
use PHPUnit\Framework\TestCase;

class SimpleConfigTest extends TestCase
{
    public function testLoadConfig(){
        $this->cleanConfig();
        SimpleConfig::loadConfigVarsFile(__DIR__ . '/config/test.conf.php');
        $this->assertEquals(time(), SimpleConfig::get('FOR_SIMPLECONFIG_TEST'));
        $this->assertNotEmpty(SimpleConfig::getFileConfigs());
        $this->assertEmpty(SimpleConfig::getFileConfigs('no'));
        $this->assertEmpty(SimpleConfig::getFileConfigs(SimpleConfig::NAMESPACE_DEFAULT, 'no'));
    }

    function testQconf(){
        $this->cleanConfig();

        SimpleConfig::loadConfigVarsFile(__DIR__ . '/config/config_for_test_load.php');

        // NOTICE：需要在装有Qconf的服务器上执行测试才能测试出来Qconf的功能
        if (is_file('/home/q/php/Qconf/Qconf.php')){
            $this->assertEquals('test for phpunit', SimpleConfig::get('KEY_FOR_TEST'));
            $this->assertEquals(['a' => 1], SimpleConfig::get('KEY_FOR_TEST_JSON'));
        }else{
            $this->assertEquals('in local file', SimpleConfig::get('KEY_FOR_TEST'));
            $this->assertEquals([1,2,3], SimpleConfig::get('KEY_FOR_TEST_JSON'));
        }
    }

    private function cleanConfig(){
        $reflectClass = new ReflectionClass("\Pepper\Lib\SimpleConfig");
        $configProperty = $reflectClass->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue([]);
    }
}
