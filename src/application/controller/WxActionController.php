<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2019/1/5
 * Time: 上午11:33
 */

namespace Pepper\Framework\Controller;
use Pepper\Framework\Lib\Context;
use Pepper\Framework\Model\WxAction;

class WxActionController extends BaseController
{
    public function reportAction() {
        $userid = Context::get('userid');
        $formId = $this->getRequire('form_id', 'isStr');
        if (strlen($formId) != 32) {
            $this->render("form_id 格式错误");
        }
        WxAction::add($userid, $formId);
        $this->render();
    }

    public function sendAction() {
        $userid = Context::get('userid');

        WxAction::sendPayMsg($userid, "200", date("Y-m-d H:i:s"), "xxxx");
        $this->render();
    }
}