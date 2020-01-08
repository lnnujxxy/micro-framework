<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午9:46
 */
namespace Pepper\Framework\Model;
use Pepper\Framework\Dao\DAOHelp;


class Help
{
    const SUBTYPE_COMMENT = 1;
    const SUBTYPE_HELP = 2;

    public static function addHelp($userid, $uid, $content) {
        Task::doTask($userid, 'help');
        $params = [
            'uid' => $userid,
            'vid' => $uid,
            'content' => $content,
            'ispublic' => 1,
        ];
        return (new DAOHelp())->add($params);
    }

    public static function topN($vid, $limit = 3) {
        $helps = (new DAOHelp())->listingByVid($vid,0, $limit);
        if (empty($helps)) {
            return [];
        }
        $uids = array_column($helps, 'uid');
        $userInfos = User::getFormatUsersInfo($uids);

        foreach ($helps as $index=>$help) {
            $help['user_info'] = $userInfos[$help['uid']];
            $help['content'] = json_decode($help['content'], true);
            $help['created_at_timestamp'] = strtotime($help['created_at']);
            $helps[$index] = $help;
        }

        return $helps;
    }
}