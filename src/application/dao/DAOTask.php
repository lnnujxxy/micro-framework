<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:24
 */

namespace Pepper\Framework\Dao;

class DAOTask extends DAOProxy
{
    public function __construct() {
        parent::__construct();
        $this->setTableName("task");
    }

    public function get($taskid) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE taskid = ?";
        $row = $this->getRow($sql, $taskid);
        if (isset($row['award'])) {
            $row['award'] = json_decode($row['award'], true);
        }
        if (isset($row['period'])) {
            $row['period'] = json_decode($row['period'], true);
        }

        return $row;
    }

    public function gets($taskids) {
        $in  = str_repeat('?,', count($taskids) - 1) . '?';
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() ." WHERE taskid IN (" . $in .")";
        $all = $this->getAll($sql, $taskids);
        foreach ($all as $index=>$row) {
            if (isset($row['award'])) {
                $row['award'] = json_decode($row['award'], true);
            }
            if (isset($row['period'])) {
                $row['period'] = json_decode($row['period'], true);
            }
            $all[$index] = $row;
        }
        return $all;
    }

    public function getTasksByAction($action) {
        $sql = "SELECT " . $this->getFields() . " FROM " . $this->getTableName() . " WHERE action = ?";
        $tasks = $this->getAll($sql, $action);
        foreach ($tasks as $index=>$task) {
            if (isset($task['award'])) {
                $task['award'] = json_decode($task['award'], true);
            }
            if (isset($task['period'])) {
                $task['period'] = json_decode($task['period'], true);
            }
            $tasks[$index] = $task;
        }
        return $tasks;
    }

    private function getFields() {
        return "taskid, type, title, `desc`, award, action, goal, period, isprogress, created_at, updated_at";
    }
}