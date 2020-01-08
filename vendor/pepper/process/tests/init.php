<?php
date_default_timezone_set('Asia/Harbin');

require_once dirname(__DIR__) . '/vendor/autoload.php';
use Pepper\Lib\SimpleConfig;
SimpleConfig::loadConfigVarsFile(__DIR__. '/config/process_conf.php.test.mode');

