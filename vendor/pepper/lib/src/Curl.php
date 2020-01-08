<?php

namespace Pepper\Lib;

/**
 * Curl
 * @author wangchenglong
 *
 */
class Curl {

	const METHOD_GET = 'GET';

	const METHOD_POST = 'POST';

	private static $username;

	private static $password;
	
	private static $cookie;
	
	private static $ua;

	private static $useIp = false;

	private static $info = array();

	private static $lastUrl;

	private static $lastErrno;

	private static $lastError;

	private static $timeout = 3;

	private static $rollingQueue = null;

	private static $rollingMap = array();
	
	private static $rollingResponseParser = array();
	
	private static $rollingResponseAsObject = array();

	private static $rollingCallback = array();
	
	private static $keepOrigUrl = false;
	
	/**
	 * 获取上次请求的信息
	 * @return array
	 */
	public static function getInfo(){
		return self::$info;
	}

	/**
	 * 获取上次请求的url
	 * @return string
	 */
	public static function getLastUrl(){
		return self::$lastUrl;
	}

	/**
	 * 获取上次错误码
	 * @return int
	 */
	public static function getLastErrno(){
		return self::$lastErrno;
	}

	/**
	 * 获取上次错误信息
	 * @return string
	 */
	public static function getLastError(){
		return self::$lastError;
	}

	/**
	 * 设置basic auth验证
	 * @param string $username
	 * @param string $password
	 */
	public static function setAuth($username, $password){
		self::$username = $username;
		self::$password = $password;
	}

	/**
	 * 设置默认UA
	 * @param string $ua
	 */
	public static function setDefaultUserAgent($ua){
		self::$ua = $ua;
	}

	/**
	 * 使用指定IP的方式请求
     * @param string $ip
	 */
	public static function useIp($ip = '127.0.0.1'){
		self::$useIp = $ip;
	}
	
	public static function keepOrigUrl($keepOrigUrl){
		self::$keepOrigUrl = (bool)$keepOrigUrl;
	}

	/**
	 * 设置默认超时时间
	 * @param int $timeout
	 */
	public static function setTimeout($timeout){
		self::$timeout = $timeout;
	}
	
	/**
	 * 设置cookie
	 * @param mixed $cookie true表示使用当前cookie；false表示不使用cookie；数组表示自定义cookie；字符串表示直接设置cookie字符串
	 */
	public static function setCookie($cookie){
		self::$cookie = $cookie;
	}

	/**
	 * GET方式请求
	 * @param string $url
	 * @param array $params
	 * @param array $headers
	 * @param number $timeout 单位：秒。支持0.1 0.001的写法
	 * @return boolean
	 */
	public static function get($url, $params = array(), $headers = array(), $timeout = null){
		if (!self::$keepOrigUrl && strpos($url, '?')){
			$urlInfo = parse_url($url);
			$query = array();
			parse_str($urlInfo['query'], $query);
			$params = array_merge($query, $params);
			$url = substr($url, 0, strpos($url, '?'));
		}
		return self::request($url, $params, $headers, self::METHOD_GET, $timeout);
	}

	/**
	 * POST方式请求
	 * @param string $url
	 * @param array $params
	 * @param array $headers
	 * @param number $timeout 单位：秒。支持0.1 0.001的写法
	 * @return boolean
	 */
	public static function post($url, $params = array(), $headers = array(), $timeout = null){
		return self::request($url, $params, $headers, self::METHOD_POST, $timeout);
	}

	/**
	 * rolling方法并发get请求
	 * @param array $result
     * @param callable $responseCallback
     * @param bool $responseAsObject
     * @param callable $responseParser
	 * @param string $url
	 * @param array $params
	 * @param array $headers
     * @param array|bool $cookie
	 * @param string $timeout
	 * @param string $useIp
	 * @param string $username
	 * @param string $password
	 */
	public static function rollingGet(&$result, $responseCallback, $responseAsObject, $responseParser, $url, $params = array(), $headers = array(), $cookie = false, $timeout = null, $useIp = '', $username = null, $password = null){
		if (self::$rollingQueue === null){
			self::$rollingQueue = curl_multi_init();
		}
		$ch = self::initCurl($url, $params, $headers, $cookie, self::METHOD_GET, $timeout, $useIp, $username, $password);
		curl_multi_add_handle(self::$rollingQueue, $ch);
		self::$rollingMap[(string)$ch] = &$result;
		self::$rollingResponseAsObject[(string)$ch] = $responseAsObject;
		self::$rollingResponseParser[(string)$ch] = $responseParser;
		self::$rollingCallback[(string)$ch] = $responseCallback;
	}

	/**
	 * rolling方式并发post请求
	 * @param array $result
     * @param callable $responseCallback
     * @param bool $responseAsObject
     * @param callable $responseParser
	 * @param string $url
	 * @param array $params
	 * @param array $headers
     * @param array|bool $cookie
	 * @param string $timeout
	 * @param string $useIp
	 * @param string $username
	 * @param string $password
	 */
	public static function rollingPost(&$result, $responseCallback, $responseAsObject, $responseParser, $url, $params = array(), $headers = array(), $cookie = false, $timeout = null, $useIp = '', $username = null, $password = null){
		if (self::$rollingQueue === null){
			self::$rollingQueue = curl_multi_init();
		}
		$ch = self::initCurl($url, $params, $headers, $cookie, self::METHOD_POST, $timeout, $useIp, $username, $password);
		curl_multi_add_handle(self::$rollingQueue, $ch);
		self::$rollingMap[(string)$ch] = &$result;
		self::$rollingResponseAsObject[(string)$ch] = $responseAsObject;
		self::$rollingResponseParser[(string)$ch] = $responseParser;
		self::$rollingCallback[(string)$ch] = $responseCallback;
	}

	/**
	 * 执行rolling curl 
	 */
	public static function rollingDo(){
		if (empty(self::$rollingQueue)){
			return ;
		}
		do{
			do{
				$code = curl_multi_exec(self::$rollingQueue, $active);
			}while ($code == CURLM_CALL_MULTI_PERFORM);
			
			if ($code != CURLM_OK){
				break;
			}
			
			while (($done = curl_multi_info_read(self::$rollingQueue)) !== false){
				$info = curl_getinfo($done['handle']);
				$errno = curl_errno($done['handle']);
				$error = curl_error($done['handle']);
				$response = curl_multi_getcontent($done['handle']);
				$mapkey = (string)$done['handle'];
				//定义了结果解析函数
				if (self::$rollingResponseParser[$mapkey]){
					$response = call_user_func(self::$rollingResponseParser[$mapkey], $response);
					//如果返回类型为object
					if (self::$rollingResponseAsObject[$mapkey]){
						$response = (object)array('info' => $info, 'errno' => $errno, 'error' => $error, 'response' => $response);
					}
					//如果有返回值回调函数
					if (self::$rollingCallback[$mapkey]){
						call_user_func(self::$rollingCallback[$mapkey], $response);
					}
				}
				self::$rollingMap[$mapkey] = $response;
				
				curl_multi_remove_handle(self::$rollingQueue, $done['handle']);
				curl_close($done['handle']);
			}
			if ($active > 0){
				curl_multi_select(self::$rollingQueue, 0.5);
			}
		}while ($active);
		curl_multi_close(self::$rollingQueue);
		self::$rollingQueue = null;
	}

	/**
	 * request api
	 * @param string $url
	 * @param array $params
	 * @param array $headers
	 * @param string $method
	 * @param number $timeout
	 * @return boolean mixed
	 */
	private static function request($url, $params, $headers, $method, $timeout){
		self::$lastErrno = 0;
		self::$lastError = '';
		self::$info = array();
		self::$lastUrl = '';
		
		$ch = self::initCurl($url, $params, $headers, self::$cookie, $method, $timeout, self::$useIp, self::$username, self::$password);
		$response = curl_exec($ch);
		
		self::$lastErrno = curl_errno($ch);
		self::$lastError = curl_error($ch);
		self::$info = curl_getinfo($ch);
		self::$lastUrl = self::$info['url'];
		
		if (self::$lastErrno !== 0){
			return false;
		}
		
		if (self::$info['http_code'] != 200){
			return false;
		}
		curl_close($ch);
		return $response;
	}

	/**
	 * 初始化curl句柄
	 * @param string $url
	 * @param array $params
	 * @param array $headers
	 * @param mixed $cookie true表示使用当前cookie；false表示不使用cookie；字符串表示自定义cookie；数组表示自定义cookie的另一种形式
	 * @param string $method
	 * @param string $timeout
	 * @param string $useIp
	 * @param string $username
	 * @param string $password
	 * @return resource
	 */
	private static function initCurl($url, $params, $headers, $cookie, $method, $timeout, $useIp, $username, $password){
		$ch = curl_init();
		
		if ($useIp){
			$urlInfo = parse_url($url);
			$url = str_replace($urlInfo['host'], $useIp, $url);
			$headers['host'] = $urlInfo['host'];
		}
		
		if (strtoupper($method) === 'GET' && $params){
			if (strpos($url, '?') !== false){
				$url .= '&' . http_build_query($params);
			}else{
				$url .= '?' . http_build_query($params);				
			}
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$timeout === null && $timeout = self::$timeout;
		if (is_int($timeout)){
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		}else{
			curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout * 1000);
		}
		
		if (strtoupper($method) === "POST"){
			curl_setopt($ch, CURLOPT_POST, 1);
			if (is_array($params)){
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }else{
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
		}
		
		if ($username){
			curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
		}
		
		if (self::$ua){
			curl_setopt($ch, CURLOPT_USERAGENT, self::$ua);
		}
		
		if ($headers){
			$tmp = array();
			foreach ($headers as $k => $v){
				$tmp[] = is_array($v) ? "$k: " . implode(';', $v) : "$k: $v";
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $tmp);
		}
		
		if ($cookie === true || is_array($cookie)){
			if ($cookie === true){
				$cookie = $_COOKIE;
			}
			$tmp = array();
			foreach ($cookie as $k => $v){
				$tmp[] = "$k=$v";
			}
			//there is a space after semicolon
			$cookie = implode('; ', $tmp);
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}elseif (is_scalar($cookie)){
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}elseif ($cookie === false){
			curl_setopt($ch, CURLOPT_COOKIE, '');
		}
		
		return $ch;
	}
}

