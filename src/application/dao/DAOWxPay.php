<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

class DAOWxPay extends DAOProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("wxpay");
    }

    public function add($uid, $goodsId, $amount, $tradeNo, $prepayId, $tuid = 0) {
        $arr = [
            'uid' => $uid,
            'goods_id' => $goodsId,
            'amount' => $amount,
            'trade_no' => $tradeNo,
            'prepay_id' => $prepayId,
            'tuid' => $tuid,
        ];
        return $this->insert($this->getTableName(), $arr);
    }

    public function finish($tradeNo, $transactionId) {
        $arr = [
            'state' => 1,
            'transaction_id' => $transactionId,
        ];
        return $this->update($this->getTableName(), $arr, ' trade_no = ? ', $tradeNo);
    }

    public function getByTradeNo($tradeNo) {
        $sql = 'SELECT ' . $this->getFields() . ' FROM ' .$this->getTableName(). ' WHERE trade_no = ?';
        return $this->getRow($sql, [$tradeNo]);
    }

    private function getFields() {
        return "id, trade_no, uid, tuid, amount, goods_id, prepay_id, transaction_id, state, created_at, updated_at";
    }
}