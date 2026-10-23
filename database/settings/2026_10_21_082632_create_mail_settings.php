<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mail.enabled', false);
        $this->migrator->add('mail.from_email', '');
        $this->migrator->add('mail.from_name', '');
        $this->migrator->add('mail.smtp_host', '');
        $this->migrator->add('mail.smtp_port', 587);
        $this->migrator->add('mail.smtp_username', '');
        $this->migrator->addEncrypted('mail.smtp_password', '');
        $this->migrator->add('mail.smtp_encryption', 'tls');
    }

    public function down(): void
    {
        $this->migrator->delete('mail.enabled');
        $this->migrator->delete('mail.from_email');
        $this->migrator->delete('mail.from_name');
        $this->migrator->delete('mail.smtp_host');
        $this->migrator->delete('mail.smtp_port');
        $this->migrator->delete('mail.smtp_username');
        $this->migrator->delete('mail.smtp_password');
        $this->migrator->delete('mail.smtp_encryption');
    }
};
