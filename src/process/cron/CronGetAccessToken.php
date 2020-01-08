<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午2:04
 */
namespace Pepper\FrameWorker\Process\Cron;


use Pepper\Framework\Lib\Curl;
use Pepper\Framework\Lib\QcloudCos;
use Pepper\Framework\Lib\Util;
use Pepper\Framework\Model\WxAuth;
use Pepper\Lib\SimpleConfig;

require_once __DIR__ . "/Init.php";
$json = Curl::get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".WX_APPID."&secret=".WX_SECRET);
$res = json_decode($json, true);
if (isset($res['access_token'])) {
    WxAuth::setAccessToken($res['access_token']);
    // todo 记录access_token
//    $data = Curl::post("https://api.weixin.qq.com/wxa/getwxacode?access_token=".$res['access_token'],
//        json_encode(array('path'=>'pages/home/index/index')));
//    $qcloudCos = new QcloudCos();
//    // 获取临时密钥，计算签名
//    $result = $qcloudCos->putObject("qrcode_".time() . ".png", $data);
//    var_dump(Util::useCDN($result));
}
echo date("Y-m-d H:i:s") . "ok\n";