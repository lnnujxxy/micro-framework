<?php
require_once('simpletest.php');
require_once('autorun.php');
require_once('reporter.php');
defined('TDD') || define('TDD', true);
defined('IN_ROOT') || define ( 'IN_ROOT', TRUE );
defined('ROOT') || define('ROOT', str_replace("\\", "/", realpath(dirname(__FILE__).'/../../')).'/');
defined('TIMESTAMP') || define('TIMESTAMP', time());
require_once(ROOT.'lib/session.class.php');


class Helper_SessionTest extends UnitTestCase {

	public function testSet() {
		Session::getInstance()->set('test', 111);
		$this->assertEqual($_SESSION['test'], 111);
	}

	public function testGet() {
		$this->assertEqual(Session::getInstance()->get('test'), 111);
	}

	public function testRemove() {
		$this->assertTrue(Session::getInstance()->remove('test'));
		$this->assertFalse(Session::getInstance()->isRegistered('test'));
	}

	public function testFlush() {
		Session::getInstance()->set('test1', 222);
		$this->assertTrue(Session::getInstance()->flush());
		$this->assertFalse(Session::getInstance()->isRegistered('test1'));
	}

	public function setUP() {

	}
}

