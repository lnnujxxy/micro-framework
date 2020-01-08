<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/18
 * Time: 下午9:43
 */

namespace Pepper\Framework\Controller;


use Pepper\Framework\Lib\Context;
use Pepper\Framework\Model\Feeds;

class HomeController extends BaseController
{
    public function getLastestAction() {
        $userid = Context::get('userid');
        $lastest = Feeds::getLastest($userid);
        $this->render($lastest);
    }
}