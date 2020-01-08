<?php

namespace Pepper\Lib;

class Random
{

    /**
     * 按权重随机
     * @param array $weights 权重数组，元素为权重值
     * @return bool|mixed 返回选中权重的key值
     */
    public static function weight($weights)
    {
        if (!$weights || !is_array($weights)) {
            return false;
        }

        $sum = array_sum($weights);
        foreach ($weights as $k => $weight) {
            $j = mt_rand(1, $sum);
            if ($j <= $weight) {
                return $k;
            } else {
                $sum -= $weight;
            }
        }
    }
}