<?php
/*
 * Copyright (c) 2010,  新浪网运营部-网络应用开发部
 * All rights reserved.
 * @description:
 * @author: zhouweiwei
 * @date:
 * @version: 1.0
 */
 class Helper_Page {
	/**
	 * 分页显示
	 *
	 * @param int $allItemTotal 所有记录数量
	 * @param int $currPageNum 当前页数量
	 * @param int $pageSize  每页需要显示记录的数量
	 * @param string $pageName  当前页面的地址, 如果为空则由系统自动获取,缺省为空
	 * @param array $getParamList  页面中需要传递的URL参数数组, 数组中key代表变量民,value代表变量值
	 * @param Int $pageShow 显示页码数
	 * @return string  返回最后解析出分页HTML代码, 可以直接使用
	 * @example
	 * 	echo Helper_Page::page(100, 2, 10, 'page.php', array('uid'=>1001, 'gid'=>2008));
	 *
	 *  输出: [上一页]  1<<  [1] [2]  [3]  [4]  [5]  [6]  [7]  [8]  [9]  [10]  >>10 [下一页]
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

		$firstPage = ($currPageNum <= 1) ? $currPageNum ."</b>&lt;&lt;" : "<a href=". $url ."1". $urlParamStr ." title='第1页'>1&lt;&lt;</a>";
		$lastPage = ($currPageNum >= $pagesNum)? "&gt;&gt;". $currPageNum : "<a href=". $url . $pagesNum . $urlParamStr." title='第". $pagesNum ."页'>&gt;&gt;". $pagesNum ."</a>";
		$prePage  = ($currPageNum <= 1) ? "上页" : "<a href=". $url . ($currPageNum-1) . $urlParamStr ." accesskey='p'  title='上一页'>[上一页]</a>";
		$nextPage = ($currPageNum >= $pagesNum) ? "下页" : "<a href=". $url . ($currPageNum+1) . $urlParamStr ."  title='下一页'>[下一页]</a>";

		$listNums = "";
		$front = floor($pageShow/2);
		for ($i=($currPageNum-$front); $i<($currPageNum+$pageShow-$front); $i++) {
			if ($i < 1 || $i > $pagesNum) continue;
			if ($i == $currPageNum) $listNums.= "[".$i."]&nbsp;";
			else $listNums.= "&nbsp;<a href=". $url . $i . $urlParamStr ." title='第". $i ."页'>[". $i ."]</a>&nbsp;";
		}

		$returnUrl = $prePage ."&nbsp;&nbsp;". $firstPage ." ". $listNums ."&nbsp;". $lastPage ."&nbsp;". $nextPage;

		return $returnUrl;
	}
 }

//echo Helper_Page::page(1000, 2, 10, 'page.php', array('uid'=>1001, 'gid'=>2008));
?>