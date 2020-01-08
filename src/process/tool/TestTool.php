<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午2:04
 */
namespace Pepper\FrameWorker\Process;
use Pepper\Framework\Model\Follow;

require_once __DIR__ . "/Init.php";

$uid = 1;
$fid = 2;
var_dump(Follow::isFollowed($uid, $fid));

echo date("Y-m-d H:i:s") . "ok\n";