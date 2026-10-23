<?php

namespace App\Models;

use App\Enums\MenuType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 菜单模型：后台菜单树节点，支持目录/菜单/标题/按钮四种类型
 *
 * @property int $id
 * @property int|null $parent_id
 * @property MenuType $type
 * @property string $title
 * @property string|null $path
 * @property string|null $icon
 * @property int $sort
 * @property bool $is_visible
 * @property string|null $permission
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Menu extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'parent_id',
        'type',
        'title',
        'path',
        'icon',
        'sort',
        'is_visible',
        'permission',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MenuType::class,
            'is_visible' => 'boolean',
        ];
    }

    /**
     * 父级菜单
     *
     * @return BelongsTo<Menu, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * 子菜单
     *
     * @return HasMany<Menu, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort')->orderBy('id');
    }

    /**
     * 是否存在子菜单
     */
    public function hasChildren(): bool
    {
        return self::query()->where('parent_id', $this->id)->exists();
    }

    /**
     * 判断给定菜单是否为当前菜单的后代（含自身），用于防止把菜单挂到自己的子级下形成环
     */
    public function isDescendantOfOrSelf(Menu $menu): bool
    {
        $current = $this;

        while ($current !== null) {
            if ($current->id === $menu->id) {
                return true;
            }

            $current = $current->parent_id !== null ? self::find($current->parent_id) : null;
        }

        return false;
    }
}
