<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

class DAOContent extends DAOProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("content");
    }

    public function getGroupContent($type) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE type = ? ORDER BY num DESC";
        return $this->getAll($sql, [$type]);
    }

    public function getRandContent($type, $dataType = "object") {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE type = ? AND data_type = ?
        AND content != '自定义' ORDER BY RAND() LIMIT 1";
        return $this->getRow($sql, [$type, $dataType]);
    }

    private function getFields() {
        return "id, type, content, num, data_type, url";
    }
}