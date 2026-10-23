/**
 * 媒体中心相关类型定义
 */

/** 媒体类型筛选项 */
export type MediaType = 'all' | 'image' | 'video' | 'audio' | 'file';

/** 媒体条目（前端输出结构） */
export type MediaAssetItem = {
    id: number;
    title: string | null;
    folder: string | null;
    size: number;
    mime_type: string;
    width: number | null;
    height: number | null;
    original_url: string;
    thumb_url: string;
    medium_url: string;
    has_crop: boolean;
};

/** Laravel 分页器结构 */
export type MediaAssetPaginator = {
    data: MediaAssetItem[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
};

/** 各类型媒体数量统计 */
export type MediaTypeCounts = Record<MediaType, number>;
