<?php
namespace Pepper\Framework\Lib;
use Pepper\Lib\SimpleConfig;
/**
 * @file Logger.php
 * @author zhoujiahao(zhoujiahao@360.cn)
 * @date 2011/11/29 10:31:44
 * @version $Revision: 1.1 $
 * @brief class for logging
 *
 **/

/**
 * @example:
 *
<?php
namespace Pepper\Passport\Model\Lib;
require_once('Logger.php');
define('PROCESS_START_TIME', microtime(true) * 1000);
SimpleConfig::get('LOG') = array(
'level'            => 0x07,        //fatal, warning, notice
'logfile'        => '/home/q/test/log/test.log',    //test.log.wf will be the wf log file
'split'         => 1,          //0 not split, 1 split by day, 2 split by hour
'others '        => array(
'time'        => '/home/q/test/log/acstat.sdf.log',
'service'    => '/home/q/test/log/bhstat.sdf.log',
),
);

$str = 'log me';
Logger::notice($str);
Logger::fatal($str);
Logger::warning($str);
Logger::debug($str);

 **/
class Logger
{
    const LOG_LEVEL_NONE    = 0x00;
    const LOG_LEVEL_FATAL   = 0x01;
    const LOG_LEVEL_WARNING = 0x02;
    const LOG_LEVEL_NOTICE  = 0x04;
    const LOG_LEVEL_TRACE   = 0x08;
    const LOG_LEVEL_DEBUG   = 0x10;
    const LOG_LEVEL_ALL     = 0xFF;

    public static $arrLogLevels = array(
        self::LOG_LEVEL_NONE    => 'NONE',
        self::LOG_LEVEL_FATAL   => 'FATAL',
        self::LOG_LEVEL_WARNING => 'WARNING',
        self::LOG_LEVEL_NOTICE  => 'NOTICE',
        self::LOG_LEVEL_TRACE   => 'TRACE',
        self::LOG_LEVEL_DEBUG   => 'DEBUG',
        self::LOG_LEVEL_ALL     => 'ALL',
    );

    const LOG_SPLIT_NONE    = 0;
    const LOG_SPLIT_BY_DAY  = 1;
    const LOG_SPLIT_BY_HOUR = 2;

    protected $intLevel;
    protected $strLogFile;
    protected $arrOtherLogs;
    protected $intLogId;
    protected $intSplit;
    protected $intStartTime;

    private static $instance = null;
    private static $config = null;

    private function __construct($arrLogConfig, $intStartTime)
    {
        $this->intLevel     = intval($arrLogConfig['level']);
        $this->strLogFile   = $arrLogConfig['logfile'];
        $this->arrOtherLogs = $arrLogConfig['others'];
        $this->intSplit     = intval($arrLogConfig['split']);
        $this->intLogId     = self::__logId();
        $this->intStartTime = $intStartTime;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            if (defined('PROCESS_START_TIME')) {
                $intStartTime = PROCESS_START_TIME;
            } elseif (isset($_SERVER['REQUEST_TIME'])) {
                $intStartTime = $_SERVER['REQUEST_TIME'] * 1000;
            } else {
                $intStartTime = microtime(true) * 1000;
            }
            $config = self::$config ? self::$config : SimpleConfig::get('LOG');
            self::$instance = new Logger($config, $intStartTime);
        }

        return self::$instance;
    }

    public static function setConfig($config)
    {
        self::$config = $config;
    }

    /**
     * write other log
     * @param string $strKey  key of other logs
     * @param string $str    log message
     * @param array $arrArgs  extra log message
     * @return
     */
    public static function log($strKey, $str, $arrArgs = null)
    {
        $log = Logger::getInstance();
        return $log->writeOtherLog($strKey, $str, $arrArgs);
    }

    /**
     * write debug message
     *
     * @param string $str  log message
     * @param array $arrArgs    extra log message
     * @param int $errno    errno, default 0
     * @param int $depth
     * @return none
     */
    public static function debug($str, $arrArgs = null, $errno = 0, $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_DEBUG, $str, $errno, $arrArgs, $depth + 1);
    }

    /**
     * write trace message
     *
     * @param string $str  log message
     * @param array $arrArgs    extra log message
     * @param int $errno    errno, default 0
     * @param int $depth
     * @return none
     */
    public static function trace($str, $arrArgs = null, $errno = 0, $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_TRACE, $str, $errno, $arrArgs, $depth + 1);
    }

    /**
     * write notice message
     *
     * @param string $str  log message
     * @param array $arrArgs    extra log message
     * @param int $errno    errno, default 0
     * @param int $depth
     * @return none
     */
    public static function notice($str, $arrArgs = null, $errno = 0, $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_NOTICE, $str, $errno, $arrArgs, $depth + 1);
    }

    /**
     * write warning message
     *
     * @param string $str  log message
     * @param array $arrArgs    extra log message
     * @param int $errno    errno, default 0
     * @param int $depth
     * @return none
     */
    public static function warning($str, $arrArgs = null, $errno = 0, $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_WARNING, $str, $errno, $arrArgs, $depth + 1);
    }

    /**
     * write fatal message
     *
     * @param string $str  log message
     * @param array $arrArgs    extra log message
     * @param int $errno    errno, default 0
     * @param int $depth
     * @return none
     */
    public static function fatal($str, $arrArgs = null, $errno = 0, $depth = 0)
    {
        $log = Logger::getInstance();
        return $log->writeLog(self::LOG_LEVEL_FATAL, $str, $errno, $arrArgs, $depth + 1);
    }

    /**
     * get current log id
     * @return int
     * @deprecated  see #getLogId()
     */
    public static function logId()
    {
        return Logger::getInstance()->intLogId;
    }

    /**
     * get current log id
     * @return int
     */
    public static function getLogId()
    {
        return Logger::getInstance()->intLogId;
    }

    /**
     * set current log id
     * @param intLogId  log id will be set
     *
     */
    public static function setLogId($intLogId)
    {
        Logger::getInstance()->intLogId = intval($intLogId);
    }

    /**
     * get client ip
     * @return string
     */
    public static function getClientIP()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENTIP'])) {
            $ip = $_SERVER['HTTP_CLIENTIP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENTIP')) {
            $ip = getenv('HTTP_CLIENTIP');
        } elseif (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = '127.0.0.1';
        }

        $pos = strpos($ip, ',');
        if ($pos > 0) {
            $ip = substr($ip, 0, $pos);
        }

        return trim($ip);
    }

    public static function getRemotePort() {
        return isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : "";
    }

    private function getLogFile($intLevel)
    {
        $strLogFile = $this->strLogFile;
        if (($intLevel & self::LOG_LEVEL_WARNING) || ($intLevel & self::LOG_LEVEL_FATAL)) {
            $strLogFile .= '.wf';
        }

        if ($this->intSplit == self::LOG_SPLIT_BY_DAY) {
            return $strLogFile . "." . date("Ymd");
        }

        if ($this->intSplit == self::LOG_SPLIT_BY_HOUR) {
            return $strLogFile . "." . date("YmdH");
        }

        return $strLogFile;
    }

    public function writeLog($intLevel, $str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        if ($intLevel > $this->intLevel || !isset(self::$arrLogLevels[$intLevel])) {
            return;
        }

        $strLevel   = self::$arrLogLevels[$intLevel];
        $strLogFile = $this->getLogFile($intLevel);

        $trace = debug_backtrace();
        if ($depth >= count($trace)) {
            $depth = count($trace) - 1;
        }
        $file = basename($trace[$depth]['file']);
        $line = $trace[$depth]['line'];

        $strArgs = '';
        if (is_array($arrArgs) && count($arrArgs) > 0) {
            foreach ($arrArgs as $key => $value) {
                $strArgs .= $key . "[$value] ";
            }
        }

        $intTimeUsed = microtime(true) * 1000 - $this->intStartTime;

        $str = sprintf("%s: time[%s] [%s:%d] errno[%d] ip[%s] logId[%u] uri[%s] time_used[%d] port[%s] %s%s\n",
            $strLevel,
            date('m-d H:i:s', time()),
            $file, $line, $errno,
            self::getClientIP(),
            $this->intLogId,
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            $intTimeUsed, self::getRemotePort(), $strArgs, $str);
        return file_put_contents($strLogFile, $str, FILE_APPEND);
    }

    private function getOtherLogFile($strKey)
    {
        if (!isset($this->arrOtherLogs[$strKey])) {
            return false;
        }

        $strLogFile = $this->arrOtherLogs[$strKey];
        if ($this->intSplit == self::LOG_SPLIT_BY_DAY) {
            return $strLogFile . "." . date("Ymd");
        }

        if ($this->intSplit == self::LOG_SPLIT_BY_HOUR) {
            return $strLogFile . "." . date("YmdH");
        }

        return $strLogFile;
    }

    public function formatLog($strKey, $arrArgs = null, $msg = "")
    {
        $log = Logger::getInstance();
        return $log->writeFormatLog($strKey, $arrArgs, $msg);
    }

    public function writeFormatLog($strKey, $arrArgs = null, $msg = "")
    {
        $arrArgs["msg"] = $msg;
        $strLogFile     = $this->getOtherLogFile($strKey);
        if (false === $strLogFile) {
            return;
        }

        if (file_exists($strLogFile) && substr(sprintf('%o', fileperms($strLogFile)), -4) != "0777") {
            try {
                $currentUser = posix_getpwuid(fileowner($strLogFile));
                if ($currentUser['name'] == exec("whoami")) {
                    chmod($strLogFile, 0777); 
                }
            } catch (\Exception $e) {}
        }
        $str = "";
        if (is_array($arrArgs) && count($arrArgs) > 0) {
            foreach ($arrArgs as $key => $value) {
                $str .= $key . "=" . $value . "\t";
            }
            $str = date("Y-m-d H:i:s") . "\t" . $str . "\n";
        }
        return file_put_contents($strLogFile, $str, FILE_APPEND);
    }

    public function writeOtherLog($strKey, $str, $arrArgs = null)
    {
        $strLogFile = $this->getOtherLogFile($strKey);
        if (false === $strLogFile) {
            return;
        }

        $strArgs = '';
        if (is_array($arrArgs) && count($arrArgs) > 0) {
            foreach ($arrArgs as $key => $value) {
                $strArgs .= $key . "[$value] ";
            }
        }

        //兼容多用户互写日志报warning问题
        if (!file_exists($strLogFile) && touch($strLogFile) && substr(sprintf('%o', fileperms($strLogFile)), -4) != "0777") {
            chmod($strLogFile, 0777); 
        }

        $str = sprintf("%s: time[%s] ip[%s] logId[%u] uri[%s] %s%s\n",
            $strKey,
            date('m-d H:i:s:', time()),
            self::getClientIP(),
            $this->intLogId,
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            $strArgs, $str);
        return file_put_contents($strLogFile, $str, FILE_APPEND);
    }

    public function writeLogs($intLevel, $str, $errno = 0, $arrArgs = null, $depth = 0)
    {
        if ($intLevel > $this->intLevel || !isset(self::$arrLogLevels[$intLevel])) {
            return;
        }

        $strLevel   = self::$arrLogLevels[$intLevel];
        $strLogFile = $this->getLogFile($intLevel);

        $trace = debug_backtrace();
        if ($depth >= count($trace)) {
            $depth = count($trace) - 1;
        }
        $file = basename($trace[$depth]['file']);
        $line = $trace[$depth]['line'];

        $strArgs = '';
        if (is_array($arrArgs) && count($arrArgs) > 0) {
            foreach ($arrArgs as $key => $value) {
                $strArgs .= $key . "[$value]\n";
            }
        }

        $intTimeUsed = microtime(true) * 1000 - $this->intStartTime;

        $str = sprintf("%s: time[%s] [%s:%d] errno[%d] ip[%s] logId[%u] uri[%s] time_used[%d] %s\n%s",
            $strLevel,
            date('m-d H:i:s', time()),
            $file, $line, $errno,
            self::getClientIP(),
            $this->intLogId,
            isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            $intTimeUsed, $str, $strArgs);
        return file_put_contents($strLogFile, $str, FILE_APPEND);
    }

    private static function __logId()
    {
        $arr = gettimeofday();
        return ((($arr['sec'] * 100000 + $arr['usec'] / 10) & 0x7FFFFFFF) | 0x80000000);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=90 noet: */
