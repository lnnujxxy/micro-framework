<?php
/*
 * Copyright (c) 2010,  新浪网运营部-网络应用开发部
 * All rights reserved.
 * @description:
 * @author: zhouweiwei
 * @date:
 * @version: 1.0
 */
require_once('../simpletest/simpletest.php');
require_once('../simpletest/autorun.php');
require_once('../simpletest/reporter.php');
defined('TDD') || define('TDD', true);
defined('ROOT') || define('ROOT', str_replace("\\", "/", realpath(dirname(__FILE__).'/../../')).'/');
defined('IN_ROOT') || define ( 'IN_ROOT', TRUE );
defined('TIMESTAMP') || define('TIMESTAMP', time());

require_once(ROOT.'common.php');
require_once(ROOT.'config/config.php');

Common::saveConfig($config);
require_once(ROOT.'helper/http.class.php');

/*
http://test.sina.com/test.php

if($_REQUEST['method'] == 'get') {
	echo 'test'.($_GET['key'] ? '|'.$_GET['key'] : '');
} elseif($_REQUEST['method'] == 'post') {
	echo 'test'.($_POST['key'] ? '|'.$_POST['key'] : '');
}
*/
class TestHttp extends UnitTestCase {

	private $httpObj;

	function testGet() {
		$this->assertEqual($this->httpObj->get('http://test.sina.com/test.php', array('method'=>'get')), 'test');
		$this->assertEqual($this->httpObj->get('http://test.sina.com/test.php', array('method'=>'get', 'key'=>'value')), 'test|value');
		$this->assertEqual($this->httpObj->get('http://test.sina.com/test.php', array('method'=>'get', 'key'=>'value'), array('cookie'=>'cookie')), 'test|value');
	}

	function testPost() {
		$this->assertEqual($this->httpObj->post('http://test.sina.com/test.php', array('method'=>'post')), 'test');
		$this->assertEqual($this->httpObj->post('http://test.sina.com/test.php', array('method'=>'post', 'key'=>'value')), 'test|value');
	}

	function setUp() {
		$this->httpObj = EP_Http::factory('CurlHttp');
	}
}