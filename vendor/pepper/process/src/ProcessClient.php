<?php

namespace Pepper\Process;

use Pepper\Process\Base\Job;
use Pepper\Lib\SimpleConfig;

const QUEUE_TYPE_NORMAL   = 1;//普通队列
const QUEUE_TYPE_PRIORITY = 2;//优先队列
const QUEUE_TYPE_ROUTE    = 3;//分发队列
const QUEUE_TYPE_DELAY    = 4;//延迟队列
SimpleConfig::loadStandardConfigFile(dirname(__DIR__) . '/config', 'process', 'process_conf.php');

class ProcessClient
{
    const CMD_START = "start";
    const CMD_RESTART = "restart";
    const CMD_STOP = "stop";
    const CMD_LIST = "ls";//list process
    const CMD_RESCUE = "rs";//rescue task

    private $_product = "";
    private $_runLogFile = "";
    private $_processLogFile = "";
    private $_debug = false;
    private $_logDays = 0;
    private $_argvs = array();
    private $_options = array();
    private $_worker = array();
    private $_handler = "";

    public function __construct($product)
    {
        $this->_product = $product;
    }

    /**
     * @param $product
     * @return ProcessClient
     */
    public static function getInstance($product)
    {
        static $instance = [];
        isset($instance[$product]) || $instance[$product] = new ProcessClient($product);

        return $instance[$product];
    }

    public function isAlive($job)
    {
        return Job::getInstance($this->_product, $job)->isQueueAlive();
    }

    public function getTaskCount($job)
    {
        return Job::getInstance($this->_product, $job)->getTaskCount();
    }

    public function getTaskBakCount($job, $date = "")
    {
        return Job::getInstance($this->_product, $job)->getTaskBakCount($date);
    }

    public static function addLog($userlog = array())
    {
        Job::addLog((array)$userlog);
    }

    public function addTask($job, $params, $rank = null)
    {
        return Job::getInstance($this->_product, $job)->addTask($params, $rank);
    }

    public function rescue($job, $date)
    {
        return Job::getInstance($this->_product, $job)->rescue($date);
    }

    public function addWorker($job, $worker, $max_children = 1, $max_task = 1000)
    {
        $this->_worker[strtolower($job)] = array("worker" => $worker, "max_children" => $max_children, "max_task" => $max_task);
        return $this;
    }

    public function run()
    {
        $this->_parseArgvs();

        if ($file = $this->_argvs[0]) {
            if (substr($file, 0, 1) == "/") {
                $this->_handler = $file;
            } else {
                chdir(dirname($file));
                $this->_handler = getcwd() . "/" . basename($file);
            }
        }

        $cmd = strtolower($this->_argvs[1]);
        $op_job = isset($this->_argvs[2]) ? strtolower($this->_argvs[2]) : "";

        if ($op_job && !isset($this->_worker[$op_job])) {
            print "$op_job is not defined \n";
            exit;
        }

        switch ($cmd) {
            case self::CMD_START:
                $this->_startJob($op_job);
                break;
            case self::CMD_RESTART:
                $this->_stopJob($op_job);
                $this->_startJob($op_job, true);
                break;
            case self::CMD_STOP:
                $this->_stopJob($op_job);
                break;
            case self::CMD_LIST:
                $this->_listJob($op_job);
                break;
            case self::CMD_RESCUE:
                $this->_rescueJob($op_job);
                break;
            default:
                $this->_showHelp();
        }
    }

    private function _parseArgvs()
    {
        $this->_argvs = $GLOBALS['argv'];
        $opts = getopt("hHdDp:P:r:R:n:N:");
        $_user_opts = array();
        foreach ($opts as $opt => $val) {
            if (is_array($val)) {
                $val = array_pop($val);
            }

            switch (strtolower($opt)) {
                case "d":
                    $this->_debug = true;
                    break;
                case "h":
                    $this->_showHelp();
                    break;
                case "p":
                    $_user_opts["p"] = "-" . $opt . $val;
                    $this->_processLogFile = $val;
                    break;
                case "r":
                    $_user_opts["r"] = "-" . $opt . $val;
                    $this->_runLogFile = $val;
                    break;
                case "n":
                    $_user_opts["n"] = "-" . $opt . $val;

                    if (is_numeric($val)) {
                        $this->_logDays = $val;
                    }
                    break;
                default:
            }
        }

        $_argvs = array();
        $jump_opt_val = false;
        foreach ($this->_argvs as $k => $v) {
            if ("-" == substr($v, 0, 1)) {
                $jump_opt_val = false;
                if (strlen($v) == 2 && isset($_user_opts[substr($v, 1, 1)])) {
                    $jump_opt_val = true;
                }
            } else {
                if ($jump_opt_val) {
                    $jump_opt_val = false;
                } else {
                    $_argvs[] = $v;
                }
            }
        }

        $this->_argvs = $_argvs;
        $this->_options = $_user_opts;
    }

    private function _stopJob($op_job = null)
    {
        if ($this->_isStoppingJob()) {
            print "stop failed! job" . ($op_job ? ":" . $op_job : "") . " is stopping or restarting with other process!\n";
            return;
        }

        $workers = $this->_getMainWorkers($op_job);
        if (!$workers) {
            print "not found!\n";
            return;
        }

        foreach ($workers as $pid => $job) {
            if ($op_job && $job != $op_job) {
                continue;
            }

            $killed = $this->_stopProcess($pid);
            print "send signal to process, pid:$pid " . ($killed ? "success" : "failed") . "\n";
            print "job:$job  pid:$pid is stopping...\n";
        }

        $done = false;
        while (!$done) {
            $done = true;
            foreach ($workers as $pid => $job) {
                if ($op_job && $job != $op_job) {
                    continue;
                }

                if ($this->_isAliveProcess($pid)) {
                    $done = false;
                } else {
                    unset($workers[$pid]);
                    print "\njob:$job is stopped!";
                }
            }

            if ($done) {
                print "\n";
            } else {
                print ".";
                sleep(1);
            }
        }
    }

    private function _isAliveProcess($pid)
    {
        return posix_kill($pid, 0);
    }

    private function _isStoppingJob()
    {
        $rs = shell_exec('ps -ef|grep "' . $this->_handler . '" | grep " restart \| stop " | grep -v grep |  grep -v ' . posix_getppid() . '');
        if ($rs && $list = explode("\n", trim($rs))) {
            return count($list) > 0;
        }

        return false;
    }

    private function _stopProcess($pid)
    {
        return posix_kill($pid, SIGKILL);
    }

    private function _listJob($op_job = null)
    {
        $workers = array_unique(array_values($this->_getMainWorkers($op_job)) + array_keys($this->_worker));

        foreach ($workers as $job) {
            if ($op_job && $job != $op_job) {
                continue;
            }

            $num = 0;
            $rs = $this->_getWorkers($job);
            if ($rs && $list = explode("\n", trim($rs))) {
                $num = count($list);
            }

            print "job[$job] process list, " . (isset($this->_worker[$job]["max_children"]) ? "set " . $this->_worker[$job]["max_children"] . " children," : "") . "launched " . ($num > 1 ? $num - 1 : 0) . ":\n";

            echo $rs ? $rs : "not found!\n";
        }
    }

    private function _rescueJob($op_job = null)
    {
        $date = $this->_argvs[3];
        $workers = array_unique(array_values($this->_getMainWorkers($op_job)) + array_keys($this->_worker));

        foreach ($workers as $job) {
            if ($op_job && $job != $op_job) {
                continue;
            }

            print "job:$job is rescuing\n";
            $job_instance = Job::getInstance($this->_product, $job);
            $date = $date ? $date : date("ymd");
            $job_instance->rescue($date);

            print "job:$job is rescued\n";
        }
    }

    private function _getMainWorkers($op_job = null)
    {
        $rs = shell_exec('ps -ef|grep "_wmasync_"|grep " start "' . ($op_job ? ' | grep "' . $op_job . '"' : '') . '|grep "' . $this->_handler . '" | grep -v grep|awk \'{print $2" "$3" "$(NF-3)}\'');

        $lines = explode("\n", trim($rs));

        $pids = $ppids = array();
        foreach ($lines as $line) {
            if ($line) {
                $pid_ppid = explode(" ", $line);
                if (1 == $pid_ppid[1] || 0 == $pid_ppid[1]) {
                    $pids[$pid_ppid[0]] = $pid_ppid[2];
                }
            }
        }
        return $pids;
    }

    private function _getWorkers($job)
    {
        return shell_exec('ps -ef|grep "_wmasync_"|grep " start ' . $job . ' _' . $this->_product . '_"| grep -v grep');
    }

    private function _startJob($op_job = null, $is_restart = false)
    {
        $php = $_SERVER["_"];
        $live_workers = array();
        $is_child_process = isset($this->_argvs[4]) ? preg_match("/^_(\d+)_$/", $this->_argvs[4]) : false;

        if (!$is_restart && !$is_child_process) {
            $live_workers = $this->_getMainWorkers($op_job);
        }

        foreach ($this->_worker as $job => $worker) {
            if ($op_job && $job != $op_job) {
                continue;
            }

            if (!$is_child_process) {
                if ($this->_isStoppingJob()) {
                    print "job:$job is stopping or restarting with other process!\n";
                    continue;
                }

                if (!$is_restart && in_array($job, $live_workers)) {
                    print "job:$job is already running!\n";
                    continue;
                }
            }

            if ($is_child_process || $this->_debug) {
                $job_instance = Job::getInstance($this->_product, $job);

                if ($this->_runLogFile) {
                    $job_instance->setRunLogPath($this->_runLogFile);
                }

                if ($this->_processLogFile) {
                    $job_instance->setProcessLogPath($this->_processLogFile);
                }

                if ($this->_logDays) {
                    $job_instance->setLogDays($this->_logDays);
                }

                if ($this->_debug) {
                    $job_instance->setDebug(true);
                }

                $job_instance->startWorker($worker);
            } else {
                print "job:$job is starting...\n";
                exec("nohup " . $php . " " . $this->_handler . " " . implode(" ", $this->_options) . " " . self::CMD_START . " " . $job . " _" . $this->_product . "_ " . " _" . getmypid() . "_ _wmasync_ >> /dev/null 2>&1 &", $retval, $status);
                if ($status > 0) {
                    print "Error:\n";
                    print implode("\n", $retval);
                } else {
                    print "sucess!\n";
                }
            }
        }
    }

    private function _showHelp()
    {
        print "Usage:\n php " . $this->_handler . " [options] <command> [job name] \n\n";
        print " options:\n\t-r <file>\trecord the runtime logs in <file>\n";
        print " \t-p <file>\trecord the process logs in <file>\n";
        print " \t-n <number>\tkeep the logs for <number> days\n";
        print " \t-d         \tdebug mode\n\n";
        print " command:\n\tstart | stop | restart | ls | rs \n\n";
        print " job name:\n\tdo all jobs without it\n\n";
        exit;
    }
}
