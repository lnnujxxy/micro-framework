<?php

namespace Pepper\QFrameDB;

class QFrameDBStatment
{
    private $_stmt;

    public function __construct(\PDOStatement $stmt)
    {
        $this->_stmt = $stmt;
    }

    public function fetch($mode = \PDO::FETCH_ASSOC)
    {
        return $this->_stmt->fetch($mode);
    }

    public function execute()
    {
        return $this->_stmt->execute();
    }

    public function bind($parameter, $value)
    {
        return $this->_stmt->bindValue($parameter, $value);
    }

    public function getEffectedRows()
    {
        return $this->_stmt->rowCount();
    }
}
