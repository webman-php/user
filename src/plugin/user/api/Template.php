<?php

namespace plugin\user\api;

use plugin\user\app\service\Register;
use stdClass;
use support\Log;
use Throwable;
use Webman\Event\Event;

/**
 * 用户页面接口
 */
class Template
{

    /**
     * 网页头header
     * @param $title
     * @param array $options
     * @return false|string
     */
    public static function header($title, array $options = [])
    {
        $css = (array)($options['css'] ?? []);
        $js = (array)($options['js'] ?? []);
        ob_start();
        include base_path('plugin/user/app/view/header.html');
        return ob_get_clean();
    }

    /**
     * 导航栏
     * @return string|null
     * @throws Throwable
     */
    public static function nav(): ?string
    {
        $navs = static::getNavData()['navs'];
        $setting = Register::getSetting();
        ob_start();
        try {
            include base_path('plugin/user/app/view/nav.html');
        } catch (Throwable $e) {
            Log::error($e);
        }
        return ob_get_clean();
    }

    /**
     * 用户中心侧边栏
     * @return string|null
     * @throws Throwable
     */
    public static function sidebar(): ?string
    {
        $sidebars = static::getSidbarData()['sidebars'];
        ob_start();
        try {
            include base_path('plugin/user/app/view/sidebar.html');
        } catch (Throwable $e) {
            Log::error($e);
        }
        return ob_get_clean();
    }

    /**
     * 网页footer
     * @param array $options
     * @return false|string
     */
    public static function footer(array $options = [])
    {
        ob_start();
        try {
            include base_path('plugin/user/app/view/footer.html');
        } catch (Throwable $e) {
            Log::error($e);
        }
        return ob_get_clean();
    }

    /**
     * 获取菜单数据
     * @return array[]
     */
    public static function getNavData(): array
    {
        $object = new stdClass();
        $object->navs = [];
        Event::emit('user.nav.render', $object);
        $navs = $object->navs;
        return [
            'navs' => $navs
        ];
    }

    public static function getSidbarData(): array
    {
        $request = request();
        $uri = rtrim($request ? $request->uri() : '', '/');
        $object = new stdClass();
        $object->sidebars = [
            [
                'name' => '用户中心',
                'items' => [
                    ['name' => '个人资料', 'url' => '/app/user', 'class' => $uri == '/app/user' ? 'active' : ''],
                    ['name' => '头像设置', 'url' => '/app/user/avatar', 'class' => $uri == '/app/user/avatar' ? 'active' : ''],
                    ['name' => '密码设置', 'url' => '/app/user/password', 'class' => $uri == '/app/user/password' ? 'active' : ''],
                ]
            ]
        ];
        Event::emit('user.sidebar.render', $object);
        $sidebars = $object->sidebars;
        return [
            'sidebars' => $sidebars
        ];
    }

}
