<?php
/*
 * @description: html过滤处理类
 * @author: sinablog
 * @update: zhouweiwei
 * @date: 2010-05-17
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');

class Helper_Tidy {
	private static $forbiddenTags = array('meta', 'xml', 'title', 'head', 'link', 'script', 'style', 'iframe');
	private static $allowIds = array();
	private static $allowClasses = array();

	/**
	 * 提示信息数组
	 */
	private static $msg = array(
		'error_tidy_noinstall'	=> 'tidy扩展未安装',	
	);

	private function __constuct() {
		if(!class_exists('tidy', false)) {
			$this->retMsg('error_tidy_noinstall', $this->msg['error_tidy_noinstall']);
		}
	}

	public static function tidy($html, $encoding = 'utf-8') {
		if ($html == '') {
			return '';
		}

		$output = '';
		$html = trim($html);

		//对于非utf-8编辑处理
		if($encoding !== 'utf-8') {
			$html = Common::convertEncoding($html, 'utf-8', $encoding);
		}
		$html = preg_replace("|\/\*(.*)\*\/|sU", "", $html);//过滤掉全部注释内容
		$html = preg_replace("/<!\[CDATA\[(.*?)\]\]>/is", "\\1", $html);//过滤掉CDATA标签
		$html = self::_escapeUnicode($html);//转义Unicode字符

		$tidy_conf = array(
			'output-xhtml' => true,
			'show-body-only' => true,
			'join-classes' => true
		);

		$html = str_replace("&", "&amp;", $html);
		$dom = tidy_parse_string($html, $tidy_conf, 'utf8');
		$body = $dom->body();
		if($body->child){
			foreach($body->child as $child) {
				self::_filterNode($child, $output);
			}
		}

		$html = self::_unEscapeUnicode($output);//反转义Unicode字符
		if($encoding !== 'utf-8') {
			$html = Common::convertEncoding($html, $encoding, 'utf-8');
		}
		
		$html = self::_insertVideo($html);
		return $html;
	}

	private static function _filterAttr($nodeName, $attrName, &$attrValue) {
		if ($attrName == "id" ) {
			foreach (self::$allowIds as $allow_id) {
				if(preg_match ("/{$allow_id}/isU", $attrValue, $arr)) {
					$isIdAllowed = true;
				}
			}

			if (!$isIdAllowed) {
				return false;
			}

		}
		
		if ($attrName == "class" ) {
			foreach (self::$allowClasses as $allow_class) {
				if (preg_match ("/{$allow_class}/isU", $attrValue, $arr)) {
					$isClassAllowed = true;
				}
			}

			if (!$isClassAllowed) {
				return false;
			}
		}
		
		if($nodeName == "param" || $nodeName == "embed") {
			if (strtolower($attrValue) == "captioningid" || $attrName == "captioningid"){
				return false;
			}

			if ($nodeName == "param" && $attrName == 'name' &&  strtolower($attrValue) == 'allowscriptaccess') {
				$attrValue = 'AllowScriptAccess_old';
			}
		}

		if (in_array('script', self::$forbiddenTags)) {
			if (substr($attrName, 0, 2) == 'on') {
				return false;
			} else if (in_array($attrName, array('src', 'href', 'codebase', 'dynsrc', 'content', 'datasrc', 'data')) && preg_match("/^(javascript|mocha|livescript|vbscript|about|view-source):/i", $attrValue) ) {
				// 判断属性中是否含有js
				return false;
			} else if (strpos(strtolower(trim($attrValue)),'javascript:') !== false 
					|| strpos(strtolower(trim($attrValue)),'vbscript:') !== false
					|| strpos(strtolower(trim($attrValue)),'expression') !== false) {
				return false ;//过滤js注入
			} else if (strtolower(trim($attrName))=="style") {
				$attrValue2 = self::_unEscapeUnicode($attrValue);
				$attrValue2 = unhtmlentities2($attrValue2); 
				$attrValue2 = preg_replace("|\/\*(.*)\*\/|sU", "", $attrValue2);//过滤注释
				$attrValue2 = stripslashes($attrValue2);  
				$reg_data  = array( iconv("GBK", "UTF-8", "ｅ"),
									iconv("GBK", "UTF-8", "ｘ"),
									iconv("GBK", "UTF-8", "ｐ"),
									iconv("GBK", "UTF-8", "ｒ"),
//									iconv("GBK", "UTF-8", "ｅ"),
									iconv("GBK", "UTF-8", "ｓ"),
									iconv("GBK", "UTF-8", "ｉ"),
									iconv("GBK", "UTF-8", "ｏ"),
									iconv("GBK", "UTF-8", "ｎ")
							);
				$rep_data = array("e","x","p","r","s","i","o","n");
				$attrValue2 = str_replace($reg_data , $rep_data , $attrValue2);

				if ( strpos(strtolower(trim($attrValue2)), 'javascript:') !== false ) {
					 return false ;
				}
				if ( strpos(strtolower(trim($attrValue2)), 'expression') !==false ) {
					 return false ;
				}

				$attrValue = str_replace($reg_data, $rep_data, $attrValue);

				$reg_data = array("E","X","P","R","S","I","O","N");
				$rep_data = array("e","x","p","r","s","i","o","n");
				$attrValue = str_replace($reg_data, $rep_data, $attrValue);

				$attrValue = str_replace('expression', 'expression_x', $attrValue);
				$attrValue = str_replace('eval', '', $attrValue);
			}
		}

		return true;
	}

	private static function _filterNode($node, &$output){

		//查看节点名，如果是<script> 和<style>就直接清除
		if (in_array($node->name, self::$forbiddenTags)) {
			return '';
		}

		if ($node->type == TIDY_NODETYPE_TEXT){
			/*
			 如果该节点内是文字
			*/
			$output .= $node->value;
			return;
		}

		//不是文字节点，那么处理标签和它的属性
		$output .= '<'.$node->name;

		//检查每个属性
		if ($node->attribute) {
			foreach ($node->attribute as $name=>$value) {
				/*
				 清理一些DOM事件，通常是on开头的，
				 比如onclick onmouseover等....
				 或者属性值有javascript:字样的，
				 比如href="javascript:"的也被清除.
				 */
				if (self::_filterAttr($node->name, $name, $value)) {
					$output .= ' '.$name.'="'.htmlspecialchars($value).'"';
				}
			}
		}

		if ($node->type == TIDY_NODETYPE_START) {
			$output .= '>';
			//递归检查该节点下的子节点
			if ($node->hasChildren()){
				foreach ($node->child as $child) {
					self::_filterNode($child, $output);
				}
			}

			if ('object' == $node->name) {
				$output .= '<embed allowscriptaccess="never"></embed>';
			}

			//闭合标签
			$output .= '</'.$node->name.'>';
		} else {
			//对单体标签，比如<hr/> <br/> <img/>等直接以 />闭合
			$output .= '/>';
		}
	}

	/**
	 * 临时对Unicode编码的字符进行自定义转义，去掉起始的&符号，方便对其他的&符号做全局转义
	 *
	 * @param string $str
	 * @return string
	 */
	private static function _escapeUnicode($str) {
		$str = preg_replace("/&(#?[0-9a-zA-Z]{2,7});/", "__".md5('word_left')."_\\1_".md5('word_right')."__", $str);
		return $str;
	}
	/**
	 * 对临时自定义转义了的字符进行反转义
	 *
	 * @param string $str
	 * @return string
	 */
	private static function _unEscapeUnicode($str) {
		$str = preg_replace("/__".md5('word_left')."_(#?[0-9a-zA-Z]{2,7})_".md5('word_right')."__/U", "&\\1;", $str);
		return $str;
	}

	/**
	 * 返回提示信息
	 * @param $no String 提示信息号
	 * @param $msg String 提示信息
	 * @return Void
	 */
	public function retMsg($no, $msg) {
		$ret = array(
			'no' => $no,
			'msg' => $msg,
		);
		throw new Exception(Common::t(json_encode($ret)));	
		//throw new Exception(json_encode($ret));
	}
}

function unhtmlentities2_preg_callback($a) {
	return chr($a[1]);
}

function unhtmlentities2($string) {
	// replace numeric entities
	$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
	$string = preg_replace('~\\\\([0-9a-f]{2,})~ei', 'chr(hexdec("\\1"))', $string);
	$string = urldecode($string);
	$string = preg_replace_callback('~&#([0-9]+);?~', 'unhtmlentities2_preg_callback', $string);
	$string = str_replace(' ' , '' , $string);
	// replace literal entities
	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
	return strtr($string, $trans_tbl);
}

