# 后台页面 UI 布局规则

适用范围：`resources/js/pages/**`（后台及所有新页面）。目的：保证各页面骨架、间距、工具栏、弹窗结构一致。配色与组件选型规则见 [ui-theme.md](./ui-theme.md)，本文件只管布局。

> 以下模式提炼自 media/Index.vue、logs/login/Index.vue、settings/*.vue 的共同骨架；`admin/Dashboard.vue` 为脚手架占位页，不作为参考。新页面必须遵循本规则，与既有页面保持一致。

## 1. 页面骨架（固定顺序，勿增删层级）

```vue
<Head title="页面名" />

<h1 class="sr-only">页面名</h1>

<div class="flex flex-col gap-6 p-4 lg:p-6">
    <!-- 顶部标题区 -->
    <!-- 内容区 -->
</div>
```

- `Head` 的 title 与页面中文名一致。
- 必须通过 `defineOptions({ layout: { breadcrumbs: [{ title: '页面名', href: 路由函数() }] } })` 声明面包屑。
- 例外：设置页（`admin/settings/`）由 SettingsLayout 提供内边距，外层容器用 `flex flex-col space-y-6`，不再套 `p-4 lg:p-6`。

## 2. 顶部标题区

- 标题一律用 `Heading variant="small"` 组件，必填 `title` + `description`（一句话说明页面用途）。
- 标题右侧放该页唯一的主操作按钮（如「上传」）：外层 `flex items-start justify-between gap-4`，按钮用默认 primary 变体 + lucide 图标（`class="size-4"`）。

## 3. 列表页工具栏

- 结构：左侧筛选控件、右侧操作按钮 —— `flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between`（窄屏堆叠、sm 起横排）。
- 筛选控件：搜索框 `Input`（`sm:w-64`）+ 状态下拉 `Select`（`sm:w-32`），纵向堆叠时 `flex flex-col gap-2 sm:flex-row sm:items-center`。
- 按钮组：`flex items-center gap-2`；次要操作（清空、导出）用 `variant="outline"`，破坏性操作（删除）用 `variant="destructive"`，依赖选中状态时未选中必须 `disabled`。
- 数量徽标行：`<Badge variant="secondary">共 N 条</Badge>`；有选中项时在右侧补充 `text-xs text-muted-foreground` 的「已选中 N 条」。

## 4. 内容容器与间距

- 表格/列表容器：`overflow-hidden rounded-xl border bg-card`。
- 网格卡片：`rounded-xl border bg-card shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md`。
- 间距体系：区块之间 `gap-5` / `gap-6`，行内元素 `gap-2` / `gap-3`，禁止其他任意间距值。

## 5. 空状态

- 表格内空状态：`TableBody` 中一行 `colspan` 跨全列，`h-32 text-center text-muted-foreground`，图标（`size-8`，`mb-2`/`gap-2`）+ `text-sm` 文案。
- 独立空状态：`rounded-xl border border-dashed py-20` 居中，图标 `size-10 mb-3`。
- 文案格式：「暂无 XX」+ 简短引导（可选）。

## 6. 分页

- 复用 shadcn-vue `Pagination` 系列（Previous / List / Ellipsis / Next），渲染条件 `v-if="xxx.last_page > 1"`。
- 尾部固定说明：`第 X / Y 页，共 N 条`（`ml-2 text-xs text-muted-foreground`）。

## 7. 表格

- 必须用 shadcn-vue `Table` 组件；表头列顺序：选择列（`w-12`）→ 业务列 → 时间列 → 操作列（`w-16 text-right`）。
- 数值/时间列加 `tabular-nums`；次要列移动端隐藏：`hidden md:table-cell`（更次要用 `lg:table-cell`）。
- 行内操作按钮：`Button variant="ghost" size="sm" class="h-7 px-2 text-xs text-muted-foreground"`，配 `title` 属性。
- 状态徽标：成功/失败等用 `Badge`，底色用主题变量的透明度形式（如 `bg-success/10 text-success`），禁止硬编码色值。

## 8. 弹窗（Dialog）

- 确认类（删除/清空）：`sm:max-w-md`，`DialogDescription` 必须说明后果并包含「此操作不可恢复」；底部「取消」（outline）+「确认」（destructive）。
- 详情/预览类：`sm:max-w-md` ~ `sm:max-w-2xl`，加 `max-h-[90vh] overflow-y-auto`；详情用 `dl` 两列结构（dt 左 `text-muted-foreground`、dd 右），隔行 `bg-muted/40` + `border-b`。
- 表单类：底部「取消」+「保存」（默认 primary）。
- 所有弹窗打开状态由页面 ref 控制，`@update:open` 关闭时置空。

## 9. 响应式

- 移动优先：网格用 `grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5`；工具栏、筛选在 `sm:` 断点切换横排。
- 页面内边距统一 `p-4 lg:p-6`。

## 10. data-test 属性

- 关键交互元素必须加 `data-test`（kebab-case）：按钮 `{动作}-{对象}-button`（如 `export-login-logs-button`）、弹窗确认按钮 `confirm-{动作}-{对象}-button`、输入框 `{对象}-search-input`、数据行 `{对象}-row-{id}`，供功能测试定位。
