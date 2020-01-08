<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/12
 * Time: 下午2:04
 */

namespace Pepper\Framework\Process\Worker;

use Pepper\Framework\Lib\QcloudCos;
use Pepper\Framework\Lib\Util;
use Pepper\Framework\Model\User;

class SaveWxAvatarWorker
{
    public function execute($value) {
        $uid = $value["params"]["uid"];
        $avatar = $value['params']['avatar'];
        $data = Util::getUrlContents($avatar, 3);
        if (!$data) {
            sleep(3);
            $data = Util::getUrlContents($avatar, 5);
            if (!$data) {
                return false;
            }
        }

        $qcloudCos = new QcloudCos();
        $avatar = $qcloudCos->putObject(md5(Util::random()) . ".png", $data);
        if ($avatar) {
            User::updateUserInfo($uid, ['avatar' => Util::useCDN($avatar)]);
        }
        return true;
    }
}