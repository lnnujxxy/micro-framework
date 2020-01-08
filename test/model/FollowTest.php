<?php
use PHPUnit\Framework\TestCase;
use Pepper\Framework\Model\Follow;

class FollowTest extends TestCase
{
    public function testFollow()
    {
        $uid = 10000;
        $fid = 20000;
        Follow::cancelFollow($uid, $fid);
        $followed = Follow::isFollowed($uid, $fid);
        $this->assertFalse($followed[$fid]);
        Follow::addFollow($uid, $fid);
        $followed = Follow::isFollowed($uid, $fid);
        $this->assertTrue($followed[$fid]);
    }
}
?>