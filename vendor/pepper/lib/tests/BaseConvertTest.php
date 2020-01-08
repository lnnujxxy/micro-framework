<?php

use Pepper\Lib\BaseConvert;
use PHPUnit\Framework\TestCase;

class BaseConvertTest extends TestCase
{

    public function testConvert()
    {
        $bc = new BaseConvert('0123456789');
        for ($i = 0; $i < 1000; ++$i) {
            try {
                $this->assertEquals($i, $bc->convert($i));
            } catch (Exception $e) {
            }
        }

        $bc = new BaseConvert('ABCDEFG');
        $this->assertEquals('A', $bc->convert(0));
        $this->assertEquals('B', $bc->convert(1));
        $this->assertEquals('C', $bc->convert(2));
        $this->assertEquals('D', $bc->convert(3));
        $this->assertEquals('E', $bc->convert(4));
        $this->assertEquals('F', $bc->convert(5));
        $this->assertEquals('G', $bc->convert(6));
        $this->assertEquals('BA', $bc->convert(7));
        $this->assertEquals('BB', $bc->convert(8));
        $this->assertEquals('BC', $bc->convert(9));
        $this->assertEquals('BD', $bc->convert(10));
        $this->assertEquals('BE', $bc->convert(11));
    }

    public function testRecover()
    {
        $bc = new BaseConvert('0123456789');
        for ($i = 0; $i < 1000; ++$i) {
            try {
                $this->assertEquals($bc->recover($i), $i);
            } catch (Exception $e) {
            }
        }

        $bc = new BaseConvert('ABCDEFG');
        $this->assertEquals(0, $bc->recover('A'));
        $this->assertEquals(1, $bc->recover('B'));
        $this->assertEquals(2, $bc->recover('C'));
        $this->assertEquals(3, $bc->recover('D'));
        $this->assertEquals(4, $bc->recover('E'));
        $this->assertEquals(5, $bc->recover('F'));
        $this->assertEquals(6, $bc->recover('G'));
        $this->assertEquals(7, $bc->recover('BA'));
        $this->assertEquals(8, $bc->recover('BB'));
        $this->assertEquals(9, $bc->recover('BC'));
        $this->assertEquals(10, $bc->recover('BD'));
        $this->assertEquals(11, $bc->recover('BE'));
    }

}
