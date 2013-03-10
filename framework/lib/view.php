<?php
/*
 * @description: 视图层
 * @author: zhouweiwei
 * @date: 2010-5-28
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');
class Lib_View extends Lib_Base {
	private static $_instance;
	private $view;

	public static function getInstance() {
		if(!self::$_instance instanceof self) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	public function __construct() {
		parent::__construct();
		Common::loadPlugin('smarty');
        $this->view = new Smarty();

		if($this->config['smarty']) {
			foreach($this->config['smarty'] as $key=>$value) {
				$this->view->$key = $value;
			}
		}
    }

    public function assign($key, $value = '') {
		if(is_object($value)) {
			$this->view->assign($key, $value);
		} else {
			$this->view->assign_by_ref($key, $value);
		}
    }

    public function display($tpl = null, $cacheId = null) {
		$this->view->display($tpl, $cacheId);
    }

	public function fetch($tpl=null, $cacheId=null) {
        return $this->view->fetch($tpl, $cacheId);
	}

}
?>
