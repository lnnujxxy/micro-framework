<?php

namespace _;

// https://laravel.com/docs/5.4/container
use Pepper\Container\Container;

require __DIR__ . "/../vendor/autoload.php";

for ($i = 0; $i < 10; $i++) {
    $ns = __NAMESPACE__;
    eval("namespace $ns; class C$i { }");
}

call_user_func(function() {
    $container = Container::getInstance();
    $container->resolving(function($object, Container $app) {

    });
    $container->resolving(C1::class, function($object, Container $app) {

    });


    $container->bind(C1::class);
    $c1 = make(C1::class);
    assert($c1 instanceof C1);
    $c11 = make(C1::class);
    assert($c1 !== $c11);


    $container->bind("C2", C2::class);
    $c2 = make("C2");
    assert($c2 instanceof C2);



    $container->bind("C3", function( ) {
        return new C3;
    });
    $c3 = make("C3");
    assert($c3 instanceof C3);




    $container->alias("C3", "c3");
    $c3 = make("c3");
    assert($c3 instanceof C3);




    $container->singleton("C4", C4::class);
    $c41 = make("C4");
    $c42 = make("C4");
    assert($c41 instanceof C4);
    assert($c42 instanceof C4);
    assert($c41 === $c42);




    class TestBindVar {
        function __construct($a)
        {
            $this->a = $a;
        }
    }
    $container->bind("testBindVar", TestBindVar::class);
    $container->when(TestBindVar::class)
        ->needs('$a')
        ->give(42);
    $testBindVar = make("testBindVar");
    assert($testBindVar instanceof TestBindVar);
    assert($testBindVar->a === 42);



    class TestBindVarA {
        function __construct($a)
        {
            $this->a = $a;
        }
    }
    $container = Container::getInstance();
    $container->bind("testBindVarA", TestBindVarA::class);
    $testBindVar = make("testBindVarA", ["a" => 42]);



    interface IA { }
    class ImplA implements IA { }
    $container->bind(IA::class, ImplA::class);
    $ia = make(IA::class);
    assert($ia instanceof ImplA);




    class B {
        function __construct(IA $a)
        {
            $this->a = $a;
        }
    }
    $container->bind("B", B::class);
    $b = make("B");
    assert($b instanceof B);
    assert($b->a instanceof ImplA);



    interface IC { }
    class ImplC1 implements IC { }
    class ImplC2 implements IC { }
    class D1 {
        function __construct(IC $c)
        {
            $this->c = $c;
        }
    }
    class D2 {
        function __construct(IC $c)
        {
            $this->c = $c;
        }
    }
    $container->bind("D1", D1::class);
    $container->bind("D2", D2::class);
    $container->when(D1::class)
        ->needs(IC::class)
        ->give(function() {
            return new ImplC1;
        });
    $container->when(D2::class)
        ->needs(IC::class)
        ->give(ImplC2::class);

    $d1 = make("D1");
    assert($d1 instanceof D1);
    assert($d1->c instanceof ImplC1);
    $d2 = make("D2");
    assert($d2 instanceof D2);
    assert($d2->c instanceof ImplC2);
});

