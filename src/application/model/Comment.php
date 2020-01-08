<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午9:46
 */
namespace Pepper\Framework\Model;
use Pepper\Framework\Dao\DAOComment;
use Pepper\Framework\Dao\DAOFeeds;
use Pepper\Framework\Lib\Context;


class Comment
{
    const SUBTYPE_COMMENT = 1;
    const SUBTYPE_HELP = 2;

    public static function addComment($relateid, $type, $uid, $comment, $subtype = self::SUBTYPE_COMMENT) {
        (new DAOComment($relateid, $type))->add($uid, $comment, $subtype);

        $field = $subtype == self::SUBTYPE_COMMENT ? 'comments' : 'helps';
        Feeds::incrField($relateid, $type, $field, 1);
        return (new DAOFeeds())->incComments($relateid, $type, 1);
    }

    public static function helpComment($relateid, $type, $tuid, $fuid, $goodsInfo, $extends = array()) {
        $toUserInfo = User::getFormatUserInfo($tuid);
        $fromUserInfo = User::getFormatUserInfo($fuid);
        $content = [
            'content' => "送出[red]{$goodsInfo['name']}[/red], 
                        为[red]{$toUserInfo['nickname']}[/red]的".Feeds::getName($type)."求得祝福值[red]".$extends['cur_bless']."[/red]",
            'images' => $goodsInfo['image'],
            'is_hide' => $_REQUEST['is_hide'],
            'tuid' => $tuid,
            'fuid' => $fuid,
            'from_nickname' => $fromUserInfo['nickname'],
            'from_nickname_hide' => '神秘人'. substr(crc32($fuid), 0, 6)
        ];
        Comment::addComment($relateid, $type, $fuid, json_encode($content, JSON_UNESCAPED_UNICODE), Comment::SUBTYPE_HELP);
        return $content;
    }

    public static function listComments($relateid, $type, $subType, $offset = 0, $limit = 20) {
        $userid = Context::get('userid');
        $comments = (new DAOComment($relateid, $type))->listComments($subType, $offset, $limit);
        if (empty($comments)) {
            return [];
        }
        $uids = array_column($comments, 'uid');
        $userInfos = User::getFormatUsersInfo($uids);

        foreach ($comments as $index=>$comment) {
            $comment['user_info'] = $userInfos[$comment['uid']];
            if ($comment['sub_type'] == self::SUBTYPE_HELP) {
                $comment['comment'] = json_decode($comment['comment'], true);

                $isHide = isset($comment['comment']['is_hide']) && $comment['comment']['is_hide']
                            && $userid != $comment['comment']['tuid'];
                if (!$isHide) {
                    if (isset($comment['comment']['is_hide'])) {
                        $comment['comment']['content'] = '[red]'.$comment['comment']['from_nickname'].'[/red]' . $comment['comment']['content'];
                    }
                } else {
                    $comment['comment']['content'] = '[red]'.$comment['comment']['from_nickname_hide'].'[/red]' . $comment['comment']['content'];
                    $comment['user_info']['nickname'] = $comment['comment']['from_nickname_hide'];
                    $comment['user_info']['avatar'] = 'https://goods-1257256615.file.myqcloud.com/20190118/shenmiren.png';
                }
            }
            $comments[$index] = $comment;
        }

        return [
            'list' => $comments,
            'more' => count($comments) >= $limit,
            'offset' => (int)$comments[count($comments)-1]['id'],
        ];
    }
}