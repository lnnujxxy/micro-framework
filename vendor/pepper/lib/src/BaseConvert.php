<?php

namespace Pepper\Lib;

/**
 * 任意进制转换，支持设置任意字符
 * Class BaseConvert
 * @package Pepper\Lib
 */
class BaseConvert
{
    private $chars = '';
    private $base = 0;

    public function __construct($chars)
    {
        $this->chars = $chars;
        $this->base = strlen($chars);
    }

    /**
     * 转换为字符串
     * @param int $number
     * @return string
     * @throws \Exception
     */
    public function convert($number)
    {
        if ($number < 0) {
            throw new \Exception('$number must gt 0');
        }
        if ($number > PHP_INT_MAX) {
            throw new \Exception('$number is bigger than PHP_INI_MAX');
        }
        if (!is_numeric($number) || !is_int((int)$number)) {
            throw new \Exception('$number must be integer');
        }

        $y = $number % $this->base;
        $c = floor($number / $this->base);
        $string = $this->chars[$y];
        if ($c > 0) {
            $string = self::convert($c) . $string;
        }
        return $string;
    }

    /**
     * 转换为数字
     * @param string $string
     * @return int
     * @throws \Exception
     */
    public function recover($string)
    {
        if (strlen($string) == 0) {
            throw new \Exception('$string is empty');
        }
        for ($i = 0; $i < strlen($string); ++$i) {
            if (strpos($this->chars, $string[$i]) === false) {
                throw new \Exception('$string contain bad char');
            }
        }

        $string = ltrim($string, $this->chars[0]);
        if ($string == '') {
            $string = $this->chars[0];
        }
        $num = 0;
        $len = strlen($string);
        for ($i = 0; $i < $len; ++$i) {
            $num += (strpos($this->chars, $string[$i])) * pow($this->base, $len - $i - 1);
        }
        return $num;
    }
}
