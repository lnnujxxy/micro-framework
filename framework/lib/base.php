<?php
/*
 * 基础类
 *
 * @package: libs
 * @author: zhouweiwei
 * @date: 2010-5-13
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');
class Lib_Base {
	/**
	 * 配置信息属性
	 * @var Array
	 */
	protected $config;

	/**
	 * 构造函数
	 *
	 * @return Void
	 */
	public function __construct() {
		$this->config = Common::getConfig();
		if(empty($this->config)) {
			throw new Exception(Common::t(__CLASS__.' config is empty!'));
		}
	}

	/**
	 * 魔术设置函数
	 *
	 * @param $key String 设置的属性
	 * @param $value Mixed 属性的值
	 * @return Void
	 */
    public function __set($key ,$value) {
        if(property_exists($this, $key)) {
			$this->$key = $value;
        }
    }

	/**
	 * 魔术获取函数
	 *
	 * @param $key String 获得的属性
	 * @return false 获取失败 $this->$key 获得成功
	 */
    public function __get($key) {
        if(isset($this->$key)) {
			return $this->$key;
        }
		return false;
    }

	public function __call($className, $methodName) {
        throw new Exception(Common::t("$className Call to undefined method: $methodName()"));
    }

	protected function __clone() {}

}
