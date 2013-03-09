<?php
/*
 * @description: http Request类文件
 * @date: 2010-6-4
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');

class Helper_Request {

	/**
	 * 获取GPC变量
	 * @param $k String
	 * @param $t String
	 * @return String
	 */
	public static function getParam($k, $t='R') {
		switch($t) {
			case 'P': $var = &$_POST; break;
			case 'G': $var = &$_GET; break;
			case 'C': $var = &$_COOKIE; break;
			case 'R': $var = &$_REQUEST; break;
		}
		return isset($var[$k]) ? (is_array($var[$k]) ? $var[$k] : trim($var[$k])) : null;
	}

	public static function env($key) {
		if ($key == 'HTTPS') {
			if (isset($_SERVER['HTTPS'])) {
				return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
			}
			return (strpos(self::env('SCRIPT_URI'), 'https://') === 0);
		}

		if ($key == 'SCRIPT_NAME') {
			if (self::env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
				$key = 'SCRIPT_URL';
			}
		}

		$val = null;
		if (isset($_SERVER[$key])) {
			$val = $_SERVER[$key];
		} elseif (isset($_ENV[$key])) {
			$val = $_ENV[$key];
		} elseif (getenv($key) !== false) {
			$val = getenv($key);
		}

		if ($key === 'REMOTE_ADDR' && $val === self::env('SERVER_ADDR')) {
			$addr = self::env('HTTP_PC_REMOTE_ADDR');
			if ($addr !== null) {
				$val = $addr;
			}
		}

		if ($val !== null) {
			return $val;
		}

		switch ($key) {
			case 'SCRIPT_FILENAME':
				if (defined('SERVER_IIS') && SERVER_IIS === true) {
					return str_replace('\\\\', '\\', self::env('PATH_TRANSLATED'));
				}
			break;
			case 'DOCUMENT_ROOT':
				$name = self::env('SCRIPT_NAME');
				$filename = self::env('SCRIPT_FILENAME');
				$offset = 0;
				if (!strpos($name, '.php')) {
					$offset = 4;
				}
				return substr($filename, 0, strlen($filename) - (strlen($name) + $offset));
			break;
			case 'PHP_SELF':
				return str_replace(self::env('DOCUMENT_ROOT'), '', self::env('SCRIPT_FILENAME'));
			break;
			case 'CGI_MODE':
				return (PHP_SAPI === 'cgi');
			break;
			case 'HTTP_BASE':
				$host = self::env('HTTP_HOST');
				if (substr_count($host, '.') !== 1) {
					return preg_replace('/^([^.])*/i', null, self::env('HTTP_HOST'));
				}
			return '.' . $host;
			break;
		}
		return null;
	}

	/**
	 * 获取Server数据
	 * @param $key String
	 * @return Mixed
	 */
	public static function getServer($key = null) {
		if($key == null) {
			return $_SERVER;
		}
        return (isset($_SERVER[$key])) ? $_SERVER[$key] : null;
    }

	/**
	 * 获取FILES数据
	 *
	 * @param String $key 需要获取的Key名，空则返回所有数据
	 * @return Mixed
	 */
	public static function getFile($key = null) {
		if ($key == null) {
			return $_FILES;
		}
		return isset($_FILES[$key]) ? $_FILES[$key] : null;
	}

    public static function isPost() {
        if ('POST' == self::getServer('REQUEST_METHOD')) {
            return true;
        }
        return false;
    }

    public static function isGet() {
        if ('GET' == self::getServer('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    public static function isPut() {
        if ('PUT' == self::getServer('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    public static function isDelete() {
        if ('DELETE' == self::getServer('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    public static function isHead() {
        if ('HEAD' == self::getServer('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    public static function isOptions() {
        if ('OPTIONS' == self::getServer('REQUEST_METHOD')) {
            return true;
        }
        return false;
    }

	public static function getReferer() {
		return $_SERVER['HTTP_REFERER'];
	}


	public static function getHost()  {
		return isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
	}

	public static function getScriptName() {
		return isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_SERVER['ORIG_SCRIPT_NAME']) ? $_SERVER['ORIG_SCRIPT_NAME'] : '');
	}

	public static function getClientIP() {
		$cip = getenv ( 'HTTP_CLIENT_IP' );
		$xip = getenv ( 'HTTP_X_FORWARDED_FOR' );
		$rip = getenv ( 'REMOTE_ADDR' );
		$srip = $_SERVER ['REMOTE_ADDR'];
		if ($cip && strcasecmp ( $cip, 'unknown' )) {
			$onlineip = $cip;
		} elseif ($xip && strcasecmp ( $xip, 'unknown' )) {
			$onlineip = $xip;
		} elseif ($rip && strcasecmp ( $rip, 'unknown' )) {
			$onlineip = $rip;
		} elseif ($srip && strcasecmp ( $srip, 'unknown' )) {
			$onlineip = $srip;
		}
		preg_match ( "/[\d\.]{7,15}/", $onlineip, $match );

		return $match [0] ? $match [0] : 'unknown';
	}

	public static function getHttpUrl() {
		$url = '';
		$url = strtolower($_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST']);
		if(!strpos($url, 'http://')) {
			$url = 'http://'.$url;
		}
		if(!empty($_SERVER['REQUEST_URI'])) {
			$url .= $_SERVER['REQUEST_URI'];
		} else {
			$url .= $_SERVER['SCRIPT_NAME'];
			if($_SERVER['QUERY_STRING']) {
				$url .= "?".$_SERVER['QUERY_STRING'];
			}
		}
		return $url;
	}
}
?>