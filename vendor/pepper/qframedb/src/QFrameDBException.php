<?php

namespace Pepper\QFrameDB;

class QFrameDBException extends \PDOException
{
    public function __construct($message, $code = 0)
    {
        $message = "数据库 错误[$code]:$message ($code)";

        parent::__construct($message, $code);
    }
}