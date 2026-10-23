/** 角色条目（角色权限页） */
export type RoleItem = {
    id: number;
    name: string;
    title: string;
    is_builtin: boolean;
    users_count: number;
    permissions_count: number;
    permissions: string[];
    created_at: string;
};

/** 权限矩阵分组：[分组名 => [权限点 => 中文标签]] */
export type PermissionGroups = Record<string, Record<string, string>>;

/** 用户角色条目 */
export type UserRoleRef = {
    id: number;
    name: string;
    title: string;
};

/** 用户条目（用户管理页） */
export type UserItem = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    blocked_at: string | null;
    roles: UserRoleRef[];
    created_at: string;
};

/** 用户分页结构 */
export type UserPaginator = {
    data: UserItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

/** 可选角色（用户管理页角色分配） */
export type RoleOption = {
    id: number;
    name: string;
    title: string;
    is_builtin: boolean;
};
