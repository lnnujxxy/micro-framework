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
defined('APP_DIR') || define('APP_DIR', ROOT.'../app/');
header('Content-type: text/html; charset=utf-8');

defined('IN_ROOT') || define ( 'IN_ROOT', TRUE );
defined('TIMESTAMP') || define('TIMESTAMP', time());

require_once(ROOT.'common.php');
require_once(APP_DIR.'config/config.php');
Common::setConfig($config);
require_once(ROOT.'lib/mysql.php');
class TestCache extends UnitTestCase {
	private $db;
	function testCreateTable() {

		$sql = "DROP TABLE IF EXISTS `cm_test`; CREATE TABLE `cm_test` (
           `id` int(10) NOT NULL auto_increment,
           `username` varchar(50) NOT NULL,
           `password` varchar(32) NOT NULL,
           PRIMARY KEY  (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

		 $this->assertTrue($this->db->query($sql));
	}

	function testQuery() {
		$sql = "SELECT * FROM cm_test";
		$this->assertTrue($this->db->query($sql));
	}



	function testAdd() {
		$data = array(
			'username' => rand(),
			'password' => md5(rand()),
		);
		$this->assertTrue($this->db->add('cm_test', $data, 'INSERT'));
		//var_dump($this->db->lastInsertId());
		$data = array(
			'username' => rand(),
			'password' => md5(rand()),
		);
		$this->assertTrue($this->db->add('cm_test', $data, 'INSERT'));
	}

	function testGetRow() {
		$sql = "SELECT * FROM cm_test WHERE id='%s'";
		$this->assertTrue($this->db->getRow($sql, array(1)));
	}

	function testGetRecord() {
		$this->assertTrue($this->db->getRecord('cm_test', '*', array('id'=>2), array('id'=>'DESC'), array(0, 2)));
	}

	function testGetAll() {
		$ids = array(1, 2);
		$ids = Helper_Input::addslashes($ids, 1);

		$sql = "SELECT * FROM cm_test WHERE id IN ('".join("','", $ids)."')";
		$this->assertTrue($this->db->getAll($sql));
	}

	function testUpdate() {
		$data = array(
			'username' => rand(),
			'password' => md5(rand()),
		);
		$this->assertTrue($this->db->update('cm_test', $data, array('id'=>2)));
	}

	function testDelete() {
		$this->assertTrue($this->db->delete('cm_test', array('id'=>'1')));
	}

	function setUp() {
		$this->config = Common::getConfig();
		$this->db = new Lib_Mysql($this->config['database']['master'], $this->config['database']['slave']);
	}
}