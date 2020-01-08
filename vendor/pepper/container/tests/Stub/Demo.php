<?php
namespace Pepper\Container\Tests\Stub;


class Demo
{
    private $arg0;
    private $arg1;

    public function __construct($arg0, $arg1)
    {

        $this->arg0 = $arg0;
        $this->arg1 = $arg1;
    }

    /**
     * @return mixed
     */
    public function getArg0()
    {
        return $this->arg0;
    }

    /**
     * @return mixed
     */
    public function getArg1()
    {
        return $this->arg1;
    }
    
}