<?php
namespace Pepper\Framework\Dao;

use Pepper\Framework\Lib\BizException;
use Pepper\Framework\Lib\Logger;
use Pepper\Lib\SimpleConfig;
use Pepper\QFrameDB\QFrameDB;

/**
 * Class DAOProxy
 * @package Pepper\Framework\Dao
 *
 * @method getInsertId()
 * @method execute($sql, $params = [], $is_open_safe = true)
 * @method query($sql, $params = [], $is_open_safe = true)
 * @method getOne($sql, $params = [], $safe = true)
 * @method getRow($sql, $params = [], $safe = true)
 * @method getAll($sql, $params = [], $safe = true)
 * @method insert($table, $record)
 * @method replace($table, $record)
 * @method update($table, $record, $condition, $params)
 * @method delete($table, $condition, $params)
 * @method setWaitTimeOut($seconds)
 * @method setAutoReconnect($autoReconnect)
 * @method setDebug($debug)
 * @method setFetchMode($fetch_type = \PDO:: FETCH_ASSOC)
 * @method startTrans()
 * @method commit()
 * @method rollback()
 * @method close()
 * @method getBindedSql($sql, $params = [])
 */
class DAOProxy
{
    private static $db;
    protected $table = '';
    protected $splitid = 0;

    public function __construct($splitid = 0) {
        $this->setSplitId($splitid);
    }

    public function __call($name, $arguments) {
        $table_map = SimpleConfig::get('TABLE_MAP');
        $table_cluster = isset($table_map[$this->table]) ? $table_map[$this->table] : 'default';
        $db_conf = SimpleConfig::get('DB_CONF');

        if (!isset(self::$db[$table_cluster])) {
            self::$db[$table_cluster] = QFrameDB::getInstance($this->_getDBConf($db_conf[$table_cluster]));
        }
        try {
            return call_user_func_array(array(self::$db[$table_cluster], $name), $arguments);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $dbConf = $this->_getDBConf($db_conf[$table_cluster]);
            $dbConfJson = json_encode($dbConf);
            $params = array(
                'table' => $this->getTableName(),
                'name' => $name,
                'arguments' => $arguments,
            );
            Logger::warning("db-exception-msg: {$msg}, db-config: {$dbConfJson}, params:" . json_encode($params));
            throw new BizException(ERROR_SYS_DB_SQL, array('code'=>$e->getCode(), 'message'=>$msg));
        }
    }

    private function _getDBConf($db_conf) {
        return $this->_getShardConf($db_conf);
    }

    private function _getShardConf($conf_list) {
        $range = $this->_getShardRange();
        $index = $this->_getShardIndex();
        foreach ($range as $record) {
            if ($index >= $record['min'] && $index <= $record['max']) {
                return $conf_list[$record['confid']];
            }
        }

        return false;
    }

    private function _getShardRange() {
        return SimpleConfig::get('RANGE_CONF');
    }

    private function _getShardIndex() {
        return abs(substr($this->splitid, -3) % $this->_getShard());
    }

    private function _getShard() {
        $table_conf = SimpleConfig::get('TABLE_CONF');

        return isset($table_conf[$this->table]['shard']) ? $table_conf[$this->table]['shard'] : 1;
    }

    protected function getSplitId() {
        return $this->splitid;
    }

    protected function setSplitId($splitid) {
        $this->splitid = $splitid;
    }

    protected function setTableName($table) {
        $this->table = $table;
    }

    protected function getTableName() {
        return $this->_getShard() == 1 ? $this->table : $this->table . '_' . $this->_getShardIndex();
    }
}
