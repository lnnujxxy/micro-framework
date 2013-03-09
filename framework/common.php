<?php
/**
 * 框架通用方法
 *
 * @category 
 * @package Common
 * @author Zhou Weiwei <lnnujxxy@126.com>
 * @date 2010-5-13
 * @license 0.1
 * @link 
 */
defined('IN_ROOT') || exit('Access Denied');

class Common {
	/**
     * 注册句柄
     *
     * @var Array
     */
	public static $registry = array();

	/**
	 * 日志句柄
	 *
	 * @var Resource
	 */
	public static $logger;

	/**
	 * 设置配置数据
	 *
	 * @param $config Array 配置数组
	 * @return Bool
	 */
	public static function setConfig($config) {
		if (empty($config)) {
			throw new Exception(self::t("function: ".__FUNCTION__." config is empty!"));
		}
		$_SERVER['config'] = $config;
		return true;
	}

	/**
	 * 读取配置数据
	 *
	 * @return Array 配置信息数组
	 */
	public static function getConfig() {
		if (empty($_SERVER['config'])) {
			throw new Exception(self::t("function: ".__FUNCTION__." config is empty!"));
		}
		return $_SERVER['config'];
	}

	/**
     * 注册变量
     *
     * @param $key String 注册key
     * @param $value Mixed 注册值
     * @param $graceful Bool 是否友好返回
     * @return
     */
    public static function register($key, $value, $graceful = false) {
        if (isset(self::$registry[$key])) {
            if ($graceful) {
                return;
            }
			throw new Exception(self::t("registry key $key already exists"));
        }
        self::$registry[$key] = $value;
    }

    /**
     * 注销注册变量
     *
     * @param $key String 注册key
	 * @return Void
     */
    public static function unregister($key) {
        if (isset(self::$registry[$key])) {
            if (is_object(self::$registry[$key]) && (method_exists(self::$registry[$key], '__destruct'))) {
                self::$registry[$key]->__destruct();
            }
            unset(self::$registry[$key]);
        }
    }

    /**
     * 获取注册值
     *
     * @param $key String
     * @return mixed
     */
    public static function registry($key) {
        if (isset(self::$registry[$key])) {
            return self::$registry[$key];
        }
        return null;
    }

	/**
	 * 包含文件
	 * @param $filename String
	 * @return Bool
	 */
	public static function requireFile($filename) {
		static $_importFiles = array();

		if (!isset($_importFiles[$filename])) {
			$filename = str_replace(array('..', "\n", "\r", "\r\n"), '', $filename);
			if (is_file($filename)) {
				require $filename;
				$_importFiles[$filename] = true;
			} else {
				$_importFiles[$filename] = false;
			}
		}
		return $_importFiles[$filename];
	}

	/**
	 * 加载插件包
	 *
	 * @param $plugin String 插件名
	 * @return Bool true 成功 false 失败
	 */
	public static function loadPlugin($plugin) {
		$plugin = strtolower($plugin);
		$file	= ROOT.'plugin/'.$plugin.'/'.$plugin.".class.php";
		return self::requireFile($file);
	}

	/**
	 * 生成Key
	 * @param $params Mixed 参数
	 * @param $glue String 分割字符
	 * @return String 字符串
	 */
	public static function generateKey($params, $glue='-') {
		if (is_array($params)) {
			foreach ($params as $param) {
				if (is_array($param)) {
					$retVal[] = self::generateKey($param, $glue);
				} else {
					$retVal[] = (string)$param;
				}
			}
			$key =  implode($glue, $retVal);
		} else {
			$key = (string)$params;
		}

		if (strlen($key) >= 100) { //避免key过长
			$key = md5($key);
		}

		return $key;
	}

	/**
	 * 获得Model 方法
	 * @param $model String
	 * @param $arg Mixed
	 * @return Object
	 */
	public static function getModel($model, $args=null) {
		$key = 'model_'.self::generateKey(func_get_args());

		if (!self::registry($key)) {
			self::register($key, new $model($args));
		}
		return self::registry($key);
	}

	/**
	 * 获得db对象
	 * @return Object
	 */
	public static function getDB() {
		$config = self::getConfig();
		$key = 'db_'.self::generateKey($config['database']);
		if (!self::registry($key)) {
			$db = new Lib_Mysql($config['database']['master'], $config['database']['slave']);
			self::register($key, $db);
		}
		return self::registry($key);
	}

	/**
	 * 获得Helper 方法
	 * @param $helper String
	 * @param $arg Mixed
	 * @return Object
	 */
	public static function helper($helper, $args=null) {
        $key = 'helper_'.self::generateKey(func_get_args());

        if (!self::registry($key)) {
            self::register($key, new $helper($args));
        }
        return self::registry($key);
    }

	/**
	 * 自动载入函数
	 *
	 * @param $className String 载入的类名
	 * @return Void
	 */
	public static function autoload($className) {
		if (!self::verifyValidString($className)) {
			exit('尝试加载非法类');
		}

		$className = strtolower($className);
		if (substr($className, 0, 4) === 'lib_') {
			$classFile = str_replace('lib_', '', $className);
			self::requireFile(ROOT."lib/{$classFile}.php");
		} elseif (substr($className, 0, 7) === 'helper_') {
			$classFile = str_replace('helper_', '', $className);
			self::requireFile(ROOT."helper/{$classFile}.php");
		} elseif (substr($className, 0, 4) === 'dao_') {
			$classFile = str_replace('dao_', '', $className);
			self::requireFile(APP_DIR."model/dao/{$classFile}.php");
		} elseif (substr($className, 0, 6) === 'logic_') {
			$classFile = str_replace('logic_', '', $className);
			self::requireFile(APP_DIR."model/logic/{$classFile}.php");
		}
	}

	/**
	 * 验证合法类名和方法名
	 * @param $string String
	 * @return Bool
	 */
	public function verifyValidString($string) {
		if (is_array($string)) {
			foreach ($string as $str) {
				if (!self::verifyValidString($str)) {
					return false;
				}
			}
		} else {
			return preg_match('/^[A-Za-z_0-9]*$/', $string);
		}
	}

	/**
	 * 转换字符集
	 *
	 * @param $data Mixed 待转换的数据
	 * @param $dstEncoding String 转换后字符集
	 * @param $srcEncoding String 转换前字符集
	 * @return Mixed 转换后的字符集
	 */
	public static function convertEncoding($data, $dstEncoding, $srcEncoding) {
		if (!is_array($data)) {
			$data = mb_convert_encoding($data, $dstEncoding, $srcEncoding);
		} else {
			foreach ($data as $key=>$value) {
				$data[$key] = self::convertEncoding($value, $dstEncoding, $srcEncoding);
			}
		}
		return $data;
	}

	/**
	 * 调试输出函数
	 *
	 * @param $data Mixed 调试输出内容
	 * @param $key String 调试输出键
	 * @param $output Int 输出显示
	 * @return Void
	 */
	public static function firePHP($data, $key='firePHP', $output=0) {
		self::loadPlugin('firephp');
		$config = self::getConfig();
		try {
			if (!$config['common']['debug'] || defined('TDD')) {
				return;
			}
			if ($output) {
				FirePHP::getInstance(true)->error($data, $key);
			} elseif ($key === 'sql' && preg_match("/^UPDATE|^DELETE|^INSERT|^REPLACE|^ALTER|^TRUNCATE|^CREATE/i", $data)) {
				FirePHP::getInstance(true)->warn($data, $key);
			} else {
				FirePHP::getInstance(true)->log($data, $key);
			}

			$config['common']['xdebug'] && self::xdebug();
		} catch(Exception $e) {}
	}

	/**
	 * xdebug调试
	 *
	 * @return Void
	 */
	public static function xdebug() {
		if (function_exists('xdebug_time_index')) {
			FirePHP::getInstance(true)->error('usetime:'.xdebug_time_index().'curmemory:'.xdebug_memory_usage(), 'xdebug');
		}
	}


	/**
	 * 错误信息页面
	 *
	 * @param $message String 错误提示信息
	 * @param $goUrl String 跳转链接地址
	 * @param $limitTime Int 等待跳转时间
	 * @return Void
	 */
	public static function error($message, $goUrl=null, $limitTime=0) {
		if (empty($message)) {
			return false;
		}

		if ($goUrl == '') {
			$limitTime = empty($limitTime) ? 3000 : 1000*$limitTime;
			$goUrl = "javascript:history.go(-1);";
			$message .= "<br/>\n<a href=\"javascript:history.go(-1);\" target=\"_self\">如果你的浏览没反应,请点击这里...</a>\n";
		} else {
			$limitTime = empty($limitTime) ? 3000 : 1000*$limitTime;
			$goUrl = str_replace(array("\n","\r"), '', $goUrl);
			$message .= "<br/>\n<a href=\"{$goUrl}\" target=\"_self\">如果你的浏览没反应,请点击这里...</a>\n";
		}
		$message .= "<script type=\"text/javascript\">\nfunction redirect_url(url){location.href=url;}setTimeout(\"redirect_url('{$goUrl}')\", {$limitTime});\n</script>\n";

		$fileContent = file_get_contents(APP_DIR.'view/error.html');

		$fileContent = str_replace('{{$message}}', $message, $fileContent);
		echo $fileContent;
		exit;
	}

	/**
	 * 记录日志
	 * @param $msg String 记录日志内容
	 * @param $level String 日志级别
	 * @param $category String 日志分类
	 * @return Object 日志对象
	 */
	public static function log($msg,$level=Helper_Log::LEVEL_INFO,$category='application') {
		if (self::$logger===null) {
			$config = self::getConfig();
			self::$logger = Helper_Log::factory($config['log']['type'], $config['log']['param']);
		}
		self::$logger->log($msg, $level, $category);
		return self::$logger;
	}

	/**
	 * 记录日志
	 * @param $msg String 记录日志内容
	 * @param $level String 日志级别
	 * @param $category String 日志分类
	 * @return Object 日志对象
	 */
	public static function writeLog($msg, $level=Helper_Log::LEVEL_ERROR,$category='application') {
		if (self::$logger===null) {
			$config = self::getConfig();
			self::$logger = Helper_Log::factory($config['log']['type'], $config['log']['param']);
		}
		self::$logger->log($message, Helper_Log::LEVEL_EXCEPTION, $category);
		self::$logger->process();
	}

	/**
	 * 封装抛出异常并记录日志
	 * @param $message String 异常信息
	 * @param $category String 分类
	 * @param $goUrl String 跳转链接地址
	 * @param $limitTime Int 等待跳转时间
	 */
	public static function t($message, $category=null, $goUrl=null, $limitTime=0) {
		if (self::$logger===null) {
			$config = self::getConfig();
			self::$logger = Helper_Log::factory($config['log']['type'], $config['log']['param']);
		}
		self::$logger->log($message, Helper_Log::LEVEL_EXCEPTION, $category);
		self::$logger->process();
		return self::error($message, $goUrl, $limitTime);
	}

	/**
	 * 设置模板目录
	 *
	 * @param $dir String 目录
	 * @return Void
	 */
	public static function setTemplateDir($dir = null) {
		empty($dir) && $dir = 'default';
		$_SERVER['template_dir'] = $dir;
	}

	/**
	 * 获取模板目录
	 *
	 * @return String
	 */
	public static function getTemplateDir() {
		return isset($_SERVER['template_dir']) ? $_SERVER['template_dir'] : 'default';
	}
}

spl_autoload_register(array('Common','autoload'));

//对于 < php5.2处理
if (!function_exists('json_encode')) {
	Common::requireFile(ROOT.'plugin/jsonwrapper/jsonwrapper.php');
}