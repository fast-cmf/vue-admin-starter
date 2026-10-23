<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * 角色模型：在 spatie Role 基础上增加内置角色标记与中文名称，内置角色不可在后台编辑与删除
 *
 * @property int $id
 * @property string $name
 * @property string|null $title
 * @property string $guard_name
 * @property bool $is_builtin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Role extends SpatieRole
{
    /** @var list<string> */
    protected $fillable = ['name', 'title', 'guard_name', 'is_builtin'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_builtin' => 'boolean',
        ];
    }
}
