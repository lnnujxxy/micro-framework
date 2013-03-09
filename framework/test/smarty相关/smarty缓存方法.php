<?php
/**
 * 生成smarty缓存文件名
 *
 * @param $cache_dir String 缓存目录
 * @param $template_name String 模板文件
 * @param $cache_id String 缓存ID
 * @return String
 */
function get_smarty_cachefile($cache_dir, $template_name, $cache_id=null) {
    $_compile_dir_sep = '^';
    $_return = $cache_dir;
    if(isset($cache_id)) {
        $auto_id = str_replace('%7C', $_compile_dir_sep, (urlencode($cache_id)));
        $_return .= $auto_id . $_compile_dir_sep;
    }

    $_filename = urlencode(basename($template_name));
    $_crc32 = sprintf('%08X', crc32($template_name));
    $_crc32 = substr($_crc32, 0, 2) . $_compile_dir_sep .
              substr($_crc32, 0, 3) . $_compile_dir_sep . $_crc32;
    $_return .= '%%' . $_crc32 . '%%' . $_filename;
    return $_return;
}

/**
 * smarty 使用 memcached 缓存
 *
 */
function cache_handler($action, &$smarty_obj, &$cache_content, $tpl_file=null, $cache_id=null, $compile_id=null, $exp_time=null) {
	$config = $_SERVER['config'];
	if(empty($config)) {
		$smarty_obj->trigger_error('cache_handler: config is empty');
		return false;
	}
	// ref to the memcache object
	$model = new Lib_Model;
	$m = $model->getCache();

	// the key to store cache_ids under, used for clearing
	$key = 'smarty_caches';

	// check memcache object
	/*
	if (get_class($m) != 'memcached') {
        $smarty_obj->trigger_error('cache_handler: $GLOBALS[\'memcached_res\'] is not a memcached object');
		return false;
	}
	*/

	// unique cache id
	$cache_id = md5($tpl_file.$cache_id.$compile_id);

	switch ($action) {
	case 'read':
		// grab the key from memcached
		$contents = $m->get($cache_id);

		// use compression
		if($smarty_obj->use_gzip && function_exists("gzuncompress")) {
			$cache_content = gzuncompress($contents);
		} else {
			$cache_content = $contents;
		}

		$return = true;
		break;

	case 'write':
		// use compression
		if($smarty_obj->use_gzip && function_exists("gzcompress")) {
			$contents = gzcompress($cache_content);
		} else {
			$contents = $cache_content;
		}

		// add the cache_id to the $key string
		$caches = $m->get($key);
		if (!is_array($caches)) {
			$caches = array($cache_id);
			$m->set($key, $caches, $config['smarty']['cache_life']);
		} else if (!in_array($cache_id, $caches)) {
			array_push($caches, $cache_id);
			$m->set($key, $caches, $config['smarty']['cache_life']);
		}

		// store the value in memcached
		$stored = $m->set($cache_id, $contents, $config['smarty']['cache_life']);

		if(!$stored) {
			$smarty_obj->trigger_error("cache_handler: set failed.");
		}

		$return = true;
		break;

	case 'clear':
		if(empty($cache_id) && empty($compile_id) && empty($tpl_file)) {
			// get all cache ids
			$caches = $m->get($key);

			if (is_array($caches)) {
				$len = count($caches);
				for ($i=0; $i<$len; $i++) {
					// assume no errors
					$m->delete($caches[$i]);
				}

				// delete the cache ids
				$m->delete($key);

				$result = true;
			}
		} else {
			$result = $m->delete($cache_id);
		}
		if(!$result) {
			$smarty_obj->trigger_error("cache_handler: query failed.");
		}
		$return = true;
		break;

	default:
		// error, unknown action
		$smarty_obj->trigger_error("cache_handler: unknown action \"$action\"");
		$return = false;
		break;
	}

	return $return;
}


?>