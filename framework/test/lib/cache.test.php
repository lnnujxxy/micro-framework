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

Common::setConfig($config);
require_once(ROOT.'lib/cache.class.php');
class TestCache extends UnitTestCase {
	private $cacheObj;
	function testRemove() {
		$this->assertTrue($this->cacheObj->remove('test'));
	}


	function testSet() {
		$this->assertTrue($this->cacheObj->set('test', '1111'));
		$this->assertEqual($this->cacheObj->get('test'), '1111');
	}

	function testReplace() {
		$this->assertTrue($this->cacheObj->replace('test', "2222"));
		$this->assertEqual($this->cacheObj->get('test'), "2222");
		$this->assertTrue($this->cacheObj->replace('test', "3333", 30));
	}

	function testFlush() {
		$this->assertTrue($this->cacheObj->flush());
		$this->assertTrue($this->cacheObj->set('test', '4444'));
	}

	function setUp() {
		$this->cacheObj = Lib_Cache::factory('EP_FileCache', dirname(__FILE__));
		//$this->cacheObj = Lib_Cache::factory('EP_MemcacheCache', array(array('127.0.0.1', 11211)));
	}
}