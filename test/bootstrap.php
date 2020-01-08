<?php

date_default_timezone_set('Asia/Harbin');
define('ROOT_PATH', dirname(__DIR__) . '/');
define('PROJECT_NAME', 'fx-wx'); // 项目名

require ROOT_PATH . 'vendor/autoload.php';

use Pepper\Lib\SimpleConfig;

$cluster = 'test'; // 测试用例仅支持测试环境
SimpleConfig::loadConfigVarsFile(ROOT_PATH . "config/server/server_conf.{$cluster}.php");
