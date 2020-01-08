<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

use Pepper\Framework\Model\Goods;

class DAOGoods extends DAOProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("goods");
    }

    public function getGroupGoods($goodsTypes = array()) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName();
        if ($goodsTypes) {
            $in  = str_repeat('?,', count($goodsTypes) - 1) . '?';
            $sql .= " WHERE type IN (" . $in . ") ";
        }
        $sql .= " ORDER BY price DESC";
        return $this->getAll($sql, $goodsTypes);
    }

    public function gets($goodsIds) {
        $in  = str_repeat('?,', count($goodsIds) - 1) . '?';
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE goods_id IN (" . $in .")";
        $sth = $this->query($sql, $goodsIds);
        $list = [];
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $list[$row['goods_id']] = $row;
        }
        return $list;
    }

    public function get($goodsId) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE goods_id = ?";
        return $this->getRow($sql, $goodsId);
    }

    public function getRandGoods() {
        $types = Goods::DEFAULT_TYPES;
        $in  = str_repeat('?,', count($types) - 1) . '?';
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE type IN(" . $in . ") AND price <= 1000 ORDER BY RAND() LIMIT 1";
        return $this->getRow($sql, $types);
    }

    private function getFields() {
        return "goods_id, name, image, type, price, ttl, merit, award";
    }
}