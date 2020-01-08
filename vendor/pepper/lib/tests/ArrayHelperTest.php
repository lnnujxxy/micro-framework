<?php

class ArrayHelperTest extends \PHPUnit\Framework\TestCase
{

	/**
	 * @param $container
	 * @param $insert
	 * @param $pos
	 * @param $ignoreSmallContailer
	 * @param $preserveKeys
	 * @param $expect
	 * @dataProvider insertProvider
	 */
	function testInsert($container, $insert, $pos, $ignoreSmallContailer, $preserveKeys, $expect){
		$result = \Pepper\Lib\ArrayHelper::insert($container, $insert, $pos, $ignoreSmallContailer, $preserveKeys);
		$this->assertEquals($expect, $result, "params:" . json_encode(func_get_args()). ' actual:' . json_encode($result));
	}

	function insertProvider(){
		return [
			[[1,2,3], [4], 0, false, false, [4,1,2,3]],
			[[1,2,3], [4], 0, true, false, [4,1,2,3]],
			[[1,2,3], [4], 0, true, true, [4,2,3]], // 数字数组如果保存key，则会覆盖同名key
			[[1,2,3], [4], -1, false, false, [1,2,3]],
			[[1,2,3], [4], 2, false, false, [1,2,4,3]],
			[[1,2,3], [4], 3, false, false, [1,2,3,4]],
			[[1,2,3], [4], 4, false, false, [1,2,3]],
			[[1,2,3], [4], 4, true, false, [1,2,3,4]],
			[['a' => 1, 'b' => 2, 'c' => 3], ['d' => 4], 0, false, false, ['d' => 4, 'a' => 1, 'b' => 2, 'c' => 3]],
			[['a' => 1, 'b' => 2, 'c' => 3], ['d' => 4], -1, false, false, ['a' => 1, 'b' => 2, 'c' => 3]],
			[['a' => 1, 'b' => 2, 'c' => 3], ['d' => 4], 4, false, false, ['a' => 1, 'b' => 2, 'c' => 3]],
			[['a' => 1, 'b' => 2, 'c' => 3], ['d' => 4], 2, false, false, ['a' => 1, 'b' => 2, 'd' => 4, 'c' => 3]],
			[['a' => 1, 'b' => 2, 'c' => 3], ['d' => 4], 2, false, true, ['a' => 1, 'b' => 2, 'd' => 4, 'c' => 3]],
			[['a' => 1, 'b' => 2, 'c' => 3], ['d' => 4], 4, true, false, ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]],
			[['a' => 1, 'b' => 2, 'c' => 3], [4], 4, true, false, ['a' => 1, 'b' => 2, 'c' => 3, 0 => 4]],
			[['a' => 1, 'b' => 2, 'c' => 3], [4], 4, true, true, ['a' => 1, 'b' => 2, 'c' => 3, 0 => 4]],
			[[123 => 1, 124 => 2, 125 => ['x' => 3]], [666 => ['y' => 4]], -1, false, false, [123 => 1, 124 => 2, 125 => ['x' => 3]]],
			[[123 => 1, 124 => 2, 125 => ['x' => 3]], [666 => ['y' => 4]], 0, false, false, [['y' => 4], 1, 2, ['x' => 3]]], //  数字key如果不指定preserveKeys则重置key
			[[123 => 1, 124 => 2, 125 => ['x' => 3]], [666 => ['y' => 4]], 0, false, true, [666 => ['y' => 4], 123 => 1, 124 => 2, 125 => ['x' => 3]]],
		];
	}

    function testRewriteArray()
    {

        $a = [
            'foo' => 123,
            'bar' => [
                1, 2, 3, 9
            ],
            'zoo' => [
                'x' => 8,
                'y' => 9,
                'a' => 666
            ],
            'k' => [
                'kk' => [
                    'kkk' => 1,
                    'kkkk' => 2
                ]
            ],
            'hehe' => true
        ];

        $b = [
            'foo' => 123456,
            'bar' => [
                3, 4, 5
            ],
            'zoo' => [
                'x' => 88,
                'y' => 99,
                'z' => 100
            ],
            'k' => [
                'kk' => [
                    'kkk' => 2,
                    'xxxxx' => '醋'
                ]
            ],
            'no' => '酱油'
        ];

        $result = \Pepper\Lib\ArrayHelper::rewriteArray($a, $b);
        $expected = array(
            'foo' => 123456, // 数字类型直接替换
            'bar' => // 自然数组直接替换
                array(
                    0 => 3,
                    1 => 4,
                    2 => 5,
                ),
            'zoo' => // KV数组，按照key进行对比替换，不存在的key不写入（z=100没有写入）
                array(
                    'x' => 88,
                    'y' => 99,
                    'a' => 666,
                ),
            'k' => // 多维KV数组，递归按照key进行对比替换，不存在的key同样不写入
                array(
                    'kk' =>
                        array(
                            'kkk' => 2,
                            'kkkk' => 2,
                        ),
                ),
            'hehe' => true,
        );
        $this->assertEquals($expected, $result);
    }
}