<?php
namespace Pepper\Framework\Dao;

class DAOFollower extends DAOProxy
{
    /*
     * __construct
     */
    public function __construct($userid) {
        parent::__construct($userid);
        $this->setTableName("follower");
    }

    public function addFollower($fid) {
        $arr_info["uid"]     = $this->getSplitId();
        $arr_info["fid"]     = $fid;

        $this->insert($this->getTableName(), $arr_info);
    }

    public function getFollowers($start = 0, $num = 50, $order = "DESC") {
        $limit = " ORDER BY id {$order} LIMIT ". intval($start) . " , " . intval($num);
        $sql   = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid = ? " . $limit;

        return $this->getAll($sql, $this->getSplitId());
    }

    public function countFollowers() {
        $sql = "SELECT count(0) FROM {$this->getTableName()} WHERE uid=?";
        return (int) $this->getOne($sql, array($this->getSplitId()));
    }

    public function exists($fid) {
        $sql = "SELECT count(0) FROM {$this->getTableName()} WHERE uid=? and fid=?";
        return (int) $this->getOne($sql, array($this->getSplitId(), $fid)) > 0;
    }

    public function isFollower($fids) {
        $fids = array_map('intval', $fids);

        $follower = array();
        $sql  = "SELECT fid from {$this->getTableName()} WHERE uid=? AND fid in (" . implode(",", $fids) . ")";
        $list = $this->getAll($sql, array($this->getSplitId()));
        foreach ($list as $v) {
            $follower[$v["fid"]] = true;
        }

        return $follower;
    }

    public function delFollower($fid) {
        $sql = "DELETE FROM {$this->getTableName()} WHERE uid=? and fid=?";
        return $this->execute($sql, array($this->getSplitId(), $fid));
    }

    private function getFields()
    {
        return "id, uid, fid, created_at, updated_at";
    }
}
