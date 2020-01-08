<?php

class CurlTest extends \PHPUnit\Framework\TestCase
{
    function testGet()
    {
        $url = 'http://baidu.com/';
        $content = \Pepper\Lib\Curl::get($url);
        $this->assertTrue(strlen($content) > 5, 'content length too short');
        $info = \Pepper\Lib\Curl::getInfo();
        $this->assertArrayHasKey('url', $info);
        $this->assertEquals($url, $info['url']);
        $this->assertEquals(200, $info['http_code']);

        $error = \Pepper\Lib\Curl::getLastError();
        $errno = \Pepper\Lib\Curl::getLastErrno();
        $this->assertEquals('', $error);
        $this->assertEquals(0, $errno);
    }

    function testKeepOriUrl(){
        $url = 'http://baidu.com/?a=12+;&3';
        \Pepper\Lib\Curl::keepOrigUrl(true);
        $content = \Pepper\Lib\Curl::get($url);
        $this->assertTrue(strlen($content) > 5, 'content length too short');
        $url2 = \Pepper\Lib\Curl::getLastUrl();
        $info = \Pepper\Lib\Curl::getInfo();
        $this->assertEquals($url, $info['url']);
        $this->assertEquals($url, $url2);
    }

}