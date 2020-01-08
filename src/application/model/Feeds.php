<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午9:46
 */
namespace Pepper\Framework\Model;
use Pepper\Framework\Dao\DAOContent;
use Pepper\Framework\Dao\DAOFeeds;
use Pepper\Framework\Dao\DAOFeedsGoods;
use Pepper\Framework\Dao\DAOLucky;
use Pepper\Framework\Dao\DAOPenance;
use Pepper\Framework\Dao\DAOPray;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Lib\SimpleConfig;
use Pepper\Process\ProcessClient;

class Feeds
{
    const PENANCE_TYPE_ID = 1; // 悔过
    const PRAY_TYPE_ID = 2; // 许愿
    const LUCKY_TYPE_ID = 5; // 转运

    const supportTypeIds = [
        self::PENANCE_TYPE_ID,
        self::PRAY_TYPE_ID,
        self::LUCKY_TYPE_ID,
    ];

    const supportJsonKeys = [
        'name', 'avatar', 'num', 'finish_num', 'card', 'image',
        'overtime', 'vid', 'uid', 'content',
    ];

    public static function addFeed($uid, $type, $content, $extends, $ispublic = 1) {
        $relateid = 0;
        switch ($type) {
            case self::PENANCE_TYPE_ID:
                $daoPenance = new DAOPenance();
                $params = [
                    'uid' => $uid,
                    'object_id' => $extends['object_id'],
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                    'ispublic' => $ispublic,
                ];
                $relateid = $daoPenance->add($params);
                break;
            case self::PRAY_TYPE_ID:
                $daoPray = new DAOPray();
                $params = [
                    'uid' => $uid,
                    'object_id' => $extends['object_id'],
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                    'ispublic' => $ispublic,
                ];
                $relateid = $daoPray->add($params);
                // 给朋友圈好友祈福
                $extends['object_id'] == 35 && Task::doTask($uid, 'pray_circle');
                break;
            case self::LUCKY_TYPE_ID:
                $daoLucky = new DAOLucky();
                $totalToday = $daoLucky->totalToday($uid);
                Interceptor::ensureFalse($totalToday >= 1, ERROR_TOAST, '每天限发1条新转运');
                $params = [
                    'uid' => $uid,
                    'object_id' => $extends['object_id'],
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                    'ispublic' => $ispublic,
                ];

                $relateid = $daoLucky->add($params);
                Task::doTask($uid, 'lucky');
                break;
        }
        $args = [
            'uid' => $uid,
            'relateid' => $relateid,
            'type' => $type,
            'ispublic' => $ispublic
        ];
        (new DAOFeeds())->add($args);
        // 机器互助
        for ($i=0; $i<3; $i++) {
            $rank = $i * $i * 3600 + mt_rand(0, 3600);
            ProcessClient::getInstance(PROJECT_NAME)->addTask('robot_help',
                array('uid' => $uid, 'relateid' => $relateid, 'type' => $type), $rank);
        }
        return $args;
    }

    public static function getFeeds($feedid, $limit = 20, $type = 0) {
        $feeds = (new DAOFeeds())->getFeeds($feedid, $limit, $type);
        $list = self::formatFeeds($feeds);
        if ($feedid == 0 && $type == 0) {
            $recFeeds = self::getRecFeeds();
            $list = array_merge($recFeeds, $list);
            foreach ($list as $item) {
                $weights[] = $item['weight'];
            }
            array_multisort($weights, SORT_DESC, $list);
        }

        return [
            'list' => (array)$list,
            'more' => count($list) >= $limit,
            'offset' => $feeds ? $feeds[count($feeds)-1]['feedid'] : 0,
        ];
    }

    public static function getOfficialFeeds($feedid, $limit = 20) {
        $feeds = (new DAOFeeds())->getOfficialFeeds($feedid, $limit);
        $list = self::formatFeeds($feeds);
        return [
            'list' => (array)$list,
            'more' => count($list) >= $limit,
            'offset' => $feeds ? $feeds[count($feeds)-1]['feedid'] : 0,
        ];
    }

    public static function getRecFeeds() {
        $officialFeeds = (new DAOFeeds())->getOfficialFeeds(0, 3);
        $officialFeeds =  self::formatFeeds($officialFeeds);
        foreach($officialFeeds as &$feed) {
            $feed['weight'] = 2;
        }
        if ($officialFeeds) {
            $excludeFeedids = array_column($officialFeeds, 'feedid');
        } else {
            $excludeFeedids = array();
        }
        $hotFeeds = (new DAOFeeds())->getHotFeeds(3, $excludeFeedids);
        $hotFeeds =  self::formatFeeds($hotFeeds);
        foreach($hotFeeds as &$feed) {
            $feed['weight'] = 1;
        }
        return array_merge($officialFeeds, $hotFeeds);
    }

    public static function getMyFeeds($uid, $feedid, $limit = 20) {
        $feeds = (new DAOFeeds())->getMyFeeds($uid, $feedid, $limit);
        $list = self::formatFeeds($feeds);
        return [
            'list' => (array)$list,
            'more' => count($list) >= $limit,
            'offset' => $feeds ? $feeds[count($feeds)-1]['feedid'] : 0,
        ];
    }

    private static function formatFeeds($feeds) {
        if (empty($feeds)) {
            return [];
        }

        $typeRelateids = $list = $idsMap = $uids = $userInfos = [];
        foreach ($feeds as $feed) {
            $idsMap[$feed['relateid'] . '_' . $feed['type']] = $feed['feedid'];
            $typeRelateids[$feed['type']][] = $feed['relateid'];
            $uids[] = $feed['uid'];
        }

        $userInfos = User::getFormatUsersInfo(array_unique($uids));
        foreach ($typeRelateids as $type => $relateids) {
            if ($relateids && in_array($type, self::supportTypeIds)) {
                $daoFactory = self::factoryDB($type);
                $_list = $daoFactory->gets($relateids);
                $list = array_merge($list, self::factoryFormatList($type, $_list, $userInfos, $idsMap));
            }
        }
        if ($list) {
            $feedids = array_column($list, 'feedid');
            array_multisort($feedids, SORT_DESC, $list);
        }
        return $list;
    }

    public static function getMyTypeFeeds($uid, $type, $objectId, $offset, $limit = 20) {
        $userInfos = User::getFormatUsersInfo($uid);
        $list = [];

        $daoFactory = self::factoryDB($type);
        $_list = $daoFactory->listing($uid, $objectId, $offset, $limit);
        $list = array_merge($list, self::factoryFormatList($type, $_list, $userInfos));
        $total = $daoFactory->total($uid);
        $objects = $groupObjects = [];
        if (in_array($type, self::supportTypeIds)) {
            $objectIds = $daoFactory->getObjectIds($uid);
            $_groupObjects = Feeds::getGroupContent($type)['object'];
            foreach ($_groupObjects as $groupObject) {
                $groupObjects[$groupObject['id']] = $groupObject;
            }

            foreach ($objectIds as $objectId) {
                if (isset($groupObjects[$objectId])) {
                    $objects[] = $groupObjects[$objectId];
                }
            }
        }

        return [
            'list' => (array)$list,
            'more' => count($list) >= $limit,
            'offset' => $list ? $list[count($list)-1]['id'] : 0,
            'total' => $total,
            'objects' => $objects,
            'can_add' => self::canAdd($uid, $type)
        ];
    }

    public static function getGroupContent($type) {
        $systemConfig = SimpleConfig::get('SYSTEM_CONFIG');
        $_groupContent =  (new DAOContent())->getGroupContent($type);
        $groupContent = [];
        foreach ($_groupContent as $row) {
            if (!$systemConfig['is_pass'] && $row['content'] == '自定义') {
                continue;
            }
            $groupContent[$row['data_type']][] = $row;
        }
        return $groupContent;
    }

    public static function getName($type) {
        if ($type == self::PENANCE_TYPE_ID) {
            return "悔过";
        } else if ($type == self::PRAY_TYPE_ID) {
            return "许愿";
        } else if ($type == self::LUCKY_TYPE_ID) {
            return "转运";
        }
        return "";
    }

    public static function getFeedInfo($relateid, $type) {
        $row = self::factoryDB($type)->get($relateid);
        return self::factoryFormatInfo($type, $row);
    }

    public static function getLastest($uid) {
        $lastest = [];
        $row = (new DAOPenance())->getLastest($uid);
        $lastest[self::PENANCE_TYPE_ID] = FeedsFormat::formatPenanceInfo($row);
        $row = (new DAOPray())->getLastest($uid);
        $lastest[self::PRAY_TYPE_ID] = FeedsFormat::formatPrayInfo($row);
        $row = (new DAOLucky())->getLastest($uid);
        $lastest[self::LUCKY_TYPE_ID] = FeedsFormat::formatLuckyInfo($row);
        return $lastest;
    }

    public static function getLastestByType($uid, $type) {
        $row = self::factoryDB($type)->getLastest($uid);
        return self::factoryFormatInfo($type, $row);
    }

    public static function getLatest($uid) {
        $latest = (new DAOFeeds())->getLatest($uid);
        if (empty($latest)) {
            return [];
        }
        $row = self::factoryDB($latest['type'])->get($latest['relateid']);
        return self::factoryFormatInfo($latest['type'], $row);
    }

    public static function clearFeeds($uid) {
        (new DAOFeeds())->clearFeeds($uid);
        foreach (self::supportTypeIds as $type) {
            self::factoryDB($type)->clearFeeds($uid);
        }
    }

    public static function appendKey($relateid, $type, $key, $val) {
        self::factoryDB($type)->appendContent($relateid, $key, $val);
    }

    public static function appendKeys($relateid, $type, $items) {
        self::factoryDB($type)->appendContents($relateid, $items);
    }

    public static function endFeed($userid, $relateid, $type, $curBless) {
        $factoryDB = self::factoryDB($type);
        $feedInfo = $factoryDB->get($relateid);

        Feeds::incrField($relateid, $type, 'bless', $curBless);
        Rank::updateRankFeed($relateid, $type, $curBless, $userid);

        $newFeedInfo = $factoryDB->get($relateid);
        $level = Task::getBlessLevel($type, $newFeedInfo['bless']);
        self::appendKeys($relateid, $type, ['level' => $level, 'bless' => $newFeedInfo['bless']]);

        Rank::updateRank($relateid, $type, $feedInfo['uid'], $newFeedInfo['bless']); // 更新榜单
        return ['level' => $level, 'type' => $type, 'bless' => $newFeedInfo['bless'], 'cur_bless' => $curBless];
    }

    public static function incrField($relateid, $type, $field, $num = 1) {
        if (!in_array($field, ['comments', 'helps', 'shares', 'bless'])) {
            return false;
        }

        $func = 'incr' . ucfirst($field);
        $factory = self::factoryDB($type);
        if (!method_exists($factory, $func)) {
            return false;
        }
        return $factory->$func($relateid, $num);
    }

    public static function factoryDB($type) {
        $db = null;
        switch ($type) {
            case self::PENANCE_TYPE_ID:
                $db = new DAOPenance();
                break;
            case self::PRAY_TYPE_ID:
                $db = new DAOPray();
                break;
            case self::LUCKY_TYPE_ID:
                $db = new DAOLucky();
                break;
        }
        return $db;
    }

    public static function factoryFormatInfo($type, $row) {
        switch ($type) {
            case self::PENANCE_TYPE_ID:
                return FeedsFormat::formatPenanceInfo($row);
                break;
            case self::PRAY_TYPE_ID:
                return FeedsFormat::formatPrayInfo($row);
                break;
            case self::LUCKY_TYPE_ID:
                return FeedsFormat::formatLuckyInfo($row);
                break;
                break;
        }
        return [];
    }

    public static function factoryFormatList($type, $list, $userInfos, $idsMap = null) {
        switch ($type) {
            case self::PENANCE_TYPE_ID:
                return FeedsFormat::formatListPenance($list, $userInfos, $idsMap);
                break;
            case self::PRAY_TYPE_ID:
                return FeedsFormat::formatListPray($list, $userInfos, $idsMap);
                break;
            case self::LUCKY_TYPE_ID:
                return FeedsFormat::formatListLucky($list, $userInfos, $idsMap);
                break;
        }
        return [];
    }

    public static function getFeedsGoods($relateid, $type, $goodsTypes) {
        $list = (new DAOFeedsGoods($relateid, $type))->getGoodsList($relateid, $type, $goodsTypes);
        if (empty($list)) {
            return [];
        }

        $feedGoods = $_feedGoods = $uids = $goodsIds = [];
        foreach ($list as $row) {
            $uids[] = $row['sender'];
            $goodsIds[] = $row['goods_id'];
            $_feedGoods[$row['sender'] . $row['goods_id']]['sender'] = $row['sender'];
            $_feedGoods[$row['sender'] . $row['goods_id']]['goods_id'] = $row['goods_id'];
            if (isset($_feedGoods[$row['sender'] . $row['goods_id']]['times'])) {
                $_feedGoods[$row['sender'] . $row['goods_id']]['times']++;
            } else {
                $_feedGoods[$row['sender'] . $row['goods_id']]['times'] = 0;
            }
            $_feedGoods[$row['sender'] . $row['goods_id']]['created_at'] = $row['created_at'];
        }
        $userInfos = User::getFormatUsersInfo($uids);
        $goodsInfos = Goods::getGoodsInfos($goodsIds);

        foreach ($_feedGoods as $key => $val) {
            $val['sender_info'] = $userInfos[$val['sender']];
            $val['goods_info'] = $goodsInfos[$val['goods_id']];
            $feedGoods[$key] = $val;
        }
        $feedGoods = array_values($feedGoods);
        $times = array_column($feedGoods, 'times');
        $senders = array_column($feedGoods, 'sender');
        $createdAts = array_column($feedGoods, 'created_at');

        array_multisort($senders, SORT_DESC, $times, SORT_DESC, $createdAts, SORT_DESC, $feedGoods);
        return $feedGoods;
    }

    public static function canAdd($uid, $type) {
        if ($type == self::LUCKY_TYPE_ID) {
            $daoPenance = new DAOLucky();
            $totalToday = $daoPenance->totalToday($uid);
            return $totalToday < 1;
        }
        return true;
    }
}