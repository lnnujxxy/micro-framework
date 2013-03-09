<?php
/*
 * Copyright (c) 2010,  ��������Ӫ��-����Ӧ�ÿ�����
 * All rights reserved.
 * @description:
 * @author: zhouweiwei
 * @date:
 * @version: 1.0
 */
 class Helper_Page {
	/**
	 * ��ҳ��ʾ
	 *
	 * @param int $allItemTotal ���м�¼����
	 * @param int $currPageNum ��ǰҳ����
	 * @param int $pageSize  ÿҳ��Ҫ��ʾ��¼������
	 * @param string $pageName  ��ǰҳ��ĵ�ַ, ���Ϊ������ϵͳ�Զ���ȡ,ȱʡΪ��
	 * @param array $getParamList  ҳ������Ҫ���ݵ�URL��������, ������key���������,value�������ֵ
	 * @param Int $pageShow ��ʾҳ����
	 * @return string  ��������������ҳHTML����, ����ֱ��ʹ��
	 * @example
	 * 	echo Helper_Page::page(100, 2, 10, 'page.php', array('uid'=>1001, 'gid'=>2008));
	 *
	 *  ���: [��һҳ]  1<<  [1] [2]  [3]  [4]  [5]  [6]  [7]  [8]  [9]  [10]  >>10 [��һҳ]
	 */
	public static function page($allItemTotal, $currPageNum, $pageSize, $pageName='', array $getParamList = array(), $pageShow = 10) {

		if ($allItemTotal == 0) return "";

		if($pageName=='') {
			$url = $_SERVER['PHP_SELF']."?page=";
		} else {
			$url = $pageName."?page=";
		}

		$urlParamStr = "&";
		if($getParamList) {
			$urlParamStr .= http_build_query($getParamList);
		}

		$pagesNum = ceil($allItemTotal/$pageSize);

		$firstPage = ($currPageNum <= 1) ? $currPageNum ."</b>&lt;&lt;" : "<a href=". $url ."1". $urlParamStr ." title='��1ҳ'>1&lt;&lt;</a>";
		$lastPage = ($currPageNum >= $pagesNum)? "&gt;&gt;". $currPageNum : "<a href=". $url . $pagesNum . $urlParamStr." title='��". $pagesNum ."ҳ'>&gt;&gt;". $pagesNum ."</a>";
		$prePage  = ($currPageNum <= 1) ? "��ҳ" : "<a href=". $url . ($currPageNum-1) . $urlParamStr ." accesskey='p'  title='��һҳ'>[��һҳ]</a>";
		$nextPage = ($currPageNum >= $pagesNum) ? "��ҳ" : "<a href=". $url . ($currPageNum+1) . $urlParamStr ."  title='��һҳ'>[��һҳ]</a>";

		$listNums = "";
		$front = floor($pageShow/2);
		for ($i=($currPageNum-$front); $i<($currPageNum+$pageShow-$front); $i++) {
			if ($i < 1 || $i > $pagesNum) continue;
			if ($i == $currPageNum) $listNums.= "[".$i."]&nbsp;";
			else $listNums.= "&nbsp;<a href=". $url . $i . $urlParamStr ." title='��". $i ."ҳ'>[". $i ."]</a>&nbsp;";
		}

		$returnUrl = $prePage ."&nbsp;&nbsp;". $firstPage ." ". $listNums ."&nbsp;". $lastPage ."&nbsp;". $nextPage;

		return $returnUrl;
	}
 }

//echo Helper_Page::page(1000, 2, 10, 'page.php', array('uid'=>1001, 'gid'=>2008));
?>