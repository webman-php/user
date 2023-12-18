<?php

namespace plugin\user\api;

use Exception;
use plugin\user\app\model\User;
use support\exception\BusinessException;

/**
 * 验证码相关
 */
class Captcha
{

    /**
     * 创建验证码数据
     * @param null $item 手机号或者邮箱
     * @param null $chars
     * @return array
     * @throws Exception
     */
    public static function create($item = null, $chars = null): array
    {
        $code = static::buildCode($chars);
        return [
            'item' => $item,
            'code' => $code,
            'time' => time(),
        ];
    }

    /**
     * 验证验证码数据
     * @param $item
     * @param $code
     * @param $data
     * @throws BusinessException
     */
    public static function verify($item, $code, $data)
    {
        // 邮箱、手机验证码10分钟过期
        $ttl = 10 * 60;
        if (!is_array($data) || strtolower((string)$data['code']) !== strtolower((string)$code)) {
            throw new BusinessException('验证码不正确', 11);
        }
        if ($item && time() - $data['time'] > $ttl) {
            throw new BusinessException('验证码已经过期', 10);
        }
        if ($item !== $data['item']) {
            throw new BusinessException('验证码不一致', 12);
        }
    }

    /**
     * 检验 email mobile 字段
     * @param string $type mobile or email
     * @param string|int $item 具体的手机号或email地址
     * @param string|false $code 验证码
     * @param mixed $captchaData 验证码数据
     * @return void
     * @throws BusinessException
     */
    public static function validate(string $type, $item, $code = false, $captchaData = null)
    {
        $name = $type === 'email' ? '邮箱' : '手机';
        if ($type === 'email') {
            if (!filter_var($item, FILTER_VALIDATE_EMAIL)) {
                throw new FormException('邮箱格式错误', 1, 'email');
            }
        } else {
            if (!preg_match('/^1[3456789]\d{9}$/', $item)){
                throw new FormException('手机号格式错误', 1, 'mobile');
            }
        }
        if ($code !== false) {
            try {
                Captcha::verify($item, $code, $captchaData);
            } catch (BusinessException $exception) {
                throw new FormException($name . $exception->getMessage(), $exception->getCode(), "{$type}_code");
            }
        }
    }

    /**
     * 获取code
     * @param string|null $chars
     * @return string
     * @throws Exception
     */
    public static function buildCode(string $chars = null): string
    {
        $chars = $chars ?: 'abcdefghjkmnpqrstuvwxyz2345678';
        $length = 4;
        $charsLength = strlen($chars);
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $charsLength - 1)];
        }
        return $code;
    }

}
