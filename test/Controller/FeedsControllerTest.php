<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/11/4
 * Time: 下午4:33
 */
namespace Pepper\Test\Controller;

use GuzzleHttp\Client;
use Pepper\Framework\Model\Feeds;
use Pepper\Framework\Model\Goods;
use Pepper\Framework\Model\User;
use PHPUnit\Framework\TestCase;

class FeedsControllerTest extends TestCase
{
    private $url = 'framework.xxx.com';
//    private $url = 'test.foxibiji.com';
    private $baseUrl = 'http://127.0.0.1:8080';
//    private $baseUrl = 'http://172.21.0.16';

    const testUids = [23, 24];

    public function setUp() {
        foreach (self::testUids as $uid) {
            Feeds::clearFeeds($uid);
        }
    }

    public function testFeeds()
    {
        $goodId = 2;
        $goodsInfo = Goods::getGoodInfo($goodId);

        $uid1 = self::testUids[0];
        $userInfo = User::getFormatUserInfo($uid1, true);

        $client   = new Client([
            'base_uri' => $this->baseUrl,
        ]);
        // 发起
        $response = $client->post('/Feeds/addFeed?userid='.$uid1, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'type'   => Feeds::PENANCE_TYPE_ID,
                'ispublic'     => 1,
                'object_id'    => 1,
                'num' => 3,
                'content' => [
                    'name' => '母亲',
                    'content' => 'test',
                    'avatar' => 'https://goods-1257256615.file.myqcloud.com/bf91ea8d164a07dbac4beacfbbfbd87a.png',
                ],
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr1 = json_decode($contents, true);
        $this->assertTrue($arr1['code'] == 0);
        $this->assertTrue(array_key_exists('relateid', $arr1['data']));
        $this->assertTrue(array_key_exists('type', $arr1['data']));

        // 获取信息
        $response = $client->post('/Feeds/getFeedInfo?uid='.$uid1, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'relateid' => $arr1['data']['relateid'],
                'type' => $arr1['data']['type'],
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr = json_decode($contents, true);
        $this->assertTrue($arr['data']['content']['content'] == 'test');
        $oldBless = $arr['data']['bless'];

        $oldBodhi = $userInfo['bodhi'];
        if ($oldBodhi < $goodsInfo['price']) {
            $diff = $goodsInfo['price'] - $oldBodhi;
            User::updateValue($uid1, 'bodhi', $diff);
        }

        $response = $client->post('/Goods/buyGoods?userid='.$uid1, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'goods_id' => $goodId,
                'relateid' => $arr1['data']['relateid'],
                'type' => $arr1['data']['type'],
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr2 = json_decode($contents, true);
        $this->assertTrue($arr2['code'] == 0);
        $this->assertTrue(array_key_exists('nonce', $arr2['data']));
        $userInfo2 = User::getFormatUserInfo($uid1, true);
        $this->assertTrue($oldBodhi - $goodsInfo['price'] == $userInfo2['bodhi']);

        $response = $client->post('/Feeds/endFeed?userid='.$uid1, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'goods_id' => 2,
                'relateid' => $arr1['data']['relateid'],
                'type' => $arr1['data']['type'],
                'nonce' => $arr2['data']['nonce'],
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr = json_decode($contents, true);
        $this->assertTrue($arr['code'] == 0);

        $response = $client->post('/Feeds/getFeedInfo?uid='.$uid1, [
            'headers'     => [
                'Host' => $this->url,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'relateid' => $arr1['data']['relateid'],
                'type' => $arr1['data']['type'],
            ])
        ]);
        $body     = $response->getBody();
        $contents = $body->getContents();
        $this->assertJson($contents);
        $arr = json_decode($contents, true);
        $newBless = $arr['data']['bless'];
        $this->assertTrue($newBless > $oldBless);
    }
}