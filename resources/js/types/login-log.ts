/**
 * 登录日志相关类型定义
 */

/** 登录日志条目（前端输出结构） */
export type LoginLogItem = {
    id: number;
    account: string;
    status: boolean;
    ip: string;
    browser: string;
    platform: string;
    message: string;
    login_at: string;
};

/** 登录状态筛选项 */
export type LoginLogStatus = '' | 'success' | 'fail';

/** Laravel 分页器结构 */
export type LoginLogPaginator = {
    data: LoginLogItem[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
};
