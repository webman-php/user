<?php

namespace plugin\user\app\controller;

use plugin\user\api\Captcha;
use plugin\user\api\Limit;
use plugin\user\app\model\User;
use support\exception\BusinessException;
use support\Request;
use support\Response;

class LoginController
{
    /**
     * 不需要登录验证的方法
     * @var string[]
     */
    protected $noNeedLogin = ['index', 'logout'];

    /**
     * 登录
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function index(Request $request): Response
    {
        if ($request->method() === 'POST') {

            // 每个ip每分钟只能调用10次
            Limit::perMinute($request->getRealIp(), 10);

            $username = $request->post('username');
            $password = $request->post('password');
            $imageCode = $request->post('image_code');

            $captchaData = session('captcha-image-login');

            try {
                Captcha::verify(null, $imageCode, $captchaData);
            } catch (BusinessException $exception) {
                return json(['code' => 1, 'msg' => '图形验证码错误', 'data' => [
                    'field' => 'image_code'
                ]]);
            }

            $request->session()->delete('captcha-image-register');

            $users = User::where('username', $username)
                ->orWhere('email', $username)
                ->orWhere('mobile', $username)
                ->get();

            foreach ($users as $user) {
                if (password_verify($password, $user->password)) {
                    $request->session()->set('user', [
                        'id' => $user->id,
                        'username' => $user->username,
                        'nickname' => $user->nickname,
                        'avatar' => $user->avatar,
                        'email' => $user->email,
                        'mobile' => $user->mobile,
                    ]);
                    return json(['code' => 0, 'msg' => 'ok']);
                }
            }

            return json(['code' => 1, 'msg' => '用户名或密码错误']);
        }

        return view('login/login', ['name' => 'user']);
    }

    /**
     * 退出
     * @param Request $request
     * @return Response
     */
    public function logout(Request $request): Response
    {
        $session = $request->session();
        $session->delete('user');
        return redirect('/app/user/login');
    }

}
