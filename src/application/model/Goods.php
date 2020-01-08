<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午9:46
 */
namespace Pepper\Framework\Model;

use Pepper\Framework\Dao\DAOFeedsGoods;
use Pepper\Framework\Dao\DAOGoods;
use Pepper\Framework\Dao\DAOGoodsUser;
use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Traits\RedisTrait;
use Pepper\Lib\SimpleConfig;

class Goods
{
    use RedisTrait;

    const XIANG_TYPE_ID = 1; // 香
    const LAMP_TYPE_ID = 2; // 灯
    const STOVE_TYPE_ID = 3; // 炉
    const FLOWER_TYPE_ID = 4; // 花
    const CASH_TYPE_ID = 9; // 现金

    const TAN_XIANG_ID = 9; // 檀香id
    const JIN_LI_ID = 1; // 锦鲤灯id
    const FREE_NUM = 3; // 每天免费给数量

    const DEFAULT_TYPES = [1, 2, 3, 4];
    const START_TYPES = [1, 2, 3, 4, 9];
    const MAP_FEED_GOODS = [
        1 => 5,
        2 => 6,
        5 => 7,
    ];
    const FROM_START = 1; // 发起
    const FROM_HELP = 2; // 送祝福

    public static function getGroupGoods($uid, $from, $relateid, $type)
    {
        $_goodses = (new DAOGoods())->getGroupGoods(self::START_TYPES);
        $freeGoods = self::getFreeGoods($uid);
        $goodses = [];
        foreach ($_goodses as $row) {
            $row['num'] = isset($freeGoods[$row['goods_id']]) ? $freeGoods[$row['goods_id']] : 1;
            $goodses[$row['type']][] = $row;
        }

        $goodsType = self::MAP_FEED_GOODS[$type];
        if ($from == self::FROM_HELP) {
            $total = (new DAOFeedsGoods($relateid, $type))->totalGoods($relateid, $type, $uid, $goodsType);
            $diff = max(0,self::FREE_NUM - $total);
            $headGoodses = (new DAOGoods())->getGroupGoods([$goodsType]);
            foreach ($headGoodses as &$row) {
                $row['num'] = $diff;
            }

            $goodses[$goodsType] = $headGoodses;
        }

        return $goodses;
    }

    public static function getGoodsInfos($goodsIds)
    {
        if (!is_array($goodsIds)) {
            $goodsIds = explode(',', $goodsIds);
        }
        return (new DAOGoods())->gets($goodsIds);
    }

    public static function getGoodInfo($goodsId)
    {
        return (new DAOGoods())->get($goodsId);
    }

    public static function getUserGoods($uid) {
        $_goods = (new DAOGoodsUser($uid))->getUserGoods();
        $goods = $goodsTypes = [];
        if ($_goods) {
            foreach ($_goods as $good) {
                $goods[$good['goods_type']] = $good;
                $goodsTypes[] = $good['goods_type'];
            }
        } else {
            $goodsTypes = [];
        }

        // 如果没有默认给用户香和香炉
        if (!in_array(self::XIANG_TYPE_ID, $goodsTypes)) {
            $goods[self::XIANG_TYPE_ID] = [
                'uid' => $uid,
                'goods_id' => 9,
                'goods_type' => self::XIANG_TYPE_ID,
                'expired_at' => 0,
            ];
        }

        if (!in_array(self::STOVE_TYPE_ID, $goodsTypes)) {
            $goods[self::STOVE_TYPE_ID] = [
                'uid' => $uid,
                'goods_id' => 15,
                'goods_type' => self::STOVE_TYPE_ID,
                'expired_at' => 0,
            ];
        }

        $goodsIds = array_column($goods, 'goods_id');
        $goodsInfos = (new DAOGoods())->gets($goodsIds);
        $userGoods = [];
        foreach ($goods as $index=>$good) {
            $good['name'] = $goodsInfos[$good['goods_id']]['name'];
            $good['image'] = $goodsInfos[$good['goods_id']]['image'];
            $good['price'] = $goodsInfos[$good['goods_id']]['price'];
            $good['merit'] = $goodsInfos[$good['goods_id']]['merit'];
            unset($good['created_at'], $good['updated_at']);
            $userGoods[$good['goods_type']] = $good;
        }

        return $userGoods;
    }

    public static function countUserGoods($uid) {
        return (new DAOGoodsUser($uid))->countUserGoods();
    }

    /**
     * 购买商品
     * @param int $fuid 发起人
     * @param int $tuid 接收人
     * @param int $goodsid 商品id
     * @param int $relateid feedid
     * @param int $type feed 类型
     * @param int $from 1 发起 2 他人互助
     * @return array
     */
    public static function buyGoods($fuid, $tuid, $goodsid, $relateid, $type, $from = self::FROM_START) {
        $goodsInfo = self::getGoodInfo($goodsid);
        if ($from == self::FROM_HELP && in_array($goodsInfo['type'], array_values(self::MAP_FEED_GOODS))) {
            $total = (new DAOFeedsGoods($relateid, $type))->totalGoods($relateid, $type, $fuid, self::MAP_FEED_GOODS[$type]);
            Interceptor::ensureNotFalse($total < self::FREE_NUM, ERROR_TOAST, '您好！只能送三次免费祝福福牌!');
        } elseif ($from == self::FROM_START && in_array($goodsInfo['type'], array_values(self::MAP_FEED_GOODS))) {
            Interceptor::ensureNotFalse(false, ERROR_TOAST, '该类型礼物不支持');
        }

        $userInfo = User::getFormatUserInfo($fuid, true);
        $bless = self::getBlessFromGoods($goodsInfo);
        if ($from == self::FROM_HELP) { // 互助 +1 祝福值
            $bless++;
        }

        $merit = $userInfo['merit'];
        $incMerit = 0;
        if (!empty($goodsInfo) && $goodsInfo['price']) {
            Interceptor::ensureNotFalse($userInfo['bodhi'] >= $goodsInfo['price'], ERROR_BUY_NOT_ENOUGH);
            Interceptor::ensureNotFalse(Task::comsumeBodhi($fuid, $goodsInfo['price'], '购买' . $goodsInfo['name']), ERROR_BUY_GOODS_FAIL);
            $incMerit = floor($goodsInfo['price']/5);
        }
        $incMerit += $goodsInfo['merit'];
        $_bless = $bless;
        $bless = intval($bless * Task::getMeritMultiple($merit + $incMerit));

        if (!isset($_REQUEST['is_hide']) || !$_REQUEST['is_hide']) {
            (new DAOFeedsGoods($relateid, $type))->add($relateid, $type, $fuid, $goodsInfo['goods_id'], $goodsInfo['type'], $goodsInfo['image'], $bless);
        }
        Task::help($fuid, $tuid, $type, $incMerit);

        // 发私信
        if ($goodsInfo['price'] && $tuid != $fuid) {
            WxAction::sendRecMsg($tuid, $userInfo['nickname'], $goodsInfo['price'], date('Y-m-d H:i:s'), '赠送'.$goodsInfo['name']);
        }
        // 记录送礼物
        if (in_array($goodsInfo['type'], self::DEFAULT_TYPES)) {
            (new DAOGoodsUser($tuid))->add($goodsInfo['goods_id'], $goodsInfo['type'], $goodsInfo['ttl']);
        }
        return [
            'merit' => $merit + $incMerit,
            'extra_bless' => $bless - $_bless,
            'bless' => $bless,
            'load_url' => SimpleConfig::get('SYSTEM_CONFIG')['is_pass'] ? 'https://goods-1257256615.file.myqcloud.com/v20181215/pusa%402x.png' : '',
        ];
    }

    /**
     * 购买现金礼物
     * @param $fuid
     * @param $tuid
     * @param $goodsInfo
     * @param $relateid
     * @param $type
     * @param int $from
     * @return array
     */
    public static function buyCash($fuid, $tuid, $goodsInfo, $relateid, $type, $from = self::FROM_START) {
        $bless = self::getBlessFromGoods($goodsInfo);
        if ($from == self::FROM_HELP) { // 互助 +1 祝福值
            $bless++;
        }

        if (in_array($goodsInfo['type'], self::START_TYPES)) {
            (new DAOGoodsUser($tuid))->add($goodsInfo['goods_id'], $goodsInfo['type'], $goodsInfo['ttl']);
        }
        $userInfo = User::getFormatUserInfo($fuid, true);
        $merit = $userInfo['merit'];
        $incMerit = 0;
        $incMerit += $goodsInfo['merit'];
        $_bless = $bless;
        $bless = intval($bless * Task::getMeritMultiple($merit + $incMerit));
        if (!isset($_REQUEST['is_hide']) || !$_REQUEST['is_hide']) {
            (new DAOFeedsGoods($relateid, $type))->add($relateid, $type, $fuid, $goodsInfo['goods_id'], $goodsInfo['type'], $goodsInfo['image'], $bless);
        }
        Task::help($fuid, $tuid, $type, $incMerit);
        $result = Feeds::endFeed($fuid, $relateid, $type, $bless);
        $result['merit'] = $merit;
        $result['extra_bless'] = $bless - $_bless;
        $result['load_url'] = SimpleConfig::get('SYSTEM_CONFIG')['is_pass'] ? 'https://goods-1257256615.file.myqcloud.com/v20181215/pusa%402x.png' : '';
        return $result;
    }

    /**
     * 根据礼物属性，随机奖励祝福
     * @param $goodsInfo
     * @return int
     */
    public static function getBlessFromGoods($goodsInfo) {
        if (strpos($goodsInfo['award'], '-') === false) {
            return $goodsInfo['award'];
        } else {
            list($min, $max) = explode('-', $goodsInfo['award']);
            return mt_rand($min, $max);
        }
    }

    /**
     * 购买礼物随机掉落奖励
     * @param $uid
     * @return int|mixed
     */
    public static function awardRandomGoods($uid) {
        $num = self::getAwardGoods($uid);
        if ($num >= self::FREE_NUM) {
            return 0;
        }

        $awardGoodsId = 0;
        if ($num == 0) {
            $goodIds = [self::TAN_XIANG_ID, self::JIN_LI_ID];
            $awardGoodsId = $goodIds[array_rand($goodIds)];
            self::incFreeGoods($uid, $awardGoodsId);
        } else {
            $value = mt_rand(1, 100);
            if ($value >= 1 && $value <= 50) {
                $awardGoodsId = self::JIN_LI_ID;
                self::incFreeGoods($uid, $awardGoodsId);
            } elseif ($value >= 51 && $value <= 80) {
                $awardGoodsId = self::TAN_XIANG_ID;
                self::incFreeGoods($uid, $awardGoodsId);
            }
        }
        return $awardGoodsId;
    }

    public static function incAwardGoods($uid, $num = 1) {
        $redis = self::getRedis();
        $key = sprintf('award:goods:%s:%d', date('Ymd'), $uid);
        if ($redis->ttl($key) == -1) {
            $redis->expire($key, 86400 * 2);
        }
        return $redis->incrBy($key, $num);
    }

    public static function getAwardGoods($uid) {
        $redis = self::getRedis();
        $key = sprintf('award:goods:%s:%d', date('Ymd'), $uid);
        return $redis->get($key);
    }

    /**
     * 每天给用户免费礼物
     * @param $uid
     * @return bool;
     */
    public static function initFreeGoods($uid) {
        $redis = self::getRedis();
        $key = sprintf('free:goods:%s:%d', date('Ymd'), $uid);
        if (!$redis->exists($key)) {
            $redis->hMSet($key, [self::TAN_XIANG_ID=>self::FREE_NUM, self::JIN_LI_ID=>self::FREE_NUM]);
        }

        if ($redis->ttl($key) == -1) {
            $redis->expire($key, 86400 * 2);
        }
        return true;
    }

    public static function incFreeGoods($uid, $goodsId, $num = 1) {
        $redis = self::getRedis();
        $key = sprintf('free:goods:%s:%d', date('Ymd'), $uid);
        if (!$redis->exists($key)) {
            $redis->hMSet($key, [self::TAN_XIANG_ID=>self::FREE_NUM, self::JIN_LI_ID=>self::FREE_NUM]);
        }
        if ($redis->ttl($key) == -1) {
            $redis->expire($key, 86400 * 2);
        }
        self::incAwardGoods($uid);
        return $redis->hIncrBy($key, $goodsId, $num);
    }

    public static function decFreeGoods($uid, $goodsId, $num = 1) {
        $redis = self::getRedis();
        $key = sprintf('free:goods:%s:%d', date('Ymd'), $uid);
        return $redis->hIncrBy($key, $goodsId, -$num);
    }

    public static function getFreeGoods($uid) {
        $redis = self::getRedis();
        $key = sprintf('free:goods:%s:%d', date('Ymd'), $uid);
        $freeGoods = $redis->hGetAll($key);
        $freeGoods = [
            self::TAN_XIANG_ID => isset($freeGoods[self::TAN_XIANG_ID]) ? $freeGoods[self::TAN_XIANG_ID] : self::FREE_NUM,
            self::JIN_LI_ID => isset($freeGoods[self::JIN_LI_ID]) ? $freeGoods[self::JIN_LI_ID] : self::FREE_NUM
        ];
        return $freeGoods;
    }
}