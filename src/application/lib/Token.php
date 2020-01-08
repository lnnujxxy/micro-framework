<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/9
 * Time: 下午5:37
 */

namespace Pepper\Framework\Lib;

use Pepper\Framework\Traits\RedisTrait;
/**
 * Token验证
 *
 */
class Token {
    use RedisTrait;
    const TOKEN_SECRET = "ee2c&43PnM2h";
    private static $string = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

    private static $configs = array(
        "default" => array(
            "flag"  => "a", // 标记, 与appname对应,保持唯一
            "level" => 1, // 安全等级
            "seed"  => "a", // 渠道加密钥
            "ttl"   => 86400 * 7, // 有效时间
        ),
    );

    /**
     * 生成Token
     * $data 包含 userid, appname, s
     * token 20位header + 12位随机 + 4位签名 包含 userid, 渠道, 渠道密钥, 过期时间, 用户存储密钥
     *
     */
    public static function makeToken($data) {
        if (!$data["userid"] || !$data["salt"]) {
            throw new \InvalidArgumentException('生成token, 参数非法');
        }

        $userid = $data["userid"];
        $appname = "default";
        $expire = isset($data["expire"]) && $data["expire"] > time() ? $data["expire"] : self::getConfig($appname, "ttl");
        if (Util::isTestEnv()) {
            $expire = time() + 86400 * 30;
        }
        $flag = self::getConfig($appname, "flag");
        $seed = self::getConfig($appname, "seed");
        $salt = self::genSalt($data["salt"]);

        $header = self::base64UrleEncode(pack("AANNH*", $flag, $seed, $userid, $expire, $salt));
        $randomStr = self::genRandomString();
        $hash = self::getHash($flag . $seed . $userid . $expire .  $salt . $randomStr);
        $token = $header . $randomStr . $hash;

        $key = self::getSessionKey($userid);
        $redis = self::getRedis($key);
        if (!$redis->set($key, $salt, 604800)) {
            throw new \RuntimeException('token 写入存储失败');
        }
        return $token;
    }

    /**
     * 解开token获取信息
     *
     */
    public static function getTokenInfo($token, $key = null) {
        static $_infos;
        if (!$_infos[$token]) {
            if (!$token) {
                $_infos[$token] = array();
            }
            $header = substr($token, 0, -16); //12位随机 4位签名
            if (!$header) {
                return $key ? null : array();
            }

            $info = @unpack("Aflag/Aseed/Nuserid/Nexpire/H*salt", self::base64UrlDecode($header));
            $randomStr = substr($token, -16, -4);
            $osign = substr($token, -4);
            $nsign = self::getHash($info["flag"] . $info["seed"] . $info["userid"] . $info["expire"] . $info["salt"] . $randomStr);
            if ($osign != $nsign || !self::checkInfo($info)) {
                $_infos[$token] = array();
            } else {
                foreach (self::$configs as $appname => $config) {
                    if ($config["flag"] == $info["flag"]) {
                        $info["appname"] = $appname;
                        $info["level"] = $config["level"];
                    }
                }
                $_infos[$token] = $info;
            }
        }

        return $key ? isset($_infos[$token][$key]) ? $_infos[$token][$key] : null : $_infos[$token];
    }

    public static function getSignByToken($token, $uid) {
        $secret = substr(md5($uid), 0, 16);
        return self::base64UrleEncode(Encrypt::encrypt_aes($secret, $token));
    }

    public static function getTokenBySign($sign, $uid) {
        $secret = substr(md5($uid), 0, 16);
        return Encrypt::decrypt_aes($secret, self::base64UrlDecode($sign));
    }

    /**
     * 验证token合法性
     */
    public static function checkToken($token) {
        $info = self::getTokenInfo($token);
        return isset($info["userid"]) && $info["userid"] > 0 && self::checkSession($info["userid"], $info["salt"]);
    }

    public static function checkSalt($token, $salt) {
        $info = self::getTokenInfo($token);
        return $info["salt"] == self::genSalt($salt);
    }

    private static function checkInfo($info) {
        if (!$info) {
            return false;
        }

        if (!$info["flag"] || !$info["seed"] || !self::checkFlagSeed($info["flag"], $info["seed"])) {
            return false;
        }

        if (!$info["userid"] || !$info["salt"] || $info["expire"] < time()) {
            return false;
        }

        if (!self::checkSession($info["userid"], $info["salt"])) {
            return false;
        }
        return true;
    }

    private static function checkFlagSeed($flag, $seed) {
        foreach (self::$configs as $index => $config) {
            if ($config["flag"] == $flag && (self::$configs[$index]["seed"] == $seed || self::$configs[$index]["old_seed"] == $seed)) {
                return true;
            }
        }
        return false;
    }

    private static function checkSession($userid, $salt) {
        $key = self::getSessionKey($userid);
        $redis = self::getRedis($key);
        $value = $redis->get($key);
        //redis异常，默认成功
        if ($value === false) {
            return true;
        }

        if (!$value || $value != $salt) {
            return false;
        }

        return true;
    }

    private static function getSessionKey($userid) {
        return sprintf("key:token:%d", $userid);
    }

    /**
     *  url safe base64
     * @param $input
     * @return string
     */
    private static function base64UrleEncode($input) {
        return strtr(base64_encode($input), "+/=", "._-");
    }

    private static function base64UrlDecode($input) {
        return base64_decode(strtr($input, "._-", "+/="));
    }

    private static function getConfig($appname, $key) {
        if ($key == "ttl") {
            return self::$configs[$appname][$key] + time();
        }

        return self::$configs[$appname][$key];
    }

    /**
     * 生成指定长度的随机串
     */
    private static function genRandomString($length = 12) {
        $sourceLength = strlen(self::$string);
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $v = mt_rand(0, $sourceLength - 1);
            $out .= self::$string[$v];
        }
        return $out;
    }

    /**
     * 生成salt 参考git取前6位即唯一
     */
    private static function genSalt($salt = "") {
        return substr(sha1((string)$salt), 0, 6);
    }

    /**
     * 生成签名比较,防伪造
     *
     */
    private static function getHash($str = "") {
        return substr(md5($str.self::TOKEN_SECRET), 4, 4);
    }
}

