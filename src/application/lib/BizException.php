<?php
namespace Pepper\Framework\Lib;

class BizException extends \RuntimeException
{
    public function __construct($error, $args = array()) {
        $args = is_array($args) ? $args : array($args);
        $errno = $error['code'];
        $errmsg = $error['message'];
        $errmsg = empty($args) ? $errmsg : vsprintf($errmsg, $args);

        parent::__construct($errmsg, intval($errno));
    }
}
