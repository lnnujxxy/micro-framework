<?php

namespace Pepper\Lib;

interface ICache
{
    public function get($key);

    public function set($key, $val, $expire);

    public function getpro($key, &$expiredData = null);

    public function setpro($key, $val, $expire);
}