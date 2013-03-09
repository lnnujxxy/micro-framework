<?php
/*
 * @description: MVC逻辑类
 * @author: zhouweiwei
 * @date: 2010-5-13
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');
class Lib_Mvc extends Lib_Base {
	private $defaultController;
	private $defaultAction;
	private $param = array ();

	public static $_instance = NULL;

	public function __construct() {
		parent::__construct();
		$this->setDefaultController($this->config['mvc']['defaultController']);
		$this->setDefaultAction($this->config['mvc']['defaultAction']);
	}

	public static function getInstance(){
		if (!(self::$_instance instanceof self)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function dispatch($path = '') {
		$this->parsePath();

		$controller = $this->getController($path);
		$action = $this->getAction();
		$app = new $controller();

		if (method_exists($app, $action)) {
			$app->$action ();
		} else {
			throw new Exception(Common::t("controller:'".$controller." Unfound Action:{$action}"));
		}
	}

	private function parsePath() {
		$this->param ['Baseurl'] = str_replace($this->config['mvc']['accessFile'], '', $_SERVER ['SCRIPT_NAME']);
		if (empty ($_SERVER ['PATH_INFO'])) {
			$_SERVER ['PATH_INFO'] = str_replace ( $this->param ['Baseurl'], '', $_SERVER ['REQUEST_URI'] );
		}
		if($this->config['mvc']['rewrite']) {
			$this->parseRewrite($_SERVER ['PATH_INFO']);
		}
		$this->param['Controller']= empty($_GET[$this->config['mvc']['controller']]) ? $this->defaultController : ucwords($_GET [$this->config['mvc']['controller']]);
		$this->param['Action'] = empty($_GET[$this->config['mvc']['action']]) ? $this->defaultAction : ucwords($_GET [$this->config['mvc']['action']]);

		if(!Common::verifyValidString($this->param['Controller']) || !preg_match('/^[A-Za-z_0-9]*$/', $this->param['Action'])) {
			exit('传递非法参数，请重新操作！');
		}
	}

	private function removeUrlSuffix($uri) {
		if ($this->config['mvc']['urlsuffix'] != "") {
			return preg_replace("|".preg_quote($this->config['mvc']['urlsuffix'])."$|", "", $uri);
		}
		return $uri;
	}

	private function parseRewrite($pathInfo) {
		if($pathInfo === '') return;
		$pathInfo = $this->removeUrlSuffix($pathInfo);
		$segs = explode('/', rtrim($pathInfo, '/'));

		$_REQUEST[$this->config['mvc']['controller']] = $_GET[$this->config['mvc']['controller']] = $segs[1];
		$_REQUEST[$this->config['mvc']['action']] = $_GET[$this->config['mvc']['action']] = $segs[2];

		$n = count($segs);
		for($i = 3; $i < $n; $i += 2) {
			$key = $segs[$i];
			if($key === '') continue;
			$value = $segs[$i+1];
			if(($pos = strpos($key, '[')) !== false && ($pos2 = strpos($key, ']', $pos+1)) !== false) {
				$name = substr($key, 0, $pos);
				if($pos2 === $pos+1) {
					$_REQUEST[$name][] = $_GET[$name][] = $value;
				} else {
					$key = substr($key, $pos+1, $pos2-$pos-1);
					$_REQUEST[$name][$key] = $_GET[$name][$key] = $value;
				}
			} else {
				$_REQUEST[$key] = $_GET[$key] = $value;
			}
		}
	}

	private function getController($path) {
		$controllerName = strtolower($this->param ['Controller']);
		$controller = ucwords($controllerName) . 'Controller';
		$path = empty($path) ? 'default' : $path;

        $controllerFile = APP_DIR.'controller/' . $path .'/'. $controllerName . '.php';

		if (!Common::requireFile( $controllerFile )) {
			throw new Exception(Common::t("Class:'".__CLASS__."'Method:".__METHOD__." Unfound Controller:{$controller}"));
		}

		return $controller;
	}

	private function getAction() {
		return strtolower($this->param ['Action']) . 'Action';
	}

	private function setDefaultController($controller) {
		$this->defaultController = $controller;
	}

	private function setDefaultAction($action) {
		$this->defaultAction = $action;
	}
}
?>