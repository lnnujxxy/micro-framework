<?php
/*
 * @description: 数据库类
 * @author: zhouweiwei
 * @date: 2010-5-13
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');

class Lib_Mysql extends Lib_Base {
	/**
	 * @var Array 主库配置
	 */
	private $masterConf = array();
	/**
	 * @var Array 从库配置
	 */
	private $slaveConf = array();
	/**
	 * @var Bool 是否持久连接
	 */
	private $isPersistent = false;
	/**
	 * @var Object 主库对象
	 */
	private $masterDB = null;
	/**
	 * @var Object 从库对象
	 */
	private $slaveDB = null;
	/**
	 * @var Object PDOStatement对象
	 */
	private $stmt;
	/**
	 * @var String 数据库字符集
	 */
	private $dbCharset = 'utf8';

	/**
	 * 构建函数
	 * @param $masterConf Array 主库数组
	 * @param $slaveConf Array 从库数组
	 * @param $isPersistent Bool 是否持久连接
	 *
	 */
	public function __construct($masterConf, $slaveConf=null, $isPersistent=false) {
		if(!class_exists('PDO', false)) {
			$ret = array(
				'no' => 'error_pdo_not_support',
				'msg' => 'PDO不支持',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}
		if(empty($masterConf) || !is_array($masterConf)) {
			$ret = array(
				'no' => 'error_config_param',
				'msg' => '配置参数有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		$this->masterConf = $masterConf;
		if(!empty($slaveConf) && is_array($slaveConf)) {
			$this->slaveConf = $slaveConf;
		} else {
			$this->slaveConf = array($masterConf);
		}

		$this->isPersistent = $isPersistent;
	}

	/**
	 * 生成主库对象
	 *
	 */
	public function &getMasterDB() {
		if(isset($this->masterDB) && is_object($this->masterDB)) {
            return $this->masterDB;
        }

		$this->masterDB = &$this->connect($this->masterConf['host'], $this->masterConf['user'], $this->masterConf['pass'], $this->masterConf['dbname'], $this->masterConf['port']);

		if(!isset($this->masterDB) || !is_object($this->masterDB)) {
			$ret = array(
				'no' => 'error_masterdb_init_fail',
				'msg' => '主库实例化失败',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		return $this->masterDB;
	}

	/**
	 * 生成从库对象
	 *
	 */
	public function &getSlaveDB() {
		if(!empty($this->slaveDB) && is_array($this->slaveDB)) {
			$sDB = $this->getSlaveSingleDB($this->slaveDB);
			return $sDB;
		}

		if(!empty($this->slaveConf)) {
			foreach($this->slaveConf as $conf) {
				$db = &$this->connect($conf['host'], $conf['user'], $conf['pass'], $conf['dbname'], $conf['port']);
				if(isset($db) && is_object($db)) {
					$this->slaveDB[] = $db;
				}
			}
			$sDB = $this->getSlaveSingleDB($this->slaveDB);
			return $sDB;
		}

		if(!isset($this->slaveDB) || !is_object($this->slaveDB)) {
			$ret = array(
				'no' => 'error_slavedb_init_fail',
				'msg' => '从库实例化失败',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}
	}

	/**
	 * 随机获取一个从库对象
	 *
	 */
	private function getSlaveSingleDB(&$slaveDB) {
		if(empty($slaveDB) || !is_array($slaveDB)) {
			$ret = array(
				'no' => 'error_slaveobj_fail',
				'msg' => '从库对象数组有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		$key = '';
		$key = array_rand($slaveDB);
		if(isset($slaveDB[$key]) && is_object($slaveDB[$key])) {
			return $slaveDB[$key];
		}
	}

	/**
	 * 建立数据库连接
	 * @param $dbHost String 主机
	 * @param $dbUser String 用户名
	 * @param $dbPwd String 密码
	 * @param $dbName String 数据库名
	 * @param $port Int 端口号
	 * @param $dbCharset String 数据库字符集
	 * @return Resource 数据库句柄
	 */
	private function &connect($dbHost, $dbUser, $dbPwd, $dbName, $port=3306, $dbCharset=null) {
		try {
			is_null($dbCharset) && $dbCharset = $this->dbCharset;
			if($this->isPersistent) {
				$opts = array(
					PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $dbCharset",
					PDO::ATTR_PERSISTENT => true
				);
			} else {
				$opts = array(
					PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $dbCharset",
				);
			}
			$conn = new PDO("mysql:host=$dbHost;port=$port;dbname=$dbName", $dbUser, $dbPwd, $opts);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $conn;
		} catch (PDOException $e) {
			$ret = array(
				'no' => 'error_db_connect',
				'msg' => $e->getMessage(),
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}
	}

	/**
	 * 执行SQL
	 * @param $sql String SQL语句
	 * @param $params Array SQL参数
	 * @return Mixed
	 */
	public function query($sql, $params = array()) {
		if(empty($sql) || !is_string($sql) || !is_array($params)) {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_params',
				'msg' => '方法'.__METHOD__.'参数有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		if(preg_match("/^select/i", trim($sql))) {
			$DB = &$this->getSlaveDB();
		} else {
			$DB = &$this->getMasterDB();
		}

		try {
			$sql = $this->generateSql($sql, $params);
			Common::firePHP($sql, 'sql');
			$this->stmt = $DB->prepare($sql);
			$this->stmt->execute();
		} catch(Exception $e) {
			$ret = array(
				'no' => 'error_db_query',
				'msg' => $e->getMessage(),
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}
	}

	/**
	 * 获得最后插入ID
	 *
	 */
	public function lastInsertId() {
		$DB = &$this->getMasterDB();
		return $DB->lastInsertId();
	}


	/**
	 * 拼接生成安全的SQL语句
	 * @param $sql String
	 * @param $params Array
	 * @return String
	 * eg $sql = "SELECT * FROM test WHERE uid='%s'", $params = array(1);
	 */
	private function generateSql($sql, $params = array()) {
		if($params) {
			$args = array_map('mysql_escape_string', $params);
			array_unshift($args, $sql);
			return call_user_func_array('sprintf', $args);
		}
		return $sql;
	}

	/**
	 * 获取一组数据
	 * @param $sql String SQL语句
	 * @param $params Array SQL 参数
	 * @param $mode Int 参见手册 PDOStatement->fetch 中支持的
	 * @param $classname String 参见手册 PDOStatement->fetch 中支持的
	 * @param $args Array 参见手册 PDOStatement->fetch 中支持的
	 * @return Mixed
	 */
	public function getAll($sql, $params=array(), $mode=PDO::FETCH_ASSOC, $classname=null, $args=null) {
		if(empty($sql) || !is_string($sql) || !is_array($params)) {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_params',
				'msg' => '方法'.__METHOD__.'参数有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		$this->query($sql, $params);
		$this->stmt->setFetchMode($mode, $classname, $args);
		$data = $this->stmt->fetchAll();

		if($this->stmt->errorCode() !== '00000') {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_ret',
				'msg' => "方法".__METHOD__."返回有误\tSQL:$sql\terrno:".$this->stmt->errorInfo[1]."\terrmsg:".$this->stmt->errorInfo[2],
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}
		if($_GET['mysql'] === 'debug') { //调试输出
			Common::firePHP($data, 'res');
		}
		return $data;
	}

	/**
	 * 获取一行数据
	 * @param $sql String SQL语句
	 * @param $params Array SQL 参数
	 * @param $mode Int 参见手册 PDOStatement->fetch 中支持的
	 * @param $classname String 参见手册 PDOStatement->fetch 中支持的
	 * @param $args Array 参见手册 PDOStatement->fetch 中支持的
	 * @return Mixed
	 */
	public function getRow($sql, $params=array(), $mode=PDO::FETCH_ASSOC, $classname=null, $args=null) {
		if(empty($sql) || !is_string($sql) || !is_array($params)) {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_params',
				'msg' => '方法'.__METHOD__.'参数有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		$this->query($sql, $params);
		$this->stmt->setFetchMode($mode, $classname, $args);
		$data = $this->stmt->fetch();

		if($this->stmt->errorCode() !== '00000') {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_ret',
				'msg' => "方法".__METHOD__."返回有误\tSQL:$sql\terrno:".$this->stmt->errorInfo[1]."\terrmsg:".$this->stmt->errorInfo[2],
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}
		if($_GET['mysql'] === 'debug') { //调试输出
			Common::firePHP($data, 'res');
		}

		return $data;
	}

	/**
	 * 获取一列数据
	 * @param $sql String SQL语句
	 * @param $params Array SQL 参数
	 */
	public function getColumn($sql, $params=array()) {
		if(empty($sql) || !is_string($sql) || !is_array($params)) {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_params',
				'msg' => '方法'.__METHOD__.'参数有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		$this->query($sql, $params);
		$data = $this->stmt->fetchColumn();
		if($this->stmt->errorCode() !== '00000') {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_ret',
				'msg' => "方法".__METHOD__."返回有误\tSQL:$sql\terrno:".$this->stmt->errorInfo[1]."\terrmsg:".$this->stmt->errorInfo[2],
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}
		if($_GET['mysql'] === 'debug') { //调试输出
			Common::firePHP($data, 'res');
		}

		return $data;
	}

	/**
	 * 根据条件获取SQL
	 * @param $table String 表名
	 * @param $field String 字段名 eg: "id, name"|"*"
	 * @param $where Mixed where
	 * @param $order Mixed order
	 * @param $limit Mixed limit
	 * @return $sql
	 */
	public function getSql($table, $field='*', $where=null, $order=null, $limit=null) {
		if(empty($table) || !is_string($table) || !is_string($field)) {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_params',
				'msg' => '方法'.__METHOD__.'参数有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		$sql = "SELECT $field FROM `$table`";
		if(!empty($where)) {
			$sql .= $this->getWhere($where);
		}
        if(!empty($order)) {
			$sql .= $this->getOrderby($order);
		}
		if(!empty($limit)) {
			$sql .= $this->getLimit($limit);
		}
		return $sql;
	}

	/**
	 * 根据条件获取数据
	 * @param $table String 表名
	 * @param $field String 字段名 eg: "id, name"|"*"
	 * @param $where Mixed where
	 * @param $order Mixed order
	 * @param $limit Mixed limit
	 * @param $single Bool true 获取一行 false 获取一组
	 * @return Mixed
	 */
	public function getRecord($table, $field='*', $where=null, $order=null, $limit=null, $single=false) {
		if(empty($table) || !is_string($table) || !is_string($field)) {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_params',
				'msg' => '方法'.__METHOD__.'参数有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		$sql = "SELECT $field FROM `$table`";
		if(!empty($where)) {
			$sql .= $this->getWhere($where);
		}
        if(!empty($order)) {
			$sql .= $this->getOrderby($order);
		}
		if(!empty($limit)) {
			$sql .= $this->getLimit($limit);
		}
        if($single) {
            return $this->getRow($sql);
        }
        return $this->getAll($sql);
	}

	/**
	 * 获得条件
	 * @param $where Mixed
	 * @return String
	 */
	private function getWhere($where) {
		$whr = " WHERE ";
		if (is_string($where)) {
        	$whr .= $where;
        } elseif (is_array($where)) {
			$comma = "";
	        foreach ($where as $key => $value) {
	            $whr .= $comma." `$key`='".Helper_Input::filterSql($value)."' ";
				$comma = " AND ";
	        }
        } else {
        	 $whr = ' ';
        }
		return $whr;
	}

	/**
	 * 获得Orderby
	 * @param $orderBy Mixed
	 * @return String
	 */
	private function getOrderby($orderBy) {
		$order = " ORDER BY ";
		if(is_string($orderBy)) {
			$order .= $orderBy;
		} elseif(is_array($orderBy)) {
			$comma = "";
			foreach($orderBy as $key=>$value) {
				$order .= $comma." `$key` $value ";
				$comma = ", ";
			}
		} else {
			$order = ' ';
		}
		return $order;
	}

	/**
	 * 获得Limit
	 * @param $limit Mixed
	 * @return String
	 */
	private function getLimit($limit) {
		$limitSql = " LIMIT ";
		if(is_string($limit)) {
			$limitSql .= $limit;
		} elseif(is_array($limit) && !empty($limit)) {
			 $startPos = intval(array_shift($limit));
			 $offset = intval(array_shift($limit));
			 $limitSql .= " $startPos, $offset ";
		} else {
			$limitSql = ' ';
		}
		return $limitSql;
	}

	/**
	 * 高效分页
	 * @param $table String 表名
	 * @param $count Int 记录总数
	 * @param $page Int 页码
	 * @param $keyField String 排序字段
	 * @param $priKey Strign 主键字段
	 * @param $ascDesc Bool 正序或倒序 true 正序 false倒序
	 * @param $pageSize Int 单页数
	 * @return String
	 */
	public function pageSql($table, $count, $page, $keyField, $where=null, $priKey ='id', $ascDesc = true, $pageSize=20) {
		if(!is_numeric($page) || $page < 1) {
			$page = 1;
		}
		$startPos = $realPage = 0;
		empty($pageSize) && $pageSize = 20;
		$realPage = ceil($count/$pageSize);

		$sql = '';
		if($realPage > 100 && $page > $realPage/10) {
			$tmpSql = $orderBy = $operate = '';
			$orderBy = $ascDesc ? 'ASC':'DESC';
			$operate = $orderBy == 'ASC' ? '>=' : '<=';
			$startPos = ($page-1)*$pageSize;
			$tmpSql = "SELECT $priKey FROM $table ".$this->getWhere($where)."ORDER BY $keyField $orderBy LIMIT $startPos, 1";
			//echo $tmpSql."\n";
			//$startId = $this->getColumn($tmpSql);
			$startSql = " $priKey $operate ($tmpSql) ";

			$startPos = $page*$pageSize;
			$tmpSql = "SELECT $priKey FROM $table ".$this->getWhere($where)."ORDER BY $keyField $orderBy LIMIT $startPos, 1";
			//echo $tmpSql."\n";
			if($orderBy == 'ASC') {
				$endId = $this->getColumn($tmpSql)-1;
			} else {
				$endId = $this->getColumn($tmpSql)+1;
			}
			$operate = $orderBy == 'ASC' ? '<=' : '>=';
			$endSql = " $priKey $operate $endId ";

			$ascDesc = !$ascDesc;
			$orderBy = $ascDesc ? 'ASC':'DESC';
			$sql = "WHERE $startSql AND $endSql ORDER BY $keyField $orderBy LIMIT $pageSize";
		} else {
			$startPos = ($page-1)*$pageSize;
			$orderBy = $ascDesc ? 'ASC':'DESC';
			$sql = $this->getWhere($where)." ORDER BY $keyField $orderBy LIMIT $startPos, $pageSize";
		}
		return $sql;
	}

	/**
	 * 插入数据
	 * @param $table String
	 * @param $insertData Array
	 * @param $action String
	 * eg: $insertData = array(
			'username' => 1,
			'age'	=> 2,
			'uid'	=> 1,
		);
	 * @return Bool
	 */
	public function add($table, $insertData, $action="INSERT") {
		if(empty($table) || !is_string($table) || empty($insertData) || !in_array(strtoupper($action), array("INSERT", "REPLACE"))) {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_params',
				'msg' => '方法'.__METHOD__.'参数有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		$params = array();
		$sql = "$action INTO `$table` SET ";
		$comma = "";
		foreach($insertData as $key=>$value) {
			$sql .= $comma."`$key` = '%s'";
			$comma = ", ";
			$params[] = $value;
		}
		$this->query($sql, $params);
		if($this->stmt->errorCode() !== '00000') {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_fail',
				'msg' => "方法".__METHOD__."失败\tSQL:$sql\terrno:".$this->stmt->errorInfo[1]."\terrmsg:".$this->stmt->errorInfo[2],
			);
			Common::writeLog(json_encode($ret), 'error', 'mysql');
			return false;
		}
		return true;
	}

	/**
	 * 更新数据
	 * @param $table String
	 * @param $updateData Array
	 * @param $where Mixed
	 * @return Bool
	 */
	public function update($table, $updateData, $where) {
		if(empty($table) || !is_string($table) || empty($updateData) || empty($where)) {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_params',
				'msg' => '方法'.__METHOD__.'参数有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		$params = array();
		$sql = "UPDATE `$table` SET ";
		$comma = "";
		foreach($updateData as $key=>$value) {
			$sql .= $comma."`$key` = '%s'";
			$comma = ", ";
			$params[] = $value;
		}
		$sql .= $this->getWhere($where);
		$this->query($sql, $params);
		if($this->stmt->errorCode() !== '00000') {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_fail',
				'msg' => "方法".__METHOD__."失败\tSQL:$sql\terrno:".$this->stmt->errorInfo[1]."\terrmsg:".$this->stmt->errorInfo[2],
			);
			Common::writeLog(json_encode($ret), 'error', 'mysql');
			return false;
		}
		return true;
	}

	/**
	 * 删除数据
	 * @param $table String
	 * @param $where Mixed
	 * @return Bool
	 */
	public function delete($table, $where) {
		if(empty($table) || !is_string($table) || empty($where)) {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_params',
				'msg' => '方法'.__METHOD__.'参数有误',
			);
			throw new Exception(Common::t(json_encode($ret), 'error', 'mysql'));
		}

		$sql = "DELETE FROM `$table`".$this->getWhere($where);
		$this->query($sql);
		if($this->stmt->errorCode() !== '00000') {
			$ret = array(
				'no' => 'error_'.__METHOD__.'_fail',
				'msg' => "方法".__METHOD__."失败\tSQL:$sql\terrno:".$this->stmt->errorInfo[1]."\terrmsg:".$this->stmt->errorInfo[2],
			);
			Common::writeLog(json_encode($ret), 'error', 'mysql');
			return false;
		}
		return true;
	}
}


$database = array(
	'master' => array(
		'host'   => '127.0.0.1',
		'dbname' => 'vipbook',
		'user'	 => 'root',
		'pass'	 => 'root',
		'port'   => '3306',
		'charset'=> 'utf8',
	),
	'slave' => array(
		array(
			'host'   => '127.0.0.1',
			'dbname' => 'vipbook',
			'user'	 => 'root',
			'pass'	 => 'root',
			'port'   => '3306',
			'charset'=> 'utf8',
		),
		array(
			'host'   => '127.0.0.1',
			'dbname' => 'vipbook',
			'user'	 => 'root',
			'pass'	 => 'root',
			'port'   => '3306',
			'charset'=> 'utf8',
		),
	),
	'tablepre' => '',
);
$starttime = microtime(true);
$db = new Lib_Mysql($database['master'], $database['slave']);
$tmpSql = $db->pageSql('books', 9849, 41, 'book_id', null, 'book_id', true);

/*
$sql = "SELECT * FROM test";
var_dump($db->getRow($sql, array(), PDO::FETCH_INTO));
$sql = "SELECT * FROM test WHERE id > %d";
var_dump($db->getAll($sql, array(5)));
$sql = "INSERT INTO test(num, string) VALUES(2, 'test')";
var_dump($db->query($sql));
var_dump($db->lastInsertId());

$data = array(
	'num' => 100,
	'string' => 'string',
);
var_dump($db->add('test', $data));
*/