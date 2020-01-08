<?php

namespace Pepper\Container\Tests;

use Pepper\Container\Container;
use Pepper\Container\Tests\Stub\Demo;
use Pepper\Container\Tests\Stub\Singleton;
use Pepper\Testing\UnitTest;

class ContainerTest extends \PHPUnit\Framework\TestCase
{
    public function testMakeWork()
    {
        $container = Container::getInstance();
        $demoInstance = $container->make(Demo::class,["arg0"=>0,"arg1"=>1]);
        $this->assertInstanceOf(Demo::class, $demoInstance, 'Container make instance failed');
        $this->assertEquals(0, $demoInstance->getArg0(), 'demoInstance made by container getArg0 failed');
        $this->assertEquals(1, $demoInstance->getArg1(), 'demoInstance made by container getArg1 failed');
    }

    public function testSingletonWork()
    {
        $container = Container::getInstance();
        $container->singleton('Singleton',Singleton::class);

        $singleton1 = $container->make('Singleton');
        $this->assertInstanceOf(Singleton::class,$singleton1,'container make Singleton fail');
        $singleton2 = $container->make('Singleton');
        $this->assertInstanceOf(Singleton::class,$singleton2,'container make Singleton fail');
        $singleton1->setValue('zan');
        $this->assertEquals($singleton1, $singleton2, 'container share Singleton fail');

    }

}