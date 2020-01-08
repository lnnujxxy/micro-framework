<?php
use PHPUnit\Framework\TestCase;
use Pepper\Framework\Lib\Token;

class TokenTest extends TestCase
{
    public function testToken()
    {
        $data = [
            'userid' => 123,
            'salt' => md5(123),
        ];

        $token = Token::makeToken($data);
        $this->assertTrue(strlen($token) == 36);

        $userid = Token::getTokenInfo($token, "userid");
        $this->assertTrue($userid == $data["userid"]);
        $this->assertTrue(Token::checkToken($token));
    }
}