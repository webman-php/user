<?php

namespace plugin\user\app\admin\controller;

use plugin\email\api\Email;
use plugin\sms\api\Sms;
use plugin\user\app\service\Register;
use support\Request;
use support\Response;

/**
 * 注册设置
 */
class RegisterController
{

    /**
     * 注册设置
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return view('register/index', [
            'support_email' => class_exists(Email::class),
            'support_sms' => class_exists(Sms::class)
        ]);
    }

    /**
     * 保存设置
     * @param Request $request
     * @return Response
     */
    public function saveSetting(Request $request): Response
    {
        Register::saveSetting($request->post());
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 获取配置
     * @param Request $request
     * @return Response
     */
    public function getSetting(Request $request): Response
    {
        return json(['code' => 0, 'msg' => 'ok', 'data' => Register::getSetting()]);
    }

}
