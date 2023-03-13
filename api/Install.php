<?php

namespace plugin\user\api;

use plugin\admin\api\Menu;
use plugin\user\app\admin\controller\RegisterController;

/**
 * 安装用户模块 依赖webman-admin
 */
class Install
{
    /**
     * 安装
     * @return void
     */
    public static function install()
    {
        if (Menu::get(RegisterController::class)) {
            return;
        }
        // 找到上级菜单
        $parentMenu = Menu::get('user');
        if (!$parentMenu) {
            echo "未找到用户菜单" . PHP_EOL;
            return;
        }
        // 插入菜单
        $pid = $parentMenu['id'];
        Menu::add([
            'title' => '注册设置',
            'href' => '/app/user/admin/register',
            'pid' => $pid,
            'key' => RegisterController::class,
            'weight' => 0,
            'type' => 1,
        ]);
    }

    /**
     * 卸载
     * @return void
     */
    public static function uninstall()
    {
        // 删除菜单
        Menu::delete(RegisterController::class);
    }
}