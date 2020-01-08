<?php

namespace Pepper\Framework\Model;

use ddliu\wxpay\Data\UnifiedOrder as WxPayUnifiedOrder;
use ddliu\wxpay\Api as WxPayApi;
use ddliu\wxpay\Config as WxPayConfig;
use Pepper\Framework\Dao\DAOUser;
use Pepper\Framework\Dao\DAOWXAction;
use Pepper\Framework\Dao\DAOWxPay;
use Pepper\Framework\Lib\Lock;
use Pepper\Framework\Lib\Logger;
use Pepper\Lib\Curl;
use Pepper\Lib\SimpleConfig;

class WxAction
{
    const TYPE_MSG_PAY = 1; // 支付购买礼物
    const TYPE_MSG_REC = 2; // 收到礼物
    const TYPE_MSG_UP = 3; // feed升级
    const TYPE_MSG_CASH = 4; // 现金礼物

    const MAP_TYPE_TEMPLATE = [
        self::TYPE_MSG_PAY => 'ldnPOfre0niyTK5ITY6yGy0lkWL0yw6_9rNK3yXCz5k',
        self::TYPE_MSG_REC => 'IpiqkHTTSdKi4IgicIuIjYonDDbXopLZ_y-IRKz14JA',
        self::TYPE_MSG_CASH => 'IpiqkHTTSdKi4IgicIuIjYonDDbXopLZ_y-IRKz14JA',
    ];

    public static function add($uid, $formId) {
        return (new DAOWXAction())->add($uid, $formId);
    }

    public static function sendPayMsg($uid, $keyword1, $keyword2, $keyword3, $page = "pages/wish/wish") {
        $data = [
            'keyword1' => ["value" => $keyword1],
            'keyword2' => ["value" => $keyword2],
            'keyword3' => ["value" => $keyword3],
        ];
        return self::sendMsg($uid, self::TYPE_MSG_PAY, $data, $page);
    }

    public static function sendRecMsg($uid, $keyword1, $keyword2, $keyword3, $keyword4, $page = "pages/wish/wish") {
        if (date("H") >= 22 || date("H") < 10) {
            return;
        }

        $data = [
            'keyword1' => ["value" => $keyword1],
            'keyword2' => ["value" => $keyword2],
            'keyword3' => ["value" => $keyword3],
            'keyword4' => ["value" => $keyword4],
        ];
        return self::sendMsg($uid, self::TYPE_MSG_REC, $data, $page);
    }

    public static function sendCashMsg($uid, $keyword1, $keyword2, $keyword3, $keyword4, $page = "pages/wish/wish") {
        $data = [
            'keyword1' => ["value" => $keyword1],
            'keyword2' => ["value" => $keyword2],
            'keyword3' => ["value" => $keyword3],
            'keyword4' => ["value" => $keyword4],
        ];
        return self::sendMsg($uid, self::TYPE_MSG_CASH, $data, $page);
    }

    public static function sendMsg($uid, $type, $data, $page = "pages/wish/wish") {
        $daoWxAction = new DAOWXAction();
        $action = $daoWxAction->getAction($uid);
        if ($action) {
            // 发送
            self::sendTemplateMessage($uid, self::MAP_TYPE_TEMPLATE[$type], $action['form_id'], $data, $page);
            $daoWxAction->delAction($action['id']);
            return true;
        }
        return false;
    }

    public static function sendTemplateMessage($uid, $templateId, $formId, $data, $page = "") {
        $toUserInfo = User::getUserInfo($uid);
        $accessToken = WxAuth::getAccessToken();

        $params = [
            'touser' => $toUserInfo['openid'],
            'template_id' => $templateId,
            'form_id' => $formId,
            'data' => $data,
            'page' => $page
        ];
        $json = Curl::post("https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=" . $accessToken,
            json_encode($params), array("content-type"=>"application/json"));
        $json && $res = json_decode($json, true);
        $params['uid'] = $uid;
        if ($res && $res['errcode'] == 0) {
            Logger::log('wxacion', 'sendTemplateMessage', array_merge(['params' => json_encode($params)], $res));
        } else {
            Logger::log('wxacion', 'sendTemplateMessage_fail', array_merge(['params' => json_encode($params)], $res));
        }
    }
}