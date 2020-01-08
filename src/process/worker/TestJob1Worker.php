<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午2:04
 */

namespace Pepper\Framework\Process\Worker;

use Pepper\Framework\Model\User;

/**
 * Class TestJob1Worker
 * @package Pepper\Framework\Process\Worker
 * test worker
 */
class TestJob1Worker
{
    public function execute($value) {
        $token      = $value["params"]["token"];
        $createdAt  = $value["params"]["createdAt"];

        $userid = User::getLoginid($token);
        file_put_contents("/tmp/test.log", $userid . "\t" . $createdAt . "\n", FILE_APPEND);
        return true;
    }

    public function test() {
        echo "test";
    }
}