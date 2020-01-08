<?php
namespace Pepper\Framework\Dao;

class DAOWXAction extends DAOProxy
{
    /*
     * __construct
     */
    public function __construct() {
        parent::__construct();
        $this->setTableName('wx_action');
    }

    public function add($uid, $formId, $expire = 7 * 86400) {
        $arr['uid']    = $uid;
        $arr['form_id'] = $formId;
        $arr['expired_at']  = date('Y-m-d H:i:s', time() + $expire);
        return $this->insert($this->getTableName(), $arr);
    }

    public function getAction($uid) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE uid = ? AND expired_at >= NOW() LIMIT 1";
        return $this->getRow($sql, [$uid]);
    }

    public function delAction($id) {
        return $this->delete($this->getTableName(), " id = ? ", $id);
    }

    private function getFields()
    {
        return "id, uid, form_id, created_at, expired_at";
    }
}
