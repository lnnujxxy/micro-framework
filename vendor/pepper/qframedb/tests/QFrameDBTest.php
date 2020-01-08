<?php

class QFrameDBTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Pepper\QFrameDB\QFrameDBPDO
     */
    protected $db;

    protected $config = [
        "driver"      => "mysql",
        "host"        => "154.8.195.226",
        "port"        => "3306",
        "username"    => "root",
        "password"    => "test123",
        "charset"     => "utf8mb4",
        "database"    => "demo",
        "persistent"  => false,
        "unix_socket" => "",
        "options"     => array(PDO::ATTR_TIMEOUT => 3),
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->db = \Pepper\QFrameDB\QFrameDB::getInstance($this->config);
    }


    function testInstance()
    {
        $instance = \Pepper\QFrameDB\QFrameDB::getInstance();
        $this->assertTrue($instance !== null);
        $instance = \Pepper\QFrameDB\QFrameDB::getInstance($this->config);
        $this->assertTrue($instance !== null);

    }

    function testGetRow()
    {
        $sql = "select * from live order by liveid desc limit 1";
        $result = $this->db->getRow($sql);
        $this->assertArrayHasKey('liveid', $result);
    }

    function testGetOne(){
        $sql = "select liveid from live order by liveid desc limit 1";
        $liveid = $this->db->getOne($sql);
        $this->assertTrue(is_numeric($liveid));
    }

    function testGetAll(){
        $sql = "select * from live order by liveid desc limit 3";
        $result = $this->db->getAll($sql);
        $this->assertEquals(3, count($result));
    }

    function testGetQuery(){
        $sql = "select * from live order by liveid desc limit 1";
        $result = $this->db->query($sql);
        $this->assertTrue($result instanceof \Pepper\QFrameDB\QFrameDBStatment);
        $row = $result->fetch();
        $this->assertArrayHasKey('liveid', $row);
    }

    function testExecute(){
        $sql = "select * from live order by liveid desc limit 1";
        $result = $this->db->execute($sql);
        $this->assertTrue($result);
    }
}
