<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午2:04
 */

namespace Pepper\Framework\Process\Worker;

use Pepper\Framework\Dao\DAOGoods;
use Pepper\Framework\Lib\Logger;
use Pepper\Framework\Lib\Token;
use Pepper\Framework\Model\Robot;
use Pepper\Framework\Model\User;
use Pepper\Framework\Lib\Curl;
use Pepper\Lib\SimpleConfig;

class RobotHelpWorker
{
    /**
     * @param $value
     * @return bool
     */
    public function execute($value) {
        $uid = $value['params']['uid'];
        $relateid = $value['params']['relateid'];
        $type = $value['params']['type'];
        Logger::log('robot_help', 'params',  $value['params']);

        $robotId = Robot::getRobotIds(1)[0];
        $robotUser = User::getUserInfo($robotId);

        $token = Token::makeToken(['userid' => $robotId, 'salt' => $robotUser['salt']]);
        $url = 'http://' . SimpleConfig::get('URL') . '/Help/start?userid=' . $robotId .
            '&platform=server&inner_secret=5ff10ecc78ada17c37b96fdf1ecb0c9e';
        $goods = (new DAOGoods())->getRandGoods();
        $params = [
            'token' => $token,
            'uid' => $uid,
            'relateid' => $relateid,
            'type' => $type,
            'goods_id' => $goods['goods_id'],
        ];
        $res = Curl::post($url, json_encode($params), 0, array(
            'Content-Type: application/json',
        ));
        $arr = json_decode($res, true);
        if ($arr['code'] != 0) {
            Logger::fatal('robot help', array_merge($arr, $value['params']));
        }
        return true;
    }
}