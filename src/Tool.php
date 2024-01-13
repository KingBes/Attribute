<?php

declare(strict_types=1);

namespace Kingbes\Attribute;

class Tool
{
    /**
     * 控制器注解 function
     *
     * @param string $path
     * @return array
     */
    public static function controller(string $path): array
    {
        // 读取配置
        $controllerSuffix = config('app.controller_suffix', '');
        $suffixLength = strlen($controllerSuffix);

        // 遍历控制器目录
        $dirIterator = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($dirIterator);

        $arr = [];

        // 文件或文件夹为空就返回
        if (!file_exists($path)) {
            return $arr;
        }

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
            // 忽略应用
            if (self::is_route_lose($v->getPathname())) {
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
            $controllerClass = $controller->getAttributes(Annotation::class);
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

    /**
     * 方法注解信息 function
     *
     * @param \ReflectionClass $ReflectionClass
     * @param object|string $className
     * @param string $shortName
     * @return array
     */
    public static function methods(
        \ReflectionClass $ReflectionClass,
        object|string $className,
        string $shortName
    ): array {
        $arr = [];
        $i = 0;

        $app = self::prefix_route_name($className);

        foreach ($ReflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (in_array($method->name, ['__construct', '__destruct'])) {
                continue;
            }

            $attr = (new \ReflectionMethod($className, $method->name))
                ->getAttributes(Annotation::class);

            if (count($attr) < 1) {
                throw new \RuntimeException("$className 的 $method->name 方法没有配置注解!");
            }

            $arr[$i] = $attr[0]->newInstance()->get();
            if (count($arr[$i]["path"]) === 0) {
                $arr[$i]["path"] = self::get_route_path($app, $shortName, $method->name);
            }
            if ($arr[$i]["name"] === "") {
                $arr[$i]["name"] =  self::get_route_name($app, $shortName, $method->name);
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

    /**
     * 是否忽略 function
     *
     * @return boolean
     */
    private static function is_route_lose(string $str): bool
    {
        // 忽略的应用
        $route_lose = config('plugin.kingbes.attribute.attribute.route_lose', []);
        $str = str_replace("\\", ".", $str);
        $res = false;
        // 判断字符串是否包含数组中的任何字符
        foreach ($route_lose as $char) {
            if (strpos($str, $char) !== false) {
                $res = true;
            } else {
                continue;
            }
        }
        return $res;
    }

    /**
     * 获取路由名前缀 function
     *
     * @param string $str 路径
     * @return string
     */
    private static function prefix_route_name(string $str): string
    {
        preg_match('/(.*?)app(.*?)controller/', str_replace(["plugin", "\\"], ["", ""], $str), $use);
        $res = "";
        if ($use[1] == "") {
            $res = "app/" . $use[2];
        } else if ($use[1] !== "") {
            $res = "plugin/" . $use[1];
        }
        return $res;
    }

    /**
     * 获取路由名称 function
     *
     * @param string $app 路由名前缀
     * @param string $shortName 短class名
     * @param string $method 方法
     * @return string
     */
    private static function get_route_name(string $app, string $shortName, string $method): string
    {
        $res = str_replace("..", ".", ltrim(str_replace(["/"], ["."], $app) .
            "." . lcfirst($shortName) .
            ".{$method}", '.'));
        $bind = config('plugin.kingbes.attribute.attribute.bind', []);
        $res = str_replace(array_values($bind), array_keys($bind), $res);
        return $res;
    }

    /**
     * 获取路由path function
     *
     * @param string $app 路由名前缀
     * @param string $shortName 短class名
     * @param string $method 方法
     * @return array
     */
    private static function get_route_path(string $app, string $shortName, string $method): array
    {
        $res = str_replace(
            "//",
            "/",
            "/" . $app . "/" . lcfirst($shortName) . "/{$method}"
        );
        $bind = config('plugin.kingbes.attribute.attribute.bind', []);
        $res = str_replace(array_values($bind), array_keys($bind), str_replace("/", ".", $res));
        $res = str_replace(".", "/", $res);
        $arr[] = $res;
        $default = config('plugin.kingbes.attribute.attribute.default', 'index');
        if (strpos($res, "/" . $default) === 0) {
            $arr[] = "/";
        }
        return $arr;
    }

    /**
     * plugin path
     *
     * @param string $path
     * @return string
     */
    public static function plugin_path(string $path = ''): string
    {
        return path_combine(BASE_PATH . DIRECTORY_SEPARATOR . 'plugin', $path);
    }
}
