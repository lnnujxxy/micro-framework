<?php
$PROJECT = PROJECT_NAME;

$DB_CONF["default"] = array(
    // todo 数据库配置
);

// 配置表所在的库
$TABLE_MAP = array(

);

// 分表配置
$TABLE_CONF = array(
    "follower"      => array("shard" => 2),
    "following"     => array("shard" => 2),
    "goods_user"    => array("shard" => 2),
    "comment"       => array("shard" => 2),
    "msg"           => array("shard" => 2),
    "task_progress" => array("shard" => 2),
    "feeds_goods"    => array("shard" => 2),
);

$RANGE_CONF = array(
    array("min"=>0,  "max"=>9, "confid"=>1), // 指定库 confid对应DB_CONF中index值
);

$REDIS_CONF = array(
    // todo redis配置
);

$REDIS_LOCK = array(
    // todo redis配置
);
$URL = "XXX"; // todo接口地址
$TEST_MODE = true;
require_once "server_conf.common.php";
require_once "server_conf.business.php";
