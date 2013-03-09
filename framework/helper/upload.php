<?php
/*
 * @description: 上传处理类
 * @author: zhouweiwei
 * @date: 2010-5-18
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');
defined('TIMESTAMP') || define('TIMESTAMP', time());
defined('DS') || define('DS', DIRECTORY_SEPARATOR);
class Helper_Upload {
	const TIME_PATH_FORMAT = 1;
	const HASH_PATH_FORMAT = 2;
	public static $_instance;

	private $uploadInput = 'uploadFile';
	private $maxFileSize = 100000;
	private $checkSriptSafe = true;

    private $allowFileExt = array('gif', 'jpeg', 'jpg', 'jpe', 'png', 'rar', 'txt');
    private $imgExt = array('gif', 'jpeg', 'jpg', 'jpe', 'png');

	private $isRename = true;
	private $isSubPath = true;
	private $subPathFormat = 0; //目前支持两种形式，0 相对路径 1日期，2随机
	private $saveName = null;
	private $retFile = null; //是否返回文件名

	public function __construct() {

	}

	public static function getInstance() {
		if(!self::$_instance instanceof self) {
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	public function setAllowFileExt($allowFileExt) {
		$this->allowFileExt = $allowFileExt;
	}

	public function getAllowFileExt() {
		return $this->allowFileExt;
	}

	public function setSaveName($name) {
		return $this->saveName = $name;
	}

	public function getSaveName() {
		return $this->saveName;
	}

	public function setIsRename($isRename) {
		$this->isRename = $isRename;
	}

	public function getIsRename() {
		return $this->isRename;
	}

	public function setIsSubPath($isSubPath) {
		$this->isSubPath = $isSubPath;
	}

	public function getIsSubPath() {
		return $this->isSubPath;
	}

	public function setSubPathFormat($subPathFormat) {
		$this->subPathFormat = $subPathFormat;
	}

	public function getSubPathFormat() {
		return $this->subPathFormat;
	}

	public function getMaxFileSize() {
		return $this->maxFileSize;
	}

	public function setMaxFileSize($maxFileSize) {
		$this->maxFileSize = $maxFileSize;
	}

	public function setRetFile() {
		$this->retFile = 1;
	}

	public function getRetFile() {
		return $this->retFile;
	}

	public function getUploadInput() {
		return $this->uploadInput;
	}

	public function setUploadInput($uploadInput) {
		$this->uploadInput = $uploadInput;
	}

	public function uploadFile($uploadInput, $savePath) {
		$this->checkUpload($uploadInput);
		$dest = $this->generateSaveFile($_FILES[$uploadInput]['name'], $savePath);
		$this->upload($_FILES[$uploadInput]['tmp_name'], $dest);
		if($this->getRetFile()) {
			return basename($dest);
		}
		return $dest;
	}

	private function upload($source, $dest) {
		if(!move_uploaded_file($source, $dest) && !copy($source, $dest)) {
			throw new Exception(Common::t("File upload failed!"));
		}
	}

	private function checkUpload($uploadInput) {
		if(!$this->isValid($uploadInput)) {
			throw new Exception(Common::t("File is not upload"));
		}
		$ext = $this->getFileExt($_FILES[$uploadInput]['name']);
		if($this->getAllowFileExt() && !in_array($ext, $this->getAllowFileExt())) {
			throw new Exception(Common::t("File ext is not allow!"));
		}
		if(filesize($_FILES[$uploadInput]['tmp_name']) > $this->getMaxFileSize()) {
			throw new Exception(Common::t("File is too big!"));
		}

		if($this->checkSriptSafe && preg_match("/\.(cgi|pl|js|asp|php|html|htm|jsp|jar)(\.|$)/i", $_FILES[$uploadInput]['name'])) {
			 $this->setIsRename(true);
		}

		if(in_array($ext, $this->imgExt)) {
			$this->checkImg($uploadInput);
		}
	}

	private function checkImg($uploadInput) {
		$attrs = @getimagesize($_FILES[$uploadInput]['tmp_name']);
		$ext = $this->getFileExt($_FILES[$uploadInput]['name']);
		if (!is_array($attrs) || !count($attrs) || !$attrs[2] || ($attrs[2] == 1 && ($ext == 'jpg' || $ext == 'jpeg'))) {
			throw new Exception(Common::t("Image is invliad!"));
		}
	}

	private function isValid($uploadInput) {
		return $_FILES[$uploadInput] && is_uploaded_file($_FILES[$uploadInput]['tmp_name']);
	}

	private function generateSaveFile($file, $path=null) {
		$path = $this->generateSavePath($file, $path);
		if($this->getSaveName()) {
			return $path.$this->getSaveName().'.'.$this->getFileExt($file);
		}
		if($this->getIsRename()) {
			return $path.substr(md5(uniqid(mt_rand(), true)), 8, 8).substr(md5($file), 4, 8).'.'.$this->getFileExt($file);
		}
		return $path.$file;
	}

	private function getFileExt($file) {
        return strtolower(str_replace(".", "", substr($file, strrpos($file, '.'))));
    }

	private function generateSavePath($file, $path = null) {
		is_null($path) && $path = '.'.DS;
		if($this->getIsSubPath()) {
			$path .= DS.$this->hashPath($file);
		}
		$this->reMkdir($path);
		if(substr($path, -1, 1) !== DS) $path .= DS;
		return $path;
	}

	private function hashPath($file) {
		if($this->getSubPathFormat() == self::TIME_PATH_FORMAT) {
			return date('Y', TIMESTAMP).DS.date('md', TIMESTAMP);
		} elseif($this->getSubPathFormat() == self::HASH_PATH_FORMAT) {
			$hash = md5($file);
			return substr($hash, 4, 2).DS.substr($hash, 16, 2);
		}
	}

	private function reMkdir($dir, $mode=0700) {
		is_dir(dirname($dir)) || $this->reMkdir(dirname($dir), $mode);
    	return is_dir($dir) || mkdir($dir, $mode);
	}
}


//测试
/*
if($_POST['submit']) {
	$uploadObj = Helper_Upload::getInstance();
	$uploadObj->setSubPathFormat(Helper_Upload::TIME_PATH_FORMAT);
	$uploadObj->setIsRename(1);
	$uploadObj->setIsSubPath(1);
	echo $uploadObj->uploadFile('uploadFile', dirname(__FILE__).DS.'upload');
	exit;
} else {
	echo '<form action="" method="post" enctype="multipart/form-data">';
	echo '	<input type="file" name="uploadFile" />';
	echo '	<input type="submit" name="submit" value="提交" />';
	echo '</form>';
}
*/
?>
