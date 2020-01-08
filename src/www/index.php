<?php
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('PROJECT_NAME', 'fx-wx'); // 项目名
require  ROOT_PATH . '/vendor/autoload.php';

use Pepper\Framework\Controller\BaseController;
use Pepper\Framework\Lib\BizException;
use Pepper\Lib\SimpleConfig;
use Pepper\Framework\Lib\Util;

// 加载配置文件
$cluster = Util::getCluster();
SimpleConfig::loadConfigVarsFile(ROOT_PATH . '/config/server/server_conf.' . $cluster . '.php');
// 处理http请求
try {
    Pepper\Framework\Controller\BaseController::handleHTTP();
} catch (BizException $e) {
    // 业务异常
    BaseController::outputJson($e->getCode(), $e->getMessage());
} catch (Throwable $e) {
    // 未知异常 & 未显式处理异常
    header('Debug-Errno: ' . $e->getCode());
    header('Debug-Error: ' . $e->getMessage());

    \Pepper\Framework\Lib\Logger::warning("throwable", ['code' => $e->getCode(), 'message' => $e->getMessage()]);
    BaseController::outputJson(ERROR_SYS_UNKNOWN['code'], ERROR_SYS_UNKNOWN['message']);
}
