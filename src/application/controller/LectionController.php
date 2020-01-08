<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/16
 * Time: 下午7:22
 */

namespace Pepper\Framework\Controller;

use Pepper\Framework\Dao\DAOLection;

class LectionController extends BaseController
{
    public function listAction()
    {
        $this->render();
        $offset = $this->getParam('offset');
        $limit = $this->getParam('limit', 10);

        $list = (new DAOLection())->listing($offset, $limit);
        $data = array(
            'list' => $list,
            'more' => count($list) >= $limit,
            'offset' => $list[count($list)-1]['id']
        );
        $this->render($data);
    }
}