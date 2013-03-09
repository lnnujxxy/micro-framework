<?php
/*
 * @description: 模块类
 * @update: zhouweiwei
 * @date: 2010-5-13
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');
class Lib_Model extends Lib_Base {
    protected $db;
	protected $cache;

	protected $tableName; //表名
	protected $cacheKeyPrefix; //缓存keyprefix

    public function __construct() {
		parent::__construct();
		$this->db = $this->getDb();
		//$this->cache = $this->getCache();
    }

	/**
	 * 实例化数据库对象
	 *
	 */
	public function getDb() {
		$key = Common::generateKey($this->config['database']);
		if($this->db = Common::registry($key)) {
			return $this->db;
		}

		$this->db = new Lib_Mysql($this->config['database']['master'], $this->config['database']['slave']);
		Common::register($key, $this->db);
		return $this->db;
	}

	/**
	 * 实例化缓存对象
	 *
	 */
	public function getCache() {
		$key = Common::generateKey($this->config['cache']);

		if($this->cache = Common::registry($key)) {
			return $this->cache;
		}

		$this->cache = Lib_Cache::factory($this->config['cache']['type'], $this->config['cache']['param']);
		Common::register($key, $this->cache);
		return $this->cache;
	}

	public function getKey($params) {
		return Common::generateKey($params);
	}

	public function getTable() {
		return $this->config['database']['tablepre'].$this->tableName;
	}
}
?>
