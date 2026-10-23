# AI Rules Index

本目录存放按路径作用域分组的项目规则。编写或修改任何文件前，必须先阅读所有 glob 覆盖该路径的规则文件。

| 文件通配符 (Globs) | 规则文件 |
| --- | --- |
| `**/*`（全项目通用） | [general.md](./general.md) |
| `routes/**`, `app/Http/Controllers/**`, `app/Http/Requests/**`, `resources/js/pages/**`, `resources/js/app.ts`, `tests/Feature/**`, `config/scramble.php`, `config/fortify.php` | [structure.md](./structure.md) |
| `resources/js/**`, `resources/css/**`, `resources/views/**` | [ui-theme.md](./ui-theme.md) |
| `resources/js/pages/**`, `resources/js/components/**` | [ui-layout.md](./ui-layout.md) |
