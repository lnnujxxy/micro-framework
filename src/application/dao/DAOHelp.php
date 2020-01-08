<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

class DAOHelp extends DAOFeedsProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("help");
    }

    protected function getFields() {
        return "id, uid, vid, content, ispublic, state, comments, shares, created_at, updated_at";
    }

    public function listingByVid($vid, $offset = 0, $limit = 20) {
        $offset = intval($offset);
        $limit = intval($limit);
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE vid = ?";
        if ($offset) {
            $sql .= " AND id < ? AND state = ?";
            $params = [$vid, $offset, 1];
        } else {
            $sql .= " AND state = ?";
            $params = [$vid, 1];
        }
        $sql .= " ORDER BY id DESC LIMIT $limit";

        return $this->getAll($sql, $params);
    }
}