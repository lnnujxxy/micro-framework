<?php

namespace Pepper\Lib;

class StringHelper
{
    /**
     * 移除字符串中非utf8编码的字符
     * @param string $string
     * @return string
     */
    public static function removeNotUtf8Char($string)
    {
        return mb_convert_encoding($string, "UTF-8", "UTF-8");
    }
}