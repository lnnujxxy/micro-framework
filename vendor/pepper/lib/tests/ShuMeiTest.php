<?php


use Pepper\Lib\ShuMei;
use PHPUnit\Framework\TestCase;

class ShuMeiTest extends TestCase
{

    public function testLogin()
    {
        $uid = 20000000;
        $smid = '201709161222409077181e8504e936cf1ecb537e8e9ada015f1da34432362b';
        $ip = '112.97.58.150';
        $timestamp = microtime(true) * 1000;
        $phone = '13888881234';
        $result = ShuMei::login($uid, $smid, $ip, $timestamp, $phone);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('requestId', $result);
        $this->assertArrayHasKey('detail', $result);
        $this->assertArrayHasKey('token', $result['detail']);
        $this->assertArrayHasKey('riskType', $result['detail']['token']);
        /*
        array(6) {
          'code' =>
          int(1100)
          'message' =>
          string(6) "成功"
          'requestId' =>
          string(32) "dd6492ec16c768b700216874fa7f69b7"
          'riskLevel' =>
          string(4) "PASS"
          'score' =>
          int(0)
          'detail' =>
          array(4) {
            'description' =>
            string(6) "正常"
            'model' =>
            string(5) "M1000"
            'relatedItems' =>
            array(0) {
            }
            'token' =>
            array(7) {
              'tokenId' =>
              string(0) ""
              'score' =>
              int(0)
              'riskType' =>
              string(0) ""
              'riskLevel' =>
              string(0) ""
              'riskReason' =>
              string(0) ""
              'groupId' =>
              string(0) ""
              'groupSize' =>
              int(0)
            }
          }
        }
        */
    }


    public function testFission()
    {
        $uid = 20000000;
        $smid = '201709161222409077181e8504e936cf1ecb537e8e9ada015f1da34432362b';
        $ip = '112.97.58.150';
        $timestamp = microtime(true) * 1000;
        $phone = '13888881234';
        $channel = 'token';
        $invitor = 20000001;
        $invitorPhone = '';
        $result = ShuMei::fission($uid, $smid, $ip, $timestamp, $channel, $phone, $invitor, $invitorPhone);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('requestId', $result);
        $this->assertArrayHasKey('detail', $result);
        $this->assertArrayHasKey('token', $result['detail']);
        $this->assertArrayHasKey('riskType', $result['detail']['token']);
        var_dump($result);
        /*
array(6) {
  'code' =>
  int(1100)
  'message' =>
  string(6) "成功"
  'requestId' =>
  string(32) "a51e015f4c849853bb84055185822537"
  'riskLevel' =>
  string(4) "PASS"
  'score' =>
  int(0)
  'detail' =>
  array(4) {
    'description' =>
    string(6) "正常"
    'hits' =>
    array(0) {
    }
    'model' =>
    string(5) "M1000"
    'token' =>
    array(7) {
      'tokenId' =>
      string(0) ""
      'score' =>
      int(0)
      'riskType' =>
      string(0) ""
      'riskLevel' =>
      string(0) ""
      'riskReason' =>
      string(0) ""
      'groupId' =>
      string(0) ""
      'groupSize' =>
      int(0)
    }
  }
}
         */
    }

    public function testWithdraw()
    {
        $uid = 20000000;
        $smid = '201709161222409077181e8504e936cf1ecb537e8e9ada015f1da34432362b';
        $ip = '112.97.58.150';
        $timestamp = microtime(true) * 1000;
        $phone = '13888881234';
        $amount = 200;
        $account = 'asdfjajjj@qq.com';
        $accountType = 'alipay';
        $result = ShuMei::withdraw($uid, $smid, $ip, $timestamp, $phone, $amount, $account, $accountType);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('requestId', $result);
        $this->assertArrayHasKey('detail', $result);
        $this->assertArrayHasKey('token', $result['detail']);
        $this->assertArrayHasKey('riskType', $result['detail']['token']);
        $this->assertArrayHasKey('riskLevel', $result['detail']['token']);
        /*
array(6) {
  'code' =>
  int(1100)
  'message' =>
  string(6) "成功"
  'requestId' =>
  string(32) "7e926fe0ec000b6d82e3124c5c6edcb2"
  'riskLevel' =>
  string(4) "PASS"
  'score' =>
  int(0)
  'detail' =>
  array(4) {
    'description' =>
    string(6) "正常"
    'hits' =>
    array(0) {
    }
    'model' =>
    string(5) "M1000"
    'token' =>
    array(7) {
      'tokenId' =>
      string(0) ""
      'score' =>
      int(0)
      'riskType' =>
      string(0) ""
      'riskLevel' =>
      string(0) ""
      'riskReason' =>
      string(0) ""
      'groupId' =>
      string(0) ""
      'groupSize' =>
      int(0)
    }
  }
}
         */
    }

    public function testRegister()
    {
        $uid = 20000000;
        $smid = '201709161222409077181e8504e936cf1ecb537e8e9ada015f1da34432362b';
        $ip = '112.97.58.150';
        $timestamp = microtime(true) * 1000;
        $phone = '13888881234';
        $channel = 'phone';
        $nickname = 'nemo';
        $result = ShuMei::register($uid, $smid, $ip, $timestamp, $channel, $phone, $nickname);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('requestId', $result);
        $this->assertArrayHasKey('detail', $result);
        $this->assertArrayHasKey('token', $result['detail']);
        $this->assertArrayHasKey('riskType', $result['detail']['token']);
        $this->assertArrayHasKey('riskLevel', $result['detail']['token']);
        /*
array(6) {
  'code' =>
  int(1100)
  'message' =>
  string(6) "成功"
  'requestId' =>
  string(32) "e76f3df5436556178aec1e526f7fc9ae"
  'riskLevel' =>
  string(4) "PASS"
  'score' =>
  int(0)
  'detail' =>
  array(5) {
    'description' =>
    string(6) "正常"
    'model' =>
    string(5) "M1000"
    'relatedItems' =>
    array(0) {
    }
    'riskInfo' =>
    array(0) {
    }
    'token' =>
    array(7) {
      'tokenId' =>
      string(0) ""
      'score' =>
      int(0)
      'riskType' =>
      string(0) ""
      'riskLevel' =>
      string(0) ""
      'riskReason' =>
      string(0) ""
      'groupId' =>
      string(0) ""
      'groupSize' =>
      int(0)
    }
  }
}
         */
    }

    public function testBrowse()
    {
        $uid = 20000000;
        $smid = '201709161222409077181e8504e936cf1ecb537e8e9ada015f1da34432362b';
        $ip = '112.97.58.150';
        $timestamp = microtime(true) * 1000;
        $author = 20000001;
        $contentId = time();
        $contentType = 'video';
        $result = ShuMei::browse($uid, $smid, $ip, $timestamp, $author, $contentId, $contentType);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('requestId', $result);
        $this->assertArrayHasKey('detail', $result);
        $this->assertArrayHasKey('token', $result['detail']);
        $this->assertArrayHasKey('riskType', $result['detail']['token']);
        $this->assertArrayHasKey('riskLevel', $result['detail']['token']);
        /*
        array(6) {
          'code' =>
          int(1100)
          'message' =>
          string(6) "成功"
          'requestId' =>
          string(32) "aab20417d0d92b1d887bc336f931b336"
          'riskLevel' =>
          string(4) "PASS"
          'score' =>
          int(0)
          'detail' =>
          array(4) {
            'description' =>
            string(6) "正常"
            'hits' =>
            array(0) {
            }
            'model' =>
            string(5) "M1000"
            'token' =>
            array(7) {
              'tokenId' =>
              string(0) ""
              'score' =>
              int(0)
              'riskType' =>
              string(0) ""
              'riskLevel' =>
              string(0) ""
              'riskReason' =>
              string(0) ""
              'groupId' =>
              string(0) ""
              'groupSize' =>
              int(0)
            }
          }
        }
         */
    }
}
