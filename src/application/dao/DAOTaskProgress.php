<?php
namespace Pepper\Framework\Dao;

use Pepper\Framework\Model\Task;

class DAOTaskProgress extends DAOProxy
{
    /*
     * __construct
     */
    public function __construct($userid) {
        parent::__construct($userid);
        $this->setTableName("task_progress");
    }

    public function updateProgress($taskid, $type, $num, $goal, $expired, $extend = '') {
        if ($task = $this->getTaskByTaskid($taskid)) {
            if ($task['goal'] > $task['num']) {
                if ($task['goal'] > $task['num'] + $num) {
                    $arr['num'] = $task['num'] + $num;
                } else {
                    $arr['num'] = $task['goal'];
                    $arr['state'] = Task::STATE_FINISH; // 完成
                }
                $arr['extend'] = $extend;
                return $this->update($this->getTableName(), $arr, " uid = ? AND taskid = ?", [$this->getSplitId(), $taskid]) ? true : false;
            } else {
                return false;
            }
        } else {
            $arr['uid']     = $this->getSplitId();
            $arr['taskid']  = $taskid;
            $arr['type']    = $type;
            $arr['num']     = $num;
            $arr['goal']    = $goal;
            $arr['state']   = $goal <= $num ? 1 : 0;
            $arr['expired'] = $expired;
            $arr['extend']  = $extend;
            return $this->replace($this->getTableName(), $arr);
        }
    }

    public function getProgresses() {
        $sql = "SELECT " . $this->getFields() . " FROM ". $this->getTableName() . " WHERE uid = ? AND expired >= " . time();
        return $this->getAll($sql, [$this->getSplitId()]);
    }

    public function getProgress($taskid) {
        $sql = "SELECT " . $this->getFields() . " FROM ". $this->getTableName() . " WHERE uid = ? AND taskid = ? AND expired >= " . time();
        return $this->getRow($sql, [$this->getSplitId(), $taskid]);
    }

    public function clearProgress() {
        $sql = "DELETE FROM " . $this->getTableName() . " WHERE uid = ? ";
        return $this->execute($sql, [$this->getSplitId()]);
    }

    public function updateRecord($taskid, $record) {
        return $this->update($this->getTableName(), $record, " taskid = ? ", $taskid);
    }

    public function getTaskByTaskid($taskid) {
        $sql = "SELECT " . $this->getFields() . " FROM ". $this->getTableName() . " WHERE uid = ? AND taskid = ? AND expired >= " . time();
        return $this->getRow($sql, [$this->getSplitId(), $taskid]);
    }

    private function getFields() {
        return "id, uid, taskid, type, num, goal, award_num, expired, state, extend, created_at, updated_at";
    }
}
