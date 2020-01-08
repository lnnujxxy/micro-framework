<?php
namespace Pepper\Framework\Dao;

class DAOUser extends DAOProxy
{
    const GROUPID_ROBOT = 1; // 机器人组id
    /*
     * __construct
     */
    public function __construct() {
        parent::__construct();
        $this->setTableName('user');
    }

    public function createUser($unionid, $openid, $nickname = "", $avatar = "", $sex = 0, $extend = "",
                               $sessionKey = "", $groupid = 0) {
        if ($nickname && $this->existNickname($nickname)) {
            $nickname = $nickname . substr(uniqid(), 0, 4); // todo 先简单这么处理，后期优化
        } else {
            $nickname = $nickname ? $nickname : $unionid;
        }
        $salt = uniqid();
        $arr = [
            "unionid" => $unionid,
            "openid" => $openid,
            "salt" => $salt,
            "nickname" => $nickname,
            "avatar" => $avatar,
            "sex" => $sex,
            "extend" => $extend,
            "session_key" => $sessionKey,
            "groupid" => $groupid,
        ];
        $uid = $this->insert($this->getTableName(), $arr);
        return [
            "uid" => $uid,
            "unionid" => $unionid,
            "openid" => $openid,
            "salt" => $salt,
            "nickname" => $nickname,
            "avatar" => $avatar,
            "sex" => $sex,
        ];
    }

    public function updateUserInfo($uid, $record) {
        return $this->update($this->getTableName(), $record, " uid = ? ", $uid);
    }

    public function updateUserInfoByUnionid($unionid, $record) {
        return $this->update($this->getTableName(), $record, " unionid = ? ", $unionid);
    }

    public function getUserByUnionid($unionid) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() ." WHERE unionid = ?";
        return $this->getRow($sql, [$unionid]);
    }

    public function getUserByOpenid($openid) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() ." WHERE openid = ?";
        return $this->getRow($sql, [$openid]);
    }

    public function getUserByOpenidOfficial($openidOfficial) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() ." WHERE openid_official = ?";
        return $this->getRow($sql, [$openidOfficial]);
    }

    public function getUser($uid) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() ." WHERE uid = ?";
        return $this->getRow($sql, [$uid]);
    }

    public function getUsers($uids) {
        $in  = str_repeat('?,', count($uids) - 1) . '?';
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() ." WHERE uid IN (" . $in .")";
        return $this->getAll($sql, $uids);
    }

    public function existNickname($nickname) {
        $sql = "SELECT count(0) FROM " . $this->getTableName() . " WHERE nickname = ?";
        return $this->getOne($sql, [$nickname]) > 0 ? true : false;
    }

    public function updateValue($uid, $field, $val) {
        if (!in_array($field, ['merit', 'joss', 'bodhi'])) {
            return false;
        }

        $affect = 0;
        $val = intval($val);
        if ($val > 0) {
            $sql = "UPDATE " . $this->getTableName() . " SET $field = $field + $val WHERE uid = ?";
            $affect = $this->execute($sql, [$uid]);
        } elseif ($val < 0) {
            $sql = "UPDATE " . $this->getTableName() . " SET $field = $field + $val WHERE uid = ? AND $field >= " . abs($val);
            $affect = $this->execute($sql, [$uid]);
        }
        return $affect > 0;
    }

    public function getRobotIds($num = 100) {
        $sql = "SELECT uid FROM " . $this->getTableName() . " WHERE groupid = ? ORDER BY RAND() LIMIT " . $num;
        $sth = $this->query($sql, self::GROUPID_ROBOT);
        $robotIds = [];
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $robotIds[] = $row['uid'];
        }
        return $robotIds;
    }

    private function getFields() {
        return "uid, unionid, openid, openid_official, nickname, salt, avatar, mobile, sex, active, 
        session_key,mobile, sex, active,merit, joss, bodhi, created_at, updated_at";
    }
}
