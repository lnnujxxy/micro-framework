<?php
namespace Pepper\Framework\Controller;

use Pepper\Framework\Lib\BizException;
use Pepper\Framework\Lib\Consume;
use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Degraded;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Lib\Logger;
use Pepper\Framework\Lib\Util;
use Pepper\Framework\Lib\Validate;
use Pepper\Framework\Model\User;
use Pepper\Lib\SimpleConfig;

class BaseController
{
    protected static $controllerName;
    protected static $actionName;

    public function __construct() {
        Consume::start();
        Degraded::set();
        $this->prepare();
    }

    public function prepare() {
        $userid = (int)trim($this->getParam('userid'));

        Context::add('deviceid', trim(strip_tags($this->getParam('deviceid'))));
        Context::add('version', $this->getParam('version'));
        Context::add('user_agent', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
        Context::add('userid', $userid);
        Context::add('platform', trim(strip_tags($this->getParam('platform', 'web'))));
        Context::add('ip', trim(Util::getIP()));
        Context::add('brand', trim(strip_tags($this->getParam('devicebrand'))));
        Context::add('model', trim(strip_tags($this->getParam('model'))));
        Context::add('network', trim(strip_tags($this->getParam('network'))));
        Context::add('partner', trim(strip_tags($this->getParam('partner'))));
        Context::add('token', trim(strip_tags($this->getParam('token'))));

        $base_url = strtolower('/' . self::$controllerName . '/' . self::$actionName);

        if (Context::get('platform') == 'server') {
            Interceptor::ensureNotFalse(Validate::is_valid_server($this->getRequest()), ERROR_PARAM_INVALID_SIGN);

            $limit_conf = array();
            $arr_limit_conf = SimpleConfig::get('INNER_REQUEST_LIMIT');
            if (in_array($base_url, $arr_limit_conf)) {
                $limit_conf = $arr_limit_conf[$base_url];
            }

            if ($limit_conf && in_array(AUTH_CHECK_POST, $limit_conf)) {
                Interceptor::ensureNotFalse($_SERVER['REQUEST_METHOD'] == 'POST', ERROR_SYS_NEEDPOST);
            }
        } else {
            $arr_auth_conf = SimpleConfig::get('AUTH_CONF');
            $auth_conf = array(AUTH_CHECK_FLOOD_REQUEST, AUTH_CHECK_LOGIN); // 默认验证签名和登陆状态

            foreach ($arr_auth_conf as $auth_uri => $value) {
                if ($base_url == strtolower($auth_uri)) {
                    $auth_conf = $value;
                }
            }

            if (!$auth_conf) {
                return;
            }

            if (in_array(AUTH_INNER_HOST_ONLY, $auth_conf)) {
                Interceptor::ensureNotFalse(self::isInnerHost(), ERROR_INNER_HOST_ONLY);
            }

            if (in_array(AUTH_CHECK_FLOOD_REQUEST, $auth_conf) && !Util::isInnerEnv()) {
                Interceptor::ensureNotFalse(Validate::is_valid_client($this->getRequest()), ERROR_PARAM_INVALID_SIGN);
            }

            if (in_array(AUTH_CHECK_POST, $auth_conf)) {
                Interceptor::ensureNotFalse($_SERVER['REQUEST_METHOD'] == 'POST', ERROR_SYS_NEEDPOST);
            }

            if (in_array(AUTH_CHECK_LOGIN, $auth_conf) && !Util::isInnerEnv()) {
                $token = trim(strip_tags($this->getParam('token')));
                $loginId = (int)User::getLoginid($token);
                if ($userid) {
                    Interceptor::ensureNotFalse($userid === $loginId, ERROR_USER_ERR_TOKEN, $userid . ':' . $token);
                } else {
                    Context::set('userid', $loginId);
                }
            }
        }
    }

    protected function getParam($key, $default = null) {
        $value = $this->_get($key);
        return (null == $value && null !== $default) ? $default : $value;
    }

    protected function getRequire($key, $check = null, $default = null) {
        $value = $this->getParam($key, $default);
        Interceptor::ensureNotFalse(method_exists((new Validate()), $check), ERROR_VALIDATE_NOT_METHOD);

        if (!call_user_func(array("\Pepper\Framework\Lib\Validate", $check), $value)) {
            Interceptor::ensureNotFalse(false, ERROR_PARAM_INVALID_FORMAT, "$key");
        }
        return $value;
    }

    protected function _get($key) {
        switch (true) {
            case isset($_GET[$key]):
                return $_GET[$key];
            case isset($_POST[$key]):
                return $_POST[$key];
            case isset($_COOKIE[$key]):
                return $_COOKIE[$key];
            case isset($_SERVER[$key]):
                return $_SERVER[$key];
            case isset($_ENV[$key]):
                return $_ENV[$key];
            case (strcasecmp($_SERVER['CONTENT_TYPE'], 'application/json') == 0) : // 支持application/json
                $params = json_decode(file_get_contents('php://input'), true);
                return isset($params[$key]) ? $params[$key] : null;
                break;
            default:
                return null;
        }
    }

    protected function getRequest($key = null, $default = null) {
        $value = $this->_getRequest($key, $default);
        return $value;
    }

    private function _getRequest($key = null, $default = null) {
        if (null === $key) {
            $res = array_merge($this->getPost(null, null), $this->getQuery(null, null));
            return $res;
        }
        if (null !== ($res = $this->getQuery($key, null))) {
            return $res;
        }
        if (null !== ($res = $this->getPost($key, null))) {
            return $res;
        }

        return $default;
    }

    protected function getPost($key = null, $default = null) {
        $value = $this->_getPost($key, $default);
        return $value;
    }

    public function _getPost($key = null, $default = null) {
        if (null === $key) {
            return $_POST;
        }

        return (isset($_POST[$key])) ? $_POST[$key] : $default;
    }

    public static function getCookie($key = null, $default = null) {
        if($key === null){
            return $_COOKIE;
        }
        return (isset($_COOKIE[$key])? $_COOKIE[$key] : $default);
    }

    public function getQuery($key = null, $default = null) {
        if (null === $key) {
            return $_GET;
        }
        return (isset($_GET[$key])) ? $_GET[$key] : $default;
    }

    /**
     * @return bool 是否是内网域名
     */
    public static function isInnerHost() {
        if( strpos( $_SERVER['HTTP_HOST'], 'inner') === false) {
            return false;
        }
        return true;
    }

    public static function isTestEnv() {
        if(self::getCookie('dbg', false)) {
            return SimpleConfig::get('TEST_MODE');
        }
        return false;
    }

    /**
     * Hold http request
     * @throws BizException
     */
    public static function handleHTTP() {
        // 防止误用
        static $handled = false;
        if ($handled === true) {
            return true;
        }
        $handled = true;

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = explode('/', ltrim($path, '/'));
        if (!$path) {
            self::$controllerName = 'Index';
            self::$actionName = 'index';
        } else {
            $tmp = array_shift($path);
            self::$controllerName = $tmp ? ucfirst($tmp) : 'Index';
            $tmp = array_shift($path);
            self::$actionName = $tmp ? $tmp : 'index';
        }
        $controllerClassName = __NAMESPACE__ . '\\' . self::$controllerName . 'Controller';
        if (!class_exists($controllerClassName)) {
            throw new BizException(ERROR_SYS_CONTROLLER_ERROR);
        }

        $controller = new $controllerClassName();
        $actionMethodName = self::$actionName . 'Action';
        if (!method_exists($controller, $actionMethodName)) {
            throw new BizException(ERROR_SYS_METHOD_ERROR);
        }

        call_user_func([$controller, $actionMethodName]);

        return true;
    }

    protected function render($data = array()) {
        self::outputJson(0, '', $data);
    }

    public static function outputJson($code, $message = '', $data = array()) {
        header('Content-Type: application/json; charset=UTF-8');
        header('Server: nginx/1.2.3');

        $result = array(
            'code' => $code,
            'message' => $message,
            'consume' => Consume::getTime(),
            'time' => Util::getTime(false)
        );

        if ($data) {
            $result['md5'] = md5(json_encode($data));
            $result['data'] = $data;
        }
        $content = json_encode($result, JSON_UNESCAPED_UNICODE);

        $log_data['post']  = json_encode($_POST, JSON_UNESCAPED_UNICODE); // post参数记录下来
        $log_data['input'] = file_get_contents('php://input');
        $log_data['loginid'] = Context::get('userid');
        Logger::notice($content, $log_data, $result['code']);

        $callback = isset($_GET['callback']) ? htmlspecialchars(trim($_GET['callback']), ENT_QUOTES) : '';

        if ($callback) {
            header('Content-type: application/x-javascript; charset=UTF-8');
            echo $callback . '(' . $content . ');';
        } else {
            echo $content;
        }

        exit();
    }

    protected function createPaySign($result, $appid = WX_APPID, $key = WX_KEY) {
        return md5("appId=".$appid."&nonceStr=".$result['nonce_str']."&package=prepay_id=".$result["prepay_id"].
            "&signType=MD5&timeStamp=".$result["timestamp"]."&key=".$key);
    }
}
