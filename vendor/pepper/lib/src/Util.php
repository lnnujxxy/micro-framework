<?php
namespace Pepper\Lib;


class Util
{
    public static function getCluster ()
    {
        // 读取环境变量
        $cluster = @getenv('CLUSTER');
        if (!empty($cluster)) {
            return $cluster;
        }
        // 测试环境文件
        if (file_exists(ROOT_PATH . '/.is_test_env')) { // 在系统根目录放置
            return 'test';
        }

        $hostname  = gethostname();
        if (in_array($hostname, ['xxxx'])) { // 指定测试机器
            return "test";
        }

        return 'release';
    }
}