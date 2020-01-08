<?php

namespace Pepper\Framework\Model;

use ddliu\wxpay\Config;
use ddliu\wxpay\Data\UnifiedOrder as WxPayUnifiedOrder;
use ddliu\wxpay\Api as WxPayApi;
use ddliu\wxpay\Config as WxPayConfig;
use Pepper\Framework\Dao\DAOUser;
use Pepper\Framework\Dao\DAOWxPay;
use Pepper\Framework\Lib\Lock;
use Pepper\Framework\Lib\Logger;
use Pepper\Framework\Lib\Restrict;
use Pepper\Framework\Lib\Util;
use Pepper\Lib\SimpleConfig;

class WxPay
{
    CONST STATE_DEFAULT = 0; // 订单生成状态
    CONST STATE_PAY = 1; // 订单支付完成，收到回调通知
    CONST STATE_TRANSFER_SUCC = 2; // 公司汇款个人成功状态
    CONST STATE_TRANSFer_FAIL = 3; // 公司汇款个人失败状态

    public static function unifiedOrderOfficialAccount($productId, $openid) {
        WxPayConfig::$APPID = OFFICIAL_APPID;
        WxPayConfig::$APPSECRET = OFFICIAL_SECRET;
        WxPayConfig::$MCHID = OFFICIAL_MCHID;
        WxPayConfig::$KEY = OFFICIAL_KEY;

        $config = self::getGoodsConfig();
        $body = $config[$productId]['name'];
        $attach = '使用' . $config[$productId]['name'] . '购买' . $config[$productId]['amount'];
        $tradeNo = date("YmdHis").uniqid();
        $input = new WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach($attach);
        $input->SetOut_trade_no($tradeNo);
        $input->SetTotal_fee( $config[$productId]['fee']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetNotify_url("https://".SimpleConfig::get('URL')."/WxPay/notifyOfficialAccount");
        $input->SetTrade_type("JSAPI");
        $input->SetProduct_id($productId);
        $input->SetOpenid($openid);
        $result = WxPayApi::unifiedOrder($input);
        $userInfo = (new DAOUser())->getUserByOpenidOfficial($openid);
        (new DAOWxPay())->add($userInfo['uid'], $productId, $config[$productId]['fee'], $tradeNo, $result['prepay_id']);
        return $result;
    }

    public static function unifiedOrder($productId, $userInfo) {
        $config = self::getGoodsConfig();
        $body = $config[$productId]['name'];
        $attach = '使用' . $config[$productId]['name'] . '购买' . $config[$productId]['amount'];
        $tradeNo = date("YmdHis").uniqid();
        $input = new WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach($attach);
        $input->SetOut_trade_no($tradeNo);
        $input->SetTotal_fee( $config[$productId]['fee']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetNotify_url("https://".SimpleConfig::get('URL')."/WxPay/notify");
        $input->SetTrade_type("JSAPI");
        $input->SetProduct_id($productId);
        $input->SetOpenid($userInfo['openid']);
        $result = WxPayApi::unifiedOrder($input);
        $result['trade_no'] = $tradeNo;
        (new DAOWxPay())->add($userInfo['uid'], $productId, $config[$productId]['fee'], $tradeNo, $result['prepay_id']);
        return $result;
    }

    public static function unifiedOrderGoods($goodsInfo, $userInfo, $tuid) {
        $body = $goodsInfo['name'];
        $attach = '购买' . $goodsInfo['name'];
        $tradeNo = date("YmdHis").uniqid();
        $input = new WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach($attach);
        $input->SetOut_trade_no($tradeNo);
        $input->SetTotal_fee($goodsInfo['price']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetNotify_url("https://".SimpleConfig::get('URL')."/WxPay/notify");
        $input->SetTrade_type("JSAPI");
        $input->SetProduct_id($goodsInfo['goods_id']);
        $input->SetOpenid($userInfo['openid']);
        $result = WxPayApi::unifiedOrder($input);
        $result['trade_no'] = $tradeNo;

        Restrict::addItem(Restrict::KEY_PAY_GOODS_CASH, $tradeNo);
        (new DAOWxPay())->add($userInfo['uid'], $goodsInfo['goods_id'], $goodsInfo['price'], $tradeNo, $result['prepay_id'], $tuid);
        return $result;
    }

    public static function orderQuery($tradeNo) {
        $input = new WxPayUnifiedOrder();
        $input->SetOut_trade_no($tradeNo);
        $result = WxPayApi::orderQuery($input);
        return $result;
    }

    /**
     * @param $wxPay
     * @return array
     * @throws \ddliu\wxpay\Data\WxPayException
     * @throws \ddliu\wxpay\Exception
     */
    public static function transfers($wxPay) {
        WxPayConfig::$SSLCERT_PATH = ROOT_PATH . '/config/cert/apiclient_cert.pem';
        WxPayConfig::$SSLKEY_PATH = ROOT_PATH . '/config/cert/apiclient_key.pem';

        if ($wxPay['state'] != self::STATE_PAY && !Lock::lock('lock:transfers'.$wxPay['trade_no'])) {
            Logger::log('wxpay', 'transfers fail', array('wx_pay' => json_encode($wxPay)));
            return [];
        }

        $userInfo = User::getUserInfo($wxPay['uid']);
        $toUserInfo = User::getUserInfo($wxPay['tuid']);
        $goodsInfo = Goods::getGoodInfo($wxPay['goods_id']);
        $input = new WxPayUnifiedOrder();
        $input->SetOpenid($toUserInfo['openid']);
        $input->SetKeyVal('partner_trade_no', md5($wxPay['trade_no']));
        $input->SetKeyVal('check_name', 'NO_CHECK');
        $discount = self::discount($goodsInfo['price']);
        $input->SetKeyVal('amount', $discount);
        $input->SetKeyVal('desc', $userInfo['nickname'] . '送给了你' . self::formatPrice($discount) . '元现金礼物，祝福你许愿成功');
        $ip = Util::getIP();
        $input->SetKeyVal('spbill_create_ip', $ip ? $ip : '154.8.195.226');
        $input->SetKeyVal('mch_appid', WX_APPID);//公众账号ID
        $input->SetKeyVal('mchid', WX_MCHID);//商户号
        $result = WxPayApi::transfers($input);
        Logger::log('wxpay', 'transfers', array('result' => json_encode($result), 'wx_pay' => json_encode($wxPay)));
        return $result;
    }

    public static function discount($price) {
        return floor($price * 0.95);
    }

    public static function formatPrice($price) {
        return number_format($price/100,2);
    }

    public static function gettransferinfo($tradeNo) {
        WxPayConfig::$SSLCERT_PATH = ROOT_PATH . '/config/cert/apiclient_cert.pem';
        WxPayConfig::$SSLKEY_PATH = ROOT_PATH . '/config/cert/apiclient_key.pem';

        $input = new WxPayUnifiedOrder();
        $input->SetKeyVal('partner_trade_no', md5($tradeNo));
        $input->SetKeyVal('appid', WX_APPID);//公众账号ID
        $input->SetKeyVal('mch_id', WX_MCHID);//商户号
        $result = WxPayApi::gettransferinfo($input);
        Logger::log('wxpay', 'gettransferinfo', array('result' => json_encode($result), 'trade_no' => $tradeNo));
        return $result;
    }

    public static function notify() {
        $data = WxPayApi::notifyData();
        Logger::log('wxpay', 'call notify', $data);
        if ($data === false) {
            Logger::warning('notify', array('params' => file_get_contents('php://input')));
            return []; // 回调异常，不返回内容
        }
        $tradeNo = $data['out_trade_no'];
        $transactionId = $data['transaction_id'];
        if ($transactionId && Lock::lock('lock:notify:' . $transactionId) && WxPay::orderQuery($tradeNo)['trade_state'] == 'SUCCESS') {
            $daoWxPay = new DAOWxPay();
            Logger::log('wxpay', 'notify success', $data);
            $daoWxPay->finish($tradeNo, $transactionId);
            $wxPay = $daoWxPay->getByTradeNo($tradeNo);
            $goodsInfo = Goods::getGoodInfo($wxPay['goods_id']);
            if (in_array($goodsInfo['type'], Goods::DEFAULT_TYPES)) { // 购买礼物获取菩提币
                $config = self::getGoodsConfig()[$wxPay['goods_id']];
                User::updateValue($wxPay['uid'], 'bodhi', $config['amount']);
                // 发消息
                WxAction::sendPayMsg($wxPay['uid'], $config['amount'], date("Y-m-d H:i:s"), '购买菩提币');
            } else if ($goodsInfo['type'] == Goods::CASH_TYPE_ID) { // 收到现金礼物
                // 发消息
                if ($wxPay['tuid']) {
                    Logger::log('wxacion', 'notify send msg', $wxPay);
                    $userInfo = User::getUserInfo($wxPay['uid']);
                    WxAction::sendRecMsg($wxPay['tuid'], $userInfo['nickname'],
                        self::formatPrice(self::discount($wxPay['amount'])) . '元', date('Y-m-d H:i:s'),
                        $userInfo['nickname']." 送您价值".self::formatPrice($wxPay['amount'])."元的现金礼物。" . self::formatPrice(self::discount($wxPay['amount'])) . "元会在三日内打入您的微信钱包。");
                }
            }

            return [
                'return_code' => 'SUCCESS',
                'return_msg' => 'OK',
            ];
        }
        return [];
    }

    public static function notifyOfficialAccount() {
        WxPayConfig::$APPID = OFFICIAL_APPID;
        WxPayConfig::$APPSECRET = OFFICIAL_SECRET;
        WxPayConfig::$MCHID = OFFICIAL_MCHID;
        WxPayConfig::$KEY = OFFICIAL_KEY;

        return self::notify();
    }

    public static function getGoodsConfig() {
        return [
            1 => [
                'id' => 1,
                'name' => '1元',
                'fee' => 100,
                'amount' => 10,
                'discount' => 0,
            ],
            [
                'id' => 2,
                'name' => '20元',
                'fee' => 2000,
                'amount' => 200,
                'discount' => 0,
            ],
            [
                'id' => 3,
                'name' => '50元',
                'fee' => 5000,
                'amount' => 500,
                'discount' => 0,
            ],
            [
                'id' => 4,
                'name' => '100元',
                'fee' => 10000,
                'amount' => 1000,
                'discount' => 0,
            ],
            [
                'id' => 5,
                'name' => '200元',
                'fee' => 20000,
                'amount' => 2000,
                'discount' => 0,
            ],
            [
                'id' => 6,
                'name' => '300元',
                'fee' => 30000,
                'amount' => 3000,
                'discount' => 0,
            ],
            [
                'id' => 7,
                'name' => '475元',
                'fee' => 47500,
                'amount' => 5000,
                'discount' => 9.5,
            ],
            [
                'id' => 8,
                'name' => '720元',
                'fee' => 72000,
                'amount' => 8000,
                'discount' => 9,
            ],
            [
                'id' => 9,
                'name' => '850元',
                'fee' => 85000,
                'amount' => 10000,
                'discount' => 8.5,
            ]
        ];
    }
}