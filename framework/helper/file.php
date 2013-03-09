<?php
/*
 * 文件类
 *
 * @package: Helper
 * @author: zhouweiwei
 * @date: 2010-9-19
 * @version: 1.0
 */
//defined('IN_ROOT') || exit('Access Denied');

class Helper_File {

	public static function getExt($file) {
		return substr(strrchr($file, "."), 1);
	}

	public static function getMineType($file, $magicFile=null) {
		static $mineType = array(
			'ai'=>'application/postscript',
			'aif'=>'audio/x-aiff',
			'aifc'=>'audio/x-aiff',
			'aiff'=>'audio/x-aiff',
			'asc'=>'text/plain',
			'au'=>'audio/basic',
			'avi'=>'video/x-msvideo',
			'bcpio'=>'application/x-bcpio',
			'bin'=>'application/octet-stream',
			'bmp'=>'image/bmp',
			'c'=>'text/plain',
			'cc'=>'text/plain',
			'ccad'=>'application/clariscad',
			'cdf'=>'application/x-netcdf',
			'class'=>'application/octet-stream',
			'cpio'=>'application/x-cpio',
			'cpt'=>'application/mac-compactpro',
			'csh'=>'application/x-csh',
			'css'=>'text/css',
			'dcr'=>'application/x-director',
			'dir'=>'application/x-director',
			'dms'=>'application/octet-stream',
			'doc'=>'application/msword',
			'drw'=>'application/drafting',
			'dvi'=>'application/x-dvi',
			'dwg'=>'application/acad',
			'dxf'=>'application/dxf',
			'dxr'=>'application/x-director',
			'eps'=>'application/postscript',
			'etx'=>'text/x-setext',
			'exe'=>'application/octet-stream',
			'ez'=>'application/andrew-inset',
			'f'=>'text/plain',
			'f90'=>'text/plain',
			'fli'=>'video/x-fli',
			'flv'=>'video/x-flv',
			'gif'=>'image/gif',
			'gtar'=>'application/x-gtar',
			'gz'=>'application/x-gzip',
			'h'=>'text/plain',
			'hdf'=>'application/x-hdf',
			'hh'=>'text/plain',
			'hqx'=>'application/mac-binhex40',
			'htm'=>'text/html',
			'html'=>'text/html',
			'ice'=>'x-conference/x-cooltalk',
			'ief'=>'image/ief',
			'iges'=>'model/iges',
			'igs'=>'model/iges',
			'ips'=>'application/x-ipscript',
			'ipx'=>'application/x-ipix',
			'jpe'=>'image/jpeg',
			'jpeg'=>'image/jpeg',
			'jpg'=>'image/jpeg',
			'js'=>'application/x-javascript',
			'kar'=>'audio/midi',
			'latex'=>'application/x-latex',
			'lha'=>'application/octet-stream',
			'lsp'=>'application/x-lisp',
			'lzh'=>'application/octet-stream',
			'm'=>'text/plain',
			'man'=>'application/x-troff-man',
			'me'=>'application/x-troff-me',
			'mesh'=>'model/mesh',
			'mid'=>'audio/midi',
			'midi'=>'audio/midi',
			'mif'=>'application/vnd.mif',
			'mime'=>'www/mime',
			'mov'=>'video/quicktime',
			'movie'=>'video/x-sgi-movie',
			'mp2'=>'audio/mpeg',
			'mp3'=>'audio/mpeg',
			'mpe'=>'video/mpeg',
			'mpeg'=>'video/mpeg',
			'mpg'=>'video/mpeg',
			'mpga'=>'audio/mpeg',
			'ms'=>'application/x-troff-ms',
			'msh'=>'model/mesh',
			'nc'=>'application/x-netcdf',
			'oda'=>'application/oda',
			'pbm'=>'image/x-portable-bitmap',
			'pdb'=>'chemical/x-pdb',
			'pdf'=>'application/pdf',
			'pgm'=>'image/x-portable-graymap',
			'pgn'=>'application/x-chess-pgn',
			'png'=>'image/png',
			'pnm'=>'image/x-portable-anymap',
			'pot'=>'application/mspowerpoint',
			'ppm'=>'image/x-portable-pixmap',
			'pps'=>'application/mspowerpoint',
			'ppt'=>'application/mspowerpoint',
			'ppz'=>'application/mspowerpoint',
			'pre'=>'application/x-freelance',
			'prt'=>'application/pro_eng',
			'ps'=>'application/postscript',
			'qt'=>'video/quicktime',
			'ra'=>'audio/x-realaudio',
			'ram'=>'audio/x-pn-realaudio',
			'ras'=>'image/cmu-raster',
			'rgb'=>'image/x-rgb',
			'rm'=>'audio/x-pn-realaudio',
			'roff'=>'application/x-troff',
			'rpm'=>'audio/x-pn-realaudio-plugin',
			'rtf'=>'text/rtf',
			'rtx'=>'text/richtext',
			'scm'=>'application/x-lotusscreencam',
			'set'=>'application/set',
			'sgm'=>'text/sgml',
			'sgml'=>'text/sgml',
			'sh'=>'application/x-sh',
			'shar'=>'application/x-shar',
			'silo'=>'model/mesh',
			'sit'=>'application/x-stuffit',
			'skd'=>'application/x-koan',
			'skm'=>'application/x-koan',
			'skp'=>'application/x-koan',
			'skt'=>'application/x-koan',
			'smi'=>'application/smil',
			'smil'=>'application/smil',
			'snd'=>'audio/basic',
			'sol'=>'application/solids',
			'spl'=>'application/x-futuresplash',
			'src'=>'application/x-wais-source',
			'step'=>'application/STEP',
			'stl'=>'application/SLA',
			'stp'=>'application/STEP',
			'sv4cpio'=>'application/x-sv4cpio',
			'sv4crc'=>'application/x-sv4crc',
			'swf'=>'application/x-shockwave-flash',
			't'=>'application/x-troff',
			'tar'=>'application/x-tar',
			'tcl'=>'application/x-tcl',
			'tex'=>'application/x-tex',
			'texi'=>'application/x-texinfo',
			'texinfo'=>'application/x-texinfo',
			'tif'=>'image/tiff',
			'tiff'=>'image/tiff',
			'tr'=>'application/x-troff',
			'tsi'=>'audio/TSP-audio',
			'tsp'=>'application/dsptype',
			'tsv'=>'text/tab-separated-values',
			'txt'=>'text/plain',
			'unv'=>'application/i-deas',
			'ustar'=>'application/x-ustar',
			'vcd'=>'application/x-cdlink',
			'vda'=>'application/vda',
			'viv'=>'video/vnd.vivo',
			'vivo'=>'video/vnd.vivo',
			'vrml'=>'model/vrml',
			'wav'=>'audio/x-wav',
			'wrl'=>'model/vrml',
			'xbm'=>'image/x-xbitmap',
			'xlc'=>'application/vnd.ms-excel',
			'xll'=>'application/vnd.ms-excel',
			'xlm'=>'application/vnd.ms-excel',
			'xls'=>'application/vnd.ms-excel',
			'xlw'=>'application/vnd.ms-excel',
			'xml'=>'text/xml',
			'xpm'=>'image/x-xpixmap',
			'xwd'=>'image/x-xwindowdump',
			'xyz'=>'chemical/x-pdb',
			'zip'=>'application/zip',
		);

		if(function_exists('finfo_open')) {
			$info = $magicFile===null ? finfo_open(FILEINFO_MIME_TYPE) : finfo_open(FILEINFO_MIME_TYPE, $magicFile);
			if($info && ($result=finfo_file($info,$file))!==false)
				return $result;
		}

		if(function_exists('mime_content_type') && ($result=mime_content_type($file)) !== false) {
			return $result;
		}

		$ext = self::getExt($file);
		return $mineType[$ext];
	}

	public static function mkdir($dir, $mode = '0755') {
        if(is_dir($dir)) {
			return true;
		} elseif(!is_dir(dirname($dir))) {
			self::mkdir(dirname($dir), $mode);
		}

        if (is_writable(dirname($dir))) {
            return mkdir($dir, $mode);
        } else {
            throw new Exception(Common::t(dirname($dir) . " can not be written"));
        }
    }

}
/*
$file = "crypt.jpg";
echo Helper_File::getExt($file);
echo Helper_File::getMineType($file);
Helper_File::mkdir('aaa/bbb');
*/
?>