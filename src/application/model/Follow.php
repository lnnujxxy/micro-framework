<?php
namespace Pepper\Framework\Model;

use Pepper\Framework\Dao\DAOFollower;
use Pepper\Framework\Dao\DAOFollowing;
use Pepper\Framework\Dao\DAOProxy;
use Pepper\Framework\Lib\Logger;
use Pepper\Framework\Dao\DAOFollowLog;

class Follow
{
    const FOLLOWING_LIMIT = 1000;

    public static function addFollow($uid, $fids, $reason = "") {
        if (!is_array($fids)) {
            $fids = array($fids);
        }

        $followed = array();
        try {
            $dao_proxy     = new DAOProxy();
            $dao_following = new DAOFollowing($uid);

            $dao_proxy->startTrans();
            foreach ($fids as $fid) {
                $followed[$fid] = false;

                if ($uid == $fid || $dao_following->exists($fid)) {
                    continue;
                }
                $dao_following->addFollowing($fid);

                $hisdao_follower = new DAOFollower($fid);
                $hisdao_follower->addFollower($uid);

                $followed[$fid] = true;
            }

            $dao_proxy->commit();
        } catch (\PDOException $e) {
            $dao_proxy->rollback();
            $msg = $e->getMessage();
            Logger::warning("follow-exception-msg: {$msg}");
            throw new \RuntimeException($msg);
        }

        // 写日志库
        $dao_followlog = new DAOFollowLog();
        foreach ($followed as $fid => $value) {
            if ($value) {
                try {
                    $dao_followlog->addFollowlog($uid, $fid, DAOFollowlog::ACTION_ADD, $reason);
                } catch (\PDOException $e) {
                }
            }
        }

        return $followed;
    }

    public static function cancelFollow($uid, $fid, $reason = "") {
        $dao_following = new DAOFollowing($uid);
        if ($following_info = $dao_following->getFollowingInfo($fid)) {
            try {
                $dao_proxy = new DAOProxy();
                $dao_proxy->startTrans();

                $dao_following->delFollowing($fid);

                $dao_follower = new DAOFollower($fid);
                $dao_follower->delFollower($uid);

                $dao_followlog = new DAOFollowlog();
                $dao_followlog->addFollowlog($uid, $fid, DAOFollowlog::ACTION_CANCEL, $reason);

                $dao_proxy->commit();
            } catch (\PDOException $e) {
                $dao_proxy->rollback();
                $msg = $e->getMessage();
                Logger::warning("follow-exception-msg: {$msg}");
                throw new \RuntimeException($msg);
            }
            return true;
        }

        return false;
    }

    public static function countFollowers($uid) {
        $dao_follower = new DAOFollower($uid);
        return $dao_follower->countFollowers();
    }

    public static function countFollowings($uid) {
        $dao_following = new DAOFollowing($uid);
        return $dao_following->countFollowings();
    }

    public static function isFollowed($uid, $fids) {
        if (!$fids) {
            return array();
        }

        if (!is_array($fids)) {
            $fids = array($fids);
        }

        $followed = array();

        $dao_following = new DAOFollowing($uid);
        $followed_list = $dao_following->isFollowed($fids);

        foreach ($fids as $fid) {
            if ($uid == $fid) {
                $followed[$fid] = true;
            } else {
                $followed[$fid] = isset($followed_list[$fid]) && !!$followed_list[$fid];
            }
        }

        return $followed;
    }

    public static function isFollower($uid, $fids) {
        if (!$fids) {
            return array();
        }

        if (!is_array($fids)) {
            $fids = array($fids);
        }

        $follower = array();

        foreach ($fids as $fid) {
            if ($uid == $fid) {
                $follower[$fid] = true;
            } else {
                $followed       = self::isFollowed($fid, $uid);
                $follower[$fid] = $followed[$uid];
            }
        }

        return $follower;
    }
}
