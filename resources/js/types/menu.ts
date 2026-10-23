/** 侧边栏菜单树节点（由后端 MenuService::sidebarFor 按权限过滤后下发） */
export type SidebarMenuNode = {
    id: number;
    /** 菜单类型：directory 目录 / menu 菜单 / title 标题（button 不下发） */
    type: 'directory' | 'menu' | 'title';
    title: string;
    /** 跳转地址：仅 menu 类型有值 */
    path: string | null;
    /** 图标名（PascalCase），经 menu-icons.ts 解析为组件 */
    icon: string | null;
    children: SidebarMenuNode[];
};

/** 菜单管理页：菜单树节点（全量，不过滤权限与可见性） */
export type MenuTreeItem = {
    id: number;
    parent_id: number | null;
    type: string;
    type_label: string;
    title: string;
    path: string | null;
    icon: string | null;
    sort: number;
    is_visible: boolean;
    permission: string | null;
    children: MenuTreeItem[];
};

/** 菜单管理页：可选父级（树形扁平列表，仅目录与菜单类型，depth 为层级深度） */
export type MenuParentOption = {
    id: number;
    title: string;
    type: string;
    depth: number;
};

/** 菜单管理页：可选跳转页面（后台已注册的 GET 页面路由） */
export type MenuPageOption = {
    /** 站内相对路径，如 /admin/media */
    path: string;
    /** 路由名，如 admin.media.index */
    name: string;
};

/** 菜单管理页表单错误 */
export type MenuFormErrors = Partial<
    Record<'parent_id' | 'type' | 'title' | 'path' | 'icon' | 'permission', string>
> & {
    menu?: string;
};

