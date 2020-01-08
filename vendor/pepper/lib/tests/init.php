<?php
date_default_timezone_set('Asia/Harbin');

require_once dirname(__DIR__) . '/vendor/autoload.php';

\Pepper\Lib\SimpleConfig::loadConfigVarsFile(__DIR__ . '/config/test.conf.php');