<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午5:22
 */

namespace Pepper\Framework\Dao;

class DAOFollowLog extends DAOProxy
{
    const ACTION_ADD    = "ADD";
    const ACTION_CANCEL = "CANCEL";

    public function __construct() {
        parent::__construct();
        $this->setTableName("followlog");
    }

    public function addFollowlog($uid, $fid, $action, $reason = "") {
        $arr_info["uid"]     = $uid;
        $arr_info["fid"]     = $fid;
        $arr_info["action"]  = $action;
        $arr_info["reason"]  = trim($reason);

        return $this->insert($this->getTableName(), $arr_info);
    }
}
