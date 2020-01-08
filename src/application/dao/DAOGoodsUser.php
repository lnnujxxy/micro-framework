<?php
namespace Pepper\Framework\Dao;

class DAOGoodsUser extends DAOProxy
{
    /*
     * __construct
     */
    public function __construct($userid) {
        parent::__construct($userid);
        $this->setTableName("goods_user");
    }

    public function add($goodsId, $goodsType, $ttl) {
        // 永久贡品在数据库中设置10年有效
        $expireAt = $ttl ? (time() + $ttl) : (time() + 86400 * 3650);
        if ($this->getGoodsByUidType($goodsType)) {
            $arr['goods_id']    = $goodsId;
            $arr['expired_at']  = $expireAt;
            return $this->update($this->getTableName(), $arr, " uid = ? AND goods_type = ?", [$this->getSplitId(), $goodsType]);
        } else {
            $arr['uid']         = $this->getSplitId();
            $arr['goods_id']    = $goodsId;
            $arr['goods_type']  = $goodsType;
            $arr['expired_at']  = $expireAt;
            return $this->insert($this->getTableName(), $arr);
        }
    }

    public function getGoodsByUidType($goodsType) {
        $sql = "SELECT " . $this->getFields() . " FROM ". $this->getTableName() . " WHERE uid = ? AND goods_type = ?";
        return $this->getRow($sql, [$this->getSplitId(), $goodsType]);
    }

    public function getUserGoods() {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid = ? ";
        $sql .= " AND expired_at >=  " . time();

        return $this->getAll($sql, $this->getSplitId());
    }

    public function countUserGoods() {
        $sql = "SELECT count(0) FROM " . $this->getTableName() . " WHERE uid = ? ";
        $sql .= " AND expired_at >=  " . time();

        return $this->getOne($sql, $this->getSplitId());
    }

    private function getFields()
    {
        return "id, uid, goods_id, goods_type, expired_at, created_at, updated_at";
    }
}
