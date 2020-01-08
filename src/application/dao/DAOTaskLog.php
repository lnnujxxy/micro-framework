<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

class DAOTaskLog extends DAOProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("tasklog");
    }

    public function add($taskid, $uid, $type, $award, $reason = "") {
        $arr = array(
            'taskid' => $taskid,
            'uid' => $uid,
            'type' => $type,
            'award' => $award,
            'reason' => $reason,
        );

        return $this->insert($this->getTableName(), $arr);
    }

    public function listing($uid, $type, $subType = 0, $offset = 0, $limit = 20) {
        $offset = intval($offset);
        $limit = intval($limit);
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid = ? AND type = ?";
        if ($offset) {
            $sql .= " AND id < ? ";
            $params = [$uid, $type, $offset];
        } else {
            $params = [$uid, $type];
        }
        if ($subType == 1) {
            $sql .= " AND award > 0";
        } else if ($subType == 2) {
            $sql .= " AND award < 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT $limit";
        return $this->getAll($sql, $params);
    }

    private function getFields() {
        return "id, taskid, uid, type, award, created_at, reason";
    }
}