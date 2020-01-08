<?php

namespace Pepper\Framework\Model;


use Pepper\Framework\Dao\DAOUser;

class Robot
{
    public static function getRobotIds($num = 10) {
        return (new DAOUser())->getRobotIds($num);
    }
}