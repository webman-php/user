<?php

namespace plugin\user\app\controller;

use plugin\user\api\Captcha;
use plugin\user\api\FormException;
use plugin\user\api\Limit;
use plugin\user\app\model\User;
use support\exception\BusinessException;
use support\Request;
use support\Response;

class PasswordController
{
    /**
     * 不需要登录验证的方法
     * @var string[]
     */
    protected $noNeedLogin = ['reset', 'sendResetCaptcha'];

    /**
     * 重置密码
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function reset(Request $request): Response
    {
        if ($request->method() === 'GET') {
            return view('password/reset');
        }
        $type = $request->post('type');// == 'email' ? 'email' : 'mobile';
        $item = $request->post('item');
        $captcha = $request->post('captcha');
        $password = $request->post('password');
        $passwordConfirm = $request->post('password_confirm');
        if ($password !== $passwordConfirm) {
            return json(['code' => 1, 'msg' => '两次输入的密码不一致']);
        }
        if (strlen($password) < 6) {
            return json(['code' => 1, 'msg' => '密码至少6个字符', 'data' => [
                'field' => 'password'
            ]]);
        }
        $captchaData = session("captcha-$type-password-reset");
        try {
            Captcha::validate($type, $item, $captcha, $captchaData);
        } catch (FormException $exception) {
            $exception->field = 'captcha';
            throw $exception;
        }

        $user = User::where($type, $item)->first();
        if (!$user) {
            return json(['code' => 1, 'msg' => '用户不存在', 'data' => []]);
        }
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();

        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 发送重置密码验证码
     * @param Request $request
     * @return Response
     */
    public function sendResetCaptcha(Request $request): Response
    {
        $imageCode = $request->post('image_code');
        $email = $request->post('email');
        $mobile = $request->post('mobile');
        if (!$email && !$mobile) {
            return json(['code' => 1, 'msg' => '字段不能为空', 'data' => [
                'field' => 'email_or_mobile'
            ]]);
        }
        $type = $mobile ? 'mobile' : 'email';
        $key = 'password-reset';
        $captchaData = session("captcha-image-$key");
        try {
            Captcha::verify(null, $imageCode, $captchaData);
        } catch (BusinessException $exception) {
            return json(['code' => 1, 'msg' => '图形验证码错误', 'data' => [
                'field' => 'image_code'
            ]]);
        }
        if(!User::where($type, $$type)->first()) {
            return json(['code' => 1, 'msg' => '账户不存在', 'data' => [
                'field' => 'email_or_mobile'
            ]]);
        }
        $captchaController = new CaptchaController();
        return call_user_func([$captchaController, $type], $request, $key);
    }

}
