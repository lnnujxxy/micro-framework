<?php

namespace Pepper\Process\Base;

use Pepper\Lib\SimpleConfig;

class Job
{
    private $_children = array();
    private $_queue = null;
    private $_product = "";
    private $_job = null;
    private $_logDays = 7;
    private $_debug = false;
    private $_runLogFile = "";
    private $_processLogFile = "";
    private $_taskLogFile = "";
    private $_task = array();
    private $_logroot = '';

    private static $_user_log = array();

    public function __construct($product, $jobname = "")
    {
        pcntl_signal(SIGCHLD, SIG_IGN);

        $this->_queue = new Queue($product);
        $this->_product = $product;
        $this->_job = $jobname;
        $this->_logroot = SimpleConfig::get('LOG_PATH', 'process');
        $this->_createLogDir($this->_logroot);
        $this->_runLogFile = $this->_logroot . "/run/run-" . $this->_product . "-" . $this->_job;
        $this->_processLogFile = $this->_logroot . "/job/job-" . $this->_product . "-" . $this->_job;
        $this->_taskLogFile = $this->_logroot . "/task/task-" . $this->_product . "-" . $this->_job;

        self::$_user_log = array();
    }

    public static function getInstance($product, $jobname)
    {
        static $job;

        if (!isset($job[$jobname]) || !$job[$jobname] instanceof Job) {
            $job[$jobname] = new Job($product, $jobname);
        }

        return $job[$jobname];
    }

    public function getTaskCount()
    {
        return $this->_queue->getLength($this->_job);
    }

    public function getTaskBakCount($date = "")
    {
        return $this->_queue->getBakLength($this->_job, $date);
    }

    public function isQueueAlive()
    {
        return $this->_queue->isAlive();
    }

    public static function addLog($userlog = array())
    {
        self::$_user_log = array_merge(self::$_user_log, $userlog);
    }

    public function addTask($params, $rank = null)
    {
        try {
            $traceid = $this->_queue->addQueue($this->_job, $params, $rank);
        } catch (\Exception $e) {
            $this->_writeTaskLog(json_encode(array("params" => $params, "rank" => $rank, "error" => $e->getMessage())), $this->_taskLogFile . ".wf");
            return 0;
        }

        $this->_writeTaskLog(json_encode(array("params" => $params, "traceid" => $traceid)), $this->_taskLogFile);

        return $traceid;
    }

    public function setDebug($debug)
    {
        $this->_debug = $debug;
    }

    public function setLogDays($n)
    {
        $this->_logDays = $n;
    }

    public function setRunLogPath($run_log_file)
    {
        if (is_dir($run_log_file)) {
            $run_log_file .= "/run-" . $this->_product . "-" . $this->_job;
        }

        $this->_runLogFile = $run_log_file;
    }

    public function setProcessLogPath($process_log_file)
    {
        if (is_dir($process_log_file)) {
            $process_log_file .= "/job-" . $this->_product . "-" . $this->_job;
        }

        $this->_processLogFile = $process_log_file;
    }

    public function startWorker($worker)
    {
        if ($this->_debug) {
            $this->_print("start in debug model");
            $this->_runWorker($worker);
            exit;
        }

        set_time_limit(0);
        ini_set('default_socket_timeout', -1);
        register_shutdown_function(array($this, "_getLastError"));
        set_error_handler(array($this, "_catchError"));

        $this->_children = array();
        $pid = getmypid();
        $this->_print("start main process, pid:$pid");

        for ($i = 0; $i < $worker["max_children"]; $i++) {
            $this->_start($worker);
        }

        while (true) {
            foreach ($this->_children as $child) {
                if (!$this->_alive($child)) {
                    $this->_start($worker);
                }
            }

            if ((rand(1, 10000) == 1) && !pcntl_fork()) {
                $this->_clearLog();
                exit;
            }

            //print "round --------".date("Y-m-d H:i:s")."---------\n";
            sleep(3);
        }
    }

    private function _alive($pid)
    {
        $alive = posix_kill($pid, 0);

        if (!$alive) {
            $this->_print("process done, pid:$pid");
            unset($this->_children[$pid]);
        }

        return $alive;
    }

    private function _start($worker)
    {
        $pid = pcntl_fork();

        if ($pid > 0) {
            $this->_print("start process, pid:$pid");
            $this->_children[$pid] = $pid;
            return;
        }

        $this->_runWorker($worker);
        exit;
    }

    private function _runQueueTask($worker_func, $value)
    {
        $worker = array();
        if ($worker_func) {
            $worker = $worker_func;
            $method = new \ReflectionMethod($worker_func[0], $worker_func[1]);
            if(!$method->isStatic()){
                $class=new \ReflectionClass($worker_func[0]);
                $worker[0] = $class->newInstance();
            }
        }

        if (!is_callable($worker)) {
            throw new  \Exception(json_encode($worker) . " is invalid");//may be 'class not found'
        }

        $start_time = microtime(true);
        call_user_func($worker_func, $value);

        $consume = round((microtime(true) - $start_time) * 1000);

        $this->_task["status"] = 'run worker function done';
        $this->_runTaskDone($value, $consume);
    }

    private function _runTaskDone($value, $consume = 0)
    {
        self::addLog(array("consume" => $consume));

        if ($this->_logDays > 0) {
            $this->_log(json_encode($value));
        }

        if ($this->_debug) {
            $this->_print(json_encode($value));
        }

        $this->_task["status"] = 'consumed';
    }

    private function _runWorker($worker)
    {
        $i = 0;
        $ppid = posix_getppid();
        while ($i < $worker["max_task"]) {
            $_ppid = posix_getppid();
            if ($ppid != $_ppid) {//父进程退出
                $this->_print("auto exit, pid:" . getmypid() . ", ppid:$ppid, complete:$i, parent is died!");
                exit;
            }

            try {
                $task = $this->_queue->getQueue($this->_job);
                if (!$task) {
                    sleep(rand(3, 10));
                    continue;
                }
                $this->_task = array("data" => $task, "status" => "");
                $this->_runQueueTask($worker["worker"], $task);
            } catch (\Exception $e) {
                $this->_printError("caught exception (" . $e->getMessage() . "), pid:" . getmypid() . ", ppid:$ppid, complete:$i");

                if ($this->_logDays > 0) {
                    $this->_logError(json_encode($this->_task) . " [caught exception (" . $e->getMessage() . "), complete:$i ]");
                }

                if (!empty($task)) {
                    $this->_queue->addRescueQueue($this->_job, $task);
                    $this->_task["status"] = 'rescued';
                }
                sleep(rand(3, 10));//出错时消费得慢一点
                continue;
            }
            $i++;
        }
    }

    private function _print($str)
    {
        if ($this->_runLogFile) {
            $this->_writeLog($str, $this->_runLogFile);
        }

        print $str . "\n";
    }

    private function _printError($str)
    {
        if ($this->_runLogFile) {
            $this->_writeLog($str, $this->_runLogFile . ".wf");
        }

        print $str . "\n";
    }

    private function _log($str)
    {
        if ($this->_processLogFile) {
            $this->_writeLog($str, $this->_processLogFile);
        }
    }

    private function _logError($str)
    {
        if ($this->_processLogFile) {
            $this->_writeLog($str, $this->_processLogFile . ".wf");
        }
    }

    private function _writeLog($str, $logfile)
    {
        $userlog = "";
        if (self::$_user_log) {
            foreach (self::$_user_log as $k => $v) {
                $userlog .= $k . "[" . (is_array($v) ? json_encode($v) : $v) . "]\t";
            }

            self::$_user_log = array();
        }
        $log = date("m-d H:i:s") . "\t[" . $this->_job . "]\t" . $userlog . $str . "\n";
        error_log($log, 3, $logfile . '.' . date("Ymd"));
    }

    private function _writeTaskLog($str, $logfile)
    {
        $log = date("m-d H:i:s") . "\t[" . $this->_job . "]\t" . $str . "\n";
        $logfile .= '.' . date("Ymd");

        touch($logfile) && @chmod($logfile, 0777);
        error_log($log, 3, $logfile);
    }

    private function _clearLog()
    {
        $this->_print("clear logs, pid:" . getmypid());

        if ($this->_runLogFile) {
            $this->_unlinkLogFile($this->_runLogFile);
        }

        if ($this->_processLogFile) {
            $this->_unlinkLogFile($this->_processLogFile);
        }

        if ($this->_taskLogFile) {
            $this->_unlinkLogFile($this->_taskLogFile);
        }
    }

    private function _unlinkLogFile($logpath)
    {
        $logfile = basename($logpath);
        $logdir = dirname($logpath);
        $logs = scandir($logdir);

        foreach ($logs as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }

            if ($logfile == substr($file, 0, -9) && strtotime(substr($file, -8)) <= strtotime("-" . $this->_logDays . " day")) {
                unlink($logdir . '/' . $file);
            }
        }
    }

    public function _getLastError()
    {
        if ($e = error_get_last()) {
            $this->_printError("ERROR: " . $e['message'] . " in " . $e['file'] . ' line ' . $e['line'] . "\t" . json_encode($this->_task));
            $this->_queue->addRescueQueue($this->_job, $this->_task["data"]);
        }
    }

    public function _catchError($error, $message, $file, $line)
    {
        switch ($error) {
            case E_ERROR:
                $type = 'ERROR';
                break;
            case E_WARNING:
                $type = 'WARNING';
                break;
            default:
                return;
        }
        /*
        * 兼容PHP bug, PDO throw exception 同时 warnning
        * https://bugs.php.net/bug.php?id=63812
        */
        if (false !== strpos($message, "MySQL server has gone away") || false !== strpos($message, "Error reading result")) {
            return;
        }

        $this->_printError($type . ': ' . $message . ' in line ' . $line . ' of file ' . $file . "\t" . json_encode($this->_task));
        $this->_queue->addRescueQueue($this->_job, $this->_task["data"]);
    }

    public function rescue($date)
    {
        $this->_print("rescue task, pid:" . getmypid());
        return $this->_queue->rescueQueue($this->_job, $date);
    }

    private function _createLogDir($logRoot)
    {
        if (!is_dir($logRoot)) {
            @mkdir($logRoot, 0777, true);
        }
        $logfile[] = $logRoot . "/run";
        $logfile[] = $logRoot . "/job";
        $logfile[] = $logRoot . "/task";
        $logfile[] = $logRoot . "/app";
        foreach ($logfile as $value) {
            if (!is_dir($value)) {
                @mkdir($value, 0777, true);
            }
        }
    }

}
