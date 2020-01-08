<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

class DAOLection extends DAOProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("lection");
    }

    public function listing($offset = 0, $limit = 20) {
        $offset = intval($offset);
        $limit = intval($limit);
        $sql = "SELECT " . $this->getFields() . " FROM ". $this->getTableName() . " WHERE state = ?";
        if ($offset) {
            $sql .= " AND id < $offset ";
        }
        $sql .= " ORDER BY orderby DESC LIMIT $limit";

        $list = $this->getAll($sql, [1]);
        foreach ($list as $index=>&$row) {
            $row['text'] = json_decode($row['text'], true);
        }
        return $list;
    }

    public function getTotal() {
        $sql = "SELECT count(0) FROM ". $this->getTableName() . " WHERE state = ?";
        return $this->getOne($sql, [1]);
    }

    public function get($id) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE id = ?";
        return $this->getRow($sql, $id);
    }

    private function getFields() {
        return "id, text, orderby, state, created_at, updated_at";
    }
}