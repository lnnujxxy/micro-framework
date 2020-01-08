<?php
namespace Pepper\Framework\Lib;

use Pepper\Lib\SimpleConfig;

class Util
{
    public static function getCluster() {
        return \Pepper\Lib\Util::getCluster();
    }

    public static function isTestEnv() {
        return SimpleConfig::get('TEST_MODE') == true;
    }

    public static function isInnerEnv() {
        return self::isTestEnv() && !filter_var(Util::getIP(), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    public static function getTime($cache = true) {
        static $time;

        if ($cache) {
            if (!$time) {
                $time = isset($_SERVER['REQUEST_TIME']) && !empty($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
            }
        } else {
            $time = time();
        }

        return $time;
    }

    public static function getNextDayUnixTime() {
        return strtotime(date('Y-m-d', strtotime('+1 day')));
    }

    public static function diffNextDay() {
        return strtotime(date('Y-m-d', strtotime('+1 day'))) - time();
    }

    /**
     * 计算时间差
     * @param int $timestamp1 时间戳开始
     * @param int $timestamp2 时间戳结束
     * @return string
     */
    public static function timeDiff($timestamp1, $timestamp2) {
        if ($timestamp2 <= $timestamp1) {
            return '';
        }
        $timediff = $timestamp2 - $timestamp1;
        // 时
        $remain = $timediff%86400;
        $hours = intval($remain/3600);

        // 分
        $remain = $timediff%3600;
        $mins = intval($remain/60);
        // 秒
        $secs = $remain%60;

        $time = ['hours'=>$hours, 'minutes'=>$mins, 'seconds'=>$secs];
        $str = '';
        if ($time['hours']) {
            $str .= $time['hours'].'小时';
        }
        if ($time['minutes']) {
            $str .= $time['hours'].'分钟';
        }
        return $str;
    }

    public static function useCDN($url) {
        $map = [
            'http://' => 'https://',
            'goods-1257256615.cos.ap-beijing.myqcloud.com' => 'goods-1257256615.file.myqcloud.com',
        ];
        return str_replace(array_keys($map), array_values($map), $url);
    }

    public static function isValidUrl($url) {
        $url = trim($url);
        if (!is_string($url)) {
            return false;
        }

        $result = parse_url($url);
        if ($result === false || !isset($result['scheme']) || !isset($result['host'])) {
            return false;
        }

        if(strip_tags($url) != $url){
            return false;
        }
        //如果包含特殊字符，则返回false（防止xss攻击)
        if(filter_var($url, FILTER_VALIDATE_URL) === false){
            return false;
        }

        return true;
    }

    public static function getIP() {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $ip = getenv("REMOTE_ADDR");
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } else {
            $ip = '';
        }
        $pos = strpos($ip, ',');
        if ($pos > 0) {
            $ip = substr($ip, 0, $pos);
        }
        return trim($ip);
    }

    /*
     * build pages
     */
    public static function buildPages($data, $num, $offset, $name='data')
    {
        if (count($data) == $num + 1) {
            $more = true;
            array_pop($data);
        } else {
            $more = false;
        }
        $offset = $num + $offset;
        return [$name => $data, 'offset' => $offset, 'more' => $more];
    }

    public static function getUrlContents($url, $timeout = 30) {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.109 Safari/537.36');

        $output    = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($http_code != 200) {
            $output = false;
        }

        return $output;
    }

    public static function random() {
        return time() . mt_rand(1000000, 9000000);
    }

    public static function arrayToXml($data) {
        $xml = "<xml>";
        foreach ($data as $key=>$val) {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
}
