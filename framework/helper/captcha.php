<?php
/*
 * @description: 验证码功能
 * @author: heiyeluren
 * @update: zhouweiwei
 * @date: 2010-05-18
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');
defined('TIMESTAMP') || define('TIMESTAMP', time());
class Helper_Captcha {
	private static $captcha_key = 'captcha_key';
	private static $ttl_key = 'captcha_ttl';
	private static $ttl = 600;

	public static function generateHash($length=4) {
		return substr(md5(uniqid(mt_rand())), 8, $length);
	}

	public static function generateCaptcha($imgX=80, $imgY=25) {
		isset($_SESSION) || session_start();
		$authCode = self::generateHash();
		$_SESSION[self::$captcha_key] = $authCode;
		$_SESSION[self::$ttl_key] = TIMESTAMP + self::$ttl;

		$randStr = preg_split('//', $authCode, -1, PREG_SPLIT_NO_EMPTY);

		$size = 20;
		$width = $imgX;
		$height = $imgY;
		$degrees = array(rand(0, 45), rand(0, 45), rand(0, 45), rand(0, 45)); // 生成数字旋转角度

		for($i = 0; $i < 4; ++$i) {
			if(rand() % 2);
			else $degrees[$i] = -$degrees[$i];
		}

		$image = imagecreatetruecolor($size, $size);   // 数字图片画布
		$validate = imagecreatetruecolor($width, $height);  // 最终验证码画布
		$back = imagecolorallocate($image, 255, 255, 255);  // 背景色
		$border = imagecolorallocate($image, 0, 0, 0);    // 边框
		imagefilledrectangle($validate, 0, 0, $width, $height, $back); // 画出背景色

		// 数字颜色
		for($i = 0; $i < 4; ++$i)
		{
		 // 考虑为使字符容易看清使用颜色较暗的颜色
		 $temp = self::rgb2hsv(rand(0, 255), rand(0, 255), rand(0, 255));

		 if($temp[2] > 60)
		  $temp [2] = 60;

		 $temp = self::hsv2rgb($temp[0], $temp[1], $temp[2]);
		 $textcolor[$i] = imagecolorallocate($image, $temp[0], $temp[1], $temp[2]);
		}

		for($i = 0; $i < 200; ++$i) //加入干扰象素
		{
		 $randpixelcolor = ImageColorallocate($validate, rand(0, 255), rand(0, 255), rand(0, 255));
		 imagesetpixel($validate, rand(1, 87), rand(1, 27), $randpixelcolor);
		}

		// 干扰线使用颜色较明亮的颜色
		$temp = self::rgb2hsv(rand(0, 255), rand(0, 255), rand(0, 255));

		if($temp[2] < 200)
		 $temp [2] = 255;

		$temp = self::hsv2rgb($temp[0], $temp[1], $temp[2]);
		$randlinecolor = imagecolorallocate($image, $temp[0], $temp[1], $temp[2]);

		// 画5条干扰线
		for ($i = 0;$i < 5; $i ++)
			imageline($validate, rand(1, 79), rand(1, 24), rand(1, 79), rand(1, 24), $randpixelcolor);

		imagefilledrectangle($image, 0, 0, $size, $size, $back); // 画出背景色
		imagestring($image, 5, 6, 2, $randStr[0], $textcolor[0]);  // 画出数字
		$image = imagerotate($image, $degrees[0], $back);
		imagecolortransparent($image, $back);
		imagecopymerge($validate, $image, 1, 4, 4, 5, imagesx($image) - 10, imagesy($image) - 10, 100);

		$image = imagecreatetruecolor($size, $size); // 刷新画板
		imagefilledrectangle($image, 0, 0, $size, $size, $back);  // 画出背景色
		imagestring($image, 5, 6, 2, $randStr[1], $textcolor[1]);  // 画出数字
		$image = imagerotate($image, $degrees[1], $back);
		imagecolortransparent($image, $back);
		imagecopymerge($validate, $image, 21, 4, 4, 5, imagesx($image) - 10, imagesy($image) - 10, 100);

		$image = imagecreatetruecolor($size, $size); // 刷新画板
		imagefilledrectangle($image, 0, 0, $size - 1, $size - 1, $back);  // 画出背景色
		imagestring($image, 5, 6, 2, $randStr[2], $textcolor[2]);  // 画出数字
		$image = imagerotate($image, $degrees[2], $back);
		imagecolortransparent($image, $back);
		imagecopymerge($validate, $image, 41, 4, 4, 5, imagesx($image) - 10, imagesy($image) - 10, 100);

		$image = imagecreatetruecolor($size, $size); // 刷新画板
		imagefilledrectangle($image, 0, 0, $size - 1, $size - 1, $back);  // 画出背景色
		imagestring($image, 5, 6, 2, $randStr[3], $textcolor[3]);  // 画出数字
		$image = imagerotate($image, $degrees[3], $back);
		imagecolortransparent($image, $back);
		imagecopymerge($validate, $image, 61, 4, 4, 5, imagesx($image) - 10, imagesy($image) - 10, 100);

		imagerectangle($validate, 0, 0, $width - 1, $height - 1, $border);  // 画出边框

		self::clearHttpCache();
		header('Content-type: image/png');
		imagepng($validate);
		imagedestroy($validate);
		imagedestroy($image);
	}

	private static function clearHttpCache() {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
	}


	/**
	 * RGB颜色值转换为HSV
	 *
	 * @param int $R
	 * @param int $G
	 * @param int $B
	 * @return array
	 */
	public static function rgb2hsv($R, $G, $B)
	{
	 $tmp = min($R, $G);
	  $min = min($tmp, $B);
	  $tmp = max($R, $G);
	  $max = max($tmp, $B);
	  $V = $max;
	  $delta = $max - $min;

	  if($max != 0)
	   $S = $delta / $max; // s
	  else
	  {
	   $S = 0;
	    //$H = UNDEFINEDCOLOR;
	    return;
	  }
	  if($R == $max)
	   $H = ($G - $B) / $delta; // between yellow & magenta
	  else if($G == $max)
	    $H = 2 + ($B - $R) / $delta; // between cyan & yellow
	  else
	    $H = 4 + ($R - $G) / $delta; // between magenta & cyan

	  $H *= 60; // degrees
	  if($H < 0)
	   $H += 360;
	  return array($H, $S, $V);
	}

	/**
	 * HSV颜色值转换为RGB
	 *
	 * @param int $H
	 * @param int $S
	 * @param int $V
	 * @return array
	 */
	public static function hsv2rgb($H, $S, $V)
	{
	 if($S == 0)
	  {
	   // achromatic (grey)
	   $R = $G = $B = $V;
	    return;
	  }

	  $H /= 60;  // sector 0 to 5
	  $i = floor($H);
	  $f = $H - $i;  // factorial part of h
	  $p = $V * (1 - $S);
	  $q = $V * (1 - $S * $f);
	  $t = $V * (1 - $S * (1 - $f));

	  switch($i)
	  {
	   case 0:
	     $R = $V;
	      $G = $t;
	      $B = $p;
	      break;
	    case 1:
	      $R = $q;
	      $G = $V;
	      $B = $p;
	      break;
	    case 2:
	      $R = $p;
	      $G = $V;
	      $B = $t;
	      break;
	    case 3:
	      $R = $p;
	      $G = $q;
	      $B = $V;
	      break;
	    case 4:
	      $R = $t;
	      $G = $p;
	      $B = $V;
	      break;
	    default: // case 5:
	      $R = $V;
	      $G = $p;
	      $B = $q;
	      break;
	 }
	  return array($R, $G, $B);
	}

	public static function generateCnHash($len = 2) {
		$s = array('的','一','是','在','不','了','有','和','人','这','中','大','为','上','个','国','我','以','要','他','时','来','用','们','生','到','作','地','于','出','就','分','对','成','会','可','主','发','年','动','同','工','也','能','下','过','子','说','产','种','面','而','方','后','多','定','行','学','法','所','民','得','经','十','三','之','进','着','等','部','度','家','电','力','里','如','水','化','高','自','二','理','起','小','物','现','实','加','量','都','两','体','制','机','当','使','点','从','业','本','去','把','性','好','应','开','它','合','还','因','由','其','些','然','前','外','天','政','四','日','那','社','义','事','平','形','相','全','表','间','样','与','关','各','重','新','线','内','数','正','心','反','你','明','看','原','又','么','利','比','或','但','质','气','第','向','道','命','此','变','条','只','没','结','解','问','意','建','月','公','无','系','军','很','情','者','最','立','代','想','已','通','并','提','直','题','党','程','展','五','果','料','象','员','革','位','入','常','文','总','次','品','式','活','设','及','管','特','件','长','求','老','头','基','资','边','流','路','级','少','图','山','统','接','知','较','将','组','见','计','别','她','手','角','期','根','论','运','农','指','几','九','区','强','放','决','西','被','干','做','必','战','先','回','则','任','取','据','处','队','南','给','色','光','门','即','保','治','北','造','百','规','热','领','七','海','口','东','导','器','压','志','世','金','增','争','济','阶','油','思','术','极','交','受','联','什','认','六','共','权','收','证','改','清','己','美','再','采','转','更','单','风','切','打','白','教','速','花','带','安','场','身','车','例','真','务','具','万','每','目','至','达','走','积','示','议','声','报','斗','完','类','八','离','华','名','确','才','科','张','信','马','节','话','米','整','空','元','况','今','集','温','传','土','许','步','群','广','石','记','需','段','研','界','拉','林','律','叫','且','究','观','越','织','装','影','算','低','持','音','众','书','布','复','容','儿','须','际','商','非','验','连','断','深','难','近','矿','千','周','委','素','技','备','半','办','青','省','列','习','响','约','支','般','史','感','劳','便','团','往','酸','历','市','克','何','除','消','构','府','称','太','准','精','值','号','率','族','维','划','选','标','写','存','候','毛','亲','快','效','斯','院','查','江','型','眼','王','按','格','养','易','置','派','层','片','始','却','专','状','育','厂','京','识','适','属','圆','包','火','住','调','满','县','局','照','参','红','细','引','听','该','铁','价','严');
		$ks = array_rand($s, $len);
		$ret = '';
		for ($i=0; $i<$len; $i++){
			$ret .= $s[$ks[$i]];
		}
		return $ret;
	}


	public static function generateCnCaptcha($imgX=60, $imgY=20, $font = '') {
		isset($_SESSION) || session_start();
		$authCode = self::generateCnHash();

		$_SESSION[self::$captcha_key] = $authCode;
		$_SESSION[self::$ttl_key] = TIMESTAMP + self::$ttl;
		//下载simkai.ttf到指定目录
		$font = $font != '' ? $font : dirname(__FILE__).'/simkai.ttf';
		if (!is_file($font) || !is_readable($font)) {
			throw new Exception(__CLASS__ ." font file $font not exist or not readable");
		}

		$im = imagecreate($imgX,$imgY);
		$bkg = imagecolorallocate($im, 90, 90, 123);
		$clr = imagecolorallocate($im, 255, 255, 255);

		//绘制背景和干扰线
		$temp = self::rgb2hsv(rand(0, 255), rand(0, 255), rand(0, 255));
		if($temp[2] < 200) $temp [2] = 255;
		$temp = self::hsv2rgb($temp[0], $temp[1], $temp[2]);
		$white=imagecolorallocate($im, $temp[0], $temp[1], $temp[2]);

		imagearc($im, rand(0,$imgX/2), rand(0, $imgY/2), $imgX/2, $imgY/2, 0, 360, $white);
		// 画5条干扰线
		for ($i = 0;$i < 3; $i++)
			imageline($im, rand(0, $imgX), rand(0, $imgY), rand(0, $imgX), rand(0, $imgY), $white);

		//填充文字后输出
		header("content-type: image/png");
		self::clearHttpCache();
		imagettftext($im, 14, 10, 10, 20, $clr, $font, $authCode); //写ttf文字到图中
		imagepng($im);
		imagedestroy($im);
	}

	public static function checkCaptcha($value) {
		if($_SESSION[self::$ttl_key] < TIMESTAMP) {
			return false;
		}
		return $_SESSION['captcha'] && strcasecmp($_SESSION['captcha'], $value);
	}
}

//Captcha::generateCaptcha();