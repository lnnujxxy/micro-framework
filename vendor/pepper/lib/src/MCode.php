<?php

namespace Pepper\Lib;

/**
 * 隐藏码
 * 通过指定任意字符列表作为任意进制转换的基础字符集合，掺杂干扰码及校验码，生成可解码的一串隐藏码
 * Class MCode
 * @package Pepper\Lib
 */
class MCode
{
    /**
     * 基础码字符集合
     * 任意字符集合，不允许存在重复字符
     * @var string
     */
    protected $baseChars = 'w85gr2pukm7q3c6tfdj9bzneys';

    /**
     * 干扰码字符集合
     * 不允许重复、不允许和基础码存在重复
     * @var string
     */
    protected $noiseChars = 'h4xva';

    /**
     * 校验码字符集合
     * 任意字符集合，不允许存在重复字符
     * 校验码使用自定义字符串对生成的码更可控
     * @var string
     */
    protected $checkChars = '25r9swc78pazdgt6jbu4eh3fnqymxkv';

    /**
     * MCode constructor.
     * @param string $baseChars 基础码字符集合 任意字符集合，不允许存在重复字符
     * @param string $noiseChars 干扰码字符集合 不允许重复、不允许和基础码存在重复
     * @param string $checkChars 校验码字符集合 任意字符集合，不允许存在重复字符
     * @throws \Exception
     */
    public function __construct($baseChars = 'w85gr2pukm7q3c6tfdj9bzneys', $noiseChars = 'h4xva', $checkChars = '25r9swc78pazdgt6jbu4eh3fnqymxkv')
    {
        $this->baseChars = $baseChars;
        $this->noiseChars = $noiseChars;
        $this->checkChars = $checkChars;
        if (strlen(trim($baseChars)) == 0) {
            throw new \Exception('bad baseChars');
        }

        if (strlen(trim($noiseChars)) == 0) {
            throw new \Exception('bad noiseChars');
        }

        if (strlen(trim($checkChars)) == 0) {
            throw new \Exception('bad checkChars');
        }
    }

    /**
     * 编码
     * @param int $number 待编码数字
     * @param int $minLength 编码最小长度（包含干扰码，不包含校验码）
     * @param int $checkLength 校验码长度
     * @return string
     * @throws \Exception
     */
    public function encode($number, $minLength = 5, $checkLength = 0)
    {
        $base = new BaseConvert($this->baseChars);
        $result = $base->convert($number);
        // 长度不足，补充干扰码
        if (strlen($result) < $minLength) {
            $appendLength = $minLength - strlen($result);
            $noiseStr = $this->generateNoiseStr($appendLength);
            $tmp = '';
            $offsetResult = 0;
            $offsetNoise = 0;
            for ($i = 0; $i < $minLength; ++$i){
                if (mt_rand(0, 1) == 1){
                    $tmp .= $offsetResult < strlen($result) ? $result[$offsetResult++] : $noiseStr[$offsetNoise++];
                }else{
                    $tmp .= $offsetNoise < strlen($noiseStr) ? $noiseStr[$offsetNoise++] : $result[$offsetResult++];
                }
            }
            $result = $tmp;
        }
        // 添加校验码
        if ($checkLength) {
            $checkSum = $this->getCheckSum($result);
            $baseCheck = new BaseConvert($this->checkChars);
            $strCheck = $baseCheck->convert($checkSum);
            $result .= substr($strCheck, -$checkLength);
        }

        return $result;
    }

    /**
     * 解码
     * @param string $string 待解码字符串
     * @param int $checkLength 校验码长度
     * @return int
     * @throws \Exception
     */
    public function decode($string, $checkLength = 0)
    {
        // 检查码
        if ($checkLength > 0) {
            $strCheck = substr($string, -$checkLength);
            $string = substr($string, 0, -$checkLength);
            $checkSum = $this->getCheckSum($string);
            $baseCheck = new BaseConvert($this->checkChars);
            $strCheck2 = $baseCheck->convert($checkSum);

            if ($strCheck !== substr($strCheck2, -$checkLength)) {
                throw new \Exception('check failed');
            }
        }

        // 剔除干扰码
        $tmp = '';
        for ($i = 0; $i < strlen($string); ++$i) {
            if (strpos($this->noiseChars, $string[$i]) === false) {
                $tmp .= $string[$i];
            }
        }
        $string = $tmp;

        $baseConvert = new BaseConvert($this->baseChars);
        return $baseConvert->recover($string);
    }

    /**
     * 获取字符串的校验值
     * @param string $string 待校验字符串
     * @param int $min 校验码最小值
     * @return int
     */
    protected function getCheckSum($string, $min = 9999)
    {
        $crc32 = abs(crc32($string)) + $min;
        $crc32 > PHP_INT_MAX && $crc32 = ceil($crc32 / 123);
        return $crc32;
    }

    /**
     * 生成指定长度的干扰码
     * @param int $len
     * @return string
     */
    protected function generateNoiseStr($len)
    {
        $str = '';
        $noiseCharsLength = strlen($this->noiseChars);
        for ($i = 0; $i < $len; ++$i) {
            $str .= $this->noiseChars[mt_rand(0, $noiseCharsLength - 1)];
        }
        return $str;
    }

}
