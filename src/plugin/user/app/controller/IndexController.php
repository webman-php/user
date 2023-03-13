<?php

namespace plugin\user\app\controller;

use Intervention\Image\ImageManagerStatic as Image;
use plugin\user\api\Captcha;
use plugin\user\api\FormException;
use plugin\user\app\model\User;
use plugin\user\app\service\Register;
use support\exception\BusinessException;
use support\Request;
use support\Response;

class IndexController
{

    /**
     * 用户中心
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return view('index/info', [
            'user' => User::find(session('user.id')),
            'setting' => Register::getSetting()
        ]);
    }

    /**
     * 保存设置
     * @param Request $request
     * @return Response
     */
    public function save(Request $request): Response
    {
        $availableFields = [
            'nickname',
        ];
        $post = $request->post();
        $update = [];
        foreach ($availableFields as $field) {
            if (isset($post[$field])) {
                $update[$field] = $post[$field];
            }
        }
        if ($update) {
            $user = User::find(session('user.id'));
            foreach ($update as $key => $value) {
                $user->$key = $value;
            }
            $user->save();
            $user = session('user');
            $user['nickname'] = $update['nickname'];
            $request->session()->set('user', $user);
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 头像设置
     * @param Request $request
     * @return Response
     */
    public function avatar(Request $request): Response
    {
        if ($request->method() === 'GET') {
            return view('index/avatar', [
                'user' => User::find(session('user.id'))
            ]);
        }
        $user = session('user');
        $uid = $user['id'];
        $file = $request->file('avatar');
        if ($file && $file->isValid()) {
            $ext = strtolower($file->getUploadExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png'])) {
                return json(['code' => 2, 'msg' => '仅支持 jpg jpeg gif png格式']);
            }
            $image = Image::make($file);
            $width = $image->width();
            $height = $image->height();
            $size = min($width, $height);
            $image->crop($size, $size)->resize(128, 128);
            $relativePath = 'upload/avatar/'.substr($uid, 0, 3);
            $realPath = base_path("/plugin/user/public/$relativePath");
            if (!is_dir($realPath)) {
                mkdir($realPath, 0777, true);
            }

            $url = "/app/user/$relativePath/$uid.$ext";
            $path = "$realPath/$uid.$ext";
            $image->save($path);
            $waUser = User::find($uid);
            $waUser->avatar = $url;
            $waUser->save();

            $user['avatar'] = $url;
            $request->session()->set('user', $user);
            return json(['code' => 0, 'msg' => 'upload success', 'data' => ['url' => $url]]);
        }
        return json(['code' => 1, 'msg' => 'file not found']);
    }

    /**
     * 密码设置
     * @param Request $request
     * @return Response
     */
    public function password(Request $request): Response
    {
        if ($request->method() === 'GET') {
            return view('index/password', [
                'user' => User::find(session('user.id'))
            ]);
        }
        $password = $request->post('password');
        $newPassword = $request->post('new_password');
        $user = User::find(session('user.id'));
        if (!$password || !password_verify($password, $user->password)) {
            return json(['code' => 1, 'msg' => '原始密码不正确', 'data' => [
                'field' => 'password'
            ]]);
        }
        if (strlen($newPassword) < 6) {
            return json(['code' => 1, 'msg' => '新密码至少6个字符', 'data' => [
                'field' => 'new_password'
            ]]);
        }
        $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->save();
        $request->session()->delete('user');
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 更改邮箱
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function email(Request $request): Response
    {
        $settings = Register::getSetting();
        $email = $request->post('email');
        $emailCode = $settings['email_verify'] ? $request->post('email_code') : false;
        $loginUserId = session('user.id');

        Captcha::validate('email', $email, $emailCode, session("captcha-email-change"));
        if (User::where('email', $email)->where('id', '<>', $loginUserId)->first()) {
            throw new FormException("{$email}已经被占用", 1, 'email');
        }

        $user = User::find($loginUserId);
        $user->email = $email;
        $user->save();

        return json(['code' => 0]);
    }

    /**
     * 更改手机
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function mobile(Request $request): Response
    {
        $settings = Register::getSetting();
        $mobile = $request->post('mobile');
        $mobileCode = $settings['mobile_verify'] ? $request->post('mobile_code') : false;
        $loginUserId = session('user.id');

        Captcha::validate('mobile', $mobile, $mobileCode, session("captcha-mobile-change"));
        if (User::where('mobile', $mobile)->where('id', '<>', $loginUserId)->first()) {
            throw new FormException("{$mobile}已经被占用", 1, 'mbile');
        }

        $user = User::find(session('user.id'));
        $user->mobile = $mobile;
        $user->save();

        return json(['code' => 0]);
    }

}
