<?php

use App\Mail\TestMail;
use App\Models\User;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * 邮箱配置测试：RefreshDatabase 每个用例自带事务回滚，
 * 这里只需重置容器中的设置实例，避免跨用例持有旧状态
 */
beforeEach(function () {
    app()->forgetInstance(MailSettings::class);
});

test('mail page is displayed', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('admin.mail.edit'));

    $response->assertOk();
});

test('mail settings can be disabled by default', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('admin.mail.update'), [
            'enabled' => '0',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.mail.edit'));

    $settings = app(MailSettings::class);
    expect($settings->enabled)->toBeFalse();
    expect($settings->isSmtpConfigured())->toBeFalse();
});

test('mail settings can be saved and password is encrypted at rest', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('admin.mail.update'), [
            'enabled' => '1',
            'from_email' => 'no-reply@example.com',
            'from_name' => '站点机器人',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_username' => 'smtp-user',
            'smtp_password' => 'super-secret-value',
            'smtp_encryption' => 'tls',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.mail.edit'));

    $settings = app(MailSettings::class);
    expect($settings->enabled)->toBeTrue();
    expect($settings->from_email)->toBe('no-reply@example.com');
    expect($settings->smtp_host)->toBe('smtp.example.com');
    expect((int) $settings->smtp_port)->toBe(587);
    expect($settings->smtp_password)->toBe('super-secret-value');
    expect($settings->isSmtpConfigured())->toBeTrue();

    // 数据库中密码必须是密文，不落明文
    $stored = DB::table('settings')
        ->where('group', 'mail')
        ->where('name', 'smtp_password')
        ->value('payload');

    expect($stored)->not->toBe('super-secret-value');
    expect($stored)->not->toContain('super-secret-value');
});

test('smtp fields are required when enabled', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('admin.mail.edit'))
        ->patch(route('admin.mail.update'), [
            'enabled' => '1',
            'from_email' => '',
            'smtp_host' => '',
            'smtp_username' => '',
        ]);

    $response
        ->assertSessionHasErrors(['from_email', 'smtp_host', 'smtp_username'])
        ->assertRedirect(route('admin.mail.edit'));

    expect(app(MailSettings::class)->enabled)->toBeFalse();
});

test('empty password keeps previous value', function () {
    $user = User::factory()->superAdmin()->create();

    // 先设置一次密码
    $this
        ->actingAs($user)
        ->patch(route('admin.mail.update'), [
            'enabled' => '1',
            'from_email' => 'no-reply@example.com',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_username' => 'smtp-user',
            'smtp_password' => 'first-secret',
        ]);

    // 再次保存且密码为空，应保留原值
    $this
        ->patch(route('admin.mail.update'), [
            'enabled' => '1',
            'from_email' => 'no-reply@example.com',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_username' => 'smtp-user',
        ]);

    app()->forgetInstance(MailSettings::class);

    expect(app(MailSettings::class)->smtp_password)->toBe('first-secret');
});

test('test mail is sent using saved settings', function () {
    Mail::fake();

    $user = User::factory()->superAdmin()->create();

    // 先保存一份完整配置
    $this
        ->actingAs($user)
        ->patch(route('admin.mail.update'), [
            'enabled' => '1',
            'from_email' => 'no-reply@example.com',
            'from_name' => '站点机器人',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '587',
            'smtp_username' => 'smtp-user',
            'smtp_password' => 'smtp-pass',
            'smtp_encryption' => 'tls',
        ]);

    $response = $this
        ->actingAs($user)
        ->post(route('admin.mail.sendTest'), [
            'test_email' => 'receiver@example.com',
        ]);

    $response->assertSessionHasNoErrors();

    Mail::assertSent(TestMail::class, function ($mail) {
        return $mail->hasTo('receiver@example.com')
            && $mail->hasSubject('【'.config('app.name').'】SMTP 配置测试邮件');
    });
});

test('test mail fails when smtp is not configured', function () {
    Mail::fake();

    $user = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('admin.mail.sendTest'), [
            'test_email' => 'receiver@example.com',
        ]);

    $response->assertSessionHasErrors(['test_email']);

    Mail::assertNothingSent();
});

test('guest cannot access mail page', function () {
    $response = $this->get(route('admin.mail.edit'));

    $response->assertRedirect(route('login', absolute: false));
});
