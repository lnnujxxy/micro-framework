<?php
/*
 * @description: 控制层
 * @author: zhouweiwei
 * @date: 2010-5-13
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');

class Lib_Controller extends Lib_Base {
	public function __construct() {
		parent::__construct();
        Helper_Input::filterVar();
	}

	public function redirect($url, $message = '') {
		if (headers_sent()) {
			return $this->showMessage($message, $url);
		}
		header("Location: $url");
		exit;
	}

	public function showMessage($message, $url = '-1', $limitTime = 3) {
		$view = Lib_View::getInstance();
		if(empty($message)) {
			return false;
		}
		if($url == '-1') {
			$limitTime = empty($limitTime) ? 5000 : 1000*$limitTime;
			$url = "javascript:history.go(-1);";
			$message .= "<br/>\n<a href=\"javascript:history.go(-1);\" target=\"_self\">如果你的浏览没反应,请点击这里...</a>\n";
		} else {
			$limitTime = empty($limitTime) ? 1000 : 1000*$limitTime;
			$url = str_replace(array("\n","\r"), '', $url);
			$message .= "<br/>\n<a href=\"{$url}\" target=\"_self\">如果你的浏览没反应,请点击这里...</a>\n";
		}
		$message .= "<script type=\"text/javascript\">\nfunction redirect_url(url){location.href=url;}setTimeout(\"redirect_url('{$url}')\", {$limitTime});\n</script>\n";
		$view->assign('message', $message);
		$view->display('message.html');
		exit;
	}

	/**
	 * 格式化返回数据
	 * @param $data Array
	 * @param $type String|null
	 * @param $url String
	 * @param $limitTime Int
	 */
	public function formatData($data, $type='json', $url = '-1', $limitTime = 3) {
		if(empty($type)) {
			$this->showMessage(isset($data['msg']) ? $data['msg']: $data['no'], $url, $limitTime);
		} elseif($type == 'json') {
			echo json_encode($data);
			exit(0);
		}
	}

	/**
	 * 提交表单来源检查
	 * @param $domain String 应用域名
	 * @return Bool
	 */
	public function checkSubmit($domain = null) {
		if(empty($domain)) {
			$domain = $this->config['site']['domain'];
		}
		return $_SERVER['REQUEST_METHOD'] == 'POST' && (empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], $domain) || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST']));
	}

	/**
	 * 绑定模板数据
	 * @param $data String 需要赋值二维数组 eg: array(array('str'=>'aaa'), array('str1'=>'bbb'))
	 * @param $className String Lib_Controller名
	 * @param $funcName String Action名
	 */
	public function bindView($data=null, $return=false) {
		$view = Lib_View::getInstance();
		if($data) {
			foreach($data as $item) {
				$view->assign(key($item), current($item));
			}
		}
		if($return) {
			return $view->fetch($this->template());
		}
		$view->display($this->template());
	}

	public function template() {
		$controllerName = empty($_GET[$this->config['mvc']['controller']]) ?  $this->config['mvc']['defaultController']: $_GET[$this->config['mvc']['controller']];
		$actionName = empty($_GET[$this->config['mvc']['action']]) ? $this->config['mvc']['defaultAction'] : $_GET[$this->config['mvc']['action']];

		$template =  strtolower($controllerName.'_'.$actionName).'.html';
		return $template;
	}

	/**
	 * 输出内容,在此可以改写支持APC,Xcache,Memcache存储
	 *
	 * @param $data Array 绑定数据
	 * @param $cacheid Mixed
	 * @return String
	 */
	public function output($data, $cacheid = null) {
		$this->bindView($data);
	}
}
?>
