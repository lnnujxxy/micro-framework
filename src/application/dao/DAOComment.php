<?php
namespace Pepper\Framework\Dao;

class DAOComment extends DAOProxy
{
    /*
     * __construct
     */
    private $relateid;
    private $type;
    public function __construct($relateid, $type) {
        $this->relateid = $relateid;
        $this->type = $type;
        $id = $relateid . '_' . $type;
        parent::__construct(crc32($id));
        $this->setTableName("comment");
    }

    public function add($uid, $comment, $subType) {
        $arr['relateid']    = $this->relateid;
        $arr['type']        = $this->type;
        $arr['uid']         = $uid;
        $arr['comment']    = $comment;
        $arr['sub_type']   = $subType;

        return $this->insert($this->getTableName(), $arr);
    }

    public function appendComment($id, $item) {
        $sql = "SELECT comment FROM " . $this->getTableName() . " WHERE id = ?";
        $comment = $this->getOne($sql, $id);
        if ($comment) {
            $arr = json_decode($comment, true);
            if (is_array($arr)) {
                foreach ($item as $key=>$val) {
                    $arr[$key] = $val;
                }
                $comment = json_encode($arr, JSON_UNESCAPED_UNICODE);
                $sql = "UPDATE " . $this->getTableName() . " SET $comment = ? WHERE id = ?";
                $this->execute($sql, [$comment, $id]);
            }
        }
    }

    public function test() {
        return "test";
    }


    public function listComments($subType = 0, $offset = 0, $limit = 20) {
        $offset = intval($offset);
        $limit = intval($limit);
        $sql = "SELECT " . $this->getFields() . " FROM ". $this->getTableName() . " WHERE relateid = ? AND type = ? AND state = 1";
        if ($subType) {
            $sql .= " AND sub_type = " . intval($subType);
        }
        if ($offset) {
            $sql .= " AND id < $offset ";
        }
        $sql .= " ORDER BY created_at DESC LIMIT $limit";

        return $this->getAll($sql, [$this->relateid, $this->type]);
    }

    private function getFields()
    {
        return "id, relateid, type, sub_type, uid, comment, state, created_at, updated_at";
    }
}
