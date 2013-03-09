<?php
/*
 * @description: 配置文件
 * @author: zhouweiwei
 * @date: 2010-5-13
 * @version: 1.0
 */
$config = array(
	'smarty' => array(
		'template_dir'		=> APP_DIR.'view/'.Common::getTemplateDir().'/',
		'compile_dir'		=> APP_DIR.'data/template_c/'.Common::getTemplateDir().'/',
		'plugins_dir'		=> ROOT.'plugin/smarty/plugins/',
		'cache_dir'			=> APP_DIR.'data/cache/'.Common::getTemplateDir().'/',
		'left_delimiter'	=> '{{',
		'right_delimiter'	=> '}}',
		'default_modifiers'	=> array('escape:dhtmlspecialchars'),
		'compile_check'		=> true, //是否检查模板
		'caching'			=> false, //是否开始自定义缓存 false关闭
		//'cache_life'		=> 600,  //缓存时间 单位(秒) 0为永不过期
		//'cache_handler_func' => 'cache_handler',
	),
	'database' => array(
		'master' => array(
			'host'   => 'localhost',
			'dbname' => 'test',
			'user'	 => 'root',
			'pass'	 => 'root',
			'port'   => '3306',
			'charset'=> 'utf8',
		),
		'tablepre' => '',
	),
	'mvc' => array(
		'defaultController' => 'Index',
		'defaultAction' => 'ls',
		'controller'	=> 'c',
		'action'		=> 'a',
		'accessFile'	=> 'index.php',
		'rewrite'		=> true,
		'urlsuffix'		=> '.html'
	),
	'common' => array(
		'charset'			=> 'utf8',  //字符集
		'hostenv'			=> 'dev',	//开发模式 dev 开发模式 debug 测试模式 product 发布模式
		'debug'				=> true,	//firephp调试
		'compress'			=> true,	//压缩
		'xdebug'			=> true,	//xdebug调试
	),
	'cache' => array(
		'type' => 'Lib_FileCache',
		'param' => APP_DIR.'data/cache/',
	),
	'log' => array(
		'type' => 'Helper_FileLog',
		'param' => array(
				APP_DIR.'data/log/',
		),
	),
	'site' => array(
		'domain' => 'cm.com',
	),
);

class ConfigConst {

	public static function set($array) {
		define('CONFIG', var_export($array, true));
		return true;
	}

	public static function get() {
		if(!defined('CONFIG')) {
			return false;
		}
		$array = eval('return '.CONFIG.';');
		return $array;
	}
}
