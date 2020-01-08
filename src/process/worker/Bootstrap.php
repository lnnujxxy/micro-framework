<?php
namespace Pepper\Framework\Process;

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require ROOT_PATH  . '/vendor/autoload.php';
define('PROJECT_NAME', 'fx-wx'); // 项目名

use Pepper\Framework\Lib\Logger;
use Pepper\Lib\SimpleConfig;
use Pepper\Framework\Lib\Util;
use Pepper\Process\ProcessClient;

// 加载配置文件
$cluster = Util::getCluster();
SimpleConfig::loadConfigVarsFile(ROOT_PATH . "/config/server/server_conf.{$cluster}.php");

Logger::setConfig(SimpleConfig::get("LOG_PROCESS"));

if (!is_dir(SimpleConfig::get("LOG_PROCESS_PATH"))) {
    mkdir(SimpleConfig::get("LOG_PROCESS_PATH"), 0777, true);
}

try {
    $process = new ProcessClient(PROJECT_NAME);

    $process->addWorker('save_wx_avatar', array(__NAMESPACE__ . '\\Worker\\SaveWxAvatarWorker', 'execute'), 1, 1000);
    $process->addWorker('robot_help', array(__NAMESPACE__ . '\\Worker\\RobotHelpWorker', 'execute'), 1, 1000);

    $process->run();
} catch (\Exception $e) {

}
