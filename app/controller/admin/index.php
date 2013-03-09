<?php
defined('IN_ROOT') || exit('Access Denied');

class IndexController extends Lib_Controller {

    public function lsAction() {
		$this->redirect('?c=index&a=login');
    }

	/**
	 * 登陆验证
	 *
	 */
	public function loginAction() {
		if($this->checkSubmit()) { //提交后
			$post = array(
				'username' => Helper_Request::getParam('username'),
				'password' => Helper_Request::getParam('password'),
			);

			$isValid = Helper_Validation::validate($post,
							array('username'=>
									array(
										'required' => true,
										),
								   'password' =>
									array(
										'required' => true,
										'max'	   => 16,
										'min'	   => 3,
									),
							)
						);
			if(!$isValid) {
				$errMsg = Helper_Validation::error();
				if($errMsg['username'] === 'empty') {
					$this->formatData(array('no'=>'error_empty', 'msg'=>'用户名不能为空'));
				} elseif($errMsg['password'] === 'empty') {
					$this->formatData(array('no'=>'error_empty', 'msg'=>'密码不能为空'));
				} elseif($errMsg['password'] === 'above_max') {
					$this->formatData(array('no'=>'error_above_max', 'msg'=>'密码不能超过16个字'));
				} elseif($errMsg['password'] === 'below_min') {
					$this->formatData(array('no'=>'error_below_min', 'msg'=>'密码不能短于3个字'));
				}
			}

			if(Common::getModel('DB_AdminUser')->checkLogin($post['username'], $post['password'])) {
				$this->formatData(array('no'=>'success',
					'msg'=>array('url'=>Helper_Request::getParam('url'), 'isclose'=>Helper_Request::getParam('isclose'))));
			}
			$this->formatData(array('no'=>'error_failed', 'msg'=>'登陆失败'));
		} else {
			$this->bindView();
		}
	}

	/**
	 * 列出管理
	 *
	 */
	 public function listadminAction() {

		$this->bindView();
	 }
}

?>
