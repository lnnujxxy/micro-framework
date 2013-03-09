<?php
require_once('simpletest.php');
require_once('autorun.php');
require_once('reporter.php');
defined('TDD') || define('TDD', true);
defined('IN_ROOT') || define ( 'IN_ROOT', TRUE );
defined('ROOT') || define('ROOT', str_replace("\\", "/", realpath(dirname(__FILE__).'/../../../')).'/');
defined('TIMESTAMP') || define('TIMESTAMP', time());
require_once(ROOT.'lib/controller.class.php');
require_once(ROOT.'lib/model.class.php');
require_once(ROOT.'common.php');
require_once(ROOT.'config.php');
require_once(ROOT.'plugin/firephp.class.php');

Common::setConfig($config);
require_once(ROOT.'controller/default/index.php');
require_once(ROOT.'model/index.php');

class IndexTest extends UnitTestCase {
	private $indexController;
	public function testIndexAction() {

	}

	public function testLsAction() {
		$id = 2;
		$m = new index;
		$list = $m->getIdData($id);
		$this->assertTrue(is_array($list) && !empty($list));
	}

	public function setUP() {
		$this->indexController = new IndexController();

	}
}

