<?php

namespace plugin\user\api;

use support\exception\BusinessException;
use Webman\Http\Request;
use Webman\Http\Response;

class FormException extends BusinessException
{
    /**
     * 错误字段
     * @var mixed|null
     */
    public $field;

    /**
     * Construct
     * @param string $message
     * @param int $code
     * @param $field
     */
    public function __construct(string $message = "", int $code = 0, $field = null)
    {
        $this->field = $field;
        parent::__construct($message, $code);
    }

    /**
     * Render
     * @param Request $request
     * @return Response|null
     */
    public function render(Request $request): ?Response
    {
        if ($request->expectsJson()) {
            $code = $this->getCode();
            $json = ['code' => $code ?: 500, 'msg' => $this->getMessage(), 'data' => [
                'field' => $this->field
            ]];
            return new Response(200, ['Content-Type' => 'application/json'],
                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        return new Response(200, [], $this->getMessage());
    }
}