<?php
/*
 * @description: 数组操作类
 * @update: zhouweiwei
 * @date: 2010-05-30
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');

class Helper_Array {
	/**
	 * 二维数组根据特定键值索引排序
	 * @param $arr Array 二维数组
	 * @param $orderbyKey String 特定索引
	 * @param $type String ASC|DESC
	 * @return Array
	 */
	public static function arrayOrderBy($arr, $orderbyKey, $type = 'ASC') {
        $column = array();
        foreach($arr as $key => $value) {
            $column[$key] = $value[$orderbyKey];
        }
        $type = strtoupper($type) == "ASC" ? SORT_ASC : SORT_DESC;

		array_multisort($column, $type, $arr);
		unset($column);
        return $arr;
    }

	/**
	 * 获取二维数组的指定字段值
	 *
	 */
	public static function arrayMapHelper($mapper, $array) {
		$mapper = preg_replace('/^return (.*?);$/', '$1', trim($mapper));
		$result = array();
		if (preg_match('/(\(?)(.*?)\s*=>\s*(.*?)(\)?)$/', $mapper, $matches)) {
		   list($full_found, $array_open, $left, $right, $array_close) = $matches;
		   if ($array_open && $array_close) {
			   $mapper = '$result[] = array' . $full_found . ';';
		   } else {
			   $mapper = '$result[' . $left . '] = ' . $right . ';';
		   }
		} else {
		   $mapper = '$result[] = ' . $mapper . ';';
		}

		foreach ($array as $key => $value) {
		   eval($mapper);
		}
		unset($array, $matches, $mapper);
		return $result;
	}
}

/*
$array = array('aaa'=>array('foo' => '中国', 'bar' => 22),
               'bbb'=>array('foo' => '中国', 'bar' => 222),
               'ccc'=>array('bar' => 2222));
$mapped = Helper_Array::arrayMapHelper('$value["foo"]', $array);
var_dump($mapped);
*/
?>