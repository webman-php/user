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
        $file = runtime_path("tmp/limit/minute-" . date('Hi') . "-$key.limit");
        $path = dirname($file);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (!is_file($file)) {
            if (!preg_match('/^[0-9a-zA-Z\-_.]+$/', $key)) {
                throw new RuntimeException('$key只能是字母和数字以及(-_.)的组合');
            }
            file_put_contents($file, 1);
            $time = time() - 60;
            foreach (glob(runtime_path('tmp/limit/minute-*')) as $file) {
                if (filemtime($file) < $time) {
                    unlink($file);
                }
            }
            return;
        }
        $count = (int)file_get_contents($file);
        if ($count++ >= $maxRequests) {
            throw new BusinessException('请求速度过快，请稍后访问');
        }
        file_put_contents($file, $count);
    }

}