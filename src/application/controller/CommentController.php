<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/18
 * Time: 下午9:43
 */

namespace Pepper\Framework\Controller;

use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Model\Comment;
use Pepper\Framework\Model\Feeds;

class CommentController extends BaseController
{
    public function addAction() {
        $userid = Context::get('userid');
        $relateid = $this->getParam('relateid');
        $type = $this->getParam('type');
        $comment = $this->getParam('comment');
        Interceptor::ensureNotFalse($relateid > 0, ERROR_PARAM_INVALID_FORMAT, 'relateid');
        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');
        Interceptor::ensureNotFalse(strlen($comment) > 0 && mb_strlen($comment, 'UTF-8') < 200, ERROR_PARAM_INVALID_FORMAT, 'comment');

        Comment::addComment($relateid, $type, $userid, $comment);
        $this->render();
    }

    public function listAction() {
        $relateid = $this->getParam('relateid');
        $type = $this->getParam('type');
        $subType = $this->getParam('sub_type', 0);
        $offset = $this->getParam('offset');
        $limit = $this->getParam('limit', 20);
        Interceptor::ensureNotFalse($relateid > 0, ERROR_PARAM_INVALID_FORMAT, 'relateid');
        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');

        $this->render(Comment::listComments($relateid, $type, $subType, $offset, $limit));
    }
}