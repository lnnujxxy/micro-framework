<?php
/**
 * XML与数组转换
 * @author: cnteacher
 * @update: zhouweiwei
 * @date: 2010/09/07
 */

class Helper_XmlArray {
	public static function xml2array(&$xml, $isnormal = FALSE) {
		$xml_parser = new XMLparse($isnormal);
		$data = $xml_parser->parse($xml);
		$xml_parser->destruct();
		return $data;
	}

	public static function array2xml($arr, $htmlon = FALSE, $isnormal = FALSE, $level = 1) {
		$s = $level == 1 ? "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n<root>\r\n" : '';
		$space = str_repeat("\t", $level);
		foreach($arr as $k => $v) {
			if(!is_array($v)) {
				$s .= $space."<item id=\"$k\">".($htmlon ? '<![CDATA[' : '').$v.($htmlon ? ']]>' : '')."</item>\r\n";
			} else {
				$s .= $space."<item id=\"$k\">\r\n".self::array2xml($v, $htmlon, $isnormal, $level + 1).$space."</item>\r\n";
			}
		}
		$s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
		return $level == 1 ? $s."</root>" : $s;
	}
}

class XMLparse {

	var $parser;
	var $document;
	var $stack;
	var $data;
	var $last_opened_tag;
	var $isnormal;
	var $attrs = array();
	var $failed = FALSE;

	function __construct($isnormal) {
		$this->XMLparse($isnormal);
	}

	function XMLparse($isnormal) {
		$this->isnormal = $isnormal;
		$this->parser = xml_parser_create('ISO-8859-1');
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, 'open','close');
		xml_set_character_data_handler($this->parser, 'data');
	}

	function destruct() {
		xml_parser_free($this->parser);
	}

	function parse(&$data) {
		$this->document = array();
		$this->stack	= array();
		return xml_parse($this->parser, $data, true) && !$this->failed ? $this->document : '';
	}

	function open(&$parser, $tag, $attributes) {
		$this->data = '';
		$this->failed = FALSE;
		if(!$this->isnormal) {
			if(isset($attributes['id']) && !is_string($this->document[$attributes['id']])) {
				$this->document  = &$this->document[$attributes['id']];
			} else {
				$this->failed = TRUE;
			}
		} else {
			if(!isset($this->document[$tag]) || !is_string($this->document[$tag])) {
				$this->document  = &$this->document[$tag];
			} else {
				$this->failed = TRUE;
			}
		}
		$this->stack[] = &$this->document;
		$this->last_opened_tag = $tag;
		$this->attrs = $attributes;
	}

	function data(&$parser, $data) {
		if($this->last_opened_tag != NULL) {
			$this->data .= $data;
		}
	}

	function close(&$parser, $tag) {
		if($this->last_opened_tag == $tag) {
			$this->document = $this->data;
			$this->last_opened_tag = NULL;
		}
		array_pop($this->stack);
		if($this->stack) {
			$this->document = &$this->stack[count($this->stack)-1];
		}
	}

}

//测试
/*
$data = array('aa'=>array('中国'), 'bb'=>2);
$xml = Helper_XmlArray::array2xml($data);
var_dump(Helper_XmlArray::xml2array($xml));
*/
/*
$xml = "<?xml version='1.0' encoding='utf-8'?>
<rows><page>1</page><total>1</total><records>1</records><row><cell>1</cell><cell>Cash</cell><cell>100</cell><cell>400.00</cell><cell>250.00</cell><cell>150.00</cell><cell>0</cell><cell>0</cell><cell>1</cell><cell>8</cell><cell>false</cell><cell>true</cell></row><row><cell>2</cell><cell>Cash 1</cell><cell>1</cell><cell>300.00</cell><cell>200.00</cell><cell>100.00</cell><cell>0</cell><cell>1</cell><cell>2</cell><cell>5</cell><cell>false</cell><cell>true</cell></row><row><cell>3</cell><cell>Sub Cash 1</cell><cell>1</cell><cell>300.00</cell><cell>200.00</cell><cell>100.00</cell><cell>1</cell><cell>2</cell><cell>3</cell><cell>4</cell><cell>true</cell><cell>true</cell></row><row><cell>4</cell><cell>Cash 2</cell><cell>2</cell><cell>100.00</cell><cell>50.00</cell><cell>50.00</cell><cell>1</cell><cell>1</cell><cell>6</cell><cell>7</cell><cell>true</cell><cell>true</cell></row><row><cell>5</cell><cell>Banks</cell><cell>200</cell><cell>1500.00</cell><cell>1000.00</cell><cell>500.00</cell><cell>1</cell><cell>0</cell><cell>9</cell><cell>14</cell><cell>false</cell><cell>true</cell></row><row><cell>6</cell><cell>Bank 1</cell><cell>1</cell><cell>500.00</cell><cell>0.00</cell><cell>500.00</cell><cell>0</cell><cell>1</cell><cell>10</cell><cell>11</cell><cell>true</cell><cell>true</cell></row><row><cell>7</cell><cell>Bank 2</cell><cell>2</cell><cell>1000.00</cell><cell>1000.00</cell><cell>0.00</cell><cell>0</cell><cell>1</cell><cell>12</cell><cell>13</cell><cell>true</cell><cell>true</cell></row><row><cell>8</cell><cell>Fixed asset</cell><cell>300</cell><cell>0.00</cell><cell>1000.00</cell><cell>-1000.00</cell><cell>1</cell><cell>0</cell><cell>15</cell><cell>16</cell><cell>true</cell><cell>true</cell></row></rows>";
var_dump(Helper_XmlArray::xml2array($xml));
*/
?>