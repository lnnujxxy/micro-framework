<?php
namespace Pepper\Framework\Dao;

class DAOFeedsGoods extends DAOProxy
{
    /*
     * __construct
     */
    public function __construct($relateid, $type) {
        parent::__construct(crc32($relateid.$type));
        $this->setTableName("feeds_goods");
    }

    public function add($relateid, $type, $sender, $goodsId, $goodsType = 0, $goodsImage = "", $bless = 0) {
        $arr = [
            'relateid' => $relateid,
            'type' => $type,
            'sender' => $sender,
            'goods_id' => $goodsId,
            'goods_type' => $goodsType,
            'goods_image' => $goodsImage,
            'bless' => $bless
        ];
        return $this->insert($this->getTableName(), $arr);
    }

    public function updateBless($relateid, $type, $bless) {
        $record = [
            'bless' => $bless,
        ];
        return $this->update($this->getTableName(), $record, " relateid = ? AND type = ? ", [$relateid, $type]);
    }

    public function getGoodsList($relateid, $type, $goodsTypes = array()) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE relateid = ? AND type = ?";
        $params = [$relateid, $type];
        if ($goodsTypes) {
            $sql .= " AND goods_type IN (" . str_repeat('?,', count($goodsTypes) - 1) . '?' . ')';
            $params = array_merge($params, $goodsTypes);
        }
        $sql .= " ORDER BY id DESC";
        return $this->getAll($sql, $params);
    }

    public function getGoodsDetail($relateid, $type, $sender, $limit = 4) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE relateid = ? AND type = ? 
                AND sender = ?  ORDER BY bless DESC limit " . intval($limit);
        return $this->getAll($sql, [$relateid, $type, $sender]);
    }

    public function totalGoods($relateid, $type, $sender, $goodsType) {
        $sql = "SELECT count(0) FROM " . $this->getTableName() . " WHERE relateid = ? AND type = ? AND sender = ? AND goods_type = ?";
        return $this->getOne($sql, [$relateid, $type, $sender, $goodsType]);
    }

    private function getFields() {
        return "id, relateid, type, sender, goods_id, goods_type, goods_image, bless,created_at, updated_at";
    }
}
