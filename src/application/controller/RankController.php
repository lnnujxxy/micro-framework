<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/12/17
 * Time: 下午10:56
 */

namespace Pepper\Framework\Controller;
use Pepper\Framework\Lib\Context;
use Pepper\Framework\Lib\Interceptor;
use Pepper\Framework\Model\Feeds;
use Pepper\Framework\Model\Rank;

class RankController extends BaseController
{
    public function indexAction() {
        $userid = Context::get('userid');
        $type = $this->getParam('type');
        Interceptor::ensureNotEmpty(in_array($type, Feeds::supportTypeIds), ERROR_PARAM_INVALID_FORMAT, 'type');

        $rank = Rank::getRank($type);
        foreach($rank as $index=>$row) {
            if (!isset($uids[$row['uid']])) {
                $uids[$row['uid']] = $index;
            }
        }

        $data = [
            'rank' => $rank,
            'pos' => isset($uids[$userid]) ? $uids[$userid] : 999,
        ];
        $this->render($data);
    }
}