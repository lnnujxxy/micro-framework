<?php

namespace Pepper\QFrameDB;

class QFrameDB
{
    private static $_container = [];
    private static $_default_config = [
        "driver" => "mysql",
        "host" => "127.0.0.1",
        "port" => "3306",
        "username" => "root",
        "password" => "",
        "charset" => "utf8",
        "database" => "test",
        "persistent" => true,
        "unix_socket" => "",
        "options" => []
    ];

    /**
     * 获取一个QFrameDB实例
     * @param array $config
     * @return QFrameDBPDO
     */
    public static function getInstance($config = [])
    {
        $key = md5(serialize($config));

        if (!isset(self::$_container[$key]) || !(self::$_container[$key] instanceof QFrameDBPDO)) {
            $final_config = [];
            foreach (self::$_default_config as $index => $value) {
                $final_config[$index] = isset($config[$index]) && ('' !== $config[$index]) ? $config[$index] : self::$_default_config[$index];
            }
            self::$_container[$key] = new QFrameDBPDO($final_config);
        }

        return self::$_container[$key];
    }

    /**
     * 销毁一个实例
     * @param array $config
     * @return bool
     */
    public static function destroyInstance($config = [])
    {
        $key = md5(serialize($config));
        if (isset(self::$_container[$key]) && (self::$_container[$key] instanceof QFrameDBPDO)) {
            self::$_container[$key]->close();
            unset(self::$_container[$key]);
        }
        return true;
    }
}

