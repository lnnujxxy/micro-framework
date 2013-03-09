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
defined('ROOT') || define('ROOT', str_replace("\\", "/", realpath(dirname(__FILE__).'/../../')));
defined('IN_ROOT') || define ( 'IN_ROOT', TRUE );
defined('TIMESTAMP') || define('TIMESTAMP', time());

require_once(ROOT.'common.php');
require_once(ROOT.'config/config.php');

Common::saveConfig($config);
require_once(ROOT.'helper/arraylist.class.php');

class TestEP_Arraylist extends UnitTestCase {
	function testToXML() {
		$data = array(
			'a' => 1,
			'b' => array(
				'c' => 2,
				'd' => 3,
			)
		);
		$this->assertTrue(EP_Arraylist::toXml($data));
	}

	function testArrayOrderBy() {
		$data = array(
			array(
				'id' => 1,
				'name' => 'aaa',
			),
			array(
				'id' => 5,
				'name' => 'eee',
			),
			array(
				'id' => 4,
				'name' => 'ddd',
			),
			array(
				'id' => 3,
				'name' => 'ccc',
			),
			array(
				'id' => 2,
				'name' => 'bbb',
			),
		);
		$this->assertTrue(EP_Arraylist::arrayOrderBy($data, 'id'));
	}

	function testNumberToIndex() {
		$data = array(
			array(
				'id' => 3,
				'name' => 'aaa',
			),
			array(
				'id' => 4,
				'name' => 'bbb',
			),
		);
		$this->assertTrue(ArrayList::numberToIndex($data, 'id'));
	}

}