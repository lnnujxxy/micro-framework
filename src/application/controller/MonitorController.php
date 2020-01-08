<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/18
 * Time: 下午9:43
 */

namespace Pepper\Framework\Controller;

use Pepper\Framework\Traits\RedisTrait;
use Pepper\Lib\SimpleConfig;
use Pepper\QFrameDB\QFrameDB;

class MonitorController extends BaseController
{
    use RedisTrait;
    public function webAction() {
        $db = QFrameDB::getInstance(SimpleConfig::get('DB_CONF')['default'][1]);
        $val = $db->getOne('SELECT 1');
        if ($val != 1) {
            $alarmContent = "mysql_error";
            $cmd = "cagent_tools alarm $alarmContent ". POLICY_ID;
            system($cmd);
        }

        $redis = RedisTrait::getRedis('monitor:redis');
        $redis->set('test', 1);
        if ($redis->get('test') != 1) {
            $alarmContent = "redis_error";
            $cmd = "cagent_tools alarm $alarmContent " . POLICY_ID;
            system($cmd);
        }

        $this->render();
    }
}