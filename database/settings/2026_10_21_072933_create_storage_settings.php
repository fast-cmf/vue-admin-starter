<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // 存储配置：默认本地磁盘，OSS 参数按需填写
        $this->migrator->add('storage.disk', 'local');
        $this->migrator->add('storage.oss_bucket', '');
        $this->migrator->add('storage.oss_endpoint', '');
        $this->migrator->add('storage.oss_region', '');
        $this->migrator->add('storage.oss_access_key_id', '');
        $this->migrator->addEncrypted('storage.oss_secret_access_key', '');
        $this->migrator->add('storage.oss_url', '');
    }
};
