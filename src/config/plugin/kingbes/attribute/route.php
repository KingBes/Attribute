<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;
use Kingbes\Attribute\Data;

$ann_data = new Data();

// 注册单条路由
$register = function (string $class, array $method, string $path) use ($ann_data) {
    $route = Route::add(
        $method["request"],
        $path,
        [$class, $method["name"]]
    )->middleware($method["middleware"]);
    // 自定义路由名优先，否则用默认生成的路径名
    $name = $method["route_name"] ?? $ann_data->toName($path);
    if ($name !== '') {
        $route->name($name);
    }
};

foreach (Data::$route as $v) {
    $plugin = isset($v["author"]) ? "/plugin/" . $v['author'] : "";
    $app = isset($v["app"]) ? "/" . $v['app'] : "";
    $controllerPath = $plugin . $app . "/" . $ann_data->bc2us($v["name"]);

    foreach ($v['methods'] as $method) {
        $methodName = $ann_data->bc2us($method["name"]);
        $fullPath = $controllerPath . "/" . $methodName;

        // 完整路由：/{控制路径}/{方法名下划线}
        $register($v["class"], $method, $fullPath);

        // index 方法优化：兼容去掉末尾 /index 的路径
        // 例：/about/index -> /about，/user/about/index -> /user/about
        // 根控制器 IndexController::index（/index/index）兼容 "/"
        if ($methodName === 'index') {
            $alias = $controllerPath === '/index' ? '/' : $controllerPath;
            $register($v["class"], $method, $alias);
        }

        // 注解自定义 path
        foreach ($method["path"] as $p) {
            $register($v["class"], $method, $p);
        }
    }
}

// 禁用默认路由
Route::disableDefaultRoute();