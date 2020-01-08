<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午2:04
 * 发悔过
 */
namespace Pepper\FrameWorker\Process\Cron;

use Pepper\Framework\Model\WxAction;
use Pepper\Framework\Model\WxPay;
use Pepper\Lib\SimpleConfig;
use Pepper\QFrameDB\QFrameDB;

require_once __DIR__ . "/Init.php";
//todo 后续优化
$limit  = 100;
$offset = 0;
$maxId = 10000;
while (1) {
    if ($offset > $maxId) break;
    $db = QFrameDB::getInstance(SimpleConfig::get("DB_CONF")["default"][1]);
    $sql = "SELECT * FROM wxpay WHERE id >= ? and id < ? AND tuid > 0 AND state = " . WxPay::STATE_PAY;
    $sth = $db->query($sql, array($offset, $offset+$limit));
    while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
        $transferInfo = WxPay::gettransferinfo($row['trade_no']); // 查询零钱到账
        if ($transferInfo['return_code'] == 'SUCCESS' && !in_array($transferInfo['status'], ['SUCCESS', 'PROCESSING'])) {
            $result = WxPay::transfers($row);
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                $db->execute("UPDATE wxpay SET state = ? WHERE trade_no = ?", [WxPay::STATE_TRANSFER_SUCC, $row['trade_no']]);
            } else {
                $db->execute("UPDATE wxpay SET state = ? WHERE trade_no = ?", [WxPay::STATE_TRANSFer_FAIL, $row['trade_no']]);
                // 发送不成功报警
                $alarmContent = "transfer_error_" . $row['trade_no'];
                $cmd = "cagent_tools alarm $alarmContent " . POLICY_ID;
                system($cmd);
            }
        } elseif ($transferInfo['return_code'] == 'SUCCESS' && $transferInfo['status'] == 'SUCCESS') {
            $db->execute("UPDATE wxpay SET state = ? WHERE trade_no = ?", [WxPay::STATE_TRANSFER_SUCC, $row['trade_no']]);
        }
    }
    $offset += $limit;
    unset($db);
}

echo date("Y-m-d H:i:s") . "ok\n";