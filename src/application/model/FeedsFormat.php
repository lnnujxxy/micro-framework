<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午9:46
 */
namespace Pepper\Framework\Model;

class FeedsFormat
{
    public static function formatListPenance($list, $userInfos, $idsMap = null) {
        if (empty($list)) {
            return [];
        }
        $type = Feeds::PENANCE_TYPE_ID;
        $formatList = [];
        foreach ($list as $index=>$_row) {
            $row['id'] = $_row['id'];
            $row['uid'] = $_row['uid'];
            $row['type'] = $type;
            $row['content'] = json_decode($_row['content'], true);
            $row['created_at'] = strtotime($_row['created_at']);
            $row['helps'] = $_row['helps'];
            $row['comments'] = $_row['comments'];
            $row['shares'] = $_row['shares'];
            $row['feedid'] =  isset($idsMap[$_row['id'].'_'.$type]) ? $idsMap[$_row['id'].'_'.$type] : 0;
            $row['user_info'] = $userInfos[$row['uid']];
            $formatList[] = $row;
        }
        return $formatList;
    }

    public static function formatListPray($list, $userInfos, $idsMap = null) {
        if (empty($list)) {
            return [];
        }
        $type = Feeds::PRAY_TYPE_ID;
        $formatList = [];
        foreach ($list as $index=>$_row) {
            $row['id'] = $_row['id'];
            $row['uid'] = $_row['uid'];
            $row['type'] = $type;
            $row['content'] = json_decode($_row['content'], true);
            $row['created_at'] = strtotime($_row['created_at']);
            $row['helps'] = $_row['helps'];
            $row['comments'] = $_row['comments'];
            $row['shares'] = $_row['shares'];
            $row['feedid'] =  isset($idsMap[$_row['id'].'_'.$type]) ? $idsMap[$_row['id'].'_'.$type] : 0;
            $row['user_info'] = $userInfos[$row['uid']];
            $formatList[] = $row;
        }
        return $formatList;
    }

    public static function formatListLucky($list, $userInfos, $idsMap = null) {
        if (empty($list)) {
            return [];
        }

        $type = Feeds::LUCKY_TYPE_ID;
        $formatList = [];
        foreach ($list as $index=>$_row) {
            $row['id'] = $_row['id'];
            $row['uid'] = $_row['uid'];
            $row['type'] = $type;
            $row['content'] = json_decode($_row['content'], true);
            $row['created_at'] = strtotime($_row['created_at']);
            $row['helps'] = $_row['helps'];
            $row['comments'] = $_row['comments'];
            $row['shares'] = $_row['shares'];
            $row['feedid'] =  isset($idsMap[$_row['id'].'_'.$type]) ? $idsMap[$_row['id'].'_'.$type] : 0;
            $row['user_info'] = $userInfos[$row['uid']];
            $formatList[] = $row;
        }
        return $formatList;
    }

    public static function formatPenanceInfo($row) {
        if (empty($row)) {
            return [];
        }
        $type = Feeds::PENANCE_TYPE_ID;
        return [
            'id' => $row['id'],
            'uid' => $row['uid'],
            'type' => $type,
            'content' => json_decode($row['content'], true),
            'created_at' => strtotime($row['created_at']),
            'helps' => $row['helps'],
            'comments' => $row['comments'],
            'shares' => $row['shares'],
            'bless' => $row['bless'],
            'user_info' => User::getFormatUserInfo($row['uid'])
        ];
    }

    public static function formatPrayInfo($row) {
        if (empty($row)) {
            return [];
        }
        $type = Feeds::PRAY_TYPE_ID;
        return [
            'id' => $row['id'],
            'uid' => $row['uid'],
            'type' => $type,
            'content' => json_decode($row['content'], true),
            'created_at' => strtotime($row['created_at']),
            'helps' => $row['helps'],
            'comments' => $row['comments'],
            'shares' => $row['shares'],
            'bless' => $row['bless'],
            'user_info' => User::getFormatUserInfo($row['uid'])
        ];
    }

    public static function formatLuckyInfo($row) {
        if (empty($row)) {
            return [];
        }
        $type = Feeds::LUCKY_TYPE_ID;
        return [
            'id' => $row['id'],
            'uid' => $row['uid'],
            'type' => $type,
            'content' => json_decode($row['content'], true),
            'created_at' => strtotime($row['created_at']),
            'helps' => $row['helps'],
            'comments' => $row['comments'],
            'shares' => $row['shares'],
            'bless' => $row['bless'],
            'user_info' => User::getFormatUserInfo($row['uid'])
        ];
    }
}