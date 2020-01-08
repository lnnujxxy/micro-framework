<?php
namespace Pepper\Framework\Dao;

class DAOShare extends DAOProxy
{
    /*
     * __construct
     */
    public function __construct() {
        parent::__construct();
        $this->setTableName("share");
    }

    public function add($uid, $relateid, $type, $to) {
        $arr['relateid']    = (int)$relateid;
        $arr['type']        = (int)$type;
        $arr['uid']         = (int)$uid;
        $arr['to']          = (int)$to;

        return $this->insert($this->getTableName(), $arr);
    }

    public function total($uid, $relateid, $type) {
        $sql = "SELECT count(*) FROM " . $this->getTableName() . " WHERE relateid = ? AND type = ? AND uid = ?";
        return $this->getOne($sql, [$relateid, $type, $uid]);
    }

    private function getFields() {
        return "id, relateid, type, uid, to, created_at";
    }
}
