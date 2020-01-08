<?php

namespace Pepper\Framework\Controller;

use Pepper\Container\Container;
use Pepper\Framework\Dao\DAOComment;
use Pepper\Framework\Lib\Token;
use Pepper\Framework\Model\User;
use Pepper\Process\ProcessClient;

class TestController extends BaseController
{
    /*
     * container
     */
    public function containerAction() {
        $container = Container::getInstance();
        $container->bind('daoComment', function() {
            return new DAOComment(1, 2);
        });
        $instance = Container::getInstance()->make('daoComment');
        $user = $instance->test();
        $this->render($user);
    }

    public function testAction() {
        $val = $this->getRequire('test', 'is_numeric');
        $this->render($val);
    }

    public function tokenAction() {
        $params = [
            'userid' => 1,
            'salt' => 1, // 对应uid
        ];
        $token = Token::makeToken($params);
        $tokenInfo = Token::getTokenInfo($token);
        $this->render($params['userid'] == $tokenInfo['userid']);
    }

    public function workerAction() {
        $data = [
            'userid' => 123,
            'salt' => time(),
        ];
        $params = [
            'token' => Token::makeToken($data),
            'created_at' => time(),
        ];
        ProcessClient::getInstance(PROJECT_NAME)->addTask('test_job2', $params);
        $this->render();
    }
}
