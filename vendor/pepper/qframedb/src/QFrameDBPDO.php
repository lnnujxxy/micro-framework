<?php

namespace Pepper\QFrameDB;

class QFrameDBPDO
{
    /**
     * @var \PDO
     */
    private $connection = null;
    private $config = [];
    private $fetchType = \PDO::FETCH_ASSOC;
    private $debug = false;
    private $transaction = false;
    private $errorMode = \PDO::ERRMODE_EXCEPTION;
    private $reconnected = false; // 是否需要重新链接
    private $autoReconnect = true;  // 是否需要开启自动重连

    public function __construct($config)
    {
        $this->config = $config;
    }

    private function _connect()
    {
        if ($this->connection == null) {
            if ($this->config["unix_socket"]) {
                $dsn = "mysql:dbname={$this->config["database"]};unix_socket={$this->config["unix_socket"]}";
            } else {
                $dsn = "{$this->config["driver"]}:dbname={$this->config["database"]};host={$this->config["host"]};port={$this->config["port"]}";
            }

            $username = $this->config["username"];
            $password = $this->config["password"];
            //$options    = array_unique(array_merge([\PDO::ATTR_PERSISTENT=>$this->_config["persistent"]], $this->_config["options"]));
            $options = $this->config['options'] + [\PDO::ATTR_PERSISTENT => $this->config['persistent']];

            try {
                $this->connection = new \PDO($dsn, $username, $password, $options);
            } catch (\PDOException $e) {
                throw new QFrameDBException($e->getMessage(), $e->getCode());
            }

            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, $this->errorMode);
            $this->execute("SET NAMES '{$this->config["charset"]}'");
            $this->execute("SET character_set_client=binary");
        }
    }

    private function _exec($sql, $params)
    {
        $this->_connect();

        if ($this->debug) {
            echo $this->getBindedSql($sql, $params), PHP_EOL;
        }

        $stmt = new QFrameDBStatment($this->connection->prepare($sql));
        if (is_array($params)) {
            if (!empty($params)) {
                $i = 0;
                foreach ($params as $value) {
                    $stmt->bind(++$i, $value);
                }
            }
        } else {
            $stmt->bind(1, $params);
        }
        $execute_return = $stmt->execute();

        return ["stmt" => $stmt, "execute_return" => $execute_return];
    }

    private function _process($sql, $params)
    {
        //关闭直接事务语句
        if (in_array(preg_replace("/\s{2,}/", " ", strtolower($sql)), ["begin", "commit", "rollback", "start transaction", "set autocommit=0", "set autocommit=1"])) {
            throw new QFrameDBException("为避免操作异常，请使用包装后的事务处理接口[startTrans, commit, rollback]");
        }

        if ($this->transaction) {
            if ($this->reconnected) {
                throw new QFrameDBException("数据库链接已丢失!");
            } else {
                try {
                    $arr_exec_result = $this->_exec($sql, $params);
                } catch (\PDOException $e) {
                    if (in_array($e->errorInfo[1], [2013, 2006])) {
                        $this->reconnected = true;
                    }

                    throw new QFrameDBException($e->errorInfo[2], $e->errorInfo[1]);
                }
            }
        } else {
            try {
                $arr_exec_result = $this->_exec($sql, $params);
            } catch (\PDOException $e) {
                if ($this->autoReconnect && in_array($e->errorInfo[1], [2013, 2006])) {
                    try {
                        $this->close();
                        $arr_exec_result = $this->_exec($sql, $params);
                        $this->reconnected = true;
                    } catch (\PDOException $e) {
                        throw new QFrameDBException($e->errorInfo[2], $e->errorInfo[1]);
                    }
                } else {
                    throw new QFrameDBException($e->errorInfo[2], $e->errorInfo[1]);
                }
            } catch (\Throwable $e) { // 异常没有抛出，处理错误，超时重连
                if ($this->autoReconnect && strpos($e->getMessage(), "MySQL server has gone away") !== false) {
                    try {
                        $this->close();
                        $arr_exec_result = $this->_exec($sql, $params);
                        $this->reconnected = true;
                    } catch (\PDOException $e) {
                        throw new \RuntimeException($e->getMessage());
                    }
                } else {
                    throw new \RuntimeException($e->getMessage());
                }
            }
        }

        return $arr_exec_result;
    }

    private function _checkSafe($sql, $is_open_safe = true)
    {
        if (!$is_open_safe) {
            return true;
        }

        $string = strtolower($sql);
        $operate = strtolower(substr($sql, 0, 6));
        $is_safe = true;
        switch ($operate) {
            case "select":
                if (strpos($string, "where") && !preg_match("/\(.*\)/", $string) && !strpos($string, "?")) {
                    $is_safe = false;
                }
                break;
            case "insert":
            case "update":
            case "delete":
                if (!strpos($string, "?")) {
                    $is_safe = false;
                }
                break;
        }

        if (!$is_safe) {
            throw new QFrameDBException("SQL语句:[$sql],存在SQL注入漏洞隐患，请改用bind方式处理或关闭sql执行safe模式.");
        }

        return $is_safe;
    }

    /**
     * 获取last_insert_id()
     * @return string
     */
    public function getInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * 直接执行sql
     * @param string $sql
     * @param array $params
     * @param bool $is_open_safe
     * @return bool|string insert操作返回last_insert_id()，update、delete操作返回影响行数，其他操作返回bool
     */
    public function execute($sql, $params = [], $is_open_safe = true)
    {
        $this->_checkSafe($sql, $is_open_safe);

        $arr_process_result = $this->_process($sql, $params);

        if ($arr_process_result["execute_return"]) {
            $operate = strtolower(substr($sql, 0, 6));
            switch ($operate) {
                case "insert":
                    $arr_process_result["execute_return"] = $this->getInsertId();
                    break;
                case "update":
                case "delete":
                    $arr_process_result["execute_return"] = $arr_process_result["stmt"]->getEffectedRows();
                    break;
                default:
                    break;
            }
        }

        return $arr_process_result["execute_return"];
    }

    /**
     * 执行SQL查询
     * @param string $sql
     * @param array $params
     * @param bool $is_open_safe
     * @return QFrameDBStatment
     */
    public function query($sql, $params = [], $is_open_safe = true)
    {
        $this->_checkSafe($sql, $is_open_safe);
        $result = $this->_process($sql, $params);
        return $result["stmt"];
    }

    /**
     * 获取一条记录中的第一列
     * @param string $sql
     * @param array $params
     * @param bool $safe
     * @return string|null
     */
    public function getOne($sql, $params = [], $safe = true)
    {
        $stmt = $this->query($sql, $params, $safe);
        $record = $stmt->fetch($this->fetchType);
        return is_array($record) && !empty($record) ? array_shift($record) : null;
    }

    /**
     * 获取一条记录
     * @param string $sql
     * @param array $params
     * @param bool $safe
     * @return array
     */
    public function getRow($sql, $params = [], $safe = true)
    {
        $stmt = $this->query($sql, $params, $safe);
        $record = $stmt->fetch($this->fetchType);
        return is_array($record) && !empty($record) ? $record : [];
    }

    /**
     * 获取多行记录
     * @param string $sql
     * @param array $params
     * @param bool $safe
     * @return array
     */
    public function getAll($sql, $params = [], $safe = true)
    {
        $stmt = $this->query($sql, $params, $safe);
        $data = [];
        while ($record = $stmt->fetch($this->fetchType)) {
            $data[] = $record;
        }
        return $data;
    }

    private function _operate($table, $record, $operate, $condition = "", $params = [])
    {
        $values = $fields = [];
        if (in_array($operate, ["insert", "replace", "update"])) {
            $fields = is_array($record) ? array_keys($record) : [];
            $values = is_array($record) ? array_values($record) : [];

            if (empty($fields)) {
                throw new QFrameDBException("\$record 操作数据必须使用关联数组形式");
            }
        }

        switch ($operate) {
            case "insert":
            case "replace":
                $sql = "$operate into $table (`" . implode("`,`", $fields) . "`) values (" . str_repeat("?,", count($fields) - 1) . "?)";
                return $this->execute($sql, $values);
                break;
            case "update":
                $sql = "update $table set ";
                foreach ($fields as $field) {
                    $sql .= "$field=?,";
                }
                $sql = substr($sql, 0, -1);

                if ($condition) {
                    $sql .= " where " . $condition;
                }
                is_array($params) ? $values = array_merge($values, $params) : $values[] = $params;
                return $this->execute($sql, $values);
                break;
            case "delete":
                $sql = "delete from $table where $condition";
                return $this->execute($sql, $params);
                break;
        }
        return true;
    }

    /**
     * insert操作
     * @param string $table
     * @param array $record
     * @return bool|mixed|string
     */
    public function insert($table, $record)
    {
        return $this->_operate($table, $record, "insert");
    }

    /**
     * replace操作
     * @param string $table
     * @param array $record
     * @return bool|mixed|string
     */
    public function replace($table, $record)
    {
        return $this->_operate($table, $record, "replace");
    }

    /**
     * update操作
     * @param string $table
     * @param array $record
     * @param string $condition
     * @param array $params
     * @return bool|mixed|string
     * @throws QFrameDBException
     */
    public function update($table, $record, $condition, $params)
    {
        try {
            return $this->_operate($table, $record, "update", $condition, $params);
        } catch (QFrameDBException $e) {
            throw new QFrameDBException($e->getMessage());
        }
    }

    /**
     * delete操作
     * @param string $table
     * @param string $condition
     * @param array $params
     * @return bool|mixed|string
     */
    public function delete($table, $condition, $params)
    {
        return $this->_operate($table, null, "delete", $condition, $params);
    }

    /**
     * 设置wait_timeout
     * @param number $seconds
     */
    public function setWaitTimeOut($seconds)
    {
        $this->execute("set wait_timeout=$seconds");
    }

    /**
     * 设置是否自动重连
     * @param bool $autoReconnect
     */
    public function setAutoReconnect($autoReconnect)
    {
        $this->autoReconnect = $autoReconnect;
    }

    /**
     * 设置是否为DEBUG模式
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * 设置数据fetch格式
     * @param int $fetch_type
     */
    public function setFetchMode($fetch_type = \PDO:: FETCH_ASSOC)
    {
        $this->fetchType = $fetch_type;
    }

    /**
     * 开启事务
     * @throws QFrameDBException
     */
    public function startTrans()
    {
        if ($this->transaction) {
            throw new QFrameDBException("之前开启的事务尚未结束，事务处理不能嵌套操作!");
        }

        $this->_connect();

        try {
            $this->connection->beginTransaction();
        } catch (\PDOException $e) {
            $errorInfo = $this->connection->errorInfo();
            throw new QFrameDBException($errorInfo[2], $errorInfo[1]);
        }

        $this->transaction = true;
        $this->reconnected = false;
    }

    /**
     * 提交事务
     * @throws QFrameDBException
     */
    public function commit()
    {
        if (!$this->transaction) {
            throw new QFrameDBException("之前开启的事务已经被提交或没有开启，请仔细查看事务处理过程中的操作语句!");
        }

        $this->transaction = false;
        $this->reconnected = false;

        try {
            $this->connection->commit();
        } catch (\PDOException $e) {
            $errorInfo = $this->connection->errorInfo();
            throw new QFrameDBException($errorInfo[2], $errorInfo[1]);
        }
    }

    /**
     * 回滚事务
     * @throws QFrameDBException
     */
    public function rollback()
    {
        if (!$this->transaction) {
            throw new QFrameDBException("之前开启的事务已经被提交或没有开启，请仔细查看事务处理过程中的操作语句!");
        }

        $this->transaction = false;
        $this->reconnected = false;

        try {
            $this->connection->rollback();
        } catch (\PDOException $e) {
            $errorInfo = $this->connection->errorInfo();
            throw new QFrameDBException($errorInfo[2], $errorInfo[1]);
        }
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        $this->connection = null;
    }

    /**
     * DEBUG用，获取经过绑定内容替换后的sql语句
     * @param $sql
     * @param array $params
     * @return mixed|string
     */
    public function getBindedSql($sql, $params = [])
    {
        if (!preg_match("/\?/", $sql)) {
            return $sql;
        }

        /* 先找出非正常的变量区域并用"#"代替 */
        preg_match_all('/(?<!\\\\)\'.*(?<!\\\\)\'/U', $sql, $arr_match_list);
        $arr_exists_list = $arr_match_list[0];
        foreach ($arr_match_list[0] as $value) {
            $sql = str_replace($value, "#", $sql);
        }

        if (!is_array($params)) {
            $params = [$params];
        }

        /* 根据#或?分解语句,将内容填充到对应位置上 */
        preg_match_all("/[#\?]/", $sql, $arr_match_list);
        $arr_split_list = preg_split("/[#\?]/", $sql);

        $sql = "";
        foreach ($arr_match_list[0] as $key => $flag) {
            $sql .= $arr_split_list[$key] . ($flag == "#" ? array_shift($arr_exists_list) : $this->_quote(array_shift($params)));
        }

        return $sql;
    }

    private function _quote($string)
    {
        return $this->connection->quote($string);
    }
}
