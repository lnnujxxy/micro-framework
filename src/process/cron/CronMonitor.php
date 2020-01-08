<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午2:04
 * 监控
 */
namespace Pepper\FrameWorker\Process\Cron;

use Pepper\Framework\Lib\Curl;
use Pepper\Lib\SimpleConfig;

require_once __DIR__ . "/Init.php";
$json = Curl::get('https://' . SimpleConfig::get('URL') . '/Monitor/web');
$arr = json_decode($json, true);
if (!isset($arr['code']) || $arr['code'] != 0) {
    $alarmContent = "web_error";
    $cmd = "cagent_tools alarm $alarmContent " . POLICY_ID;
    system($cmd);
}

echo date("Y-m-d H:i:s") . "ok\n";