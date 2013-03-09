<?php
/*
 * @description: 缓存类, 目前支持Memcached, File两种载体
 * @update: zhouweiwei
 * @date: 2010-5-13
 * @update:2010-11-03
 * @version: 1.0
 */
defined('IN_ROOT') || exit('Access Denied');
define('TIMESTAMP', time());

class Lib_Cache {

    public static function &factory($driver, $params=null) {
		$args = func_get_args();
		$key = Common::generateKey($args);
		if(class_exists($driver, false) {
			$cache = new $driver($params);
		}
		return $cache;
    }
}

class Lib_FileCache extends  Lib_Base {
    private $cachePath;

    private $prefix = 'file_cache_';

    public function __construct($params) {
		if (empty($params['cachePath'])) {
            $ret = array(
                'no' => 'error_param',
                'msg' => '配置参数错误',
            );
			throw new Exception(Common::t(json_encode($ret)));
		}

		$this->cachePath = $params['cachePath'];
    }

    public function set($key, $value, $expire = 0) {
        $cacheFile = $this->getFilePath($key);
        if (file_exists($cacheFile)) {
            $fp = fopen($cacheFile, "rb+");
            $line = $this->readMeta($fp);
            if ($line['expired']==0 || $line['expired'] > TIMESTAMP) {
                fclose($fp);
                return true;
            }
        }
        $fp = fopen($cacheFile, "wb+");
        $meta = $this->getMeta($key, $expire);
        $data = $meta . json_encode($value);
		flock($fp, LOCK_EX);
        fwrite($fp, $data);
		flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }


    public function get($key) {
        $cacheFile = $this->getFilePath($key);
        if (!file_exists($cacheFile)){
            return '';
        }
        $fp = fopen($cacheFile, 'rb');
        $meta = $this->readMeta($fp);
        if ($meta['expired'] != 0 && $meta['expired'] < TIMESTAMP) {
            fclose($fp);
            unlink($cacheFile);
            return '';
        }

        $ret = $str = '';
        while($str = fread($fp, 8192)) {
            $ret .= $str;
        }
        fclose($fp);
        return json_decode($ret, true);
    }

    public function replace($key, $value, $expire = 0) {
        $cacheFile = $this->getFilePath($key);
        $fp = fopen($cacheFile, "wb+");
        $meta = $this->getMeta($key, $expire);
        $data = $meta . json_encode($value);
        flock($fp, LOCK_EX);
        fwrite($fp, $data);
		flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    public function remove($key) {
        $cacheFile = $this->getFilePath($key);
        if (file_exists($cacheFile)){
            unlink($cacheFile);
        }
        return true;
    }

    public function flush(){
        $path = $this->cachePath .'/'. $this->prefix .'*';
        $files = glob($path);
        $rm_total = 0;
        if (is_array($files) && !empty($files)){
            foreach($files as $file){
                $fp = fopen($file, "rb");
                $meta = $this->readMeta($fp);
                if ($meta['expired']!=0 && $meta['expired']<TIMESTAMP){
                    fclose($fp);
                    unlink($file);
                    ++$rm_total;
                }
            }
        }
        return true;
    }

    public function destroy(){
        $path = $this->cachePath .'/'. $this->prefix .'*';
        $files = glob($path);
        if (is_array($files) && !empty($files)){
            foreach($files as $file){
                @unlink($file);
            }
        }
        return true;
    }

    private function getFilePath($key){
        return $this->cachePath .'/'. $this->prefix . md5($key);
    }


    private function readMeta($fp){
        $line = fgets($fp, 8192);
        $arr = explode("|", trim($line));
        $ret = array(
            "hash"      => strval($arr[0]),
            "created"   => intval($arr[1]),
            "expired"   => intval($arr[2]),
        );
        return $ret;
    }

    private function getMeta($key, $expired = 0){
        $e = ( $expired == 0 ? 0 : TIMESTAMP + $expired );
        $line = md5($key) ."|". TIMESTAMP ."|". $e ."\n";
        return $line;
    }

}


class Lib_Memcached extends Lib_Base{

	public $mc;
	public function __construct($params) { //$conf, $opts=array()
		if(!class_exists('Memcached', false)) {
            $ret = array(
                'no' => 'error_lib_noload',
                'msg' => 'Memcached 类库未加载'
            );
            throw new Exception(Common::t(json_encode($ret)));
		}
        if(empty($params['conf']) || !is_array($params['conf'])) {
            $ret = array(
                'no' => 'error_conf_param',
                'msg' => '配置参数有误'
            );
            throw new Exception(Common::t(json_encode($ret)));
        }
		$this->mc = new Memcached();
		$this->mc->addServers($params['conf']);
		if($params['opts']) {
            while(list($opt, $value) = each($params['opts'])) {
                $this->mc->setOption($opt, $value);
			}
		}
	}

	/**
	 * 从缓存中获取数据，增加了锁减少对数据库的请求
	 * @param $key String 缓存key
	 * @param $expire Int 该缓存时间
	 * @return Mixed
	 */
	public function getCache($key, $expire) {
		$data = $this->get($key);
		if($data === false) {
			//增加锁
			if($this->add($key.'_mutex', 1, 60) === true) {
				$dbData = $db->get($key); //从数据库中获取数据
				$this->setCache($key, $dbData, $expire);
				$this->delete($key.'_mutex');
			} else {
				sleep(10);
				$this->getCache();
			}
		} else {
			//在data内部设置1个超时值(timeout), timeout比实际的memcache expire小。当从cache读取到timeout发现它已经过期时候，马上延长timeout并重新设置到cache。然后再从数据库加载数据并设置到cache中
			if($data['timeout'] <= TIMESTAMP) {
				if($this->add($key.'_mutex', 1, 60) === true) {
					$data['timeout'] = TIMESTAMP+$expire*2;
					$this->set($key, $data, $expire*2);

					$dbData = $db->get($key); //从数据库中获取数据
					$this->setCache($key, $dbData, $expire);
					$this->delete($key.'_mutex');
				} else {
					sleep(10);
					$this->getCache();
				}
			}
		}
		return $data['value'];
	}

	/**
	 * 设置缓存
	 * @param $key String 缓存key
	 * @param $value Mixed 缓存数据
	 * @param $expire Int 缓存时间
	 * @return Bool
	 */
	public function setCache($key, $value, $expire) {
		$data = array(
			'value' => $value,
			'timeout' => TIMESTAMP+$expire-10; //timeout比expire要小10S
		);
		return $this->set($key, $data, $expire);
	}

	public function get($key, $callback=null, $isCas=false) {
        $key = $this->checkKey($key);
        if(!$isCas) {
            $data = $this->mc->get($key, $callback);
        } else {
            $data = $this->mc->get($key, $callback, &$cas);
        }

		if($this->mc->getResultCode() == Memcached::RES_SUCCESS) {
            if($_GET['mc'] == 'debug') {
                Common::firePHP($data, $key);
            }
			return $data;
		}
		return false;
    }


	public function getMulti($keys, $isCas=false) {
        if(!is_array($keys)) {
            $keys = array($keys);
        }
        $keys = $this->checkKey($keys);
        if(!$isCas) {
            $data = $this->mc->getMulti($keys);
        } else {
            $data = $this->mc->getMulti($keys, &$cas);
        }
		if($this->mc->getResultCode() == Memcached::RES_SUCCESS) {
            if($_GET['mc'] == 'debug') {
                Common::firePHP($data, $key);
            }
            return $data;
        }
		return false;
	}

	public function fetch($keys, $withCas=null, $callback=null) {
        if(!is_array($keys)) {
            $keys = array($keys);
        }

		$this->mc->getDelayed($keys, $withCas=null, $callback=null);
		if($this->mc->getResultCode() == Memcached::RES_SUCCESS) {
			$data = $this->mc->fetch();
            if($_GET['mc'] == 'debug') {
                Common::firePHP($data, $key);
            }
            return $data;
		}
		return false;
	}

	public function fetchAll($keys, $withCas=null, $callback=null) {
        if(!is_array($keys)) {
            $keys = array($keys);
        }
		$this->mc->getDelayed($keys, $withCas=null, $callback=null);
		if($this->mc->getResultCode() == Memcached::RES_SUCCESS) {
			$data = $this->mc->fetchAll();
            if($_GET['mc'] == 'debug') {
                Common::firePHP($data, $key);
            }

            return $data;
		}
		return false;
    }

	public function add($key, $value, $expire=0) {
        $key = $this->checkKey($key);
        return $this->mc->add($key, $value, $expire) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
	}

    public function cas($key, $value, $expire=0) {
        $key = $this->checkKey($key);
        $this->get($key, null, true);
        if($this->mc->getResultCode() == Memcached::RES_NOTFOUND) {
            return $this->add($key, $value, $expire);
        } else {
            return $this->mc->cas($cas, $key, $value, $expire) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
        }
    }

	public function set($key, $value, $expire=0) {
        $key = $this->checkKey($key);
		return $this->mc->set($key, $value, $expire) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
	}

	public function setMulti($keys, $expire=0) {
        if(!is_array($keys)) {
            $keys = array($keys);
        }
        $keys = $this->checkKey($keys);
		return $this->mc->setMulti($keys, $expire) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
	}

	public function replace($key, $value, $expire=0) {
        $key = $this->checkKey($key);
		return $this->mc->replace($key, $value, $expire) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
	}

	public function delete($key, $time=0) {
        $key = $this->checkKey($key);
		return $this->mc->delete($key, $time) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
	}

	public function flush($delay=0) {
		return $this->mc->flush($delay) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
	}

	public function append($key, $value) {
        $key = $this->checkKey($key);
		$this->mc->setOption(Memcached::OPT_COMPRESSION, false);
		return $this->mc->append($key, $value) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
	}

	public function prepend($key, $value) {
        $key = $this->checkKey($key);
		$this->mc->setOption(Memcached::OPT_COMPRESSION, false);
		return $this->mc->prepend($key, $value) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
	}

	public function increment($key, $offset=1) {
        $key = $this->checkKey($key);
		return $this->mc->increment($key, $offset) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
	}

	public function decrement($key, $offset=1) {
        $key = $this->checkKey($key);
		return $this->mc->decrement($key, $offset) && $this->mc->getResultCode() == Memcached::RES_SUCCESS;
	}

    private function checkKey($key) {
        if(is_array($key)) {
            $key = array_map(array($this,'checkKey'), $key);
        } else {
            if(empty($key)) {
                $key = md5(uniqid(mt_rand()));
            } elseif(mb_strlen($key) > 200) {
                $key = md5($key);
            }
            $key = trim($key);
        }
        return $key;
    }

	public function getStats() {
		return $this->mc->getStats();
	}

	public function getVersion() {
		return $this->mc->getVersion();
	}

	public function getServerList() {
		return $this->mc->getServerList();
	}
}


