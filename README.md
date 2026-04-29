# Attribute
🚀 🔥 🌈 基于webman使用 KingBes/Attribute 包实现的注解路由，中间件，权限标识，视图标识等解决方案
# PHP8的注解方案

## 更新日志

### v1.0.1

获取注解信息
```php
use Kingbes\Attribute\Data; //引入

Data::$data // 获取全部注解信息
```


## 安装
```shell
composer require kingbes/attribute
```

## 使用，建议结合php8的命名参数使用
```php
use Kingbes\Attribute\Annotation; //引入

#[Annotation([
    "title": "首页的",
])]
class IndexController
{
    #[Annotation([
        "title": "首页",
        "path": ["/index", "/", "/home"],
        "request": ["get", "post"],
        "auth": true,
        ])]
    public function index(Request $request)
    {
        return json(Annotation::data());
    }
}
```
request 默认：['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS']
middleware 默认： []
path 默认： []  ，path 为空时，会自动获取类名和方法名作为路由 