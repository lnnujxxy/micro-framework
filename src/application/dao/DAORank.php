<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/12/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

use Pepper\Framework\Model\Feeds;
use Pepper\Framework\Model\Rank;

class DAORank extends DAOProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("rank");
    }

    public function updateRank($relateid, $type, $uid, $bless, $key) {
        $sql = "INSERT INTO {$this->getTableName()} (relateid, type, uid, `key`, bless) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE bless = ?, `key` = ?";
        return $this->execute($sql, array($relateid, $type, $uid, $key, $bless, $bless, $key));
    }

    public function top($key, $type, $limit = 100) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE `key` = ? AND type = ? ORDER BY bless DESC, updated_at ASC limit ". $limit;
        return $this->getAll($sql, [$key, $type]);
    }

    public function getBest($key, $type, $uid) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid = ? AND `key` = ? AND type = ?";
        return $this->getRow($sql, [$uid, $key, $type]);
    }

    protected function getFields() {
        return "id, relateid, type, uid, bless, created_at, updated_at";
    }
}