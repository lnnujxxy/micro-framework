<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午2:04
 * 发悔过
 */
namespace Pepper\FrameWorker\Process\Cron;

use Pepper\Framework\Dao\DAOContent;
use Pepper\Framework\Dao\DAOGoods;
use Pepper\Framework\Lib\Curl;
use Pepper\Framework\Lib\Token;
use Pepper\Framework\Model\Feeds;
use Pepper\Framework\Model\Robot;
use Pepper\Framework\Model\User;
use Pepper\Lib\SimpleConfig;

require_once __DIR__ . "/Init.php";
$num = 1; // 每次选出n个用户

$robotIds = Robot::getRobotIds($num);
foreach ($robotIds as $robotId) {
    $robotUser = User::getUserInfo($robotId);
    $token = Token::makeToken(['userid' => $robotId, 'salt' => $robotUser['salt']]);
    $url = 'http://' . SimpleConfig::get('URL') . '/Feeds/addFeed?userid=' . $robotId .
            '&platform=server&inner_secret=5ff10ecc78ada17c37b96fdf1ecb0c9e';
    $type = Feeds::supportTypeIds[array_rand(Feeds::supportTypeIds)];
    $object = (new DAOContent())->getRandContent($type, 'object');
    $content = (new DAOContent())->getRandContent($type, 'content');
    $params = [
        'token' => $token,
        'type' => $type,
        'object_id' => $object['id'],
        'content' => [
            'name' => $object['content'],
            'content' => $content['content'],
            'avatar' => $robotUser['avatar'],
            'url' => $content['url'],
            'userid' => $robotId,
        ],
    ];
    $res = Curl::post($url, json_encode($params), 0, array(
        'Content-Type: application/json',
    ));
    $arr = json_decode($res, true);
    if ($arr['code'] == 0 && isset($arr['data'])) { // 购买礼物
        $data = $arr['data'];
        $goods = (new DAOGoods())->getRandGoods();
        $params = [
            'goods_id' => $goods['goods_id'],
            'relateid' => $data['relateid'],
            'type' => $data['type'],
            'from' => 1,
            'userid' => $robotId
        ];
        $url = 'http://' . SimpleConfig::get('URL') . '/Goods/buyGoods?userid=' . $robotId .
            '&platform=server&inner_secret=5ff10ecc78ada17c37b96fdf1ecb0c9e';
        Curl::post($url, json_encode($params), 0, array(
            'Content-Type: application/json',
        ));
    }
}

echo date("Y-m-d H:i:s") . "ok\n";