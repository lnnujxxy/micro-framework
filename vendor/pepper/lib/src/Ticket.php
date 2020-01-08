<?php
namespace Pepper\Lib;

class Ticket
{
    const LOGIN_SECRET = '2!@SEV&S43D';
    const LOGIN_SECRET_TTL = 604800;//86400 * 7 
    
    public static function getTaskTicket($uid, $taskid, $num = 1, $expire = 14400)
    {
        return self::makeToken(array("userid" => $uid, "taskid" => $taskid, "num" => $num, "time" => time()), $expire);
    }

    /*expire : 过期时间秒数或时间戳*/
    public static function makeToken($data, $expire = null, $secret = null)
    {
        $secret = $secret ? $secret : self::LOGIN_SECRET;
        $data = http_build_query($data);
        $data = base64_encode($data); 
        $time = $expire > 315360000 ? $expire : ($_SERVER['REQUEST_TIME'] + ($expire ? $expire : self::LOGIN_SECRET_TTL)); 
        return $data.'.'.md5($data.'.'.$time.'.'.$secret).'.'.$time; 
    }
 
    public static function getTokenInfo($token, $key = null, $secret = null)
    {
        $secret = $secret ? $secret : self::LOGIN_SECRET;
        $token_arr= explode('.', $token, 3);
        $data = $token_arr[0];
        $time = $token_arr[2];
        
        parse_str(base64_decode($data), $output);

        if(($time > $_SERVER['REQUEST_TIME']) && ($token == self::makeToken($output, $time, $secret))) {
            return $key ? (isset($output[$key]) ? $output[$key] : null) : $output; 
        }

        return null;
    }
  
    public static function checkToken($token, $key_val_arr = null, $secret = null)
    {
        $secret = $secret ? $secret : self::LOGIN_SECRET;
        $token_info = self::getTokenInfo($token, null, $secret);
        if(!$token_info) {
            return false;
        }

        if($key_val_arr) {
            foreach($key_val_arr as $k=>$v) {
                if(!isset($token_info[$k]) || $token_info[$k] != $v) {
                    return false;
                }
            }
        }

        return true;
    }
}
