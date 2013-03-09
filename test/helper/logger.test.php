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
require_once(ROOT.'helper/log.class.php');


class TestHttp extends UnitTestCase {
	private $logObj;

	function testLog() {
		$this->logObj->log("error", EP_Log::LEVEL_ERROR);
		$this->logObj->log("warning", EP_Log::LEVEL_WARNING);
		$logs = $this->logObj->getLogs();
		$this->assertTrue($this->logObj->process($logs));
	}

	function testFilterLogLevel() {
		$this->logObj->log("error", EP_Log::LEVEL_ERROR);
		$this->logObj->log("warning", EP_Log::LEVEL_WARNING);
		$this->logObj->log("info", EP_Log::LEVEL_INFO);
		$this->logObj->log("profile", EP_Log::LEVEL_PROFILE);
		$this->logObj->setLogFile('info.log');
		$logs = $this->logObj->getLogs('info');
		$this->assertTrue($this->logObj->process($logs));
	}

	function testFilterLogCategories() {
		$this->logObj->log("error", EP_Log::LEVEL_ERROR, 'cat1');
		$this->logObj->log("warning", EP_Log::LEVEL_WARNING, 'cat2');
		$this->logObj->log("info", EP_Log::LEVEL_INFO, 'cat3');
		$this->logObj->log("profile", EP_Log::LEVEL_PROFILE, 'cat4');
		$this->logObj->setLogFile('cat.log');
		$logs = $this->logObj->getLogs('', 'cat1');
		$this->assertTrue($this->logObj->process($logs));
	}

	function setUp() {
		$this->logObj = EP_Log::factory('EP_FileLog', dirname(__FILE__).'/');
	}
}