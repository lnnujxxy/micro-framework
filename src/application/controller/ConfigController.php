<?php
/**
 * Created by PhpStorm.
 * User: zhouweiwei
 * Date: 2018/10/18
 * Time: 下午9:43
 */

namespace Pepper\Framework\Controller;


use Pepper\Framework\Model\User;
use Pepper\Lib\SimpleConfig;

class ConfigController extends BaseController
{
    public function getConfigsAction() {
        $data['url_map'] = [
            'goods-1257256615.cos.ap-beijing.myqcloud.com' => 'goods-1257256615.file.myqcloud.com',
            'pic2018-1257256615.cos.ap-beijing.myqcloud.com' => 'pic2018-1257256615.file.myqcloud.com'
        ];
        $data['default_avatar'] = User::getDefaultAvatar();
        $data['system_config'] = SimpleConfig::get('SYSTEM_CONFIG');
        $this->render($data);
    }

    public function getCommonConfigsAction() {
        $data['bless_config'] = SimpleConfig::get('BLESS_CONFIG');
        $data['default_comment'] = SimpleConfig::get('DEFAULT_COMMENTS');
        $this->render($data);
    }
}