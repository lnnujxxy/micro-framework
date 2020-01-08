<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/18
 * Time: 下午9:43
 */

namespace Pepper\Framework\Controller;

use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Lib\QcloudCos;
use Pepper\Framework\Lib\Util;
use Pepper\Lib\SimpleConfig;

class QcloudController extends BaseController
{
    public function getTempKeysAction() {
        $domain = SimpleConfig::get('URL');
        $qcloudCos = new QcloudCos();
        // 获取临时密钥，计算签名
        $tempKeys = $qcloudCos->getTempKeys();
        Interceptor::ensureNotEmpty($tempKeys, ERROR_QCLOUD_AUTH_FAIL);
        // 返回数据给前端
        header('Allow-Control-Allow-Origin: http://'.$domain); // 这里修改允许跨域访问的网站
        header('Allow-Control-Allow-Origin: https://'.$domain); // 这里修改允许跨域访问的网站
        header('Allow-Control-Allow-Headers: origin,accept,content-type');
        $this->render($tempKeys);
    }

    public function pubObjectAction() {
        $avatar = "https://goods-1257256615.file.myqcloud.com/079077c7c9213fa2f1c665390315b481.png";
        $data = Util::getUrlContents($avatar, 3);
        if (!$data) {
            sleep(3);
            $data = Util::getUrlContents($avatar, 5);
            if (!$data) {
                return false;
            }
        }

        $qcloudCos = new QcloudCos();
        $result = $qcloudCos->putObject(time() . ".png", $data);
        $this->render(Util::useCDN($result));
    }
}