<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/18
 * Time: 下午9:43
 */

namespace Pepper\Framework\Controller;
use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Curl;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Lib\QcloudCos;
use Pepper\Framework\Lib\Util;
use Pepper\Framework\Model\Feeds;
use Pepper\Framework\Model\Share;
use Pepper\Framework\Model\Task;
use Pepper\Framework\Model\WxAuth;

class ShareController extends BaseController
{
    public function indexAction() {
        $userid = Context::get('userid');
        $relateid = $this->getParam('relateid');
        $type = $this->getParam('type');
        $to = (int)$this->getParam('to', 1);

        Task::doTask($userid, 'share');

        $this->render();
    }

    public function getWXACodeAction() {
        $relateid = $this->getParam('relateid');
        $type = $this->getParam('type');
        Interceptor::ensureNotFalse($relateid > 0, ERROR_PARAM_INVALID_FORMAT, 'relateid');
        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');

        $this->render(['url' => Util::useCDN(Share::genWXACodeUrl($relateid, $type))]);
    }
}