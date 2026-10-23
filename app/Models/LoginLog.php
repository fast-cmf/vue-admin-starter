<?php

namespace App\Models;

use Database\Factories\LoginLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $account
 * @property bool $status
 * @property string $ip
 * @property string $browser
 * @property string $platform
 * @property string $message
 * @property Carbon $login_at
 */
class LoginLog extends Model
{
    /** @use HasFactory<LoginLogFactory> */
    use HasFactory;

    /**
     * 不使用标准时间戳，以 login_at 作为唯一时间字段
     */
    public $timestamps = false;

    /**
     * 批量赋值的字段
     *
     * @var list<string>
     */
    protected $fillable = ['user_id', 'account', 'status', 'ip', 'browser', 'platform', 'message', 'login_at'];

    /**
     * 字段类型转换
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'login_at' => 'datetime',
        ];
    }

    /**
     * 登录成功时关联的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
