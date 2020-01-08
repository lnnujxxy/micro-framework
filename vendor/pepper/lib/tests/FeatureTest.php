<?php
/**
 * Created by PhpStorm.
 * User: wangchenglong
 * Date: 2017/7/7
 * Time: 20:41
 */

use Pepper\Lib\Feature;
use PHPUnit\Framework\TestCase;

class FeatureTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        \Pepper\Lib\SimpleConfig::loadConfigVarsFile(__DIR__ . '/config/test.conf.php');
    }

    public function testDummyFeature(){
        $this->assertFalse(Feature::support('dummy_feature'));
    }

    public function testSupport(){
        $this->assertTrue(Feature::support('feedInfoV2', 'ios', '3.8.5'));
    }

    public function testNotSupport(){
        $this->assertFalse(Feature::support('feedInfoV2', 'ios', '3.8.4'));
    }

    public function testSupportOtherApp(){
        $this->assertTrue(Feature::support('category', 'ios', '1.0', 'game'));
    }

}
