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
foreach (Data::$route as $k => $v) {
    $plugin = isset($v["author"]) ? "/plugin/" . $v['author'] : "";
    $app = isset($v["app"]) ? "/" . $v['app'] : "";
    
    foreach ($v['methods'] as $method) {
        $path = $plugin . $app . "/" . $ann_data->bc2us($v["name"]) . "/" . $ann_data->bc2us($method["name"]);
        Route::add(
            $method["request"],
            $path,
            [$v["class"], $method["name"]]
        )->middleware($method["middleware"])
            ->name($ann_data->toName($path));
        foreach ($method["path"] as $p) {
            Route::add(
                $method["request"],
                $p,
                [$v["class"], $method["name"]]
            )->middleware($method["middleware"])
                ->name($ann_data->toName($path));
        }
    }
}

// 禁用默认路由
Route::disableDefaultRoute();
