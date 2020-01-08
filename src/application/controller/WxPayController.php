<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/18
 * Time: 下午9:43
 */

namespace Pepper\Framework\Controller;


use Pepper\Framework\Dao\DAOWxPay;
use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Lib\Util;
use Pepper\Framework\Model\Feeds;
use Pepper\Framework\Model\Goods;
use Pepper\Framework\Model\User;
use Pepper\Framework\Model\WxPay;

class WxPayController extends BaseController
{
    public function unifiedOrderAction() {
        $userid = Context::get('userid');
        $id = $this->getParam('id');
        $config = WxPay::getGoodsConfig();
        $ids = array_column($config, 'id');
        Interceptor::ensureNotFalse(in_array($id, $ids), ERROR_PARAM_INVALID_FORMAT, 'id');
        $userInfo = User::getUserInfo($userid);

        $result = WxPay::unifiedOrder($id, $userInfo);
        $result['timestamp'] = time();
        $result['pay_sign'] = $this->createPaySign($result);
        $this->render($result);
    }

    public function unifiedOrderOfficialAccountAction() {
        $openid = $this->getParam('openid');
        $id = $this->getParam('id');
        $config = WxPay::getGoodsConfig();
        $ids = array_column($config, 'id');
        Interceptor::ensureNotFalse(in_array($id, $ids), ERROR_PARAM_INVALID_FORMAT, 'id');
        Interceptor::ensureNotFalse(strlen($openid) > 0, ERROR_PARAM_INVALID_FORMAT, 'openid');

        $result = WxPay::unifiedOrderOfficialAccount($id, $openid);
        $result['timestamp'] = time();
        $result['pay_sign'] = $this->createPaySign($result, OFFICIAL_APPID, OFFICIAL_KEY);
        $this->render($result);
    }

    public function payGoodsAction() {
        $userid = Context::get('userid');
        $relateid = $this->getRequire('relateid', 'isPositiveNumber');
        $type = $this->getRequire('type', 'isPositiveNumber');
        $feedInfo = Feeds::getFeedInfo($relateid, $type);
        Interceptor::ensureNotFalse(!empty($feedInfo), ERROR_PARAM_INVALID_FORMAT, 'relateid or type');
        $goodsId = $this->getRequire('goods_id', 'isPositiveNumber');
        $goodsInfo = Goods::getGoodInfo($goodsId);
        Interceptor::ensureNotFalse($goodsInfo['type'] == Goods::CASH_TYPE_ID, ERROR_PARAM_INVALID_FORMAT, 'goods_id');
        $userInfo = User::getUserInfo($userid);
        $result = WxPay::unifiedOrderGoods($goodsInfo, $userInfo, $feedInfo['uid']);
        $result['timestamp'] = time();
        $result['pay_sign'] = $this->createPaySign($result);

        $this->render($result);
    }

    public function orderQueryAction() {
        $tradeNo = $this->getRequire('trade_no', 'isStr');
        $this->render(WxPay::orderQuery($tradeNo));
    }

    public function transfersAction() {
        if (!Util::isTestEnv()) {
            $this->render();
        }
        $tradeNo = $this->getParam('trade_no');

        $daoWxPay = new DAOWxPay();
        $wxPay = $daoWxPay->getByTradeNo($tradeNo);
        $transferInfo = WxPay::gettransferinfo($wxPay['trade_no']);
        $result = [];
        if ($transferInfo['return_code'] == 'SUCCESS' && !in_array($transferInfo['status'], ['SUCCESS', 'PROCESSING'])) {
            $result = WxPay::transfers($wxPay);
        }

        $this->render($result);
    }

    public function configAction() {
        $this->render(WxPay::getGoodsConfig());
    }

    public function notifyAction() {
        echo Util::arrayToXml(WxPay::notify());
        exit;
    }

    public function notifyOfficialAccountAction() {
        echo Util::arrayToXml(WxPay::notifyOfficialAccount());
        exit;
    }
}