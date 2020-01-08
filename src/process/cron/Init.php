<?php
namespace Pepper\Framework\Process;

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require ROOT_PATH  . '/vendor/autoload.php';
define('PROJECT_NAME', 'fx-wx'); // 项目名

use Pepper\Lib\SimpleConfig;
use Pepper\Framework\Lib\Util;

// 加载配置文件
$cluster = Util::getCluster();
SimpleConfig::loadConfigVarsFile(ROOT_PATH . "/config/server/server_conf.{$cluster}.php");

