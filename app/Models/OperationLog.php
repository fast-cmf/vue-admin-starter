<?php

namespace App\Models;

use Database\Factories\OperationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $username
 * @property string $module
 * @property string $summary
 * @property string $type
 * @property bool $status
 * @property string $url
 * @property string $ip
 * @property array|null $parameters
 * @property string|null $error_message
 * @property int $cost_ms
 * @property Carbon $performed_at
 */
class OperationLog extends Model
{
    /** @use HasFactory<OperationLogFactory> */
    use HasFactory;

    /**
     * 不使用标准时间戳，以 performed_at 作为唯一时间字段
     */
    public $timestamps = false;

    /**
     * 批量赋值的字段
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id', 'username', 'module', 'summary', 'type', 'status',
        'url', 'ip', 'parameters', 'error_message', 'cost_ms', 'performed_at',
    ];

    /**
     * 字段类型转换
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'cost_ms' => 'integer',
            'parameters' => 'array',
            'performed_at' => 'datetime',
        ];
    }

    /**
     * 关联的操作人员
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 按路由名解析模块名称（admin.xxx 前两段），未收录时回退「后台管理」
     */
    public static function resolveModule(string $routeName): string
    {
        $segments = explode('.', $routeName);
        $prefix = implode('.', array_slice($segments, 0, 2));

        return self::MODULES[$prefix] ?? '后台管理';
    }

    /**
     * 按 HTTP 动词解析操作类型
     */
    public static function resolveType(string $method): string
    {
        return match (strtoupper($method)) {
            'POST' => '新增',
            'PUT', 'PATCH' => '修改',
            'DELETE' => '删除',
            default => '操作',
        };
    }

    /**
     * 各后台模块的路由名前缀 → 中文名称映射
     *
     * @var array<string, string>
     */
    private const MODULES = [
        'admin.media' => '媒体中心',
        'admin.login-logs' => '登录日志',
        'admin.operation-logs' => '操作日志',
        'admin.profile' => '个人资料',
        'admin.storage' => '存储设置',
        'admin.mail' => '邮箱设置',
        'admin.security' => '安全设置',
        'admin.user-password' => '登录密码',
        'admin.appearance' => '外观设置',
    ];
}
