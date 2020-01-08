<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

use Pepper\Framework\Model\User;

class DAOFeeds extends DAOProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("feeds");
    }

    public function add($arr) {
        return $this->insert($this->getTableName(), $arr);
    }

    public function getOfficialFeeds($offset = 0, $limit = 20) {
        $uids = User::OFFICIAL_UIDS;
        $in  = str_repeat('?,', count($uids) - 1) . '?';
        $offset = intval($offset);
        $limit = intval($limit);
        $params = $uids;
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid IN (" . $in .") AND ";
        if ($offset) {
            $sql .= " feedid < ? AND ispublic = ? AND state = ?";
            array_push($params, $offset, 1, 1);
        } else {
            $sql .= " ispublic = ? AND state = ?";
            array_push($params, 1, 1);
        }
        $sql .= " ORDER BY feedid DESC LIMIT $limit";
        return $this->getAll($sql, $params);
    }

    public function getHotFeeds($limit = 3, $excludeFeedIds = array()) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE ispublic = ? AND state = ?";
        if ($excludeFeedIds) {
            $excludeFeedIds = array_map('intval', $excludeFeedIds);
            $sql .= " AND feedid NOT IN (".join(',', $excludeFeedIds).") ";
        }
        $sql .= " ORDER BY comments DESC LIMIT " . intval($limit);
        return $this->getAll($sql, [1, 1]);
    }

    public function getFeeds($offset = 0, $limit = 20, $type = 0) {
        $offset = intval($offset);
        $limit = intval($limit);
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE ";
        if ($offset) {
            $sql .= " feedid < ? AND ispublic = ? AND state = ?";
            $params = [$offset, 1, 1];
        } else {
            $sql .= " ispublic = ? AND state = ?";
            $params = [1, 1];
        }
        if ($type) {
            $sql .= " AND type = " . intval($type);
        }
        $sql .= " ORDER BY feedid DESC LIMIT $limit";
        return $this->getAll($sql, $params);
    }

    public function getMyFeeds($uid, $offset = 0, $limit = 20)
    {
        $offset = intval($offset);
        $limit = intval($limit);
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE ";
        if ($offset) {
            $sql .= " uid = ? AND feedid < ? AND state = ?";
            $params = [$uid, $offset, 1];
        } else {
            $sql .= " uid = ? AND  state = ?";
            $params = [$uid, 1];
        }
        $sql .= " ORDER BY feedid DESC LIMIT $limit";
        return $this->getAll($sql, $params);
    }

    public function getLatest($uid)
    {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid = ? ";
        $sql .= " ORDER BY feedid DESC LIMIT 1";
        return $this->getRow($sql, $uid);
    }

    public function clearFeeds($uid) {
        $sql = "DELETE FROM " . $this->getTableName() . " WHERE uid = ?";
        return $this->execute($sql, [$uid]);
    }

    public function incComments($relateid, $type, $num = 1) {
        $sql = "UPDATE " . $this->getTableName() . " SET comments = comments + ? WHERE relateid = ? AND type = ?";
        return $this->execute($sql, [$num, $relateid, $type]);
    }

    private function getFields()
    {
        return "feedid, uid, relateid, type, ispublic, state, created_at, updated_at";
    }
}