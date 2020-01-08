<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

class DAOFeedsProxy extends DAOProxy
{
    public function add($arr) {
        return $this->insert($this->getTableName(), $arr);
    }

    public function get($id) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE id = ?";
        return $this->getRow($sql, [$id]);
    }

    public function gets($ids) {
        $in  = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE id IN (" . $in .")";
        return $this->getAll($sql, $ids);
    }

    public function total($uid, $objectId = null) {
        $sql = "SELECT count(0) FROM " . $this->getTableName() . " WHERE uid = ? AND state = ?";
        $params = [$uid, 1];
        if ($objectId) {
            $sql .= " AND object_id = ?";
            array_push($params, $objectId);
        }
        return $this->getOne($sql, $params);
    }

    public function getLastest($uid) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid = ? AND state = 1 ORDER BY created_at DESC limit 1";
        return $this->getRow($sql, $uid);
    }

    public function listing($uid, $objectId = null, $offset = 0, $limit = 20) {
        $offset = intval($offset);
        $limit = intval($limit);
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid = ?";
        if ($offset) {
            $sql .= " AND id < ? AND state = ?";
            $params = [$uid, $offset, 1];
        } else {
            $sql .= " AND state = ?";
            $params = [$uid, 1];
        }
        if ($objectId) {
            $sql .= " AND object_id = ?";
            array_push($params, $objectId);
        }
        $sql .= " ORDER BY id DESC LIMIT $limit";
        return $this->getAll($sql, $params);
    }

    public function appendContent($relateid, $key, $val) {
        $sql = "SELECT content FROM " . $this->getTableName() . " WHERE id = ?";
        $content = $this->getOne($sql, $relateid);
        if ($content) {
            $arr = json_decode($content, true);
            if (is_array($arr)) {
                $arr[$key] = $val;
                $content = json_encode($arr, JSON_UNESCAPED_UNICODE);
                $sql = "UPDATE " . $this->getTableName() . " SET content = ? WHERE id = ?";
                $this->execute($sql, [$content, $relateid]);
            }
        }
    }

    public function appendContents($relateid, $item) {
        $sql = "SELECT content FROM " . $this->getTableName() . " WHERE id = ?";
        $content = $this->getOne($sql, $relateid);
        if ($content) {
            $arr = json_decode($content, true);
            if (is_array($arr)) {
                foreach ($item as $key=>$val) {
                    $arr[$key] = $val;
                }
                $content = json_encode($arr, JSON_UNESCAPED_UNICODE);
                $sql = "UPDATE " . $this->getTableName() . " SET content = ? WHERE id = ?";
                $this->execute($sql, [$content, $relateid]);
            }
        }
    }

    public function totalToday($uid) {
        $sql = "SELECT count(0) FROM " . $this->getTableName() . " WHERE uid = ? AND created_at BETWEEN ? AND ?";
        $start = date("Y-m-d H:i:s", strtotime(date('Y-m-d')));
        $end = date("Y-m-d H:i:s", strtotime(date('Y-m-d', time()+86400)));
        return $this->getOne($sql, [$uid, $start, $end]);
    }

    public function getObjectIds($uid) {
        $sql = "SELECT object_id FROM " . $this->getTableName() . " WHERE uid = ? GROUP BY object_id";
        $sth = $this->query($sql, $uid);
        $objectIds = [];
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $objectIds[] = $row['object_id'];
        }
        return $objectIds;
    }

    public function incrComments($id, $num = 1) {
        $sql = "UPDATE " . $this->getTableName() . " SET comments = comments + ? WHERE id = ?";
        return $this->execute($sql, [$num, $id]);
    }

    public function incrHelps($id, $num = 1) {
        $sql = "UPDATE " . $this->getTableName() . " SET helps = helps + ? WHERE id = ?";
        return $this->execute($sql, [$num, $id]);
    }

    public function incrShares($id, $num = 1) {
        $sql = "UPDATE " . $this->getTableName() . " SET shares = shares + ? WHERE id = ?";
        return $this->execute($sql, [$num, $id]);
    }

    public function incrBless($id, $num = 1) {
        $sql = "UPDATE " . $this->getTableName() . " SET bless = bless + ? WHERE id = ?";
        return $this->execute($sql, [$num, $id]);
    }

    public function clearFeeds($uid) {
        $sql = "DELETE FROM " . $this->getTableName() . " WHERE uid = ?";
        return $this->execute($sql, [$uid]);
    }

    protected function getFields() {

    }
}