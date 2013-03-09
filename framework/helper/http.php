<?php
/*
 * @description: HTTP处理类
 * @author: Willko Cheng <willko@foxmail.com>
 * @update: zhouweiwei
 * @date: 2010-05-17
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');

class Helper_Http {
	private $header;
	private $body;
	private $ch;

	private $proxy;
	private $proxy_port;
	private $proxy_type = 'HTTP'; // or SOCKS5
	private $proxy_auth = 'BASIC'; // or NTLM
	private $proxy_user;
	private $proxy_pass;

	protected $cookie;
	protected $options;
	protected $url = array ();
	protected $referer = array ();

	/**
	 * 提示信息数组
	 */
	private static $msg = array(
		'error_curl_noinstall'	=> 'curl扩展未安装',
		'error_exec_failed'		=> '执行操作失败',		
	);

	public function __construct($options = array()) {
		if (!function_exists('curl_init')) {
			$this->retMsg('error_curl_noinstall', $this->msg['error_curl_noinstall']);
		}

		$defaults = array();
		$defaults['timeout'] = 30;
		$defaults['temp_root'] = sys_get_temp_dir();
		$defaults['user_agent'] = 'Mozilla/5.0 (Windows; U; Windows NT 6.0; zh-CN; rv:1.8.1.20) Gecko/20081217 Firefox/2.0.0.20';

		$this->options = array_merge($defaults, $options);
	}
	
	/** 
	 * 打开请求
	 * @param $action String 请求数组key
	 * @param $url String 请求地址
	 * @param $referer String 请求referer地址
	 * @param Resource
	 */
	public function open($action, $url, $referer = '') {
		$this->ch = curl_init ();

		//curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_HEADER, true);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->options ['user_agent']);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $this->options ['timeout']);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:')); 

		$this->header = '';
		$this->body = '';

		$this->url[$action] = $url;
		$this->referer[$action] = $referer;

		return $this;
	}
	
	/**
	 * 关闭请求
	 * 
	 */
	public function close() {
		if (is_resource($this->ch)) {
			curl_close($this->ch);
		}
	}
	
	/**
	 * 设置cookie
	 * 
	 */
	public function setCookie($data) {
		curl_setopt($this->ch, CURLOPT_HEADER, 0);
		if(is_array($data)) {
			foreach ($data as $key=>$value) {
				$cookieValue .= "$key=$value; ";
			}
			curl_setopt($this->ch, CURLOPT_COOKIE, $cookieValue);
		} elseif(is_string($data)) {
			curl_setopt($this->ch, CURLOPT_COOKIE, $data);
		}

		return $this;
	}
	
	/**
	 * 保存cookie
	 * 
	 */
	public function saveCookie() {
		if (! isset ( $this->cookie )) {
			if (! empty ( $this->cookie ) && $this->isTempCookie && is_file ( $this->cookie )) {
				unlink ( $this->cookie );
			}

			$this->cookie = $this->options ['temp_root'].'curl_cookie_e3n4j57gu.txt';
			$this->isTempCookie = true;
		}

		curl_setopt ( $this->ch, CURLOPT_COOKIEJAR, $this->cookie );
		curl_setopt ( $this->ch, CURLOPT_COOKIEFILE, $this->cookie );

		return $this;
	}
	
	/**
	 * 设置SSL
	 * 
	 */
	public function ssl() {
		curl_setopt ( $this->ch, CURLOPT_SSL_VERIFYPEER, false );

		return $this;
	}

	public function proxy($host = null, $port = null, $type = null, $user = null, $pass = null, $auth = null) {
		$this->proxy = isset ( $host ) ? $host : $this->proxy;
		$this->proxy_port = isset ( $port ) ? $port : $this->proxy_port;
		$this->proxy_type = isset ( $type ) ? $type : $this->proxy_type;

		$this->proxy_auth = isset ( $auth ) ? $auth : $this->proxy_auth;
		$this->proxy_user = isset ( $user ) ? $user : $this->proxy_user;
		$this->proxy_pass = isset ( $pass ) ? $pass : $this->proxy_pass;

		if (! empty ( $this->proxy )) {
			curl_setopt ( $this->ch, CURLOPT_PROXYTYPE, $this->proxy_type == 'HTTP' ? CURLPROXY_HTTP : CURLPROXY_SOCKS5 );
			curl_setopt ( $this->ch, CURLOPT_PROXY, $this->proxy );
			curl_setopt ( $this->ch, CURLOPT_PROXYPORT, $this->proxy_port );
		}

		if (! empty ( $this->proxy_user )) {
			curl_setopt ( $this->ch, CURLOPT_PROXYAUTH, $this->proxy_auth == 'BASIC' ? CURLAUTH_BASIC : CURLAUTH_NTLM );
			curl_setopt ( $this->ch, CURLOPT_PROXYUSERPWD, "[{$this->proxy_user}]:[{$this->proxy_pass}]" );
		}

		return $this;
	}

	public function post($action, $query = array()) {
		if (is_array($query)) {
			foreach ($query as $key => $val) {
				if ($val{0} != '@') {
					$encode_key = urlencode($key);

					if ($encode_key != $key) {
						unset($query[$key]);
					}

					$query[$encode_key] = urlencode($val);
				}
			}
		}

		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_URL, $this->url [$action]);
		curl_setopt($this->ch, CURLOPT_REFERER, $this->referer [$action]);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $query);

		$this->_requrest ();

		return $this;
	}

	public function get($action, $query = array()) {
		$url = $this->url [$action];

		if (! empty ( $query )) {
			$url .= strpos ( $url, '?' ) === false ? '?' : '&';
			$url .= is_array ( $query ) ? http_build_query ( $query ) : $query;
		}

		curl_setopt ( $this->ch, CURLOPT_URL, $url );
		curl_setopt ( $this->ch, CURLOPT_REFERER, $this->referer [$action] );

		$this->_requrest ();

		return $this;
	}

	public function put($action, $query = array()) {
		curl_setopt ( $this->ch, CURLOPT_CUSTOMREQUEST, 'PUT' );

		return $this->post ( $action, $query );
	}

	public function delete($action, $query = array()) {
		curl_setopt ( $this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE' );

		return $this->post ( $action, $query );
	}

	public function head($action, $query = array()) {
		curl_setopt ( $this->ch, CURLOPT_CUSTOMREQUEST, 'HEAD' );

		return $this->post ( $action, $query );
	}

	public function options($action, $query = array()) {
		curl_setopt ( $this->ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS' );

		return $this->post ( $action, $query );
	}

	public function trace($action, $query = array()) {
		curl_setopt ( $this->ch, CURLOPT_CUSTOMREQUEST, 'TRACE' );

		return $this->post ( $action, $query );
	}

	public function follow_location() {
		preg_match ('#Location:\s*(.+)#i', $this->header (), $match);

		if (isset($match [1])) {
			$this->open('auto_location_gateway', $match [1], $this->effectiveurl());

			$this->get('auto_location_gateway')->follow_location();
		}

		return $this;
	}

	public function header() {
		return $this->header;
	}

	public function body() {
		return $this->body;
	}

	public function effectiveurl() {
		return curl_getinfo ( $this->ch, CURLINFO_EFFECTIVE_URL );
	}

	public function http_code() {
		return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
	}

	public function content_type() {
		return curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
	}

	private function _requrest() {
		$response = curl_exec ( $this->ch );
		$errno = curl_errno ( $this->ch );
		if ($errno > 0) {
			$this->retMsg('error_exec_failed', $this->msg['error_exec_failed']);
		}
		$header_size = curl_getinfo ( $this->ch, CURLINFO_HEADER_SIZE );

		$this->header = substr ( $response, 0, $header_size );
		$this->body = substr ( $response, $header_size );
	}

	public function __destruct() {
		$this->close();
	}
	
	/**
	 * 检查是否成功
	 * 
	 */
    public function isSuccess() {
        return ($this->http_code() >= 200 && $this->http_code() < 300);
    }

	/**
	 * 返回提示信息
	 * @param $no String 提示信息号
	 * @param $msg String 提示信息
	 * @return Void
	 */
	public function retMsg($no, $msg) {
		$ret = array(
			'no' => $no,
			'msg' => $msg,
		);
		throw new Exception(Common::t(json_encode($ret)));	
		//throw new Exception(json_encode($ret));
	}
}
