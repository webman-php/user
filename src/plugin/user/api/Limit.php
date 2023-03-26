<?php

namespace plugin\user\api;

use RuntimeException;
use support\exception\BusinessException;

/**
 * 频率限制接口
 */
class Limit
{

    /**
     * 按分钟限制频率
     * @param $key
     * @param $maxRequests
     * @return void
     * @throws BusinessException
     */
    public static function perMinute($key, $maxRequests)
    {
        $prefix = 'minute-';
        $file = date('YmdHi') . "-$key.limit";
        static::by($key, $maxRequests, $prefix, $file);
    }

    /**
     * 按分钟限制频率
     * @param $key
     * @param $maxRequests
     * @return void
     * @throws BusinessException
     */
    public static function perDay($key, $maxRequests)
    {
        $prefix = 'day-';
        $file = date('Ymd') . "-$key.limit";
        static::by($key, $maxRequests, $prefix, $file);
    }

    /**
     * 通用频率限制
     * @param $key
     * @param $maxRequests
     * @param $file
     * @param $prefix
     * @return void
     * @throws BusinessException
     */
    public static function by($key, $maxRequests, $prefix, $file)
    {
        $basePath = '/tmp/limit';
        $file = runtime_path("$basePath/{$prefix}$file");
        $path = dirname($file);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (!is_file($file)) {
            if (!preg_match('/^[0-9a-zA-Z\-_.]+$/', $key)) {
                throw new RuntimeException('$key只能是字母和数字以及(-_.)的组合');
            }
            foreach (glob(runtime_path("$basePath/$prefix*")) as $expiredFile) {
                unlink($expiredFile);
            }
            file_put_contents($file, 1);
            return;
        }
        $count = (int)file_get_contents($file);
        if ($count++ >= $maxRequests) {
            throw new BusinessException('请求速度过快，请稍后访问');
        }
        file_put_contents($file, $count);
    }

}
