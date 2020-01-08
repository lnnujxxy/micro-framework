<?php
use PHPUnit\Framework\TestCase;

use Pepper\Framework\Dao\DAOUserAddress;

class DAOUserTest extends TestCase
{
    /**
     * @dataProvider patchProvider
     */
    public function testAddPatch($userid, $json)
    {
        $data = json_decode($json, true);
        $this->assertTrue((new DAOUserAddress())->addPatch($userid, $data));
    }

    public function testGetPhones()
    {
        $userid = 1;
        $this->assertTrue(count((new DAOUserAddress())->getPhones($userid)) > 0);
    }

    public function patchProvider()
    {
        return [
            [1, '[{"phone":"13401069591","name":"hello1"},{"phone":"13401069590","name":"hello2"}]'],
            [2, '[{"phone":"13401069591","name":"hello1"},{"phone":"13401069592","name":"hello3"}]'],
        ];
    }
}