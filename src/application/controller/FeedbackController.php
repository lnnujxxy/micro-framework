<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/18
 * Time: 下午9:43
 */

namespace Pepper\Framework\Controller;


use Pepper\Framework\Dao\DAOFeedback;
use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Interceptor;

class FeedbackController extends BaseController
{
    public function submitAction() {
        $userid = Context::get('userid');
        $content = $this->getParam('content');
        Interceptor::ensureNotFalse(strlen($content) > 0, ERROR_PARAM_INVALID_FORMAT, 'content');

        (new DAOFeedback())->add($userid, $content);
        $this->render();
    }
}