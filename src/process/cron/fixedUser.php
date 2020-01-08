<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午2:04
 * 监控
 */
namespace Pepper\FrameWorker\Process\Cron;

use Pepper\Lib\SimpleConfig;
use Pepper\QFrameDB\QFrameDB;

require_once __DIR__ . "/Init.php";

$fp = fopen("111.csv", "r");
while ($line = fgets($fp, 1024)) {
    list($nickname, $avator) = explode(",", trim($line));
    $nicknames[] = $nickname;
    $avators[] = $avator;
}

$db = QFrameDB::getInstance(SimpleConfig::get('DB_CONF')['default'][1]);
$sth = $db->query("SELECT * FROM user where groupid = ?", 1);
$i=0;
while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
    $uid = $row['uid'];
    $avator = str_replace("http://", 'https://', $avators[$i]);
    $avator = str_replace(".jpg", '-132_132.jpg', $avator);

    $db->update("user", array("avatar" => $avator), ' uid = ?', $uid);
    $i++;
}

echo date("Y-m-d H:i:s") . "ok\n";