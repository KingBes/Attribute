<?php

return [
    // 应用映射
    "bing" => [
        // "app.admin" 是admin应用 ; "plugin.foo" 是foo应用插件 ; 单应用 "app."
        "index" => "app."
    ],
    // 默认应用 应用映射的key
    "default" => "index",
    // 忽略的应用路由 例如: ["app.admin","plugin.foo"] 表示忽略admin应用和foo应用的路由注解
    "route_lose" => []
];
