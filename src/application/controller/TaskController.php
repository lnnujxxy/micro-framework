<?php

namespace Pepper\Framework\Controller;

use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Model\Task;
use Pepper\Framework\Model\User;
use Pepper\Lib\SimpleConfig;

class TaskController extends BaseController
{
    public function executeAction() {
        $userid = Context::get('userid');
        $action = $this->getParam('action');
        $value = $this->getParam('value');

        Task::execute($userid, $action, $value);
        $this->render();
    }

    public function getTasksAction() {
        $tasks = [
            2 => [
                [
                    'type' => 2,
                    'title' => '向佛献祭法器',
                    'desc' => '',
                ]
            ],
            3 => [
                [
                    'type' => 3,
                    'title' => '菩提采摘',
                    'desc' => '每10小时+10',
                ],
                [
                    'type' => 3,
                    'title' => '充值菩提币',
                    'desc' => '1¥=100菩提币',
                ],
            ]
        ];
        $this->render($tasks);
    }

    public function getBlessTasksAction() {
        $tasks = [
            [
                'title' => '分享许愿给微信朋友',
                'desc' => '让朋友送祝福',
            ],
            [
                'title' => '多次许愿',
                'desc' => '赠送免费或付费贡品',
            ],
        ];
        $systemConfig = SimpleConfig::get('SYSTEM_CONFIG');
        if ($systemConfig['is_pass']) {
            array_unshift($tasks, [
                'title' => '分享许愿到朋友圈',
                'desc' => '让朋友送祝福',
            ]);
        }
        $this->render($tasks);
    }

    public function getMeritTasksAction() {
        $userid = Context::get('userid');

        $tasks = Task::getProgresses($userid, Task::MERIT_TASK_IDS);
        $unFinishNum = $canAwardNum = 0;
        foreach ($tasks as $task) {
            if ($task['state'] != Task::STATE_RECEIVE) {
                $unFinishNum++;
            }
            if ($task['state'] == Task::STATE_FINISH) {
                $canAwardNum++;
            }
        }
        $result = [
            'tasks' => $tasks,
            'can_award_num' => $canAwardNum,
            'un_finish_num' => $unFinishNum,
            'merit' => User::getUserInfo($userid)['merit']
        ];
        $this->render($result);
    }

    public function doTaskAction() {
        $userid = Context::get('userid');
        $action = $this->getParam('action');

        Task::doTask($userid, $action);
        $this->render();
    }

    public function receiveTasksAction() {
        $userid = Context::get('userid');
        $taskid = $this->getParam('taskid');

        Interceptor::ensureNotFalse($taskid > 0, ERROR_PARAM_INVALID_FORMAT,'taskid');
        $this->render(Task::receiveAward($userid, $taskid));
    }

    public function songJingAction() {
        $userid = Context::get('userid');
        $nonce = $this->getParam('nonce', '');
        Interceptor::ensureNotFalse(Task::checkSongJing($userid), ERROR_TOAST, '您诵经时间还不到呢!');
        if ($nonce && Task::getSongJingDuration($nonce) <= 3600) {
            Task::incrSongJingDuration($nonce, 60); // 记录诵经时长
            Task::hIncNonce($nonce, 'metric', 1);
        }
        Task::hSetSongJing($userid);
        $this->render();
    }

    public function pickAction() {
        $userid = Context::get('userid');
        $num = $this->getParam('num');
        Interceptor::ensureNotFalse($num > 0, ERROR_PARAM_INVALID_FORMAT,'num');

        Task::pick($userid, $num);
        $this->render();
    }

    public function listTaskLogAction() {
        $userid = Context::get('userid');
        $type = $this->getParam('type');
        $subType = $this->getParam('subType', 0); // 0 所有 1 获取 2 支出
        $offset = $this->getParam('offset');
        $limit = $this->getParam('limit', 20);
        Interceptor::ensureNotFalse($type > 0, ERROR_PARAM_INVALID_FORMAT,'type');

        $list = Task::listTaskLog($userid, $type, $subType, $offset, $limit);
        $this->render($list);
    }

    public function getPickTaskAction() {
        $userid = Context::get('userid');

        $this->render(Task::getPickProgress($userid));
    }
}
