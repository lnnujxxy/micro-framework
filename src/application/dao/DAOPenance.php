<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

class DAOPenance extends DAOFeedsProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("penance");
    }

    protected function getFields() {
        return "id, uid, object_id, content, ispublic, state, comments, helps, shares, bless, created_at, updated_at";
    }
}