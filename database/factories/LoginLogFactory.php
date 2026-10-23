<?php

namespace Database\Factories;

use App\Models\LoginLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginLog>
 */
class LoginLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account' => fake()->unique()->safeEmail(),
            'status' => true,
            'ip' => fake()->ipv4(),
            'browser' => 'Chrome 131.0.0.0',
            'platform' => 'Windows 10',
            'message' => '登录成功',
            'login_at' => now(),
        ];
    }

    /**
     * 登录失败的日志记录
     */
    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => false,
            'message' => '账号或密码错误',
        ]);
    }
}
