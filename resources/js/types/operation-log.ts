/**
 * 操作日志相关类型定义
 */

/** 操作日志条目（前端输出结构） */
export type OperationLogItem = {
    id: number;
    username: string;
    module: string;
    summary: string;
    type: string;
    status: boolean;
    url: string;
    ip: string;
    /** 请求参数（已剔除敏感字段），无参数时为 null */
    parameters: Record<string, unknown> | null;
    error_message: string | null;
    cost_ms: number;
    performed_at: string;
};

/** Laravel 分页器结构 */
export type OperationLogPaginator = {
    data: OperationLogItem[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
};
