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
use Pepper\Framework\Lib\Restrict;
use Pepper\Framework\Model\Comment;
use Pepper\Framework\Model\Feeds;
use Pepper\Framework\Model\Goods;
use Pepper\Framework\Model\Help;
use Pepper\Framework\Model\WxPay;

class GoodsController extends BaseController
{
    public function getGroupGoodsAction() {
        $userid = Context::get('userid');
        $from = $this->getParam('from', 1); // 1 发起 2 发互助
        $type = $this->getRequire('type', 'isPositiveNumber');
        $relateid = $this->getRequire('relateid', 'isPositiveNumber');

        $groupGoods = Goods::getGroupGoods($userid, $from, $relateid, $type);
        $this->render($groupGoods);
    }

    public function getUserGoodsAction() {
        $userid = $this->getParam('uid') ? $this->getParam('uid') : Context::get('userid');
        $goods = Goods::getUserGoods($userid);
        $this->render($goods);
    }

    public function buyGoodsAction() {
        $userid = Context::get('userid');
        $goodsId = $this->getRequire('goods_id', 'isPositiveNumber');
        $relateid = $this->getRequire('relateid', 'isPositiveNumber');
        $type = $this->getRequire('type', 'isPositiveNumber');
        $from = $this->getParam('from', 1); // 1 发起 2 送祝福

        $feedInfo = Feeds::getFeedInfo($relateid, $type);
        Interceptor::ensureNotEmpty($feedInfo, ERROR_TOAST, '该feed不存在');
        $tuid = $feedInfo['user_info']['uid'];
        $buyGoodsRes = Goods::buyGoods($userid, $tuid, $goodsId, $relateid, $type, $from);
        // 计算feed数值
        $endFeedRes = Feeds::endFeed($userid, $relateid, $type, $buyGoodsRes['bless']);
        $this->render(array_merge($buyGoodsRes, $endFeedRes));
    }

    public function buyCashGoodsAction() {
        $userid = Context::get('userid');
        $goodsId = $this->getRequire('goods_id', 'isPositiveNumber');
        $relateid = $this->getRequire('relateid', 'isPositiveNumber');
        $type = $this->getRequire('type', 'isPositiveNumber');
        $from = $this->getParam('from', 1); // 1 发起 2 送祝福
        $tradeNo = $this->getRequire('trade_no', 'isStr');
        $_REQUEST['is_hide'] = $this->getParam('is_hide', 0);

        if (!Restrict::checkItem(Restrict::KEY_PAY_GOODS_CASH, $tradeNo)) {
            Interceptor::ensureNotFalse(false, ERROR_TOAST, '请先完成付款');
        }
        $orderQuery = WxPay::orderQuery($tradeNo);
        Interceptor::ensureNotFalse($orderQuery['trade_state'] == 'SUCCESS', ERROR_TOAST, '请先完成付款');

        $feedInfo = Feeds::getFeedInfo($relateid, $type);
        Interceptor::ensureNotEmpty($feedInfo, ERROR_TOAST, '该feed不存在');
        $tuid = $feedInfo['user_info']['uid'];

        $goodsInfo = Goods::getGoodInfo($goodsId);
        $result = Goods::buyCash($userid, $tuid, $goodsInfo, $relateid, $type, $from);
        // 互助评论
        $content = Comment::helpComment($relateid, $type, $feedInfo['uid'], $userid, $goodsInfo, $result);
        $content['relateid'] = $relateid;
        $content['type'] = $type;
        if ($userid != $tuid) {
            Help::addHelp($userid, $tuid, json_encode($content, JSON_UNESCAPED_UNICODE));
        }
        Restrict::clearItem(Restrict::KEY_PAY_GOODS_CASH, $tradeNo);
        $this->render($result);
    }
}