<?php
/*
 * @description: cookie²Ù×÷Àà
 * @author: network
 * @update: zhouweiwei
 * @date: 2010-05-18
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');

Class Helper_Cookie  {
    private static $cookieName = null;
    private static $cookieData = null;
    private static $cookieKey = null;
    private static $cookieExpire = 0;
    private static $cookiePath = '/';
    private static $cookieDomain = null;
    private static $cookieSecure = false;
    private static $cookieHTTPOnly = false;

    public static function get($cookieName, $isCrypt=false) {
        if(is_null($cookieName)) {
			throw new Exception(Common::t('Cookie name was null'));
        }
        if(!isset($_COOKIE[$cookieName])) {
			return;
        }
		return $isCrypt ? Helper_Crypt::secure($_COOKIE[$cookieName], 'decrypt') : $_COOKIE[$cookieName];
    }

    public static function set($cookieName, $value, $isCrypt=false, $expire=0, $path='/', $domain=null, $secure=false, $httponly=false) {
        self::setCookieName($cookieName);
		self::setCookieValue($value, $isCrypt);
		self::setExpire($expire);
		self::setPath($path);
		self::setDomain($domain);
		self::setSecure($secure);
		self::setHTTPOnly($httponly);

        return setcookie(
            self::$cookieName,
            self::$cookieData,
            self::$cookieExpire,
            self::$cookiePath,
            self::$cookieDomain,
            self::$cookieSecure,
            self::$cookieHTTPOnly
        );
    }

    public function delete($cookieName, $path='/', $domain=null) {
        if(is_null($cookieName)) {
            throw new Exception(Common::t('Cookie name was null'));
        }
		self::setPath($path);
		self::setDomain($domain);

        return setcookie(
            $cookieName,
            null,
            (time()-3600),
            self::$cookiePath,
            self::$cookieDomain
        );
    }

    /**
     * Set cookie name
     * @access public
     * @param string $cookieName cookie name
     * @return void
     */
    private static function setCookieName($cookieName=null) {
        if(is_null($cookieName)) {
            throw new Exception(Common::t('Cookie name was null'));
        }
		self::$cookieName = $cookieName;
    }

    /**
     * Set cookie value
     * @access public
     * @param string $value cookie value
     * @return void
     */
    private static function setCookieValue($value=null, $isCrypt=false) {
        if(is_null($value)) {
            throw new Exception(Common::t('Cookie value was empty'));
        }

		if(is_array($value)) {
			$value = serialize($value);
		}

		$data = $isCrypt ? Helper_Crypt::secure($value, 'encrypt') : $value;
		$len = (function_exists('mb_strlen')?mb_strlen($data):strlen($data));
		if($len > 4096) {
			throw new Exception(Common::t('Cookie data exceeds 4kb'));
		}
		self::$cookieData = $data;
		unset($data, $value);
    }

    /**
     * Set expire time
     * @access public
     * @param string $expire +1 week, etc.
     * @return void
     */
    private static function setExpire($expire=0) {
        $pre = substr($expire, 0, 1);
        if(in_array($pre, array('+', '-'))) {
            self::$cookieExpire = strtotime($expire);
        } else {
            self::$cookieExpire = 0;
        }
    }

    /**
     * Set path of the cookie
     * @access public
     * @param string $path
	 * @return void
     */
    private static function setPath($path='/') {
        self::$cookiePath = $path;
    }

    /**
     * Set the domain for the cookie
     * @access public
     * @param string $domain
	 * @return void
     */
    private static function setDomain($domain=null) {
        if(!is_null($domain)) {
            self::$cookieDomain = $domain;
        } else {
			self::$cookieDomain = self::getRootDomain();
		}
    }

    /**
     * Whether the cookie is only available under HTTPS
     * @access public
     * @param bool $secure true/false
	 * @return void
     */
    private static function setSecure($secure=false) {
        self::$cookieSecure = (bool)$secure;
    }

    /**
     * HTTPOnly flag, not yet fully supported by all browsers
     * @access public
     * @param bool $httponly yes/no
     * @return object $this
     */
    private static function setHTTPOnly($httponly=false) {
        self::$cookieHTTPOnly = (bool)$httponly;
    }

    /**
     * Jenky bit to retrieve root domain if not supplied
     * @access private
     * @return string Le Domain
     */
    private static function getRootDomain() {
        $host = $_SERVER['HTTP_HOST'];
        $parts = explode('.', $host);
        if(count($parts)>1) {
            $tld = array_pop($parts);
            $domain = array_pop($parts).'.'.$tld;
        } else {
            $domain = array_pop($parts);
        }
        return '.'.$domain;
    }
}
?>

