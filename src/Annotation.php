<?php

declare(strict_types=1);

namespace Kingbes\Attribute;

use Kingbes\Attribute\Tool;

/**
 * 注解 class
 */
#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
class Annotation
{
    private string $title = ""; // 标题
    private array $path = []; // 路由
    private array $request = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS']; // 请求方式
    private array $middleware = []; // 路由中间件
    private bool $auth = true; // 是否需要权限验证
    private bool $view = false; // 是否是视图
    private string $name = ""; // 路由名称
    private array $add = []; // 追加数据

    /**
     * 注解 function
     *
     * @param string $title 标题
     * @param array $path 路由
     * @param array $request 请求方式
     * @param array $middleware 路由中间件
     * @param boolean $auth 权限验证标识
     * @param boolean $view 视图标识
     * @param string $name 路由名称
     */
    public function __construct(
        string $title = "",
        array $path = [],
        array $request = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'],
        array $middleware = [],
        bool $auth = false,
        bool $view = false,
        string $name = "",
        array $add = []
    ) {
        $this->title = $title;
        $this->path = $path;
        $this->request = $request;
        $this->middleware = $middleware;
        $this->auth = $auth;
        $this->view = $view;
        $this->name = $name;
        $this->add = $add;
    }

    /**
     * 获取注解数据 function
     *
     * @return array
     */
    public function get(): array
    {
        return [
            'title' => $this->title,
            'path' => $this->path,
            'request' => $this->request,
            'middleware' => $this->middleware,
            'auth' => $this->auth,
            'view' => $this->view,
            'name' => $this->name,
            'add' => $this->add
        ];
    }

    /**
     * 获取应用数据 function
     *
     * @param string $use 传app为主应用注解数据 plugin为应用插件注解数据 默认全部
     * @return array
     */
    public static function data(string $use = ""): array
    {
        // 检查PHP版本
        if (floatval(PHP_VERSION) < 8) {
            throw new \RuntimeException('PHP版本必须大于8.0。您当前的版本是 ' . PHP_VERSION);
        }
        if ($use == "app") {
            return Tool::controller(app_path());
        } elseif ($use == "plugin") {
            return Tool::controller(Tool::plugin_path());
        } else {
            return array_merge(Tool::controller(app_path()), Tool::controller(Tool::plugin_path()));
        }
    }
}
