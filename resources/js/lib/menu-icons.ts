import type { LucideIcon } from '@lucide/vue';
import {
    Activity,
    Archive,
    BarChart3,
    Bell,
    Bookmark,
    Circle,
    ClipboardList,
    Clock,
    Database,
    FileText,
    FolderOpen,
    Globe,
    HardDrive,
    Home,
    Images,
    Inbox,
    KeyRound,
    Layers,
    LayoutGrid,
    Link,
    ListTree,
    Mail,
    MapPin,
    Package,
    ScrollText,
    Settings,
    ShieldCheck,
    Tag,
    UserCog,
    UsersRound,
    Wrench,
} from '@lucide/vue';

/**
 * 图标名 → Lucide 组件映射
 * 键需与 config/menus.php 的 icons 白名单保持一致
 */
export const menuIcons: Record<string, LucideIcon> = {
    LayoutGrid,
    Images,
    ScrollText,
    ClipboardList,
    UsersRound,
    ShieldCheck,
    Home,
    Settings,
    FileText,
    FolderOpen,
    Database,
    HardDrive,
    Mail,
    Activity,
    BarChart3,
    Layers,
    Package,
    Tag,
    KeyRound,
    UserCog,
    Globe,
    Link,
    Clock,
    Archive,
    ListTree,
    Wrench,
    Bell,
    Bookmark,
    Inbox,
    MapPin,
};

/** 图标名兜底：未知图标名或缺省时使用圆形图标 */
export function resolveMenuIcon(name: string | null | undefined): LucideIcon {
    if (name && menuIcons[name]) {
        return menuIcons[name];
    }

    return Circle;
}
