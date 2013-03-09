<?php
/*
 * @description: 日志处理类
 * @author: Qiang Xue <qiang.xue@gmail.com>
 * @update: zhouweiwei
 * @date: 2010-05-18
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');

class Helper_Log {
	const LEVEL_TRACE='trace';
	const LEVEL_WARNING='warning';
	const LEVEL_ERROR='error';
	const LEVEL_INFO='info';
	const LEVEL_PROFILE='profile';
	const LEVEL_EXCEPTION = 'exception';

	public static $_instance;

	public static function factory() {
		$args = func_get_args();
		if(empty($args)) {
			throw new Exception('args is empty!');
		}
		$class = $args[0];
		$param = $args[1];
		if(!isset(self::$_instance[(string)$args])) {
			self::$_instance[(string)$args] = new $args[0]($param[0], $param[1]);
		}
		return self::$_instance[(string)$args];

    }
}

abstract class Helper_AbstractLog {
	public $levels='';

	public $categories='';

	public $logs;

	public function __construct() {}

	public function log($message, $level='info', $category='application') {
		$this->logs[] = array($message,$level,$category,microtime(true));
	}

	public function getLogs($levels='', $categories='') {
		$this->levels=preg_split('/[\s,]+/',strtolower($levels),-1,PREG_SPLIT_NO_EMPTY);
		$this->categories=preg_split('/[\s,]+/',strtolower($categories),-1,PREG_SPLIT_NO_EMPTY);
		if(empty($levels) && empty($categories))
			return $this->logs;
		else if(empty($levels))
			return array_values(array_filter(array_filter($this->logs,array($this,'filterByCategory'))));
		else if(empty($categories))
			return array_values(array_filter(array_filter($this->logs,array($this,'filterByLevel'))));
		else
		{
			$ret=array_values(array_filter(array_filter($this->logs,array($this,'filterByLevel'))));
			return array_values(array_filter(array_filter($ret,array($this,'filterByCategory'))));
		}
	}

	private function filterByCategory($value) {
		foreach($this->categories as $category) {
			$cat=strtolower($value[2]);
			if($cat===$category || (($c=rtrim($category,'.*'))!==$category && strpos($cat,$c)===0))
				return $value;
		}
		return false;
	}

	private function filterByLevel($value) {
		return in_array(strtolower($value[1]),$this->levels)?$value:false;
	}

	public function process() {
		return $this->processLogs($this->logs);
	}

	protected function formatLogMessage($message, $level, $category, $time) {
		return @date('Y/m/d H:i:s',$time)." [$level] [$category] $message\n";
	}


	public function collectLogs($logger, $processLogs=false) {
		$logs=$logger->getLogs($this->levels,$this->categories);
		$this->logs=empty($this->logs) ? $logs : array_merge($this->logs,$logs);
		if($processLogs && !empty($this->logs)) {
			$this->processLogs($this->logs);
		}
	}

	abstract protected function processLogs($logs);
}

class Helper_FileLog extends Helper_AbstractLog {

	private $maxFileSize = 1024; // in KB

	private $maxLogFiles=5;

	private $logPath;

	private $logFile='application.log';


	public function __construct($logPath) {
		parent::__construct();

		if($this->getLogPath()===null)
			$this->setLogPath($logPath);

	}

	public function getLogPath() {
		return $this->logPath;
	}

	public function setLogPath($value) {
		$this->reMkdir($value);
		$this->logPath = realpath($value);
	}

	private function reMkdir($dir, $mode=0700) {
		is_dir(dirname($dir)) || $this->reMkdir(dirname($dir), $mode);
    	return is_dir($dir) || mkdir($dir, $mode);
	}

	public function getLogFile() {
		return $this->logFile;
	}


	public function setLogFile($value) {
		$this->logFile=$value;
	}

	public function getMaxFileSize() {
		return $this->maxFileSize;
	}

	public function setMaxFileSize($value) {
		if(($this->maxFileSize=(int)$value)<1)
			$this->maxFileSize=1;
	}

	public function getMaxLogFiles() {
		return $this->maxLogFiles;
	}

	public function setMaxLogFiles($value) {
		if(($this->maxLogFiles=(int)$value)<1)
			$this->maxLogFiles=1;
	}

	public function processLogs($logs) {
		$logFile=$this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();
		if(@filesize($logFile)>$this->getMaxFileSize()*1024)
			$this->rotateFiles();
		foreach($logs as $log) {
			error_log($this->formatLogMessage($log[0],$log[1],$log[2],$log[3]),3,$logFile);
		}
		return true;
	}

	public function rotateFiles() {
		$file=$this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();
		$max=$this->getMaxLogFiles();
		for($i=$max;$i>0;--$i)
		{
			$rotateFile=$file.'.'.$i;
			if(is_file($rotateFile))
			{
				if($i===$max)
					unlink($rotateFile);
				else
					rename($rotateFile,$file.'.'.($i+1));
			}
		}
		if(is_file($file))
			rename($file, $file.'.1');
	}
}

class Helper_MysqlLog extends Helper_AbstractLog {

	public $logTableName='log';

	public $autoCreateLogTable=true;

	private $db;

	public function __construct($db, $logTableName=null) {
		parent::__construct();
		$this->db = $db;
		$logTableName && $this->setLogTableName($logTableName);
		if($this->autoCreateLogTable) {
			$sql="DELETE FROM {$this->logTableName} WHERE 0=1";
			if(!$this->db->execute($sql)) {
				$this->createLogTable($this->logTableName);
			}
		}
	}

	public function getLogTableName() {
		return $this->logTableName;
	}

	public function setLogTableName($tableName) {
		 $this->logTableName = $tableName;
	}

	public function getAutoCreateLogTable () {
		return $this->autoCreateLogTable;
	}

	public function setAutoCreateLogTable($value) {
		$this->autoCreateLogTable = $value;
	}

	protected function createLogTable($tableName) {
		$sql="
			CREATE TABLE $tableName
			(
				id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
				level VARCHAR(128),
				category VARCHAR(128),
				logtime INTEGER,
				message TEXT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8";

		$this->db->execute($sql);
	}


	public function processLogs($logs) {
		$sql="
			INSERT INTO {$this->logTableName}
			(level, category, logtime, message) VALUES";
		$comma = '';
		foreach($logs as $log) {
			$sql .= $comma."('$log[1]', '$log[2]', '$log[3]', '$log[0]')";
			$comma = ', ';
		}
		return $this->db->execute($sql);
	}
}
