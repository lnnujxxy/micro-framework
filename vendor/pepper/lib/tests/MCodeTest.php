<?php

use Pepper\Lib\MCode;
use PHPUnit\Framework\TestCase;

class MCodeTest extends TestCase
{
    function test1()
    {
        $length = 3;
        $check = 2;

        $mcode = new MCode();

        $start = 0;
        $total = 1500;
        $end = $start + $total;
        for ($i = $start; $i < $end; $i += 1) {
            $code = $mcode->encode($i, $length, $check);
            $decode = $mcode->decode($code, $check);
            $this->assertEquals($i, $decode);
            $this->assertEquals($length + $check, strlen($code));
        }

        $check = 0;
        for ($i = $start; $i < $end; $i += 1) {
            $code = $mcode->encode($i, $length, $check);
            $this->assertEquals($i, $mcode->decode($code, $check));
            $this->assertEquals($length + $check, strlen($code));
        }

        $length = 1;
        $check = 0;
        for ($i = $start; $i < $end; $i += 1) {
            $code = $mcode->encode($i, $length, $check);
            $this->assertEquals($i, $mcode->decode($code, $check));
            $this->assertTrue(strlen($code) >= $length + $check);
        }

        $length = 10;
        $check = 0;
        for ($i = $start; $i < $end; $i += 1) {
            $code = $mcode->encode($i, $length, $check);
            $this->assertEquals($i, $mcode->decode($code, $check));
            $this->assertEquals($length + $check, strlen($code));
        }

        $length = 10;
        $check = 3;
        for ($i = $start; $i < $end; $i += 1) {
            $code = $mcode->encode($i, $length, $check);
            $this->assertEquals($i, $mcode->decode($code, $check));
            $this->assertEquals($length + $check, strlen($code));
        }
    }

    function test2(){
        $code = 'KHHX';
        $code = strtolower($code);
        $mcode = new MCode();
        $num = $mcode->decode($code);
        var_dump($num);
        $code = $mcode->encode($num);
        var_dump(strtoupper($code));

    }
}
