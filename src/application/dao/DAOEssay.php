<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

class DAOEssay extends DAOFeedsProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("essay");
    }

    protected function getFields() {
        return "id, uid, content, ispublic, state, comments, helps, shares, created_at, updated_at";
    }
}