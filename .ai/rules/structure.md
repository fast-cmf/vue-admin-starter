# 三端代码结构规则（admin / api / home）

适用范围：`routes/`、`app/Http/Controllers/`、`app/Http/Requests/`、`resources/js/pages/`、`tests/Feature/`。新增任何控制器、路由、页面、测试前，必须先按本规则确定归属。

## 一、三端划分

| 端 | 职责 | URL 前缀 | 路由名前缀 |
| --- | --- | --- | --- |
| **home** | 前台公开门户（无需登录） | `/` | 无 |
| **admin** | 后台管理（dashboard、媒体中心、个人设置等，需登录） | `/admin` | `admin.` |
| **api** | 对外接口 | `/api/v1` | `api.v1.` |

## 二、路由文件结构

```
routes/
├── web.php          # web 聚合入口（bootstrap/app.php 的 withRouting 引用）：require home.php + admin.php
├── home.php         # 前台公开路由（/、.well-known/passkey-endpoints）
├── admin.php        # 后台聚合：Route::prefix('admin')->name('admin.')，require admin/ 子文件
├── admin/           # 后台子模块路由（media.php、settings.php，新增模块按此扩展）
├── api.php          # API 入口：Route::prefix('v1')->name('api.v1.')，require api/v1.php
└── api/v1.php       # v1 接口定义
```

- 后台新模块：在 `routes/admin/` 下新建子文件并在 `admin.php` 中 require。
- API 新版本：建 `routes/api/v2.php` 并在 `api.php` 中新增版本分组。

## 三、后端目录结构

```
app/Http/Controllers/
├── Controller.php      # 基类保留根命名空间
├── Home/               # 前台控制器
├── Admin/              # 后台控制器（Media/、Settings/ 按业务模块分子目录）
└── Api/V1/             # API v1 控制器

app/Http/Requests/
└── Admin|Home|Api/V1/  # 与控制器同端同模块组织
```

- Form Requests 与控制器归属一致。
- `app/Actions/Fortify/`、`app/Concerns/` 属认证公共逻辑，保持不动。
- API 控制器要求：方法显式声明返回类型；列表接口使用 Eloquent API Resource（保证 Scramble 能推断文档）。

## 四、前端页面结构

```
resources/js/pages/
├── home/               # 前台页面（Index.vue 等）
├── auth/               # 登录注册等公共认证页（不属于任何端）
└── admin/              # 后台页面（Dashboard.vue、media/、settings/ 等）
```

- Inertia 组件名 = pages 下相对路径（如 `admin/Dashboard`、`home/Index`）。
- `resources/js/layouts/`、`resources/js/components/` 为跨端复用，不按模块拆分。
- `app.ts` 的 `layout` 函数按组件名前缀匹配布局（`home/Index` 无布局、`auth/` 认证布局、`admin/settings/` 双层布局、其余 AppLayout）。
- 控制器/路由变更后必须运行 `php artisan wayfinder:generate`，前端 import 从 `@/actions/App/Http/Controllers/...`（跟随后端命名空间）与 `@/routes/...`（跟随路由文件路径，如 `@/routes/admin/media`）。

## 五、测试结构

```
tests/Feature/
├── Admin/              # 后台测试（Media/、Settings/ 子目录）
├── Auth/               # 认证测试
└── Home/               # 前台测试
```

## 六、API 文档（Scramble）

- 使用 `dedoc/scramble`，`config/scramble.php` 中 `api_path = 'api/v1'`。
- 文档地址：UI `/docs/api`，OpenAPI JSON `/docs/api.json`。
- 仅 `/api/v1/*` 路由会被收录，API 路由必须挂 v1 前缀下。

## 七、命名速查

| 场景 | 写法 |
| --- | --- |
| 登录后跳转 | `/admin/dashboard`（config/fortify.php `home`） |
| 后台跳转 | `to_route('admin.media.index')`、`route('admin.profile.edit')` |
| Inertia 渲染 | `Inertia::render('admin/media/Index', ...)` |
| Wayfinder 引用 | `import MediaAssetController from '@/actions/App/Http/Controllers/Admin/Media/MediaAssetController'` |
