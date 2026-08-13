<?php

declare(strict_types=1);

namespace Kingbes\Attribute;

use Kingbes\Attribute\Annotation;

class Data
{

    /**
     * 数据
     *
     * @var array
     */
    public static array $data = [];

    public static array $route = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        self::$data = [];
        self::$route = [];
        $files = array_merge(
            $this->getControllerFile("app"),
            $this->getControllerFile("plugin")
        );
        $this->reflection($files);
    }

    /**
     * 获取控制器文件
     *
     * @param string $use 
     * @return array
     */
    private function getControllerFile(string $use): array
    {
        $arr = [];
        $path = base_path($use);
        // 文件或文件夹为空就返回
        if (!file_exists($path)) {
            return $arr;
        }
        // 遍历控制器目录
        $dirIterator = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($dirIterator);
        foreach ($iterator as $k => $v) {
            // 忽略非php文件
            if ($v->getExtension() != 'php') {
                continue;
            }
            // 忽略非controller目录
            if (!str_contains(strtolower($v->getPathname()), 'controller')) {
                continue;
            }
            $arr[] = str_replace([base_path(), ".php"], ["", ""], $v->getPathname());
        }
        return $arr;
    }

    /**
     * 反射
     *
     * @param array $arr 控制器文件数组
     * @return void
     */
    private function reflection(array $arr): void
    {
        $controller_suffix = \config('app.controller_suffix', '');
        foreach ($arr as $k => $v) {
            // 非class
            if (!class_exists($v)) {
                continue;
            }
            $class = new \ReflectionClass($v);
            $class_name = str_replace($controller_suffix, "", $class->getShortName()); // 类名
            $class_ann = $this->controller($class); // 注解信息
            $methods_ann = $this->methods($v); // 方法注解信息
            $class_ann["name"] = $class_name;
            $class_ann["methods"] = $methods_ann;
            $class_ann["class"] = $v;
            $this->handle($v, $class_ann);
        }
    }

    /**
     * 合并可重复注解.
     *
     * 同一目标上的多个注解依次合并：标量键后者覆盖前者，'path' 数组键追加合并。
     *
     * @param array $attributes 注解属性数组
     * @return array
     */
    private static function mergeAnnotations(array $attributes): array
    {
        $merged = [];
        foreach ($attributes as $attribute) {
            $data = $attribute->newInstance()->get();
            foreach ($data as $key => $value) {
                if ($key === 'path' && isset($merged['path']) && is_array($merged['path'])) {
                    $merged['path'] = array_merge($merged['path'], (array)$value);
                } else {
                    $merged[$key] = $value;
                }
            }
        }
        return $merged;
    }

    /**
     * 控制器注解信息
     *
     * @param \ReflectionClass $class
     * @return array
     */
    private function controller(\ReflectionClass $class): array
    {
        return static::mergeAnnotations($class->getAttributes(Annotation::class));
    }

    /**
     * 方法注解信息
     *
     * @param string $class_str 类名
     * @return array
     */
    private function methods(string $class_str): array
    {
        $class = new \ReflectionClass($class_str);
        $arr = [];
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            // 处理方法
            $name = $method->getName();
            if (in_array($name, ['__construct', '__destruct'])) {
                continue;
            }
            $annotation = new \ReflectionMethod($class_str, $name)
                ->getAttributes(Annotation::class);
            $ann = static::mergeAnnotations($annotation);
            // 用户自定义路由名（webman ->name()，用于模板 {:url("xxx")} 反查）
            $userRouteName = $ann['name'] ?? null;
            $ann["path"] = $ann["path"] ?? [];
            $ann["request"] = $ann["request"] ?? ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
            $ann["middleware"] = $ann["middleware"] ?? [];
            $ann["name"] = $name;
            if ($userRouteName !== null) {
                $ann["route_name"] = $userRouteName;
            }
            $arr[] = $ann;
        }
        return $arr;
    }

    /**
     * 处理
     *
     * @param string $class_name 完整类名
     * @param array $data 数据
     * @return void
     */
    private function handle(string $class_name, array $data): void
    {
        if (strncmp($class_name, '\app', 4) === 0) {
            $use = "app";
            if (preg_match('/.*?app\\\\([^\\\\]+)\\\\controller.*/', $class_name, $matches)) {
                $app = $matches[1];
                self::$data[$use][$app][] = $data;
                $data["app"] = $app;
            } else {
                self::$data[$use][] = $data;
            }
            
        }
        if (strncmp($class_name, '\plugin', 7) === 0) {
            $use = "plugin";
            if (preg_match('/.*?plugin\\\\([^\\\\]+)\\\\app.*/', $class_name, $m01)) {
                $author = $m01[1];
                if (preg_match('/.*?app\\\\([^\\\\]+)\\\\controller.*/', $class_name, $m02)) {
                    $app = $m02[1];
                    self::$data[$use][$author][$app][] = $data;
                    $data["app"] = $app;
                } else {
                    self::$data[$use][$author][] = $data;
                }
                $data["author"] = $author;
            }
        }
        self::$route[] = $data;
    }

    /**
     * 大驼峰转小驼峰 function
     *
     * @param string $string 字符串
     * @return string
     */
    public function bc2sc(string $string): string
    {
        $firstChar = substr($string, 0, 1);
        $rest = substr($string, 1);
        $smallCamelString = strtolower($firstChar) . $rest;
        return $smallCamelString;
    }

    /**
     * 大驼峰转字符拼接 function
     *
     * @param string $string 字符串
     * @param string $us 拼接字符
     * @return string
     */
    public function bc2us(string $string, string $us = "_"): string
    {
        $newString = '';
        $stringLength = strlen($string);
        for ($i = 0; $i < $stringLength; $i++) {
            $char = $string[$i];
            if ($i > 0 && ctype_upper($char)) {
                $newString .= $us;
            }
            $newString .= strtolower($char);
        }
        return $newString;
    }

    /**
     * 名称整理
     *
     * @param string $str
     * @return string
     */
    public function toName(string $str): string
    {
        $name = trim($str, "/");
        return str_replace("/", ".", $name);
    }
}
