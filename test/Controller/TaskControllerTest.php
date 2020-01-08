<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/11/4
 * Time: 下午4:33
 */
namespace Pepper\Test\Controller;

use GuzzleHttp\Client;
use Pepper\Framework\Dao\DAOTaskProgress;
use Pepper\Framework\Model\Task;
use Pepper\Framework\Model\User;
use PHPUnit\Framework\TestCase;

class TaskControllerTest extends TestCase
{
    private $url = 'framework.xxx.com';
//    private $url = 'test.foxibiji.com';
    private $baseUrl = 'http://127.0.0.1:8080';
//    private $baseUrl = 'http://172.21.0.16';

    const testUid = 23;

    public function setUp() {
        (new DAOTaskProgress(self::testUid))->clearProgress();
    }

    public function testTask()
    {
        $uid = self::testUid;

        Task::doTask($uid, 'login');
        Task::doTask($uid, 'lucky');
        Task::doTask($uid, 'pray_circle');
        Task::doTask($uid, 'help');
        Task::doTask($uid, 'share');
        $client   = new Client([
            'base_uri' => $this->baseUrl,
        ]);
        $response = $client->get('/Task/getMeritTasks?userid='.$uid, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr = json_decode($contents, true);
        $this->assertTrue($arr['code'] == 0);
        $this->assertTrue($arr['data']['tasks'][0]['state'] == 1);
        $this->assertTrue($arr['data']['tasks'][1]['state'] == 1);
        $this->assertTrue($arr['data']['tasks'][2]['state'] == 1);
        $this->assertTrue($arr['data']['tasks'][3]['state'] == 1);
        $this->assertTrue($arr['data']['tasks'][4]['state'] == 1);


        $response = $client->get('/Task/receiveTasks?userid='.$uid, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'taskid' => 1,
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr = json_decode($contents, true);
        $this->assertTrue($arr['code'] == 0);

        $response = $client->get('/Task/receiveTasks?userid='.$uid, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'taskid' => 5,
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr = json_decode($contents, true);
        $this->assertTrue($arr['code'] == 0);

        $client   = new Client([
            'base_uri' => $this->baseUrl,
        ]);
        $response = $client->get('/Task/getMeritTasks?userid='.$uid, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr = json_decode($contents, true);
        $this->assertTrue($arr['code'] == 0);
        $this->assertTrue($arr['data']['tasks'][0]['state'] == 2);
        $this->assertTrue($arr['data']['tasks'][4]['state'] == 0);

        Task::doTask($uid, 'login');
        Task::doTask($uid, 'share');
        $response = $client->get('/Task/getMeritTasks?userid='.$uid, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr = json_decode($contents, true);
        $this->assertTrue($arr['code'] == 0);
        $this->assertTrue($arr['data']['tasks'][0]['state'] == 2);
        $this->assertTrue($arr['data']['tasks'][4]['state'] == 1);

        sleep(4);
        $oldUserInfo = User::getUserInfo($uid);
        $response = $client->get('/Task/receiveTasks?userid='.$uid, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'taskid' => 5,
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr = json_decode($contents, true);
        $this->assertTrue($arr['code'] == 0);
        $newUserInfo = User::getUserInfo($uid);
        $this->assertTrue($newUserInfo['merit'] - $oldUserInfo['merit'] == 10);
        $this->assertTrue($newUserInfo['bodhi'] - $oldUserInfo['bodhi'] == 10);
    }
}