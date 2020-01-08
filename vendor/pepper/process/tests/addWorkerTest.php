<?php
use PHPUnit\Framework\TestCase;
require_once "TestWorker.php";
class addWorkerTest extends TestCase
{
    /**
     * @dataProvider addWorker
     */
    public function testAddWorker($worker)
    {
        if ($worker) {
            $method = new \ReflectionMethod($worker[0], $worker[1]);
            if (!$method->isStatic()) {
                $class = new \ReflectionClass($worker[0]);
                $worker[0] = $class->newInstance();
            }
        }
        self::assertTrue(is_callable($worker));

    }

    public function addWorker()
    {
        $worker1 = array("TestWorker", "execute1");
        $worker2 = array("TestWorker", "execute2");
        return [
            [$worker1],
            [$worker2]
        ];

    }

}