<?php
defined('IN_ROOT') || exit('Access Denied');

class Model_Index extends Lib_Model {
	public function __construct() {
		parent::__construct();
		$this->db = parent::getDb();
		$this->cache = parent::getCache();
	}

	public function getIdData($id) {
		$data = $this->db->getAll("SELECT * FROM test WHERE id=:id", array('id' => $id));
		return $data;
	}

	public function updateId($id) {
		$this->db->query("UPDATE test SET password=#password", array('password'=>'+2'));
	}
}
?>
