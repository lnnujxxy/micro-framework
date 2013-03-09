<?php
defined('IN_ROOT') || exit('Access Denied');

class IndexController extends Lib_Controller {

    public function lsAction() {
		$page = Helper_Request::getParam('page');
		$data[] = array('str' => array('11111111111', '111111111111111'));
		$data[] = array('str1' => '                 <script>alert("            第一个测试用例1")</script>');

		$this->output($data, $page);
    }

	public function abAction() {
		var_dump($_GET);
		echo "Hello world!";
	}

	public function testAction() {
		$m = new DB_Test;
		$view[] = array('test' => $m->getIdData(1));
		$this->bindView($view);
	}

	public function logAction() {
		Common::log("log1");
		Common::log("log2")->process();
	}

	public function updateAction() {
		$m = new Test;
		$m->updateId(1);
	}

	public function smartyAction() {
		require_once "HTML/QuickForm.php";

      $form = new HTML_QuickForm('frmTest', 'get');
      $form->addElement('header', 'MyHeader', 'Testing QuickForm');
      $form->addElement('text', 'MyTextBox', 'What is your name?');

      $buttons[] = &HTML_QuickForm::createElement('reset', 'btnClear', 'Clear');
      $buttons[] = &HTML_QuickForm::createElement('submit', 'btnSubmit', 'Submit');
      $form->addGroup($buttons, null, null, '&nbsp;');

      $form->addRule('MyTextBox', 'Your name is required', 'required');

      if ($form->validate()) {
          # If the form validates then freeze the data
          $form->freeze();
      }
      $form->display();



	}
}

?>
