<?php

namespace Pepper\Framework\Model;

use Pepper\Framework\Dao\DAOUser;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Lib\Token;
use Pepper\Framework\Lib\WXBizDataCrypt;
use Pepper\Process\ProcessClient;


class User
{
    const OFFICIAL_UIDS = [
        35
    ];
    public static function active($code, $encryptedData = null, $iv = null) {
        if (!$iv || !$encryptedData) {
            $user = User::getUserByOpenid($code);
            Interceptor::ensureNotFalse(!empty($user), ERROR_ACTIVE_BYCODE);
        } else {
            $user = User::getUserFromClient($code, $encryptedData, $iv);
        }
        return self::afterLogin($user);
    }

    public static function fastLogin($uid) {
        $user = self::getUserInfo($uid);
        return self::afterLogin($user);
    }

    public static function officialAuth($code) {
        $res = WxAuth::getAccessTokenByCode($code);
        if ($res['unionid'] && $res['openid']) {
            $daoUser = new DAOUser();
            $userInfo = $daoUser->getUserByUnionid($res['unionid']);
            if (!$userInfo['openid_official']) {
                $daoUser->updateUserInfoByUnionid($res['unionid'], ['openid_official' => $res['openid']]);
            }
        } else {
            Interceptor::ensureNotFalse(false, ERROR_WX_LOGIN_FAIL);
        }
        return $res;
    }

    public static function afterLogin($user) {
        $formatUser = self::formatUser($user, true);
        $token = Token::makeToken(['userid' => $user['uid'], 'salt' => $user['salt']]);
        $formatUser['token'] = $token;
        Task::doTask($user['uid'], 'login');
        return $formatUser;
    }

    public static function getUserByOpenid($code) {
        $session = WxAuth::code2Session($code);
        Interceptor::ensureNotFalse(isset($session['openid']), ERROR_WX_LOGIN_FAIL);
        $daoUser = new DAOUser();
        $user = $daoUser->getUserByOpenid($session['openid']);
        if (!empty($user) && $user['uid']) {
            $daoUser->updateUserInfo($user['uid'], ['session_key' => $session['session_key']]);
        }
        return $user;
    }

    public static function getUserFromClient($code, $encryptedData, $iv) {
        $session = WxAuth::code2Session($code);
        Interceptor::ensureNotFalse(isset($session['openid']), ERROR_WX_LOGIN_FAIL);
        $res = self::decryptWxData($session['session_key'], $encryptedData, $iv);

        $daoUser = new DAOUser();
        $user = $daoUser->getUserByUnionid($res['unionId']);
        if (empty($user)) {
            $user = $daoUser->createUser($res['unionId'], $res['openId'], $res['nickName'], $res['avatarUrl'],
                $res['gender'], '', $session['session_key']);
            $user['is_new'] = $user['active'] = true; // 新用户标示
            $user['created_at'] = time();

            ProcessClient::getInstance(PROJECT_NAME)->addTask('save_wx_avatar', array('uid' => $user['uid'], 'avatar' => $user['avatar']));
        }
        return $user;
    }

    public static function getMobileFromClient($uid, $encryptedData, $iv) {
        $userInfo = self::getUserInfo($uid);
        $mobileInfo = self::decryptWxData($userInfo['session_key'], $encryptedData, $iv);
        self::updateUserInfo($uid, ['mobile' => $mobileInfo['phoneNumber']]);
        return $mobileInfo;
    }

    public static function getUserInfo($uid) {
        return (new DAOUser())->getUser($uid);
    }

    public static function getFormatUserInfo($uid, $self = false) {
        static $_userInfo;

        if (isset($_userInfo[$uid .':'. $self]) && !empty($_userInfo[$uid .':'. $self])) {
            return $_userInfo[$uid .':'. $self];
        }
        $user = self::getUserInfo($uid);
        $_userInfo[$uid .':'. $self] = self::formatUser($user, $self);
        return $_userInfo[$uid .':'. $self];
    }

    public static function updateUserInfo($uid, $record) {
        (new DAOUser())->updateUserInfo($uid, $record);
        return self::getFormatUserInfo($uid, true);
    }

    public static function getFormatUsersInfo($uids) {
        if (!is_array($uids)) {
            $uids = explode(",", $uids);
        }
        $users = (new DAOUser())->getUsers($uids);
        $formatUsers = [];
        foreach ($users as $user) {
            $formatUsers[$user['uid']] = self::formatUser($user);
        }
        return $formatUsers;
    }

    public static function getLoginid($token) {
        return Token::getTokenInfo($token, 'userid');
    }

    public static function isLogin($token) {
        return self::getLoginid($token) > 0;
    }

    public static function formatUser($user, $self = false) {
        $formatUserInfo = [
            'uid' => $user['uid'],
            'nickname' => strval($user['nickname']),
            'avatar' => strval($user['avatar']),
            'mobile' => strval($user['mobile']),
            'sex' => $user['sex'] == 1 ? '男' : ($user['sex'] == 2 ? '女' : '未知'),
            'active' => intval($user['active']),
            'merit' => intval($user['merit']),
            'joss' => intval($user['joss']),
            'created_at' => strtotime($user['created_at']),
        ];
        // 个人隐私信息
        if ($self) {
            $formatUserInfo['bodhi'] = intval($user['bodhi']);
        }
        return $formatUserInfo;
    }

    /**
     * 更新用户数值属性
     * @param $uid
     * @param $key
     * @param $val
     * @return bool
     */
    public static function updateValue($uid, $key, $val) {
        return (new DAOUser())->updateValue($uid, $key, $val);
    }

    public static function getDefaultAvatar()
    {
        return [
            'https://goods-1257256615.file.myqcloud.com/ui/touxiang/touxiang1.png',
            'https://goods-1257256615.file.myqcloud.com/ui/touxiang/touxiang2.png',
            'https://goods-1257256615.file.myqcloud.com/ui/touxiang/touxiang3.png',
            'https://goods-1257256615.file.myqcloud.com/ui/touxiang/touxiang4.png',
            'https://goods-1257256615.file.myqcloud.com/ui/touxiang/touxiang5.png',
            'https://goods-1257256615.file.myqcloud.com/ui/touxiang/touxiang6.png',
            'https://goods-1257256615.file.myqcloud.com/ui/touxiang/touxiang7.png',
            'https://goods-1257256615.file.myqcloud.com/ui/touxiang/touxiang8.png',
            'https://goods-1257256615.file.myqcloud.com/ui/touxiang/touxiang9.png',
        ];
    }

    private static function decryptWxData($sessionKey, $encryptedData, $iv) {
        $wxCrypt = new WXBizDataCrypt(WX_APPID, $sessionKey);
        $code = $wxCrypt->decryptData($encryptedData, $iv, $data);
        Interceptor::ensureNotFalse($code == 0, ERROR_WX_DECRYPT_FAIL);
        return json_decode($data, true);
    }


}