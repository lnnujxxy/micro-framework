<?php
/**
 * 功能特性配置，提供给Feature类使用
 * min为包含；max为不包含
 * 用法：Feature::support('useLabels4Tag')
 */
$FEATURES = [
    'game' => [ // app标识，游戏直播助手
        'category' => [
            'desc' => '是否支持开播选择频道，频道支持特殊样式：可带一个小图标',
            'ios' => ['min' => '1.0',], 'android' => ['min' => '1.0',]
        ],
    ]
];

$FOR_SIMPLECONFIG_TEST = time();

$REDIS_CONF =  array(
    "host" => "154.8.195.226",
    "port" => 3306,
    "timeout" => 3,
    "password" => "test123"
);

$REDIS_CLUSTER_CONF = [
    $REDIS_CONF, $REDIS_CONF, $REDIS_CONF, $REDIS_CONF
];

// ES存储
$TEST_ES_HOSTS = [
    "elastic:pepper123@10.208.47.55:9201",
];