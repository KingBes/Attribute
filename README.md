# Attribute
🚀 🔥 🌈 基于webman使用 KingBes/Attribute 包实现的注解路由，中间件，权限标识，视图标识等解决方案
# PHP8的注解方案

## 更新日志

### v1.0.0
1. 应用插件也可注解路由
2. 可给应用或应用插件命名

下面是config/plugin/kingbes/attribute/attribute.php 注解配置

```php
return [
    // 应用映射
    "bind" => [
        // "app.admin" 是admin应用 ; "plugin.foo" 是foo应用插件 ; 单应用 "app"
        "index" => "app",
        "blog" => "plugin.kbBlog"
    ],
    // 默认应用 应用映射的key
    "default" => "blog",
    // 忽略的应用路由 例如: ["app.admin","plugin.foo"] 表示忽略admin应用和foo应用插件的路由注解
    "route_lose" => []
];

```

获取注解信息
```php
use Kingbes\Attribute\Annotation; //引入

Annotation::data() // 获取全部注解信息
Annotation::data("app") // 获取主应用的注解信息 
Annotation::data("plugin") // 获取应用插件的注解信息
```


## 安装
```shell
composer require kingbes/attribute
```

## 使用，建议结合php8的命名参数使用
```php
use Kingbes\Attribute\Annotation; //引入

#[Annotation(
    title: "首页的",
    add: ["classnew" => "class新增一个"]
)]
class IndexController
{
    #[Annotation(
        title: "首页",
        path: ["/index", "/", "/home"],
        request: ["get", "post"],
        auth: true,
        add: ["newfun" => "方法新增一个"]
    )]
    public function index(Request $request)
    {
        return json(Annotation::data());
    }
}

```
其中：return json(Annotation::data()); 得到结果
```jsonc
[
    {
        "title": "首页的", // 默认：无Controller后缀的短class名
        "class": "\\app\\controller\\IndexController", // class
        "auth": false, // 权限标识 默认： false
        "methods": [ // 方法数组
            {
                "title": "首页", // 默认：method名
                "path": [ // 路由路径组 默认：/无Controller后缀的短class名/method名  比如：/index/home
                    "/index",
                    "/",
                    "/home"
                ],
                "request": [ // 可请求方式 默认： ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS']
                    "get",
                    "post"
                ],
                "middleware": [], // 中间件
                "auth": true, // 权限标识
                "view": false, // 视图标识
                "name": "index.index", // 路由名 默认：无Controller后缀的短class名.method名  比如: index.index
                "method": "index", // method名
                "newfun": "方法新增一个" // 新增数据 默认：不存在
            }
        ],
        "classnew": "class新增一个" // 新增数据 默认：不存在
    }
]
```
class中仅title、add、auth有效