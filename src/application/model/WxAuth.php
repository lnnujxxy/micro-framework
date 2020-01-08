<?php

namespace Pepper\Framework\Model;

use Pepper\Framework\Lib\Curl;
use Pepper\Framework\Lib\Logger;
use Pepper\Framework\Lib\RedisProxy;
use Pepper\Framework\Traits\RedisTrait;
use Pepper\Lib\SimpleConfig;

class WxAuth
{
    use RedisTrait;
    public static function code2Session($code) {
        $appid = WX_APPID;
        $secret = WX_SECRET;

        $res = [];
        for ($i = 0; $i < 2; $i++) {
            $json = Curl::get("https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code");
            $json && $res = json_decode($json, true);

            if ($res && $res['errcode'] == 0) {
                Logger::log('oauth', 'code2Session_succ', array_merge(['code' => $code], $res));
                break;
            }
            Logger::log('oauth_wf', 'code2Session_fail', array_merge(['code' => $code], $res));
        }
        return $res;
    }

    public static function getAccessTokenByCode($code) {
        $params = array(
            'appid' => OFFICIAL_APPID,
            'secret' => OFFICIAL_SECRET,
            'code' => $code,
            'grant_type' => 'authorization_code'
        );

        $res = [];
        for ($i = 0; $i < 2; $i++) {
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?'.http_build_query($params);
            $json = Curl::get($url);
            $json && $res = json_decode($json, true);

            if ($res && $res['errcode'] == 0) {
                Logger::log('oauth', 'oauth_succ', array_merge(['code' => $code], $res));
                break;
            }
            Logger::log('oauth_wf', 'oauth_fail', array_merge(['code' => $code], $res));
        }
        return $res;
    }

    public static function setAccessToken($accessToken) {
        $redis = self::getRedis();
        $accessToken && $redis->set('wx_access_token', $accessToken, 180);
    }

    public static function getAccessToken() {
        $redis = self::getRedis();
        $accessToken = $redis->get('wx_access_token');
        if (!$accessToken) {
            $json = Curl::get("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid="
                . WX_APPID . "&secret=".WX_SECRET);
            $res = json_decode($json, true);
            if (isset($res['access_token'])) {
                self::setAccessToken($res['access_token']);
            }
            $accessToken = $res['access_token'];
        }
        return $accessToken;
    }
}