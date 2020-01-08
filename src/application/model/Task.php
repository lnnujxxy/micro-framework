<?php

namespace Pepper\Framework\Model;

use Pepper\Framework\Dao\DAOTask;
use Pepper\Framework\Dao\DAOTaskLog;
use Pepper\Framework\Dao\DAOTaskProgress;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Lib\Lock;
use Pepper\Framework\Lib\Util;
use Pepper\Lib\SimpleConfig;
use Pepper\Framework\Traits\RedisTrait;

class Task
{
    use RedisTrait;
    
    const STATE_UNFINISH = 0; //任务未完成
    const STATE_FINISH   = 1; //任务完成
    const STATE_RECEIVE  = 2; //任务领取完成

    const TASK_ID_USE = 11; // 使用菩提获取香火
    const TASK_ID_PICKING = 12; // 采摘任务
    const TASK_ID_CONSUME = 13; // 消耗菩提

    const TASK_ID_HELP = 15; // 互助
    const TASK_ID_SONGJING_5 = 16; // 诵经5分钟获得加持

    const MAP_TYPE_VALUE = [
        'merit' => 1,
        'joss' => 2,
        'bodhi' => 3,
    ];

    const MERIT_TASK_IDS = [1, 2, 3, 4, 5]; // 功德任务列表任务id

    public static function execute($uid, $action, $value = 0, $multiple = 1) {
        $tasks = (new DAOTask())->getTasksByAction($action);
        foreach ($tasks as $task) {
            if ($task['isprogress']) {
                // 更新个人进度
                $expired = self::getExpire($task['period']);
                if ((new DAOTaskProgress($uid))->updateProgress($task['taskid'], $task['type'], 1, $task['goal'], $expired)) {
                    $award = $task['award'];
                    if ($award['handler'] == 'incr') {
                        $v = (int)($award['params']['val'] == '$' ? $value : $award['params']['val']) * $multiple;
                        User::updateValue($uid, $award['params']['type'], $v);
                        (new DAOTaskLog())->add($task['taskid'], $uid, self::MAP_TYPE_VALUE[$award['params']['type']], $v, $task['title']);
                    }
                    return true;
                }
            } else {
                $award = $task['award'];
                if ($award['handler'] == 'exchange') {
                    User::updateValue($uid, $award['params']['type'], $award['params']['times'] * $value);
                    (new DAOTaskLog())->add($task['taskid'], $uid, self::MAP_TYPE_VALUE[$award['params']['type']], $award['params']['times'] * $value, $task['title']);
                    return true;
                } elseif ($award['handler'] == 'incr') {
                    User::updateValue($uid, $award['params']['type'], $award['params']['val']);
                    (new DAOTaskLog())->add($task['taskid'], $uid, self::MAP_TYPE_VALUE[$award['params']['type']], $award['params']['val'], $task['title']);
                    return true;
                } elseif ($award['handler'] == 'rand' && $value) {
                    $rand = (int)mt_rand(0, intval($value * 0.1)) * $multiple;
                    if ($rand) {
                        User::updateValue($uid, $award['params']['type'], $rand);
                        (new DAOTaskLog())->add($task['taskid'], $uid, self::MAP_TYPE_VALUE[$award['params']['type']], $rand, $task['title']);
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public static function doTask($uid, $action, $num = 1) {
        $tasks = (new DAOTask())->getTasksByAction($action);
        foreach ($tasks as $task) {
            if ($task['isprogress']) {
                // 更新个人进度
                $expired = self::getExpire($task['period']);
                (new DAOTaskProgress($uid))->updateProgress($task['taskid'], $task['type'], $num, $task['goal'], $expired);
            }
        }
    }

    public static function getMeritMultiple($merit) {
        $config = SimpleConfig::get('METIT_MULTIPLE');
        foreach ($config as $item) {
            if ($item['min'] <= $merit && $merit < $item['max']) {
                return $item['multiple'];
            }
        }
        return 1;
    }

    public static function getBlessLevel($type, $bless) {
        $config = SimpleConfig::get('BLESS_CONFIG')[$type];
        foreach ($config as $index=>$item) {
            if ($item['min'] <= $bless && $bless <= $item['max']) {
                return $index;
            }
        }
        return 0;
    }

    public static function help($fuid, $tuid, $type, $merit) {
        $tUserInfo = User::getFormatUserInfo($tuid);
        User::updateValue($fuid, 'merit', $merit);
        // 记录流水
        $reason = '帮助'.$tUserInfo['nickname'].'完成'.Feeds::getName($type);
        (new DAOTaskLog())->add(self::TASK_ID_HELP, $fuid, self::MAP_TYPE_VALUE['merit'], $merit, $reason);
    }

    public static function receiveAward($uid, $taskid) {
        $progress = (new DAOTaskProgress($uid))->getProgress($taskid);
        Interceptor::ensureNotFalse($progress['num'] > $progress['award_num'], ERROR_TOAST, '该任务未完成或者已经领过了奖励');
        Interceptor::ensureNotFalse(Lock::lock('lock:receive:'.$uid.':'.$taskid), ERROR_TOAST, '请稍后领取！');
        $task = (new DAOTask())->get($taskid);
        $award = $task['award'];
        $result = [];
        if ($award['handler'] == 'receive') { // 领取
            foreach ($award['params'] as $item) {
                $value = $item['val'] * ($progress['num'] - $progress['award_num']);
                $result[$item['type']] = $value;

                User::updateValue($uid, $item['type'], $value);
                // 记录流水
                (new DAOTaskLog())->add($task['taskid'], $uid, self::MAP_TYPE_VALUE[$item['type']], $value, $task['title']);
            }
            $record['award_num'] = $progress['num'];
            if ($progress['num'] == $progress['goal']) {
                $record['state'] = self::STATE_RECEIVE;
            }
            (new DAOTaskProgress($uid))->updateRecord($taskid, $record);
        }

        return $result;
    }

    public static function getProgresses($uid, $taskids) {
        $tasks = (new DAOTask())->gets($taskids);
        $progresses = (new DAOTaskProgress($uid))->getProgresses();
        $states = [];
        foreach ($progresses as $progress) {
            if (in_array($progress['taskid'], $taskids)) {
                if ($progress['award_num'] >= $progress['goal']) {
                    $state = self::STATE_RECEIVE;
                } elseif ($progress['award_num'] < $progress['num']) {
                    $state = self::STATE_FINISH;
                } else {
                    $state = self::STATE_UNFINISH;
                }
                $states[$progress['taskid']] = $state;
            }
        }
        foreach ($tasks as $index=>&$task) {
            $task['state'] = isset($states[$task['taskid']]) ? $states[$task['taskid']] : 0;
        }
        return $tasks;
    }

    public static function checkSongJing($uid) {
        $hashKey = self::songJingKey();
        return self::getRedis()->hGet($hashKey, $uid) < (time() + 3) ? true : false;
    }

    public static function hSetSongJing($uid) {
        $hashKey = self::songJingKey();
        $redis = self::getRedis();
        return $redis->hSet($hashKey, $uid, time() + 60);
    }

    public static function incrSongJingDuration($nonce, $duration) {
        $redis = self::getRedis();
        $key = sprintf("key:sj:%s", $nonce);
        $d = $redis->incrBy($key, $duration);
        if ($redis->ttl($key) < 0) {
            $redis->expire($key, 86400);
        }
        return $d;
    }

    public static function getSongJingDuration($nonce) {
        $redis = self::getRedis();
        return (int)$redis->get(sprintf("key:sj:%s", $nonce));
    }

    public static function comsumeBodhi($uid, $value, $reason) {
        $task = (new DAOTask())->get(self::TASK_ID_CONSUME); // 消耗任务
        $award = $task['award'];// 奖励
        if ($award['handler'] == 'decr') {
            if (!User::updateValue($uid, $award['params']['type'], -$value)) {
                return false;
            }
            (new DAOTaskLog())->add($task['taskid'], $uid, self::MAP_TYPE_VALUE[$award['params']['type']], -$value, $reason ? $reason : $task['title']);
        }
        return true;
    }

    public static function pick($uid, $num) {
        $task = (new DAOTask())->get(self::TASK_ID_PICKING); // 采摘任务
        $expired = self::getExpire($task['period']);

        $progress = (new DAOTaskProgress($uid))->getProgress(self::TASK_ID_PICKING);
        $extend = self::filterExpireExtend($progress['extend']);
        $count = count($extend);
        $num = $num > ($task['goal'] - $count) ? ($task['goal'] - $count) : $num;
        for ($i = 0; $i < $num; $i++) {
            $extend[$count + $i] = $expired;
        }

        if ((new DAOTaskProgress($uid))->updateProgress($task['taskid'], $task['type'], $num, $task['goal'], $expired, json_encode($extend))) {
            $award = $task['award'];// 奖励
            if ($award['handler'] == 'exchange') {
                User::updateValue($uid, $award['params']['type'], $award['params']['times'] * $num);
                (new DAOTaskLog())->add($task['taskid'], $uid, self::MAP_TYPE_VALUE[$award['params']['type']], $award['params']['times'] * $num, $task['title']);
            }
        }
    }

    public static function getPickProgress($uid) {
        $task = (new DAOTask())->get(self::TASK_ID_PICKING); // 采摘任务
        $progress = (new DAOTaskProgress($uid))->getProgress(self::TASK_ID_PICKING);
        if (empty($progress)) {
            $task['extend'] = [];
            $task['state']  = self::STATE_UNFINISH;
        } else {
            $extend = self::filterExpireExtend($progress['extend']);
            $count = count($extend);
            $state = $count < $task['goal'] ? 0 : $progress['state'];
            if ($state != $task['state'] || count($task['extend']) != $task['num']) {
                (new DAOTaskProgress($uid))->updateRecord(self::TASK_ID_PICKING, ['state' => $state, 'num' => count($task['extend'])]);
            }
            $task['extend'] = $extend;
            $task['state']  = $state;
        }
        return $task;
    }

    public static function listTaskLog($uid, $type, $subType = 0, $offset, $limit = 10) {
        $list = (new DAOTaskLog())->listing($uid, $type, $subType, $offset, $limit);
        return [
            'list' => (array)$list,
            'more' => count($list) >= $limit,
            'offset' => $list ? $list[count($list)-1]['id'] : 0,
        ];
    }

    public static function getNonceKey($relateid, $type) {
        return "nonce:{$relateid}:{$type}:" . Util::random();
    }

    public static function hIncNonce($nonce, $key, $val) {
        $redis = self::getRedis();
        $newVal = $redis->hIncrBy($nonce, $key, $val);
        if ($redis->ttl($nonce) < 0) {
            $redis->expire($nonce, 86400);
        }
        return $newVal;
    }

    public static function delNonce($nonce) {
        $redis = self::getRedis();
        $redis->del($nonce);
    }

    public static function hGetNonceVal($nonce, $key) {
        return (int)self::getRedis()->hGet($nonce, $key);
    }

    /**
     * 过滤掉过期节点
     * @param $extend
     * @return array|mixed
     */
    private static function filterExpireExtend($extend) {
        $extend = $extend ? json_decode($extend, true) : [];
        if ($extend) {
            foreach ($extend as $index=>$time) {
                if ($time < time()) {
                    unset($extend[$index]);
                }
            }
            $extend = array_values($extend);
        }
        return $extend;
    }

    private static function songJingKey() {
        return "hashkey:task:songjing";
    }

    private static function getExpire($period) {
        $expired = 0;
        if (isset($period['type']) && $period['type'] == 'daily') {
            $expired = strtotime(date("Y-m-d", strtotime("+1 day")));
        } elseif (isset($period['type']) && $period['type'] == 'time') {
            $expired = time() + $period['interval'];
        }
        return $expired;
    }
}