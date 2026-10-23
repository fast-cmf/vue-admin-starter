# Vue Admin Starter

基于 Laravel 13 + Vue 3 的中后台管理启动模板，参照 [laravel/vue-starter-kit](https://github.com/laravel/vue-starter-kit) 的技术选型，内置一套完整的中后台基础设施，开箱即用。

## 功能特性

- **认证体系**：Laravel Fortify 提供登录、注册、找回密码、邮箱验证、两步验证，认证路由统一挂载在 `/admin` 前缀下
- **RBAC 权限**：基于 spatie/laravel-permission，权限标识唯一来源为 `config/permissions.php`，支持 `php artisan permission:sync` 同步与路由漂移检查；前端按钮与后端路由双层权限防线
- **菜单管理**：侧边栏完全由数据库驱动，「目录 + 菜单 + 按钮」三级树形结构，支持图标、排序、权限绑定与页面路由下拉选择
- **用户管理**：创建/编辑用户、角色分配、用户拉黑（登录与已登录会话双重拦截）
- **角色管理**：内置超级管理员（Gate::before 全放行），自定义角色通过权限矩阵按组授权
- **媒体中心**：spatie/laravel-medialibrary 驱动，支持 Aliyun OSS（S3 兼容协议）动态切换
- **系统设置**：spatie/laravel-settings 驱动的存储配置与邮箱配置，支持发送测试邮件
- **日志审计**：登录日志（IP/浏览器/平台/结果）与操作日志（请求参数、敏感信息自动脱敏），支持批量删除、清空与 CSV 导出
- **API 文档**：dedoc/scramble 自动生成，访问 `/docs/api`
- **前端栈**：Inertia v3 + Vue 3 + Wayfinder + shadcn-vue + Tailwind CSS，中文化界面

## 环境要求

- PHP >= 8.3
- Composer
- Node.js >= 20
- SQLite（默认）或 MySQL / PostgreSQL

## 安装

### 方式一：Laravel 安装器（推荐）

使用 [Laravel 安装器](https://laravel.com/docs/installation)（`laravel/installer` >= 5.7）从模板创建项目，会自动完成依赖安装、密钥生成与数据库迁移：

```bash
laravel new your-project --using=fast-cmf/vue-admin-starter
cd your-project
```

然后创建首个超级管理员并启动：

```bash
php artisan app:install
composer dev
```

也可以非交互执行：

```bash
php artisan app:install --name=管理员 --email=admin@example.com --password=你的密码
```

### 方式二：Use this template

1. 点击仓库右上角 **Use this template** 创建你的项目
2. 克隆到本地后执行：

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite

npm install
npm run build
```

3. 一键安装（迁移数据库、写入初始角色与菜单、创建首个超级管理员）：

```bash
php artisan app:install
```

也可以非交互执行：

```bash
php artisan app:install --name=管理员 --email=admin@example.com --password=你的密码
```

4. 启动开发服务：

```bash
composer dev
```

访问 `http://localhost:8000/admin/login` 使用安装时创建的账号登录。

### 方式三：Composer create-project

```bash
composer create-project fast-cmf/vue-admin-starter your-project
cd your-project
php artisan app:install
composer dev
```

## 权限体系

- 权限标识唯一来源是 `config/permissions.php`（`模块.动作` 形式，如 `users.create`）
- 新增后台模块时：登记权限点 → 路由挂 `permission:` 中间件 → 执行 `php artisan permission:sync`
- 侧边栏菜单在「菜单管理」中维护，每个菜单可挂「按钮」类型的权限标识子项
- 前端按钮显隐使用全局 `can('xxx.yyy')`（见 `resources/js/lib/can.ts`）

## 目录结构

```
routes/
├── home.php          # 前台公开路由（/ 前缀）
├── admin.php         # 后台路由入口（/admin 前缀 + admin. 路由名前缀）
├── admin/*.php       # 后台分模块路由
├── api.php           # API 路由入口（/api/v1 前缀）
└── api/v1.php        # API 版本化路由

app/
├── Console/Commands/ # permission:sync、app:install 等命令
├── Http/Controllers/ # Admin\ / Home\ / Api\V1\ 三端划分
├── Settings/         # StorageSettings、MailSettings
└── Support/          # 轻量工具类

resources/js/
├── pages/admin/      # 后台页面
├── pages/home/       # 前台页面
├── pages/auth/       # 认证页面
└── lib/can.ts        # 前端权限判断
```

## 测试与代码质量

```bash
php artisan test --compact   # 运行全量测试（Pest）
composer lint                # 代码风格修复（Pint）
composer types:check         # 静态分析（PHPStan / Larastan）
```

## License

[MIT](LICENSE)
