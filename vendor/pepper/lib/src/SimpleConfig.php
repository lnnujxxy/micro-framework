<?php
namespace Pepper\Lib;

/**
 * Simple config class
 * @author wclssdn <ssdn@vip.qq.com>
 */
class SimpleConfig
{

    private static $config;

    /**
     * 读取配置
     * @param string $key
     * @param string $namespace 变量的命名空间 防止冲突
     * @return mixed
     */
    public static function get($key, $namespace = 'default')
    {
        return isset(self::$config[$namespace][$key]) ? self::$config[$namespace][$key] : null;
    }

    /**
     * 加载配置文件 配置文件返回一个配置数组
     * @param string $configFile
     * @param string $namespace 变量的命名空间 防止冲突
     * @return boolean
     */
    public static function loadConfigFile($configFile, $namespace = 'default')
    {
        if (!is_file($configFile) || !is_readable($configFile)) {
            return false;
        }

        self::$config[$namespace] = include $configFile;
        return true;
    }

    /**
     * 加载配置文件 配置文件内直接定义的变量
     * @param string $configFile
     * @param string $namespace 变量的命名空间 防止冲突
     * @return boolean
     */
    public static function loadConfigVarsFile($configFile, $namespace = 'default')
    {
        if (!is_file($configFile) || !is_readable($configFile)) {
            return false;
        }

        $_ = array_keys(get_defined_vars());
        include $configFile;
        $__ = get_defined_vars();
        $keys = array_diff(array_keys($__), $_);
        foreach ($keys as $key) {
            if ($key == '_') {
                continue;
            }
            self::$config[$namespace][$key] = $__[$key];
            unset($$key);
        }
        return true;
    }

    public static function loadStandardConfigFile($configPath, $namespace, $fileName = 'server_conf.php') {
        $configPath = rtrim($configPath, '/');
        $cluster = Util::getCluster();
        $configFile = $configPath . '/' . $fileName . '.' . $cluster;
        return self::loadConfigVarsFile($configFile, $namespace);
    }
}
