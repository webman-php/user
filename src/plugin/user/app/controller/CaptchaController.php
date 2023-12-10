<?php

namespace plugin\user\app\controller;

use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use PHPMailer\PHPMailer\Exception;
use plugin\email\api\Email;
use plugin\sms\api\Sms;
use plugin\user\api\Captcha;
use plugin\user\api\Limit;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use Webman\Captcha\CaptchaBuilder;

class CaptchaController
{

    /**
     * 不需要登录的方法
     * @var string[]
     */
    protected $noNeedLogin = ['image', 'email', 'mobile'];

    /**
     * 图片验证码
     * @param Request $request
     * @param string $type
     * @return Response
     * @throws \Exception
     */
    public function image(Request $request, string $type = 'captcha'): Response
    {
        $builder = new CaptchaBuilder;
        $captchaData = Captcha::create();
        $builder->setPhrase($captchaData['code']);
        $builder->build();
        $captchaType = 'image';
        $request->session()->set("captcha-$captchaType-$type", $captchaData);
        $img_content = $builder->get();
        return response($img_content, 200, ['Content-Type' => 'image/jpeg']);
    }

    /**
     * 邮箱验证码
     * @param Request $request
     * @param string $type
     * @return Response
     * @throws Exception
     * @throws BusinessException
     */
    public function email(Request $request, string $type = 'captcha'): Response
    {
        // 频率限制，每个ip每分钟最多5次
        Limit::perMinute($request->getRealIp(), 5);
        if (!class_exists(Email::class)) {
            return json(['code' => 1, 'msg' => '系统未检测到邮件插件']);
        }
        $email = $request->post('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['code' => 1, 'msg' => '邮箱格式错误', 'data' => [
                'field' => 'email'
            ]]);
        }
        $captchaType = 'email';
        $captchaData = Captcha::create($email);
        $request->session()->set("captcha-$captchaType-$type", $captchaData);
        // 固定发送模版名为captcha的邮件
        Email::sendByTemplate($email, 'captcha', [
            'code' => $captchaData['code'],
        ]);
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 短息验证码
     * @param Request $request
     * @param string $type
     * @return Response
     * @throws InvalidArgumentException
     * @throws NoGatewayAvailableException|BusinessException|Throwable
     */
    public function mobile(Request $request, string $type = 'captcha'): Response
    {
        // 频率限制，每个ip每分钟最多3次
        Limit::perMinute($request->getRealIp(), 3);
        if (!class_exists(Sms::class)) {
            return json(['code' => 1, 'msg' => '系统未检测到短信插件']);
        }
        $mobile = $request->post('mobile');
        $captchaType = 'mobile';
        $captchaData = Captcha::create($mobile, '0123456789');
        $request->session()->set("captcha-$captchaType-$type", $captchaData);
        // 固定发送tag为captcha的短信
        try {
            Sms::sendByTag($mobile, 'captcha', [
                'code' => $captchaData['code'],
            ]);
        } catch (Throwable $exception) {
            if (method_exists($exception, 'getExceptions')) {
                throw new BusinessException(current($exception->getExceptions())->getMessage());
            }
            throw $exception;
        }
        return json(['code' => 0, 'msg' => 'ok']);
    }
}
