<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

class DAOFeedback extends DAOProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("feedback");
    }

    public function add($uid, $content) {
        $arr = [
            'uid' => $uid,
            'content' => $content,
        ];
        return $this->insert($this->getTableName(), $arr);
    }
}