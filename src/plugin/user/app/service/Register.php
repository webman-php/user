<?php

namespace plugin\user\app\service;

use plugin\admin\app\model\Option;

/**
 * 注册配置相关
 */
class Register
{

    /**
     * Option表对应的name
     */
    const OPTION_NAME = 'user_register_setting';

    /**
     * 默认配置
     * @var array
     */
    protected static $defaultSetting = [
        'nickname_enable' => true,
        'email_enable' => false,
        'email_verify' => false,
        'mobile_enable' => false,
        'mobile_verify' => false,
        'captcha_enable' => true,
        'register_enable' => true,
    ];

    /**
     * 获取配置
     * @return array
     */
    public static function getSetting(): array
    {
        $option = Option::where('name', static::OPTION_NAME)->value('value');
        return $option ? json_decode($option, true) : static::$defaultSetting;
    }

    /**
     * 保存设置
     * @param $data
     * @return void
     */
    public static function saveSetting($data)
    {
        $defaultSettings = static::$defaultSetting;
        $settings = [];
        foreach ($defaultSettings as $key => $setting) {
            $settings[$key] = $data[$key] ?? false;
        }
        $option = Option::where('name', static::OPTION_NAME)->first();
        $option = $option ?: new Option();
        $option->name = static::OPTION_NAME;
        $option->value = json_encode($settings);
        $option->save();
    }


}
