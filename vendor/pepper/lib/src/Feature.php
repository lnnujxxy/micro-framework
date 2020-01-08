<?php
namespace Pepper\Lib;

/**
 * 特性支持
 * Class Feature
 * @package Pepper\Lib
 * @desc 依赖各项目的配置项：$FEATURES
 */
class Feature
{
    protected static $app;
    protected static $platform;
    protected static $version;
    protected static $config;

    /**
     * 判断当前请求是否支持某特性
     * @param $feature string 特性名称，在配置文件$FEATURES数组中
     * @param $platform string 可直接指定平台
     * @param $version string 可直接指定版本
     * @param $app string 可直接指定app
     * @return bool
     */
    public static function support($feature, $platform = null, $version = null, $app = null)
    {
        $config = self::loadConfig();
        $app || $app = self::getApp();
        $platform || $platform = self::getPlatform();
        $version || $version = self::getVersion();

        if (!isset($config[$app][$feature][$platform])) {
            return false;
        }

        $versionRange = $config[$app][$feature][$platform];
        if (!is_array($versionRange)) {
            return false;
        }

        // 当前版本小于限制最低版本
        if (isset($versionRange['min']) && version_compare($version, $versionRange['min']) < 0) {
            return false;
        }

        // 当前版本大于等于限制最高版本
        if (isset($versionRange['max']) && version_compare($version, $versionRange['max']) >= 0) {
            return false;
        }

        return true;
    }

    public static function setApp($app)
    {
        self::$app = $app;
    }

    /**
     * 设置平台参数
     * @param $platform
     */
    public static function setPlatform($platform)
    {
        self::$platform = $platform;
    }

    /**
     * 设置版本参数
     * @param $version
     */
    public static function setVersion($version)
    {
        self::$version = $version;
    }

    protected static function getApp()
    {
        return self::$app ? self::$app : (isset($_GET['appname']) ? $_GET['appname'] : 'xxxxx');
    }

    protected static function getPlatform()
    {
        return self::$platform ? self::$platform : (isset($_GET['platform']) ? $_GET['platform'] : '');
    }

    protected static function getVersion()
    {
        return self::$version ? self::$version : (isset($_GET['version']) ? $_GET['version'] : '');
    }

    protected static function loadConfig()
    {
        static $config;
        if ($config === null) {
            $config = SimpleConfig::get('FEATURES');
        }
        return $config;
    }

    private function __construct()
    {
    }
}