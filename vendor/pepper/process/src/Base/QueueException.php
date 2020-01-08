<?php
/**
 * Created by PhpStorm.
 * User: wangtonghe-hj
 * Date: 2017/9/5
 * Time: 12:08
 */
namespace Pepper\Process\Base;

class QueueException extends \Exception
{
    const CODE_PRODUCT_NOT_SUPPORT      = 1000;
    const CODE_QUEUE_NAME_INVALID       = 1001;
    const CODE_SERVER_NOT_CONFIGURATION = 1002;

    public function __construct($message, $code=0)
    {/*{{{*/
        $message = "Queue Exception[$code]:$message ($code)";
        parent::__construct($message, $code);
    }/*}}}*/
}
