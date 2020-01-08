<?php

namespace Pepper\Framework\Controller;

use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\InputHelper;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Model\Follow;

class FollowController extends BaseController
{
    public function addAction() {
        $userid = Context::get('userid');
        $uid    = intval($this->getParam('uid'));
        Interceptor::ensureNotFalse($uid > 0, ERROR_PARAM_INVALID_FORMAT, 'uid');

        $followingNum = Follow::countFollowings($userid);
        Interceptor::ensureNotFalse($followingNum < Follow::FOLLOWING_LIMIT, ERROR_FOLLOW_TOO_MUCH);

        if ($userid != $uid) {
            // todo 检查uid是否存在

            $followed = Follow::addFollow($userid, $uid);
            if ($followed[$uid]) {
                $this->render(array('followed' => $followed));
            }
        }

        $this->render();
    }

    public function cancelAction() {
        $userid = Context::get('userid');
        $uid    = intval($this->getParam('uid'));

        Interceptor::ensureNotFalse($uid > 0, ERROR_PARAM_INVALID_FORMAT, 'uid');

        $this->render(Follow::cancelFollow($userid, $uid));
    }

    public function isFollowedAction() {
        $userid = Context::get('userid');
        $fids = trim($this->getParam('fids'), ',');
        Interceptor::ensureNotFalse(InputHelper::isInts($fids), ERROR_PARAM_INVALID_FORMAT, "fids($fids)");

        if (strcmp($userid, $fids) == 0) {
            $followed = array($userid => true);
        } else {
            $followed = Follow::isFollowed($userid, explode(',', $fids));
        }

        $this->render(array('followed' => $followed));
    }
}
