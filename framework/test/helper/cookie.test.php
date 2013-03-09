<?php
/*
 * Copyright (c) 2010,  新浪网运营部-网络应用开发部
 * All rights reserved.
 * @description:
 * @author: zhouweiwei
 * @date:
 * @version: 1.0
 */

defined('TDD') || define('TDD', true);
defined('ROOT') || define('ROOT', str_replace("\\", "/", realpath(dirname(__FILE__).'/../../')));
defined('IN_ROOT') || define ( 'IN_ROOT', TRUE );
defined('TIMESTAMP') || define('TIMESTAMP', time());

require_once(ROOT.'common.php');
require_once(ROOT.'config/config.php');

Common::setConfig($config);
require_once(ROOT.'helper/security.class.php');
require_once(ROOT.'helper/cookie.class.php');

function testSet() {
	$c = new Helper_Cookie ();
	$value = '中国按当时发生地方11';
	$c->setName('myCookie')         // REQUIRED
	  ->setValue($value,true)       // REQUIRED - 1st param = data string/array, 2nd param = encrypt (true=yes)
	  ->setExpire('+1 hour');        // optional - defaults to "0" or browser close
	 $c->createCookie();
}
testSet();
function testGet() {
	$c = new Helper_Cookie ();
	return $c->getCookie('myCookie');
}
var_dump(trim(testGet()));
