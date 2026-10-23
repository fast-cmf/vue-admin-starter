<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * 权限模型：在 spatie Permission 基础上承载项目自有逻辑，
 * 权限点来源为 config/permissions.php 注册表，由 permission:sync 命令同步入库
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Permission extends SpatiePermission
{
    //
}
