<?php

namespace Pepper\Lib;

class ArrayHelper
{

	/**
	 * 向数组中特定位置插入一个子数组
	 * @param array $container 待插入的容器数组
	 * @param array $insert 待插入容器数组的子数组
	 * @param int $pos 插入索引位置
	 * @param bool $ignoreSmallContainer 当容器数组长度不足时，是否在尾部插入子数组。如果为否，则在长度不足时不进行插入操作，返回$container数组
	 * @param bool $preserveKeys 是否保持原数组的数字索引
	 * @return array
	 */
	public static function insert(array $container, array $insert, $pos, $ignoreSmallContainer = false, $preserveKeys = false){
		if ($pos < 0){
			return $container;
		}
		if (count($container) < $pos){
			if (!$ignoreSmallContainer){
				return $container;
			}
			return $preserveKeys ? $container + $insert : array_merge($container, $insert);
		}
		if ($preserveKeys){
			return array_slice($container, 0, $pos, true) + $insert + array_slice($container, $pos, count($container), true);
		}
		return array_merge(array_slice($container, 0, $pos), $insert, array_slice($container, $pos));
	}

    /**
     * 比对数据类型，使用提供数组覆盖原数组，原数组中不存在的字段，覆盖数组无法重写
     * @param array $array 原数组
     * @param array $replace 覆盖数组，此数组中的值如果在原数组中，则替换掉原数组的值
     * @return array
     */
    public static function rewriteArray(array $array, array $replace)
    {
        array_walk($array, [__CLASS__, 'compareTypeAndReplace'], $replace);
        return $array;
    }

    private static function compareTypeAndReplace(&$v, $k, $replace)
    {
        if (is_array($v) && array_keys($v) !== range(0, count($v) - 1)) {
            isset($replace[$k]) && array_walk($v, [__CLASS__, __FUNCTION__], $replace[$k]);
        } else {
            if (isset($replace[$k]) && gettype($replace[$k]) === gettype($v)) {
                $v = $replace[$k];
            }
        }
    }
}