<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/12/16
 * Time: 下午10:38
 */

namespace Pepper\Framework\Model;
use Pepper\Framework\Dao\DAOFeedsGoods;
use Pepper\Framework\Dao\DAORank;
use Pepper\Framework\Traits\RedisTrait;

class Rank {
    use RedisTrait;

    public static function updateRankFeed($relateid, $type, $bless, $uid) {
        $key = sprintf("rank:feed:%d:%d", $relateid, $type);
        $redis = self::getRedis($key);
        return $redis->zIncrBy($key, $bless, $uid);
    }

    public static function getRankFeed($relateid, $type) {
        $key = sprintf("rank:feed:%d:%d", $relateid, $type);
        $redis = self::getRedis($key);
        $ranks  = $redis->zRevRange($key, 0, 5, true);
        $list = [];
        foreach ($ranks as $uid => $bless) {
            $userInfo = User::getFormatUserInfo($uid);
            if (!isset($userInfo['uid'])) {
                continue;
            }
            $item['user_info'] = $userInfo;
            $item['bless'] = $bless;
            $list[] = $item;
        }
        return $list;
    }

    public static function getRankFeedDetail($relateid, $type, $offset = 0, $limit = 5) {
        $key = sprintf("rank:feed:%d:%d", $relateid, $type);
        $redis = self::getRedis($key);
        $ranks  = $redis->zRevRange($key, $offset, $offset + $limit, true);
        if (empty($ranks)) {
            return [
                'offset' => $offset + $limit,
                'list' => [],
                'more' => false
            ];
        }

        $uids = array_keys($ranks);
        $userInfos = User::getFormatUsersInfo($uids);
        $list = [];

        $daoFeedsGoods = new DAOFeedsGoods($relateid, $type);
        foreach ($ranks as $uid => $bless) {
            if (!isset($userInfos[$uid]['uid'])) {
                continue;
            }
            $item['user_info'] = $userInfos[$uid];
            $item['bless'] = $bless;
            $item['goods'] = $daoFeedsGoods->getGoodsDetail($relateid, $type, $uid, 4);
            $list[] = $item;
        }
        return [
            'offset' => $offset + $limit,
            'list' => $list,
            'more' => count($list) >= $limit,
        ];
    }

    public static function getRank($type) {
        $cacheKey = sprintf('rank:all:%d', $type);
        $redis = self::getRedis($cacheKey);
        $cacheData = $redis->get($cacheKey);
        if ($cacheData === false) {
            $key = self::getKey($type);
            $daoRank = new DAORank();
            $top = $daoRank->top($key, $type);
            if (empty($top)) {
                return [];
            }
            $uids = array_column($top, 'uid');
            $userInfos = User::getFormatUsersInfo($uids);

            foreach($top as &$row) {
                $row['avatar'] = $userInfos[$row['uid']]['avatar'];
                $row['nickname'] = $userInfos[$row['uid']]['nickname'];
            }

            if ($top) {
                $redis->set($cacheKey, json_encode($top), 3);
            }
        } else {
            $top = json_decode($cacheData, true);
        }
        return $top;
    }

    public static function updateRank($relateid, $type, $uid, $bless) {
        if (!in_array($type, Feeds::supportTypeIds)) {
            return false;
        }

        $key = Rank::getKey($type);
        return (new DAORank())->updateRank($relateid, $type, $uid, $bless, $key);
    }

    public static function getKey($type) {
        $key = "";
        if ($type == Feeds::PENANCE_TYPE_ID || $type == Feeds::PRAY_TYPE_ID) {
            $key = date('YW');
        } elseif ($type == Feeds::LUCKY_TYPE_ID) {
            $key = date('Ymd');
        }
        return $key;
    }
}