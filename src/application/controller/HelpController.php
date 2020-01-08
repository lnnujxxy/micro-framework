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
use Pepper\Framework\Model\Comment;
use Pepper\Framework\Model\Feeds;
use Pepper\Framework\Model\Goods;
use Pepper\Framework\Model\Help;
use Pepper\Framework\Model\User;

class HelpController extends BaseController
{
    public function startAction()
    {
        $userid = Context::get('userid');
        $uid = $this->getRequire('uid', 'isPositiveNumber');
        $goodsId = $this->getRequire('goods_id', 'isInts');
        $relateid = $this->getParam('relateid', 'isPositiveNumber');
        $type = $this->getParam('type');
        $_REQUEST['is_hide'] = $this->getParam('is_hide', 0);
        Interceptor::ensureNotFalse(in_array($type, Feeds::supportTypeIds), ERROR_TOAST, '该类型不支持互助');

        $goodsInfo = Goods::getGoodInfo($goodsId);
        $buyGoodsRes = Goods::buyGoods($userid, $uid, $goodsId, $relateid, $type, Goods::FROM_HELP);
        // 计算feed数值
        $endFeedRes = Feeds::endFeed($userid, $relateid, $type, $buyGoodsRes['bless']);
        // 互助评论
        $content = Comment::helpComment($relateid, $type, $uid, $userid, $goodsInfo, $endFeedRes);
        $content['relateid'] = $relateid;
        $content['type'] = $type;

        if ($userid != $uid) {
            Help::addHelp($userid, $uid, json_encode($content, JSON_UNESCAPED_UNICODE));
        }
        $this->render(array_merge($buyGoodsRes, $endFeedRes));
    }

    public function topNAction()
    {
        $userid = Context::get('userid');
        $this->render(Help::topN($userid));
    }
}