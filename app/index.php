<?php
/*
 * @description: 入口文件
 * @author: zhouweiwei
 * @date: 2010-5-13
 * @version: 1.0
 */
error_reporting(E_ALL ^ E_NOTICE);
version_compare(PHP_VERSION, '5.3') < 0 && set_magic_quotes_runtime(0);
header('Content-Type:text/html;charset=UTF-8');
if (is_callable('mb_internal_encoding')) {
	mb_internal_encoding('UTF-8');
}
date_default_timezone_set('PRC');

define('IN_ROOT', TRUE);
define('DS', DIRECTORY_SEPARATOR);
define('APP_DIR', dirname(__FILE__).DS);
define('ROOT', realpath(APP_DIR.'../framework').DS);
define('TIMESTAMP', time());

//set_include_path(get_include_path() . PATH_SEPARATOR . ROOT.'pear'); //引入pear

try {
	include ROOT . 'common.php';
	include APP_DIR . 'config/config.php';
	include APP_DIR . 'config/constant.php';

	Common::setConfig($config);
	unset($config);

	Lib_Mvc::getInstance()->dispatch(Common::getTemplateDir());
} catch (Exception $e) {
	//throw $e;
	throw new Exception(Common::t($e->getMessage()));
}
?>