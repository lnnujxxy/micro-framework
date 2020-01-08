<?php

namespace Pepper\Lib;
/**
 * 时间消耗记录
 */
class Timer
{

    const TYPE_START = 'START';

    const TYPE_END = 'END';

    const TYPE_TICK = 'TICK';

    private static $records;

    private static $level = 0;

    /**
     * 持续跟踪型打点开始
     * @param $sign
     * @param array $context
     */
    public static function start($sign, array $context = [])
    {
        self::save(self::TYPE_START, self::$level++, $sign, $context);
    }

    /**
     * 持续跟踪型打点结束
     * @param $sign
     */
    public static function end($sign)
    {
        self::save(self::TYPE_END, self::$level--, $sign);
    }

    /**
     * 瞬时打点
     * @param $sign
     * @param array $context
     */
    public static function tick($sign, array $context = [])
    {
        self::save(self::TYPE_TICK, self::$level, $sign, $context);
    }

    /**
     * 返回时间内存消耗
     * @return string
     */
    public static function result()
    {
        if (!self::$records){
            return '';
        }

        $result = [];

        $first = self::$records[0];
        $i = 0;

        $tmp = [];
        foreach (self::$records as $r){
            $tmp[$r['type'] . '_' . $r['uniqid']] = $r;
        }

        foreach ($tmp as $r) {
            $level = $r['level'];
            $uniqid = $r['uniqid'];
            switch ($r['type']){
                case self::TYPE_START:
                    $end = isset($tmp[self::TYPE_END . '_' . $uniqid]) ? $tmp[self::TYPE_END . '_' . $uniqid] : null;
                    if (!$end){
                        $end = [
                            'time' => microtime(true),
                            'memory' => memory_get_usage(true),
                            'memory_peak' => memory_get_peak_usage(true)
                        ];
                    }
                    $result[] = ($level - 1 >= 0 ? '├' : '') . str_repeat('─', max(0, $level)) . $r['sign'] . ' ' . json_encode($r['context']);
                    $result[] = str_repeat('│ ', $level + 1) . "cost: " . number_format($end['time'] - $r['time'], 4) . ' offset:' . number_format($r['time'] - $first['time'], 4);
                    $result[] = str_repeat('│ ', $level + 1) . "memory: " . self::bytes($r['memory']) . ' peak:' . self::bytes($r['memory_peak']);
                    break;
                case self::TYPE_TICK:
                    $result[] = ($level - 1 >= 0 ? '├' : '') . str_repeat('─', max(0, $level)) . $r['sign'] . ' ' . json_encode($r['context']);
                    $result[] = str_repeat('│ ', $level + 1) . "offset: " . number_format($r['time'] - $first['time'], 4);
                    $result[] = str_repeat('│ ', $level + 1) . "memory: " . self::bytes($r['memory']) . ' peak:' . self::bytes($r['memory_peak']);
                    break;
            }
            ++$i;
        }

        return implode(PHP_EOL, $result);
    }

    private static function save($type, $level, $sign, array $context = [])
    {
        // 一个事件/持续事件的唯一id
        $uniqid = '';
        if (in_array($type, [self::TYPE_START, self::TYPE_TICK])){
            $uniqid = uniqid('', true);
        }elseif ($type == self::TYPE_END){
            // 结束事件从后往前查找，找对最近一个同sign的开始事件，并标记此开始事件已结束
            for ($i = count(self::$records) - 1; $i > 0; --$i){
                if (self::$records[$i]['sign'] == $sign && self::$records[$i]['type'] == self::TYPE_START && !isset(self::$records[$i]['ended'])){
                    $uniqid = self::$records[$i]['uniqid'];
                    self::$records[$i]['ended'] = true;
                }
            }
        }
        self::$records[] = [
            'uniqid' => $uniqid,
            'type' => $type,
            'level' => $level,
            'sign' => $sign,
            'context' => $context,
            'time' => microtime(true),
            'memory' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];
    }

    private static function bytes($bytes)
    {
        $symbols = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        if ($bytes == 0) {
            return sprintf('%.2f ' . $symbols[0], 0);
        }

        $exp = (int)floor(log(abs($bytes)) / log(1024));
        return sprintf('%.2f ' . $symbols[$exp], $bytes / pow(1024, floor($exp)));
    }
}