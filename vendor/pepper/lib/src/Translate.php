<?php
/**
 * 多语言支持函数
 * Created by PhpStorm.
 * User: liqingshou
 * Date: 18/04/2018
 * Time: 11:46 AM
 */

namespace Pepper\Lib;

class Translate
{
    /**
     * 翻译文件所在的路径
     * @var string|null
     */
    private $path = null;
    /**
     * 本实例默认的翻译语言
     * @var string null
     */
    private $locale = null;
    /**
     * 如果在指定的语言没有找到的时候 ，使用此语言
     * @var null
     */
    private $fallbackLocale = null;
    /**
     * 在没有找到对应语言的时候是否抛出异常
     * @var bool
     */
    private $throwException = true;

    /**
     * Translate constructor.
     * 在同个请求中使用多种语言时才使用显示的构造函数，不然使用单例模式
     * @param $path
     * @param $locale
     * @param $fallbackLocale
     */
    public function __construct($path, $locale, $fallbackLocale)
    {
        $this->path = $path;
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;

    }

    /**
     * 获取指定key对应的翻译广西
     * @param string $key 由目录和对应的key组成, 如live/link.accept表示live/link.php下的acept键名
     * @param null|string $locale 指定翻译语言，如果不指定，则使用类定义的locale
     * @param bool $fallback 在指定的locale没有找到的情况 下是否使用默认的翻译
     * @return mixed 返回翻译文本，如果没有找到，并且不抛出异常的话，那么返回 key
     * @throws \Exception 如果设置了throwException，则在没有找到的情况下会抛出异常
     */
    public function get($key, $locale = null, $fallback = true)
    {
        list($file, $keys) = $this->parseKey($key);

        $line = $this->retrieve($file, $keys, $locale ?: $this->locale);
        if ($line === false && $fallback) {
            $line = $this->retrieve($file, $keys, $this->fallbackLocale);
        }

        if ($line === false) {
            if ($this->throwException) {
                throw new \Exception("translate error: key not found");
            } else {
                return $key;
            }
        } else {
            return $line;
        }

    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function setFallbackLocale($locale)
    {
        $this->locale = $locale;
    }

    public function setThrowException($switch = null)
    {
        if (is_null($switch)) {
            return $this->throwException;
        }
        return $this->throwException = boolval($switch);
    }

    private function parseKey($key)
    {
        $keys = explode(".", $key);
        $file = "{$keys[0]}.php";
        $keys = array_slice($keys, 1);
        return [$file, $keys];
    }

    /**
     * @var array 所有已经加载的翻译文件缓存
     */
    private $loaded = [];

    private function retrieve($file, array $keys, $locale)
    {
        $realFile = "{$this->path}/{$locale}/{$file}";
        if (!isset($this->loaded[$realFile])) {
            if (is_file($realFile)) {
                $this->loaded[$realFile] = require $realFile;
            } else {
                return false;
            }
        }
        $data = $this->loaded[$realFile];

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                return false;
            } else {
                $data = $data[$key];
            }
        }
        return $data;
    }

    /**
     * @var \Pepper\Lib\Translate
     */
    private static $singleton = null;

    public static function getInstance($path = null, $locale = null, $fallbackLocale = null)
    {
        self::initSingleton($path, $locale, $fallbackLocale);
        return self::$singleton;
    }

    public static function initSingleton($path, $locale, $fallbackLocale)
    {
        if (self::$singleton == null) {
            self::$singleton = new self($path, $locale, $fallbackLocale);
            return true;
        }
        return false;
    }
}