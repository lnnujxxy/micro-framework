<?php

namespace Pepper\Container\Tests\Stub;

class Singleton
{
    private $value;

    public function __construct()
    {

    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value){
        $this->value = $value;
    }
}