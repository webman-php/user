<?php
namespace plugin\user\api;

use ReflectionClass;
use ReflectionException;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use function redirect;
use function session;

class Middleware implements MiddlewareInterface
{

    /**
     * 排除的应用
     * @var array
     */
    protected $excludedApps = [];

    /**
     * 构造函数
     */
    public function __construct($excludedApps = [])
    {
        $this->excludedApps = $excludedApps;
    }

    /**
     * 权限验证
     * @param Request $request
     * @param callable $next
     * @return Response
     * @throws ReflectionException
     */
    public function process(Request $request, callable $next) : Response
    {
        // 当前请求的应用属于排除列表，则忽略
        if (in_array($request->app, $this->excludedApps)) {
            return $next($request);
        }

        $controller = $request->controller;
        // 闭包路由需要自己鉴权
        if (!$controller || session('user')) {
            return $next($request);
        }

        $action = $request->action;
        $class = new ReflectionClass($controller);
        $properties = $class->getDefaultProperties();
        $noNeedLogin = $properties['noNeedLogin'] ?? [];

        // 访问的方法需要登录
        if ($noNeedLogin !== '*' && !in_array($action, $noNeedLogin)) {
            return redirect('/app/user/login');
        }

        return $next($request);
    }
    
}
