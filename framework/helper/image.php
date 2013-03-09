<?php
/*
 * @description: 图片类, 目前支持ImageMagick, GraphicsMagick处理库
 * @update: zhouweiwei
 * @create:2010-11-13
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');
defined('DIR_WRITE_MODE') || define('DIR_WRITE_MODE', 0777);
class Lib_Image {
	/**
	 * 提示信息数组
	 */
	private static $msg = array(
		'error_dirver_unsupport'		=> '该扩展不支持',
		'error_dirver_uninstall'		=> '该扩展未安装',
		'error_params_srcimage'			=> '参数错误，原图未指定',
		'error_params_getimageprops'	=> '方法getImageProps参数错误',
		'error_image_type_unsupport'	=> '不支持的该图片类型',
		'error_bin_path'				=> '命令路径有误',
		'error_imagemagick_failed'		=> 'ImageMagick 执行操作失败',
		'error_graphicsmagick_failed'	=> 'GraphicsMagick 执行操作失败',
	);

	private $dirver;
	private $doutput			= FALSE;	// Whether to send to browser or write to disk
	private $srcName;
	private $destName;
	private $srcPath;
	private $destPath;
	private $fullSrcPath;
	private $fullDestPath;

	private $origWidth;
	private $origHeight;
	private $imageType			= 'gif';
	private $sizeStr;
	private $mimeType;
	private $renameThumb		= false;
	private $thumbSuffix		= '_thumb';

	private $width				= '';		//处理后图片宽度
	private $height				= '';		//处理后图片宽度
	private $quality			= '90';
	private $maintainRatio		= TRUE;  	// Whether to maintain aspect ratio when resizing or use hard values
	private $masterDim			= 'auto';	// auto, height, or width.  Determines what to use as the master dimension
	private $rotationAngle		= '';
	private $xaxis				= '';
	private	$yaxis				= '';
	private $binPath			= '/usr/local/bin/';		//执行命令

	public function __construct($dirver, $srcImage, $destImage = null, $options = null) {
		if (!in_array($dirver, array('ImageMagick', 'GraphicsMagick'))) {
			$this->retMsg('error_dirver_unsupport', $this->msg['error_dirver_unsupport']);
		}
		$this->dirver = $dirver;

		if (empty($srcImage)) {
			$this->retMsg('error_params_srcimage', $this->msg['error_params_srcimage']);
		}

		$this->fullSrcPath = $this->getImageRealpath($srcImage);
		if (!preg_match("/\.(jpg|jpeg|gif|png)$/i", $this->fullSrcPath)) {
			$this->retMsg('error_image_ext_unsupport', $this->msg['error_image_ext_unsupport']);
		}
		$this->srcName = basename($this->fullSrcPath);
		$this->srcPath = str_replace($this->srcName, '', $this->fullSrcPath);
		$this->getImageProps($this->fullSrcPath, true);

		if($options && is_array($options)) {
			foreach($options as $key=>$value) {
				$this->$key = $value;
			}
		}
		if (empty($destImage)) {
			$this->destName = $this->srcName;
			$this->destPath = $this->srcPath;
		} else {
			if (strpos($destImage, '/') === false) {
				$this->destPath = $this->srcPath;
				$this->destName = $destImage;
			} else {
				$this->fullDestPath = $this->getImageRealpath($destImage);
				if (!preg_match("/\.(jpg|jpeg|gif|png)$/i", $this->fullDestPath)) {
					$this->destPath = $this->fullDestPath.'/';
					$this->destName = $this->srcName;
				} else {
					$this->destName = basename($this->fullDestPath);
					$this->destPath = str_replace($this->destName, '', $this->fullDestPath);
				}
			}
			$this->reMkdir($this->destPath);	
		}
		$files = array();
		$files = $this->explodeName($this->destName);
		if($this->renameThumb) {
			$tmpName = $this->thumbSuffix ? $this->thumbSuffix : '';
		}
		$this->fullDestPath = $this->destPath.$files['name'].$tmpName.$files['ext'];
		if (!preg_match("/\.(jpg|jpeg|gif|png)$/i", $this->fullDestPath)) {
			$this->retMsg('error_image_ext_unsupport', $this->msg['error_image_ext_unsupport']);
		}

		$this->width || $this->width = $this->origWidth;
		$this->height || $this->height = $this->origHeight;
		if ($this->maintainRatio) {
			$this->imageReproportion();
		}

		$this->quality = trim(str_replace("%", "", $this->quality));
		if ($this->quality == '' || $this->quality == 0 || ! is_numeric($this->quality)) {
			$this->quality = 90;
		}
	
		$this->xaxis = (!$this->xaxis || !is_numeric($this->xaxis)) ? 0 : $this->xaxis;
		$this->yaxis = (!$this->yaxis || !is_numeric($this->yaxis)) ? 0 : $this->yaxis;
	}

	public function __destruct() {
		
	}
	
	/**
	 * 缩放图片
	 * 
	 */
	public function resize() {
		$method = 'imageProcess'.$this->dirver;
		return $this->$method('resize');
	}

	/**
	 * 剪切图片
	 * 
	 */
	public function crop() {
		$method = 'imageProcess'.$this->dirver;
		return $this->$method('crop');
	}

	
	/**
	 * 旋转图片
	 * 
	 */
	public function rotate() {
		$method = 'imageProcess'.$this->dirver;
		return $this->$method('rotate');
	}
	
	/**
	 * ImageMagick 处理图片
	 * @param $action String 指定动作
	 * @return Bool
	 */
	public function imageProcessImageMagick($action = 'resize') {
		if (!in_array($action, array('crop', 'rotate', 'resize'))) {
			return false;
		}

		if ($this->binPath == '') {
			$this->retMsg('error_bin_path', $this->msg['error_bin_path']);
		}

		if (!preg_match("/convert$/i", $this->binPath)) {
			$this->binPath = rtrim($this->binPath, '/').'/';
			$this->binPath .= 'convert';
		}

		$cmd = $this->binPath." -quality ".$this->quality;

		if ($action == 'crop') {
			$cmd .= " -crop ".$this->width."x".$this->height."+".$this->xaxis."+".$this->yaxis." \"$this->fullSrcPath\" \"$this->fullDestPath\" 2>&1";
		} elseif ($action == 'rotate') {
			switch ($this->rotationAngle) {
				case 'hor' : 
					$angle = '-flop';
					break;
				case 'vrt' : 
					$angle = '-flip';
					break;
				default	: 
					$angle = '-rotate '.$this->rotationAngle;
					break;
			}
			$cmd .= " ".$angle." \"$this->fullSrcPath\" \"$this->fullDestPath\" 2>&1";
		} elseif ($action == 'resize') {
			$cmd .= " -resize ".$this->width."x".$this->height." \"$this->fullSrcPath\" \"$this->fullDestPath\" 2>&1";
		}
		$retval = 1;

		@exec($cmd, $output, $retval);

		if ($retval > 0) {
			$this->retMsg('error_imagemagick_failed', $this->msg['error_imagemagick_failed']);
		}

		@chmod($this->fullDestPath, DIR_WRITE_MODE);

		return true;
	}

	/**
	 * GraphicsMagick 处理图片
	 * @param $action String 指定动作
	 * @return Bool
	 */
	public function imageProcessGraphicsMagick($action = 'resize') {
		if (!in_array($action, array('crop', 'rotate', 'resize'))) {
			return false;
		}

		if ($this->binPath == '') {
			$this->retMsg('error_bin_path', $this->msg['error_bin_path']);
		}

		if (!preg_match("/convert$/i", $this->binPath)) {
			$this->binPath = rtrim($this->binPath, '/').'/';
			$this->binPath .= 'gm convert';
		}

		$cmd = $this->binPath." -quality ".$this->quality;

		if ($action == 'crop') {
			$cmd .= " -crop ".$this->width."x".$this->height."+".$this->xaxis."+".$this->yaxis." \"$this->fullSrcPath\" \"$this->fullDestPath\" 2>&1";
		} elseif ($action == 'rotate') {
			switch ($this->rotationAngle) {
				case 'hor' : 
					$angle = '-flop';
					break;
				case 'vrt' : 
					$angle = '-flip';
					break;
				default	: 
					$angle = '-rotate '.$this->rotationAngle;
					break;
			}
			$cmd .= " ".$angle." \"$this->fullSrcPath\" \"$this->fullDestPath\" 2>&1";
		} elseif ($action == 'resize') {
			$cmd .= " -resize ".$this->width."x".$this->height." \"$this->fullSrcPath\" \"$this->fullDestPath\" 2>&1";
		}

		$retval = 1;

		@exec($cmd, $output, $retval);

		if ($retval > 0) {
			$this->retMsg('error_graphicsmagick_failed', $this->msg['error_graphicsmagick_failed']);
		}

		@chmod($this->fullDestPath, DIR_WRITE_MODE);

		return true;
	}

	/**
	 * 等比例缩放
	 */
	private function imageReproportion() {
		if (!is_numeric($this->width) || !is_numeric($this->height) || $this->width == 0 || $this->height == 0)
			return;

		if (!is_numeric($this->origWidth) || ! is_numeric($this->origHeight) || $this->origWidth == 0 || $this->origHeight == 0)
			return;

		$newWidth	= ceil($this->origWidth*$this->height/$this->origHeight);
		$newHeight	= ceil($this->origHeight*$this->width/$this->origWidth);

		$ratio = (($this->origHeight/$this->origWidth) - ($this->height/$this->width));

		if ($this->masterDim != 'width' && $this->masterDim != 'height') {
			$this->masterDim = ($ratio < 0) ? 'width' : 'height';
		}

		if (($this->width != $newWidth) && ($this->height != $newHeight)) {
			if ($this->masterDim == 'height') {
				$this->width = $newWidth;
			} else {
				$this->height = $newHeight;
			}
		}
	}

	/**
	 * 获得图片绝对地址
	 * @param $imagePath String 图片地址相对或绝对
	 * @param String 
	 */
	private function getImageRealpath($imagePath) {
		if(realpath($imagePath) !== false) {
			return str_replace("\\", "/", realpath($imagePath));
		} 
		return $imagePath;	
	}
	
	/**
	 * 获得图片属性
	 * @param $path String 图片路径
	 * @param $return Bool 是否返回值
	 * @return Mixed
	 */
	private function getImageProps($path, $return = false) {
		if (empty($path)) {
			$this->retMsg('error_params_getimageprops', $this->msg['error_params_getimageprops']);
		}

		$vals = @getimagesize($path);
		$types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');
		$mime = (isset($types[$vals['2']])) ? 'image/'.$types[$vals['2']] : 'image/jpg';

		if ($return == false) {
			$v['width']			= $vals['0'];
			$v['height']		= $vals['1'];
			$v['image_type']	= $vals['2'];
			$v['size_str']		= $vals['3'];
			$v['mime_type']		= $mime;
			return $v;
		}

		$this->origWidth	= $vals['0'];
		$this->origHeight	= $vals['1'];
		$this->imageType	= $vals['2'];
		$this->sizeStr		= $vals['3'];
		$this->mimeType		= $mime;

		return true;
	}
	
	/**
	 * 创建多级目录
	 * @param $dir String 目录路径
	 * @param $mode Int 目录读写权限
	 * @return Bool 
	 */
	private function reMkdir($dir, $mode = 0777) {
		is_dir(dirname($dir)) || $this->reMkdir(dirname($dir), $mode);
    	return is_dir($dir) || mkdir($dir, $mode);
	}
	
	/**
	 * 获得文件名及后缀
	 * @param $file String 文件名
	 * @return Array 
	 */
	private function explodeName($file) {
		$ext = strrchr($file, '.');
		$name = ($ext === FALSE) ? $file : substr($file, 0, -strlen($ext));
		return array('ext' => $ext, 'name' => $name);
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

/*
$img = new Lib_Image('ImageMagick', '1.jpg', mt_rand(1, 10000).'.jpg');
$img->resize();

$img = new Lib_Image("GraphicsMagick", '1.jpg', mt_rand(1, 10000).'.jpg');
$img->resize();
*/