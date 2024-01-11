<?php

declare(strict_types=1);

namespace Kingbes\Attribute;

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
    protected function get(): array
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
     * 获取数据 function
     *
     * @return array
     */
    public static function data(): array
    {
        // 检查PHP版本
        if (floatval(PHP_VERSION) < 8) {
            throw new \RuntimeException('PHP版本必须大于8.0。您当前的版本是 ' . PHP_VERSION);
        }

        // 读取配置
        $controllerSuffix = config('app.controller_suffix', '');
        $suffixLength = strlen($controllerSuffix);

        // 遍历控制器目录
        $dirIterator = new \RecursiveDirectoryIterator(app_path());
        $iterator = new \RecursiveIteratorIterator($dirIterator);

        $arr = [];
        $i = 0;
        foreach ($iterator as $k => $v) {
            // 忽略非PHP文件和目录
            if (
                $v->getExtension() != 'php' ||
                preg_match("/controller/", strtolower($v->getPathname())) !== 1
            ) {
                continue;
            }
            // 处理带 controller_suffix 后缀的文件
            if ($suffixLength && substr($v->getBaseName('.php'), -$suffixLength) !== $controllerSuffix) {
                continue;
            }

            // 根据文件路径获取类名
            $className = str_replace(
                '/',
                '\\',
                substr(substr($v->getPathname(), strlen(base_path())), 0, -4)
            );

            if (!class_exists($className)) {
                throw new \RuntimeException("没有找到Class $className ，请跳过它的路由!");
            }

            $controller = new \ReflectionClass($className);
            $controllerClass = $controller->getAttributes(self::class);
            if (!isset($controllerClass[0])) {
                throw new \RuntimeException("$className 没有配置注解!");
            }
            $parent = $controllerClass[0]->newInstance()->get();
            $arr[$i] = [
                "title" => $parent["title"] != "" ? $parent["title"] : str_replace(
                    $controllerSuffix,
                    "",
                    $controller->getShortName()
                ),
                "class" => $className,
                "auth" => $parent["auth"],
                "methods" => self::methods(
                    $controller,
                    $className,
                    str_replace($controllerSuffix, "", $controller->getShortName())
                )
            ];
            if (count($parent["add"]) > 0) {
                $arr[$i] = $arr[$i] + $parent["add"];
            }
            $i++;
        }
        return $arr;
    }

    protected static function methods(
        \ReflectionClass $ReflectionClass,
        object|string $className,
        string $shortName
    ): array {
        $arr = [];
        $i = 0;
        preg_match('/app(.*?)controller/', str_replace("\\", "", $className), $app);
        foreach ($ReflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (in_array($method->name, ['__construct', '__destruct'])) {
                continue;
            }

            $attr = (new \ReflectionMethod($className, $method->name))
                ->getAttributes(self::class);

            if (count($attr) < 1) {
                throw new \RuntimeException("$className 的 $method->name 方法没有配置注解!");
            }

            $arr[$i] = $attr[0]->newInstance()->get();
            if (count($arr[$i]["path"]) === 0) {
                $arr[$i]["path"][] = str_replace(
                    "//",
                    "/",
                    "/" . $app[1] . "/" . lcfirst($shortName) . "/{$method->name}"
                );
            }
            if ($arr[$i]["name"] === "") {
                $arr[$i]["name"] =  ltrim($app[1] . "." . lcfirst($shortName) . ".{$method->name}", '.');
            }
            if ($arr[$i]["title"] === "") {
                $arr[$i]["title"] = $method->name;
            }
            $arr[$i]["method"] = $method->name;
            if (count($arr[$i]["add"]) > 0) {
                $arr[$i] = $arr[$i] + $arr[$i]["add"];
            }
            unset($arr[$i]["add"]);
            $i++;
        }
        return $arr;
    }
}
