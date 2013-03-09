<?php
/*
 * @description: SESSION 处理类
 * @author: zhouweiwei
 * @date: 2010-5-13
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');

class Helper_Session {
	public static $_instance;

	public function getInstance() {
		if(!self::$_instance instanceof self) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

    public function __construct () {
       session_start();
    }

    public function set($key, $value) {
		$_SESSION[$key] = $value;
    }

	public function get($key = null) {
        return ($key === null) ? $_SESSION : $_SESSION[$key];
    }

    public function remove($key) {
        return session_unregister($key);
    }

    public function flush() {
        return session_destroy();
    }

    public function isRegistered($key) {
        return session_is_registered($key);
    }

    public function id() {
        return session_id();
    }

	public function setName() {
		return session_name();
	}
}
?>