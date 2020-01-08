<?php
namespace Pepper\Framework\Dao;

class DAOFollowing extends DAOProxy
{
    /*
     * __construct
     */
    public function __construct($userid = 0) {
        parent::__construct($userid);
        $this->setTableName("following");
    }

    public function addFollowing($fid) {
        $arr_info["uid"]     = $this->getSplitId();
        $arr_info["fid"]     = $fid;

        $this->insert($this->getTableName(), $arr_info);
    }

    public function getFollowingInfo($fid) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid=? and fid=?";

        return $this->getRow($sql, array($this->getSplitId(), $fid));
    }

    public function getFollowings($start = 0, $num = 50, $order = "DESC") {
        $limit = " ORDER BY id {$order} LIMIT ". intval($start) . " , " . intval($num);
        $sql   = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid = ? " . $limit;

        return $this->getAll($sql, $this->getSplitId());
    }

    public function countFollowings() {
        $sql = "SELECT count(0) FROM {$this->getTableName()} WHERE uid = ?";
        return (int) $this->getOne($sql, array($this->getSplitId()));
    }

    public function exists($fid) {
        $sql = "SELECT count(0) FROM {$this->getTableName()} WHERE uid=? and fid=?";
        return (int) $this->getOne($sql, array($this->getSplitId(), $fid)) > 0;
    }

    public function isFollowed($fids) {
        $fids = array_map('intval', $fids);

        $followed = array();
        $sql  = "SELECT fid from {$this->getTableName()} WHERE uid=? AND fid in (" . implode(",", $fids) . ")";
        $list = $this->getAll($sql, array($this->getSplitId()));
        foreach ($list as $v) {
            $followed[$v["fid"]] = true;
        }

        return $followed;
    }

    public function delFollowing($fid) {
        $sql = "DELETE FROM {$this->getTableName()} WHERE uid=? and fid=?";
        return $this->execute($sql, array($this->getSplitId(), $fid));
    }

    private function getFields() {
        return "id, uid, fid, created_at, updated_at";
    }
}