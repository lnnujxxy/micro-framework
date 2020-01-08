<?php
date_default_timezone_set('Asia/Shanghai');

// ------------------ 错误码 ------------------
// 所有错误码规范格式 define('ERROR_STRING', 'errno:errmsg');其中冒号不可省略，注意对齐方便后续修改格式

// 成功
define('OK',                                        ['code' => 0, 'message' => '操作成功']);

// 公共错误码(参数、用户、api和app、系统)
define('ERROR_PARAM_IS_EMPTY', 						['code' => 1001, 'message' => '参数%s不能为空!']);
define('ERROR_PARAM_NOT_EXIST', 					['code' => 1002, 'message' => '参数%s不存在']);
define('ERROR_PARAM_INVALID_SIGN', 					['code' => 1003, 'message' => '签名校验失败']);
define('ERROR_PARAM_FLOOD_REQUEST', 				['code' => 1004, 'message' => '不能重复请求']);
define('ERROR_PARAM_INVALID_FORMAT', 				['code' => 1005, 'message' => '参数%s格式错误']);
define('ERROR_PARAM_NOT_SMALL_ZERO', 				['code' => 1006, 'message' => '参数%s不能小于0']);
define('ERROR_IP_NOT_ALLOWED', 				        ['code' => 1007, 'message' => 'IP未授权%s']);
define('ERROR_METHOD_NOT_ALLOWED', 				    ['code' => 1008, 'message' => '方法未授权%s']);
define('ERROR_PARAM_KEY_EXISTS', 					['code' => 1009, 'message' => 'KEY(%s)已存在']);
define('ERROR_PARAM_REQUEST_RESTRICT', 				['code' => 1010, 'message' => '异常重复请求']);
define('ERROR_INNER_HOST_ONLY',      				['code' => 1011, 'message' => '非法请求']);
define('ERROR_TOAST', 				                ['code' => 1012, 'message' => '%s']);
define('ERROR_VALIDATE_NOT_METHOD', 				['code' => 1013, 'message' => '验证方法不存在']);

// 用户
define('ERROR_USER_NOT_EXIST', 						['code' => 1102, 'message' => '用户不存在']);
define('ERROR_USER_ERR_PASS', 						['code' => 1103, 'message' => '密码错误']);
define('ERROR_USER_ERR_TOKEN', 						['code' => 1104, 'message' => 'token无效']);
define('ERROR_USER_ERR_BLACK', 						['code' => 1105, 'message' => '有问题的设备或用户']);
define('ERROR_USER_NOT_SYSTEM', 					['code' => 1108, 'message' => '用户:%s不是系统用户']);
define('ERROR_USER_LOGIN_FORBID', 					['code' => 1111, 'message' => '系统繁忙，请稍后再试']); // 用户登录超过次数限制
define('ERROR_DEGADED', 							['code' => 1112, 'message' => '系统繁忙，请稍后再试']); // 降级策略
define('ERROR_NEED_CHARGE', 						['code' => 1192, 'message' => '余额不足']);
define('ERROR_USER_MOBILE_CODE', 				    ['code' => 1193, 'message' => '手机验证码不正确或已过期']);
define('ERROR_WX_LOGIN_FAIL', 				        ['code' => 1194, 'message' => '用户微信授权登陆失败']);
define('ERROR_NICKNAME_TOOLONG',                    ['code' => 1195, 'message' => '用户昵称太长']);
define('ERROR_ACTIVE_BYCODE',                       ['code' => 1196, 'message' => 'code登陆失败，使用客户端信息']);
define('ERROR_WX_DECRYPT_FAIL', 				    ['code' => 1197, 'message' => '解密微信客户端信息失败']);

// 系统
define('ERROR_SYS_DB_SQL', 							['code' => 1301, 'message' => '数据库SQL操作失败:%s']);
define('ERROR_SYS_REDIS', 							['code' => 1302, 'message' => 'REDIS错误']);
define('ERROR_SYS_DB_DRIVER', 						['code' => 1303, 'message' => '数据驱动不存在']);
define('ERROR_SYS_DB_METHOD', 						['code' => 1304, 'message' => '数据驱动方法不存在:method:%s']);
define('ERROR_SYS_UNKNOWN', 						['code' => 1305, 'message' => '系统未知错误']);
define('ERROR_SYS_NEEDPOST', 						['code' => 1306, 'message' => '需要使用POST方法']);
define('ERROR_SYS_URLGETERROR', 					['code' => 1307, 'message' => '第三方资源请求失败']); // url 调用失败
define('ERROR_SYS_INVALID_INNER_REQUEST_HOST', 		['code' => 1312, 'message' => '非法内网请求主机']);
define('ERROR_SYS_INVALID_INNER_REQUEST_KEY', 		['code' => 1313, 'message' => '非法内网请求密钥']);
define('ERROR_CURL_URL_SYSTEM_ERROR',               ['code' => 1314, 'message' => 'CURL请求系统错误']);
define('ERROR_CURL_URL_HTTP_ERROR',                 ['code' => 1315, 'message' => 'CURL请求HTTP错误']);
define('ERROR_SYS_CONTROLLER_ERROR',                ['code' => 1316, 'message' => 'Controller不存在']);
define('ERROR_SYS_METHOD_ERROR',                    ['code' => 1317, 'message' => 'Method不存在']);
define('ERROR_QCLOUD_AUTH_FAIL',                    ['code' => 1317, 'message' => '腾讯云认证失败']);

// 关注
define('ERROR_FOLLOW_TOO_MUCH',                     ['code' => 1401, 'message' => '关注超过上限']);

// 商品
define('ERROR_BUY_GOODS_FAIL',                      ['code' => 1501, 'message' => '购买贡品失败']);
define('ERROR_BUY_GOODS_NOT_EXIST',                 ['code' => 1502, 'message' => '购买贡品不存在']);
define('ERROR_BUY_GOODS_TYPE_SAME',                 ['code' => 1503, 'message' => '同时购买同类型贡品']);
define('ERROR_BUY_GOODS_BETTER',                    ['code' => 1504, 'message' => "请送比互助者更好的进行互助或者%s分钟后再进行互助"]);
define('ERROR_BUY_NOT_ENOUGH',                      ['code' => 1505, 'message' => '余额不足']);
define('ERROR_BUY_NOTIFY_FAIL',                     ['code' => 1506, 'message' => '回调通知异常']);

// feeds
define('ERROR_EXIST_PENANCE',                       ['code' => 1601, 'message' => '您存在未完成的悔过']);
define('ERROR_NOT_HELP_SELF',                       ['code' => 1602, 'message' => '您不能自己互助自己']);

$LOG_PATH = '/data/nginx/logs/' . $URL . '/app/'; // 指定业务日志路径
$LOG = array(
    'level'   => 0x07, //fatal, warning, notice
    'logfile' => $LOG_PATH . 'fx-wx.log', //test.log.wf will be the wf log file
    'split'   => 0, //0 not split, 1 split by day, 2 split by hour
    'others' => array(
        'oauth'  => $LOG_PATH . 'oauth.log',
        'oauth_wf'  => $LOG_PATH . 'oauth.wf.log',
        'encrypt_wf' => $LOG_PATH . 'encrypt.wf.log',
        'cron_wf' => $LOG_PATH . 'cron.wf.log',
        'wxpay' => $LOG_PATH . 'wx.pay.log',
        'wxacion' => $LOG_PATH . 'wx.action.log',
        'wxcode' => $LOG_PATH . 'wx.code.log',
    )
);

$LOG_PROCESS_PATH = '/data/nginx/logs/' . $URL . '/process/';
$LOG_PROCESS      = array(
    'level'   => 0x07, //fatal, warning, notice
    'logfile' => $LOG_PROCESS_PATH . "fx-wx-process.log", //test.log.wf will be the wf log file
    'split'   => 0, //0 not split, 1 split by day, 2 split by hour
    'others'  => array(
    )
);


define('AUTH_CHECK_LOGIN', 1);
define('AUTH_CHECK_POST', 2);
define('AUTH_CHECK_BLACK', 3);
define('AUTH_CHECK_FLOOD_REQUEST', 4);
define('AUTH_SERVER_ONLY', 5);
define('AUTH_INNER_HOST_ONLY', 6);

// 配置接口权限, 默认AUTH_CHECK_FLOOD_REQUEST, AUTH_CHECK_LOGIN权限，特殊单独定义
$AUTH_CONF = array(
    '/User/auth' => [],
    '/User/active'              => [AUTH_CHECK_FLOOD_REQUEST],
    '/Feeds/getFeedInfo'        => [AUTH_CHECK_FLOOD_REQUEST],
    '/Config/getConfigs'        => [AUTH_CHECK_FLOOD_REQUEST],
    '/Config/getCommonConfigs'  => [AUTH_CHECK_FLOOD_REQUEST],
    '/Feeds/getRanks'           => [AUTH_CHECK_FLOOD_REQUEST],
    '/Feeds/getFeedsGoods'      => [AUTH_CHECK_FLOOD_REQUEST],
    '/Monitor/web'              => [],
    '/WxPay/notify'             => [],
    '/WxPay/notifyOfficialAccount' => [],
    '/WxPay/config'                => [],
    '/WxPay/unifiedOrderOfficialAccount' => [],
    'Qcloud/getTempKeys' => [],
    'Share/getWXACode' => [],
);

$REQUEST_SPEED_LIMIT = array(
    '/User/active' => array(
        'prefix' => 'restrict_group_send_',
        'frequency' => 60,
        'interval' => 60,
        'text' => 3,
    ),
);

$SYSTEM_CONFIG = array(
    'is_pass' => true,
);

// 小程序配置
define('WX_APPID', 'wx36c3533138295774');
define('WX_SECRET', '0ae12b1894d918ac8ece0ebb376295a4');
define('WX_MCHID', '1516187641');
define('WX_KEY', '0cd6b1754b489acabda3079853191123');

// 公众号配置
define('OFFICIAL_APPID', 'wx95d31b522a383118');
define('OFFICIAL_SECRET', '0cd6b1754b489acabda3079853191674');
define('OFFICIAL_MCHID', '1517038531');
define('OFFICIAL_KEY', '0cd6b1754b489acabda3079853191999');

// 腾讯云API密钥
define('SECRET_ID', 'AKIDSAdrqZEGE2rfLrkKGyTjlZ9WeYDfusd9');
define('SECRET_KEY', 'SfeOs6j7IEyUmrhJgpkQs40g6LL5ZQ3T');

// 报警 policyId
define('POLICY_ID', 'cm-ij07uxiu');

// 用户信息
define('NICKNAME_MAX_LEN', 20); // 昵称长度

