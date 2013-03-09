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



require_once(ROOT.'helper/image.php');

class TestImage extends UnitTestCase {
	function testResize() {
		$props = array(
			'dynamic_output' => false,
			'width' => 60,
			'height' => 40,
			'source_image' => dirname(__FILE__).'/1.jpg',
			'new_image' => dirname(__FILE__).'/2.jpg',
		);
		$this->imageObj = new Helper_Image($props);
		$this->assertTrue($this->imageObj->resize());
	}

	function testCrop() {
		$props = array(
			'dynamic_output' => false,
			'width' => 20,
			'height' => 20,
			'source_image' => dirname(__FILE__).'/1.jpg',
		);
		$this->imageObj = new Helper_Image($props);
		$this->assertTrue($this->imageObj->crop());
	}

	function testWatermark() {
		$props = array(
			'wm_text' => 'test',
			'source_image' => dirname(__FILE__).'/3.jpeg',
		);
		$this->imageObj = new Helper_Image($props);
		$this->assertTrue($this->imageObj->watermark());
	}
}