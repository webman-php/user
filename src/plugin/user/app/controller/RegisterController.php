<?php

namespace plugin\user\app\controller;

use plugin\user\api\Captcha;
use plugin\user\api\FormException;
use plugin\user\api\Limit;
use plugin\user\app\model\User;
use plugin\user\app\service\Register;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Webman\Event\Event;

class RegisterController
{

    /**
     * 不需要登录验证的方法
     * @var string[]
     */
    protected $noNeedLogin = ['index'];

    /**
     * 注册
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function index(Request $request): Response
    {
        $settings = Register::getSetting();
        if ($request->method() === 'GET') {
            $settings = Register::getSetting();
            return view('register/register', [
                'settings' => $settings,
            ]);
        }

        if (empty($settings['register_enable'])) {
            throw new FormException("注册功能已关闭", 1);
        }

        // 每个ip每分钟只能调用10次
        Limit::perMinute($request->getRealIp(), 10);

        // 收集数据
        $username = $request->post('username');
        $password = $request->post('password');
        $nickname = $request->post('nickname');
        $email = $request->post('email');
        $mobile = $request->post('mobile');
        $emailCode = $request->post('email_code');
        $mobileCode = $request->post('mobile_code');
        $imageCode = $request->post('image_code');
        $nickname = $nickname ?: $username;

        // 长度验证
        if (strlen($username) < 4) {
            return json(['code' => 1, 'msg' => '用户名至少4个字符', 'data' => [
                'field' => 'username'
            ]]);
        }
        if (strlen($password) < 6) {
            return json(['code' => 1, 'msg' => '密码至少6个字符', 'data' => [
                'field' => 'password'
            ]]);
        }

        // 用户数据
        $user = [
            'username' => $username,
            'password' =>  password_hash($password, PASSWORD_DEFAULT),
            'nickname' => $nickname,
        ];

        // 获取注册配置
        $settings = Register::getSetting();

        // 邮箱验证
        if ($settings['email_enable']) {
            $emailCode = $settings['email_verify'] ? $emailCode : false;
            Captcha::validate('email', $email, $emailCode, session("captcha-email-register"));
            if (User::where('email', $email)->first()) {
                throw new FormException("{$email}已经被占用", 1, 'email');
            }
            $user['email'] = $email;
        }

        // 手机验证
        if ($settings['mobile_enable']) {
            $mobileCode = $settings['mobile_verify'] ? $mobileCode : false;
            Captcha::validate('mobile', $mobile, $mobileCode, session("captcha-mobile-register"));
            if (User::where('mobile', $mobile)->first()) {
                throw new FormException("{$mobile}已经被占用", 1, 'mbile');
            }
            $user['mobile'] = $mobile;
        }

        // 图形验证码验证
        if ($settings['captcha_enable']) {
            $captchaData = session('captcha-image-register');
            try {
                Captcha::verify(null, $imageCode, $captchaData);
            } catch (BusinessException $exception) {
                return json(['code' => 1, 'msg' => '图形验证码错误', 'data' => [
                    'field' => 'image_code'
                ]]);
            }
        }

        // 用户名唯一性验证
        if (User::where('username', $username)->first()) {
            return json(['code' => 1, 'msg' => '用户名已经被占用', 'data' => [
                'field' => 'username'
            ]]);
        }

        // 注册用户
        $waUser = new User();
        foreach ($user as $key => $value) {
            $waUser->$key = $value;
        }
        $waUser->avatar = '/app/user/default-avatar.png';
        $waUser->save();

        // 发布注册事件
        Event::emit('user.register', $waUser);

        // 清理session
        $request->session()->delete('user');

        return json(['code' => 0, 'msg' => 'ok']);
    }


}
