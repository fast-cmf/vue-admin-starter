# UI 组件与主题配色规则

适用范围：`resources/js/**`、`resources/css/**`、`resources/views/**`（后台及前台所有界面代码）。

## 1. 组件优先使用 shadcn-vue

- 后台（以及所有新界面）使用的组件**必须优先使用 shadcn-vue 提供的组件**，组件清单参考官方文档：https://www.shadcn-vue.com/docs/components
- 已安装的组件位于 `resources/js/components/ui/`。动手写任何 UI 前，先检查此处是否已有对应组件，有则直接复用，禁止重复造轮子。
- 若 shadcn-vue 尚未安装所需组件，先通过 shadcn-vue 的 CLI 添加（如 `npx shadcn-vue@latest add <component>`），而不是手写替代品。
- 仅当 shadcn-vue 确实不提供该类组件时，才允许编写自定义组件；自定义组件在视觉与交互上必须与 shadcn-vue 组件风格保持一致（同样的圆角、间距、边框、状态样式约定）。

## 2. 主题配色统一遵循项目主题变量

- 所有组件（包括 shadcn-vue 组件的定制与自定义组件）的配色**必须遵循项目主题色**，即 `resources/css/app.css` 中定义的 CSS 变量：
  - 浅色模式：`:root` 下的 `--background`、`--foreground`、`--primary`、`--secondary`、`--muted`、`--accent`、`--border`、`--ring` 等变量。
  - 深色模式：`.dark` 下的同名变量。
- 组件中一律通过 Tailwind 语义化工具类引用主题色（如 `bg-primary`、`text-muted-foreground`、`border-border`），**禁止硬编码具体颜色值**（如 `text-[#2563eb]`、`bg-blue-600` 等）。
- 当前主题基调：宝蓝色主色（`hsl(217 91% 53%)`）+ 深海军蓝文字 + 浅蓝灰分区背景，深色模式为深藏青底 + 提亮主蓝。

## 3. 配色集中管理，方便日后修改

- **全部颜色定义必须集中在 `resources/css/app.css`** 中（`:root` 与 `.dark` 变量块），这是唯一的配色来源。
- 需要新增颜色语义（如新的状态色、品牌辅助色）时，必须在 `app.css` 的变量块中新增 CSS 变量，并在文件顶部 `@theme inline` 中映射为 Tailwind 颜色 token，再在组件中使用。
- 禁止在 Vue 组件、Blade 模板或其他 CSS 文件中散落定义颜色常量；禁止为单个页面单独定义配色。
- 修改主题配色时，只修改 `app.css` 中的变量，同时保证浅色与深色模式一起更新。
