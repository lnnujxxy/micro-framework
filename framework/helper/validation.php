<?php
/*
 * @description: 验证处理类
 * @author: colaphp
 * @update: zhouweiwei
 * @date: 2010-5-30
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');

class Helper_Validation {
    private static $_error;

	private static $_message = array(
		'email'    => 'invalid_email',
		'required' => 'empty',
		'max'      => 'above_max',
		'min'      => 'below_min',
		'maxValue' => 'above_max_value',
		'minValue' => 'below_min_value',
		'range'    => 'not_in_rang',
		'ip'       => 'invalid_ip',
		'number'   => 'not_all_numbers',
		'int'      => 'not_int',
		'digit'    => 'not_digit',
		'string'   => 'not_string',
		'domain'   => 'invalid_domain',
		'zipCode'  => 'invalid_zipCode',
		'phone'	   => 'invalid_phone',
		'mobile'   => 'invalid_mobile',
		'idCard'   => 'invalid_idCard',
		'checkName' => 'invalid_checkName',
		'extension' => 'invalid_extension',
		'scalar'	=> 'invalid_scalar',
		'func'		=> 'invalid_func_check',
		'equal'		=> 'invalid_equal',
		'url'		=> 'invalid_url',
	);
    /**
     * Check if is not empty
     *
     * @param string $str
     * @return boolean
     */
    public static function notEmpty($str, $trim = true) {
        if (is_array($str)) {
            return 0 < count($str);
        }

        return strlen($trim ? trim($str) : $str) ? true : false;
    }

    /**
     * Match regex
     *
     * @param string $value
     * @param string $regex
     * @return boolean
     */
    public static function match($value, $regex) {
        return preg_match($regex, $value) ? true : false;
    }

    /**
     * Max
     *
     * @param mixed $value numbernic|string
     * @param number $max
     * @return boolean
     */
    public static function max($value, $max) {
        if (is_string($value)) $value = strlen($value);
        return $value <= $max;
    }


    /**
     * Min
     *
     * @param mixed $value numbernic|string
     * @param number $min
     * @return boolean
     */
    public static function min($value, $min) {
        if (is_string($value)) $value = strlen($value);
        return $value >= $min;
    }


	/**
	 * 最大值
	 * @param $value Int 数值
	 * @param $maxVal Int 最大值
	 * @return Bool
	 */
	public static function maxValue($value, $maxVal) {
		return intval($value) < intval($maxVal);
	}

	/**
	 * 最小值
	 * @param $value Int 数值
	 * @param $minVal Int 最小值
	 * @return Bool
	 */
	public static function minValue($value, $minVal) {
		return intval($value) > intval($minVal);
	}

    /**
     * Range
     *
     * @param mixed $value numbernic|string
     * @param array $max
     * @return boolean
     */
    public static function range($value, $range) {
        if (is_string($value)) $value = strlen($value);
        return $value >= $range[0] && $value <= $range[1];
    }

    /**
     * Check if in array
     *
     * @param mixed $value
     * @param array $list
     * @return boolean
     */
    public static function in($value, $list) {
        return in_array($value, $list);
    }

    /**
     * Check if is email
     *
     * @param string $email
     * @return boolean
     */
    public static function email($email) {
        return preg_match('/^[a-z0-9_\-]+(\.[_a-z0-9\-]+)*@([_a-z0-9\-]+\.)+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)$/', $email) ? true : false;
    }

    /**
     * Check if is url
     *
     * @param string $url
     * @return boolean
     */
    public static function url($url) {
        return preg_match('/^((https?|ftp|news):\/\/)?([a-z]([a-z0-9\-]*\.)+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)|(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))(\/[a-z0-9_\-\.~]+)*(\/([a-z0-9_\-\.]*)(\?[a-z0-9+_\-\.%=&amp;]*)?)?(#[a-z][a-z0-9_]*)?$/', $url) ? true : false;
    }

    /**
     * Check if is ip
     *
     * @param string $ip
     * @return boolean
     */
    public static function ip($ip) {
        return ((false === ip2long($ip)) || (long2ip(ip2long($ip)) !== $ip)) ? false : true;
    }

    /**
     * Check if is date
     *
     * @param string $date
     * @return boolean
     */
    public static function date($date) {
        return preg_match('/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/', $date) ? true : false;
    }

    /**
     * Check if is numbers
     *
     * @param mixed $value
     * @return boolean
     */
    public static function number($value) {
        return is_numeric($value);
    }

	public static function scalar($value) {
		return is_scalar($value);
	}

    /**
     * Check if is int
     *
     * @param mixed $value
     * @return boolean
     */
    public static function int($value) {
        return is_int($value);
    }

    /**
     * Check if is digit
     *
     * @param mixed $value
     * @return boolean
     */
    public static function digit($value) {
        return ctype_digit($value);
    }

    /**
     * Check if is string
     *
     * @param mixed $value
     * @return boolean
     */
    public static function string($value) {
        return is_string($value);
    }

	/**
	 * 是否是一个合法域名
	 * @param mixed $value
	 * @return boolean
	 */
	public static function domain($value) {
		return preg_match("/^[a-z0-9]([a-z0-9-]+\.){1,4}[a-z]{2,5}$/i", $value);
	}

	/*
	 * 邮编合法性检测
	 * @param mixed $value
	 * @return boolean
	 */
	public static function zipCode($value) {
		return is_numeric($value) && (strlen($value)==6);
	}

	/**
	 * 电话(传真)号码合法性检测
	 * @param mixed $value
	 * @return boolean
	 */
	public static function phone($value) {
		return preg_match("/^(\d){2,4}[\-]?(\d+){6,9}$/", $value);
	}

	/**
	 * 手机号码合法性检查
	 * @param mixed $value
	 * @return boolean
	 */
	 public static function mobile($value) {
		return preg_match("/^(13|15|18)\d{9}$/i", $value);
	 }

	/**
	 * 身份证号码合法性检测
	 * @param mixed $value
	 * @return boolean
	 */
	 public static function idCard($value){
		return preg_match("/^(\d{17}[\dx])$/i", $value);
	 }

	 /**
	 * 比较字符串是否相等
	 * @param $str1 String
	 * @param $str2 String
	 * @return Bool
	 */
	 public static function equal($str1, $str2) {
		return $str1 === $str2;
	 }

	 /**
	 * 检测一个用户名的合法性
	 *
	 * @param string $str 需要检查的用户名字符串
	 * @param int $chkType 要求用户名的类型，
	 * @		  1为英文、数字、下划线，2为任意可见字符，3为中文(GBK)、英文、数字、下划线，4为中文(UTF8)、英文、数字，缺省为1
	 * @return bool 返回检查结果，合法为true，非法为false
	 */
	public static function checkName($str, $chkType=1) {
		switch($chkType) {
			case 1:
				$result = preg_match("/^[a-zA-Z0-9_]+$/i", $str);
				break;
			case 2:
				$result = preg_match("/^[\w\d]+$/i", $str);
				break;
			case 3:
				$result = preg_match("/^[_a-zA-Z0-9\0x80-\0xff]+$/i", $str);
				break;
			case 4:
				$result = preg_match("/^[_a-zA-Z0-9\u4e00-\u9fa5]+$/i", $str);
				break;
			default:
				$result = preg_match("/^[a-zA-Z0-9_]+$/i", $str);
				break;
		}
		return $result;
	}

	public static function extension($check, $extensions = array('gif', 'jpeg', 'png', 'jpg')) {
		if (is_array($check)) {
			return self::extension(array_shift($check), $extensions);
		}
		$extension = strtolower(array_pop(explode('.', $check)));
		foreach ($extensions as $value) {
			if ($extension == strtolower($value)) {
				return true;
			}
		}
		return false;
	}

    /**
     * Check
     *
     * $rules = array(
     *     'required' => true if required , false for not
     *     'type'     => var type, should be in ('email', 'url', 'ip', 'date', 'number', 'int', 'string', 'domain', 'zipCode', 'phone', 'mobile', 'idCard', 'checkName')
     *     'regex'    => regex code to match
     *     'func'     => validate function, use the var as arg
     *     'max'      => max number or max length
     *     'min'      => min number or min length
     *     'range'    => range number or range length
     *     'msg'      => error message,can be as an array
     * )
     *
     * @param array $data
     * @param array $rules
     * @param boolean $ignorNotExists
     * @return boolean
     */
    public static function Validate($data, $rules, $ignorNotExists = false)
    {
        $error = array();

        foreach ($rules as $key => $rule) {
            $rule += array('required' => false, 'msg' => self::$_message);
            // deal with not existed or ''
            if (!isset($data[$key]) || !self::notEmpty($data[$key])) {
                if (!$ignorNotExists && $rule['required']) $error[$key] = self::msg($rule, 'required');
                continue;
            }

            $value = $data[$key];

            $result = self::check($value, $rule);

            if (true !== $result) $error[$key] = $result;
        }

        if (empty($error)) return true;

        self::$_error = $error;
        return false;
    }

    /**
     * Check value
     *
     * @param mixed $value
     * @param array $rule
     * @return mixed string as error, true for OK
     */
    private static function check($value, $rule)
    {
        if ($rule['required'] && !self::notEmpty($value)) {
            return self::msg($rule, 'required');
        }

        if (isset($rule['func']) && !call_user_func($rule['func'], $value)) {
            return self::msg($rule, 'func');
        }

        if (isset($rule['regex']) && !self::match($value, $rule['regex'])) {
            return self::msg($rule, 'regex');
        }
		/*
		if (isset($rule['equal']) && !self::match($value, $rule['equal'])) {
            return self::msg($rule, 'regex');
        }
		*/

        if (isset($rule['type']) && !self::$rule['type']($value)) {
            return self::msg($rule, $rule['type']);
        }

        $acts = array('max', 'min', 'range', 'in', 'equal', 'maxValue', 'minValue');
        foreach ($acts as $act) {
            if (isset($rule[$act]) && !self::$act($value, $rule[$act])) {
                return self::msg($rule, $act);
            }
        }

        return true;
    }

    /**
     * Get error message
     *
     * @param array $rule
     * @param string $name
     * @return string
     */
    private static function msg($rule, $name)
    {
        if (empty($rule['msg'])) return 'INVALID';

        if (is_string($rule['msg'])) return $rule['msg'];

        return isset($rule['msg'][$name]) ? $rule['msg'][$name] : 'INVALID';
    }

    /**
     * Get error
     *
     * @return array
     */
    public static function error()
    {
        return self::$_error;
    }
}


//测试
/*
$data = array(
	'aaa' => 'adfas111',
	'bbb' => 'lnnujxxy@gmail.com',
	'ccc' => 'aaaaa',
	'ddd' => '1111111',
	'eee' => 1111,
	'fff' => 2222,
	'ggg' => 101,
	'kkk' => 1,
	'url' => 'www.sina.com',
);

var_dump(Helper_Validation::validate($data,
				array('aaa'=>
						array(
							'required' => false,
							'type'     => 'scalar'
							),
					'bbb' =>
						array(
							'required' => true,
							'type'		=> 'email'
						),
					'ccc' =>
						array(
							'required' => true,
							'type' => 'string'
						),
					'ddd' =>
						array(
							'required' => false,
							'type' => 'number'
						),
					'eee' =>
						array(
							'required' => false,
							//'type' => 'number'
							//'regex' => '/\d+/',
							'func' => array('Helper_Validation', 'extension')
						),
					'fff' =>
						array(
							'equal' => $data['eee'],
						),
					 'ggg' =>
						array(
							'maxValue' => 100,
						),
					  'kkk' => array(
							'minValue' => 3,
					  ),
					  'url' => array(
							'type' => 'url',
					  )
				)
			)
		);
var_dump(Helper_Validation::error());
*/
