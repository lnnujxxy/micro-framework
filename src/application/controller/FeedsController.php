<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:22
 */

namespace Pepper\Framework\Controller;
use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Model\Feeds;
use Pepper\Framework\Model\Goods;
use Pepper\Framework\Model\Rank;

class FeedsController extends BaseController
{
    public function addFeedAction() {
        $userid = Context::get('userid');
        $type = $this->getParam('type');
        $content =  $this->getParam('content');
        $ispublic = $this->getParam('ispublic', 1);
        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');

        $extends = [];
        Interceptor::ensureNotEmpty($content, ERROR_PARAM_IS_EMPTY, 'content');
        Interceptor::ensureNotFalse(is_array($content) && isset($content['content']), ERROR_PARAM_INVALID_FORMAT, 'content');
        Interceptor::ensureNotFalse(isset($content['name']) && isset($content['avatar']), ERROR_PARAM_INVALID_FORMAT, 'content');

        $extends['object_id'] = $this->getParam('object_id');
        Interceptor::ensureNotFalse(isset($extends['object_id']), ERROR_PARAM_INVALID_FORMAT, 'object_id');

        $this->render(Feeds::addFeed($userid, $type, $content, $extends, $ispublic));
    }

    public function updateFeedAction() {
        $type = $this->getParam('type');
        $relateid = $this->getParam('relateid');
        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');
        Interceptor::ensureNotFalse($relateid > 0, ERROR_PARAM_INVALID_FORMAT, 'relateid');
        $image = $this->getParam('image');
        if ($image) {
            Feeds::appendKey($relateid, $type, 'image', $image);
        }
        $scripture = $this->getParam('scripture');
        if ($scripture) {
            Feeds::appendKey($relateid, $type, 'scripture', $scripture);
        }
        $goods = $this->getParam('goods');
        if ($goods) {
            Feeds::appendKey($relateid, $type, 'goods', $goods);
        }

        $this->render();
    }

    public function getFeedsAction() {
        $type = $this->getParam('type');
        $offset = $this->getParam('offset');
        $limit = $this->getParam('limit', 20);
        $feeds = Feeds::getFeeds($offset, $limit, $type);

        $this->render($feeds);
    }

    public function getMyFeedsAction() {
        $userid = Context::get('userid');
        $uid = $this->getParam('uid');
        $offset = $this->getParam('offset');
        $limit = $this->getParam('limit', 20);
        $uid || $uid = $userid;
        $feeds = Feeds::getMyFeeds($uid, $offset, $limit);
        $this->render($feeds);
    }

    public function getMyTypeFeedsAction() {
        $userid = Context::get('userid');
        $uid = $this->getParam('uid');
        $objectId = $this->getParam('object_id');
        $type = $this->getParam('type');
        $offset = $this->getParam('offset');
        $limit = $this->getParam('limit', 20);
        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');

        $uid || $uid = $userid;
        $feeds = Feeds::getMyTypeFeeds($uid, $type, $objectId, $offset, $limit);
        $this->render($feeds);
    }

    public function getGroupContentAction() {
        $type = $this->getParam('type');

        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');
        $this->render(Feeds::getGroupContent($type));
    }

    public function getFeedInfoAction() {
        $userid = $this->getParam('uid') ? $this->getParam('uid') : Context::get('userid');
        $relateid = $this->getParam('relateid');
        $type = $this->getParam('type');

        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');
        if (!$relateid) {
            $data = Feeds::getLastestByType($userid, $type);
        } else {
            $data = Feeds::getFeedInfo($relateid, $type);
        }
        $data['total'] = Feeds::factoryDB($type)->total($userid);
        $this->render($data);
    }

    public function getRanksAction() {
        $relateid = $this->getParam('relateid');
        $type = $this->getParam('type');

        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');
        Interceptor::ensureNotFalse($relateid > 0, ERROR_PARAM_INVALID_FORMAT, 'relateid');
        $this->render(Rank::getRankFeed($relateid, $type));
    }

    public function getRanksDetailAction() {
        $relateid = $this->getParam('relateid');
        $type = $this->getParam('type');
        $offset = $this->getParam('offset', 0);

        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');
        Interceptor::ensureNotFalse($relateid > 0, ERROR_PARAM_INVALID_FORMAT, 'relateid');

        $this->render(Rank::getRankFeedDetail($relateid, $type, $offset));
    }

    public function getLastestByTypeAction() {
        $userid = $this->getParam('uid') ? $this->getParam('uid') : Context::get('userid');
        $type = $this->getParam('type');

        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');

        $this->render(Feeds::getLastestByType($userid, $type));
    }

    public function getLatestAction() {
        $userid = $this->getParam('uid') ? $this->getParam('uid') : Context::get('userid');
        Interceptor::ensureNotFalse($userid > 0, ERROR_PARAM_INVALID_FORMAT, 'userid');
        $this->render(Feeds::getLatest($userid));
    }

    public function getFeedsGoodsAction() {
        $relateid = $this->getParam('relateid');
        $type = $this->getParam('type');

        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');

        $this->render(Feeds::getFeedsGoods($relateid, $type, Goods::DEFAULT_TYPES));
    }
}