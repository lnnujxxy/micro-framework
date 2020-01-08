<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午2:04
 */
namespace Pepper\FrameWorker\Process\Tool;

use Pepper\Framework\Dao\DAOUser;
use Pepper\Framework\Lib\QcloudCos;
use Pepper\Framework\Lib\Util;

require_once __DIR__ . "/Init.php";

$daoUser = new DAOUser();
if (($handle = fopen("users.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        list($unionid, $nickname, $avatar, $gender) = $data;
        $avatar = str_replace('.jpg', '-132_132.jpg', $avatar);
        if ($gender == "F") {
            $sex = 2;
        } elseif ($gender == "M") {
            $sex = 1;
        } else {
            $sex = 0;
        }
        $data = Util::getUrlContents($avatar, 3);
        if (!$data) {
            sleep(3);
            $data = Util::getUrlContents($avatar, 5);
            if (!$data) {
                return false;
            }
        }

        $qcloudCos = new QcloudCos();
        $result = $qcloudCos->putObject(md5(Util::random()) . ".png", $data);
        $avatar = Util::useCDN($result);
        $daoUser->createUser($unionid, $unionid, $nickname, $avatar, $sex, "", "", 1);
    }
}

echo date("Y-m-d H:i:s") . "ok\n";