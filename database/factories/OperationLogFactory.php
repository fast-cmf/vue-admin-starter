<?php

namespace Database\Factories;

use App\Models\OperationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationLog>
 */
class OperationLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->name(),
            'module' => '媒体中心',
            'summary' => '媒体中心 · 修改',
            'type' => '修改',
            'status' => true,
            'url' => '/admin/media/1',
            'ip' => fake()->ipv4(),
            'error_message' => null,
            'cost_ms' => fake()->numberBetween(10, 500),
            'performed_at' => now(),
        ];
    }

    /**
     * 操作失败的日志记录
     */
    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => false,
            'error_message' => '服务器内部错误',
        ]);
    }
}
