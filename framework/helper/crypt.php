<?php
/*
 * 加密类
 *
 * @package: Helper
 * @author: liu21st <liu21st@gmail.com>
 * @update: zhouweiwei
 * @date: 2010-8-18
 * @version: 1.0
 */
//defined('IN_ROOT') || exit('Access Denied');

class Helper_Crypt {

	public static function secure($str, $crypt='encrypt', $key=null) {
		if(empty($key)) {
			$key = 'citytc_o89L7234kjW2Wad72SHw22lPZmEbP3dSj7TT10A5Sh60';
		}
		return $crypt==='encrypt' ? self::encrypt($str, $key) : self::decrypt($str, $key);
	}

	/**
	 *
	 * 加密函数
	 * @param $data String
	 * @param $key String 密钥
	 * @return String
	 */
    public static function encrypt($data, $key) {
        $key = md5($key);
        $data = base64_encode($data);
        $x = 0;
		$len = strlen($data);
		$l = strlen($key);

		for ($i=0;$i< $len;$i++) {
            if ($x== $l) $x=0;
            $char .= substr($key,$x,1);
            $x++;
        }
        for ($i=0;$i< $len;$i++) {
            $str .= chr(ord(substr($data,$i,1))+(ord(substr($char,$i,1)))%256);
        }
        return $str;
    }

   /**
	 *
	 * 解密函数
	 * @param $data String
	 * @param $key String 密钥
	 * @return String
	 */
    public static function decrypt($data, $key) {
        $key = md5($key);
        $x = 0;
		$len = strlen($data);
		$l = strlen($key);

        for ($i=0;$i< $len;$i++) {
            if ($x== $l) $x=0;
            $char .= substr($key,$x,1);
            $x++;
        }
        for ($i=0;$i< $len;$i++) {
            if (ord(substr($data,$i,1))<ord(substr($char,$i,1))) {
                $str .=chr((ord(substr($data,$i,1))+256)-ord(substr($char,$i,1)));
            } else {
                $str .=chr(ord(substr($data,$i,1))-ord(substr($char,$i,1)));
            }
        }
        return base64_decode($str);
    }
}
//var_dump($str = Helper_Crypt::secure('中国人名', 'encrypt'));
//echo Helper_Crypt::secure($str, 'decrypt');
?>