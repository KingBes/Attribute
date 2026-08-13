# KingBes/Attribute

> 🚀 基于 webman 的 PHP 8 原生 Attribute（注解）路由方案，通过注解声明路由、中间件、权限标识、视图标识等信息。

## 环境要求

- PHP >= 8.0
- webman 框架

## 安装

```shell
composer require kingbes/attribute
```

## 快速开始

在控制器类或方法上添加 `#[Annotation(...)]` 注解，数组键值使用 `=>` 语法。

```php
use Kingbes\Attribute\Annotation;

#[Annotation([
    "title" => "首页控制器",
])]
class IndexController
{
    #[Annotation([
        "title"   => "首页",
        "path"    => ["/index", "/", "/home"],
        "request" => ["get", "post"],
        "auth"    => true,
    ])]
    public function index(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }
}
```

## 注解参数

`#[Annotation(array $data)]` 支持以下键：

| 键 | 类型 | 必须 | 说明 |
|----|------|------|------|
| `title` | string | 否 | 标题标识，用于权限/视图等场景 |
| `path` | string[] | 否 | 自定义路由路径。为空时自动使用类名 + 方法名生成路由 |
| `request` | string[] | 否 | 允许的 HTTP 方法，见下方默认值 |
| `middleware` | string[] | 否 | 路由中间件，默认 `[]` |
| `auth` | bool | 否 | 权限标识 |
| `name` | string | 否 | 自定义路由名（webman `->name()`），可覆盖默认名 |

### 默认值

- `request` 默认：`['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS']`
- `middleware` 默认：`[]`
- `path` 默认：`[]`，为空时自动取 `类名下划线/方法名下划线` 作为路由
- `name` 默认: `应用.控制器.方法`

### 自定义路由名

`name` 用于设置 webman 路由名（`->name()`），可在模板中通过 `{:route("路由名")}` 反查生成 URL，无需手写路径。指定后覆盖该方法的默认路由名：

```php
#[Annotation([
    "name" => "other.hello",
])]
public function sayHello()
{
    // 模板中可用 {:route("other.hello")} 生成该方法的 URL
}
```

### 可重复注解

同一目标（类或方法）可声明多个 `#[Annotation]`，重复注解会合并：

- 标量键（如 `title`）后者覆盖前者；
- `path` 数组会追加合并。

```php
#[Annotation(["path" => ["/a"]])]
#[Annotation(["path" => ["/b"]])]
public function index()
{
    // 同时注册 /a 与 /b 两个路由
}
```

## 注解信息

通过 `Data` 类获取全部注解信息：

```php
use Kingbes\Attribute\Data;

Data::$data  // 按 app / plugin 层级分组的全部注解
```

## 路由规则

- 应用路由：`/{控制器名下划线}/{方法名下划线}`
- 插件路由：`/plugin/{作者}/{应用}/{控制器名下划线}/{方法名下划线}`
- 使用本插件后默认路由会被禁用，所有路由均需通过注解声明。

### index 路由别名

`index` 方法除完整路径外，会自动注册一个去掉末尾 `/index` 的别名路径：

| 控制器 | 完整路径 | 别名路径 |
|--------|----------|----------|
| `IndexController::index`（单应用根） | `/index/index` | `/` |
| 默认 `index` 应用 `IndexController::index` | `/index/index/index` | `/` |
| `AboutController::index` | `/about/index` | `/about` |
| `user` 应用 `AboutController::index` | `/user/about/index` | `/user/about` |

仅在 controllerPath 只由 `index` 段组成时（`/index` 或 `/index/index`），别名才映射根 `/`；其他应用（如 `/admin/index`）保留去掉末尾 `index` 的路径，不抢占根路由。如需自定义根路径归属，可在 `index` 方法上用 `path` 显式指定。

## 测试

在 webman 项目中用 `route:list` 命令查看注解生成的路由：

```shell
php webman route:list
```

启动服务后逐个访问验证：

```shell
php windows.php
```

检查点：
- 默认路由（`/{控制器名下划线}/{方法名下划线}`）
- `index` 别名路由（去掉末尾 `/index`）
- 自定义 `path` 与自定义路由名 `name`

## 限制

- 控制器文件需位于 `app/**/controller` 或 `plugin/**/app/**/controller` 目录下，否则不会被扫描。
- 注解数组键值必须使用 PHP 数组语法 `=>`，不能使用 `:`。
- 仅扫描公共方法，`__construct`、`__destruct` 会被忽略。