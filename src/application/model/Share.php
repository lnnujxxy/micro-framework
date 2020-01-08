<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午9:46
 */
namespace Pepper\Framework\Model;

use Pepper\Framework\Dao\DAOShare;
use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Logger;
use Pepper\Framework\Lib\QcloudCos;
use Pepper\Framework\Traits\RedisTrait;
use Pepper\Lib\Curl;

class Share
{
    use RedisTrait;
    const BLESS_SHARE_NUM = 3; // 分享获取次数
    public static function addShare($uid, $relateid, $type, $to) {
        $daoShare = new DAOShare();
        if ($daoShare->total($uid, $relateid, $type) < self::BLESS_SHARE_NUM) {
            Feeds::incrField($relateid, $type, 'bless', 1);
        }
        Feeds::incrField($relateid, $type, 'shares', 1);

        $daoShare->add($uid, $relateid, $type, $to);
        return true;
    }

    public static function genWXACodeUrl($relateid, $type) {
        $helpUid = Context::get('userid');
        $key = "wx:code:" . $relateid . ':' . $type . ':' . $helpUid;
        $scene = $relateid.'&'.$type.'&'.$helpUid;

        $redis = self::getRedis($key);
        if ($url = $redis->get($key)) {
            Logger::log('wxcode', 'genWXACodeUrl cache', ['scene' => $scene]);
            return $url;
        }

        if ($type == Feeds::PENANCE_TYPE_ID) {
            $page = "pages/repent/repent";
            $defaultUrl = "https://goods-1257256615.file.myqcloud.com/qrcode_c535f73c7d7f051d2eec631b41988c1b.png";
        } else if ($type == Feeds::PRAY_TYPE_ID) {
            $page = "pages/wish/wish";
            $defaultUrl = "https://goods-1257256615.file.myqcloud.com/qrcode_bd3334dc74a4ca0025b10a3d28a5c49b.png";
        } else {
            $page = "pages/lucky/lucky";
            $defaultUrl = "https://goods-1257256615.file.myqcloud.com/qrcode_a7afd93c3205c9d9af09df6c9e5495ec.png";
        }

        $params = [
            'scene' => $scene,
            'page' => $page
        ];
        $accessToken = WxAuth::getAccessToken();
        $data = Curl::post(
            "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$accessToken,
            json_encode($params)
        );
        // 生成错误，重新生成
        if (strpos($data, 'errcode') !== false) {
            Logger::log('wxcode', 'getwxacodeunlimit fail', array_merge(json_decode($data, true), ['scene' => $scene]));
            return $defaultUrl;
        }

        $qcloudCos = new QcloudCos();
        $url = $qcloudCos->putObject("qrcode_". md5(json_encode($params) . time()) . ".png", $data);
        if ($url) {
            Logger::log('wxcode', 'genWXACodeUrl success', ['scene' => $scene]);
            $redis->set($key, $url, 3600);
            return $url;
        } else {
            Logger::log('wxcode', 'genWXACodeUrl fail', ['scene' => $scene]);
            return $defaultUrl; // 默认返回
        }
    }
}