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
use Kingbes\Attribute\Annotation;

// 获取已设置路由的URI列表
$routes = Route::getRoutes();
$ignoreList = array_map(fn ($tmpRoute) => $tmpRoute->getPath(), $routes);

$Annotation = Annotation::data();

foreach ($Annotation as $k => $v) {
    foreach ($v["methods"] as $method) {
        foreach ($method["path"] as $path) {
            if (!in_array($path, $ignoreList)) {
                Route::add($method["request"], $path, [$v["class"], $method["method"]])
                    ->middleware($method["middleware"])
                    ->name($method["name"]);
            } else {
                continue;
            }
        }
    }
}
