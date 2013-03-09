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
require_once(ROOT.'helper/upload.php');
class TestCache extends UnitTestCase {
	//注意请单个函数打开测试

	/*
	 * 返回指定文件名
	 */
	/*
	function testUpload() {
		if($_POST['submit']) {
			$uploadObj = Helper_Upload::getInstance();
			$uploadObj->setSubPathFormat(Helper_Upload::TIME_PATH_FORMAT);
			$uploadObj->setSaveName('logo');
			$uploadObj->setIsRename(1);
			$uploadObj->setIsSubPath(1);
			$this->assertTrue($uploadObj->uploadFile('uploadFile', dirname(__FILE__).DS.'upload'));
		} else {
			echo '<form action="" method="post" enctype="multipart/form-data">';
			echo '	<input type="file" name="uploadFile" />';
			echo '	<input type="submit" name="submit" value="提交" />';
			echo '</form>';
		}
	}
	*/

	/**
	 * 返回相对路径
	 */
	 /*
	function testUpload() {
		if($_POST['submit']) {
			$uploadObj = Helper_Upload::getInstance();
			$uploadObj->setSaveName('logo');
			$this->assertTrue($uploadObj->uploadFile('uploadFile', dirname(__FILE__).DS.'upload'));
		} else {
			echo '<form action="" method="post" enctype="multipart/form-data">';
			echo '	<input type="file" name="uploadFile" />';
			echo '	<input type="submit" name="submit" value="相对提交" />';
			echo '</form>';
		}
	}*/

	/**
	 * 返回文件
	 */
	function testUpload() {
		if($_POST['submit']) {
			$uploadObj = Helper_Upload::getInstance();
			$uploadObj->setSaveName('logo');
			$uploadObj->setRetFile(1);
			echo $uploadObj->uploadFile('uploadFile', dirname(__FILE__).DS.'upload');
		} else {
			echo '<form action="" method="post" enctype="multipart/form-data">';
			echo '	<input type="file" name="uploadFile" />';
			echo '	<input type="submit" name="submit" value="相对提交" />';
			echo '</form>';
		}
	}

}