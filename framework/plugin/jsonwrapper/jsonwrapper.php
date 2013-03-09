<?php
/*
 * 定义json处理方法方法
 *
 * @author: zhouweiwei
 * @date: 2010-8-29
 * @version: 1.0
 */

require_once dirname(__FILE__).'/JSON.php';

function json_encode($arg) {
	global $services_json;
	if (!isset($services_json)) {
		$services_json = new Services_JSON();
	}
	return $services_json->encode($arg);
}

function json_decode($arg, $assoc=true) {
	global $services_json;
	if (!isset($services_json)) {
		$services_json = new Services_JSON();
	}
	if($assoc) {
		return objectToArray($services_json->decode($arg));
	}
	return $services_json->decode($arg);
}

function objectToArray($object) {
	if(!is_object($object) && !is_array($object)) {
		return $object;
	}
	if(is_object($object)) {
		$object = get_object_vars($object);
	}
	return array_map('objectToArray', $object);
}
/*
$array = array(
	'aaa' => 1,
	'bbb' => array('aaa'=>111, 'bbb'=>222),
);
$str = json_encode($array);

var_dump(json_decode($str));
*/
?>
