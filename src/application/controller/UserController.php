<?php

namespace Pepper\Framework\Controller;

use Pepper\Framework\Dao\DAOUser;
use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Lib\Logger;
use Pepper\Framework\Lib\Util;
use Pepper\Framework\Model\Goods;
use Pepper\Framework\Model\User;

class UserController extends BaseController
{
    /**
     * 小程序激活
     */
    public function activeAction() {
        $code = trim($this->getParam('code'));
        $encryptedData = $this->getParam('encryptedData');
        $iv = $this->getParam('iv');
        Interceptor::ensureNotFalse(strlen($code) > 0, ERROR_PARAM_INVALID_FORMAT, 'code');
        $user = User::active($code, $encryptedData, $iv);
        $this->render($user);
    }

    /**
     * 小程序快速登录
     */
    public function fastLoginAction() {
        $token = trim($this->getParam('token'));
        Interceptor::ensureNotFalse(strlen($token) > 0, ERROR_PARAM_INVALID_FORMAT, 'token');
        $userid = User::getLoginid($token);
        Interceptor::ensureNotFalse($userid > 0, ERROR_USER_NOT_EXIST);

        $this->render(User::fastLogin($userid));
    }

    /**
     * 公众号授权
     */
    public function authAction() {
        $code = trim($this->getParam('code'));
        Interceptor::ensureNotFalse(strlen($code) > 0, ERROR_PARAM_INVALID_FORMAT, 'code');
        $this->render(User::officialAuth($code));
    }

    public function getMobileAction() {
        $userid = Context::get('userid');
        $encryptedData = $this->getParam('encryptedData');
        $iv = $this->getParam('iv');

        Interceptor::ensureNotFalse(strlen($encryptedData) > 0, ERROR_PARAM_INVALID_FORMAT, 'encryptedData');
        Interceptor::ensureNotFalse(strlen($iv) > 0, ERROR_PARAM_INVALID_FORMAT, 'iv');

        $mobileInfo = User::getMobileFromClient($userid, $encryptedData, $iv);
        $this->render($mobileInfo);
    }

    public function getUserInfoAction() {
        $userid = Context::get('userid');
        $uid = $this->getParam('uid');
        Interceptor::ensureNotFalse($uid > 0, ERROR_PARAM_INVALID_FORMAT, 'uid');
        if ($userid == $uid) { // 看自己
            $userInfo = User::getFormatUserInfo($uid, true);
            $userInfo['self'] = true;
        } else {
            $userInfo = User::getFormatUserInfo($uid);
            $userInfo['self'] = false;
        }
        $this->render($userInfo);
    }

    public function meAction() {
        $userid = Context::get('userid');
        $userInfo = User::getFormatUserInfo($userid, true);
        // todo 消息数，关注数，道具数
        $userInfo['msg_num'] = 1;
        $userInfo['following_num'] = 2;
        $userInfo['goods_num'] = Goods::countUserGoods($userid);
        $this->render($userInfo);
    }

    public function updateUserInfoAction() {
        $userid = Context::get('userid');
        $userInfo = $this->getParam('info');
        Interceptor::ensureNotFalse(is_array($userInfo), ERROR_PARAM_INVALID_FORMAT, 'info');
        $oldUserInfo = User::getUserInfo($userid);

        $diffUserInfo = [];
        foreach ($userInfo as $k => $v) {
            if (!in_array($k, ['avatar', 'nickname', 'sex', 'mobile', 'active']) || $oldUserInfo[$k] == $userInfo[$k]) {
                continue;
            }

            if ($k == 'avatar' ) {
                if (!Util::isValidUrl($v)) {
                    Logger::warning('invalid avatar url', ['uid' => $userid, 'avatar' => $v]);
                    continue;
                } else {
                    $diffUserInfo[$k] = $v;
                }
            }

            if ($k == 'nickname') {
                Interceptor::ensureFalse(mb_strlen($v, 'utf8') > NICKNAME_MAX_LEN, ERROR_NICKNAME_TOOLONG, 'nickname');
                if ((new DAOUser())->existNickname($v)) {
                    $diffUserInfo[$k] = $v . substr(uniqid(), 0, 4);
                }
            }
            $diffUserInfo[$k] = $v;
        }

        if ($diffUserInfo) {
            $newUserInfo = User::updateUserInfo($userid, $diffUserInfo);
            $this->render($newUserInfo);
        } else {
            $this->render(User::formatUser($oldUserInfo, true));
        }
    }
}
