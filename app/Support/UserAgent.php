<?php

namespace App\Support;

/**
 * 轻量 User-Agent 解析器：仅提取浏览器与操作系统展示名称，
 * 覆盖常见客户端，未识别时返回通用文案
 */
final class UserAgent
{
    /**
     * 解析浏览器名称与版本（如 Chrome 155.0.0.0）
     */
    public static function browser(string $userAgent): string
    {
        // Edge 的 UA 同时携带 Chrome 标识，必须最先匹配
        if (preg_match('/Edg(?:e|A|iOS)?\/([\d.]+)/', $userAgent, $m) === 1) {
            return 'Microsoft Edge '.$m[1];
        }

        if (preg_match('/(?:OPR|Opera)\/([\d.]+)/', $userAgent, $m) === 1) {
            return 'Opera '.$m[1];
        }

        if (preg_match('/Chrome\/([\d.]+)/', $userAgent, $m) === 1) {
            return 'Chrome '.$m[1];
        }

        if (preg_match('/Firefox\/([\d.]+)/', $userAgent, $m) === 1) {
            return 'Firefox '.$m[1];
        }

        if (preg_match('/Version\/([\d.]+).*Safari\//', $userAgent, $m) === 1) {
            return 'Safari '.$m[1];
        }

        if (str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident/')) {
            return 'Internet Explorer';
        }

        return '未知浏览器';
    }

    /**
     * 解析操作系统名称（如 Windows 10）
     */
    public static function platform(string $userAgent): string
    {
        if (preg_match('/Windows NT ([\d.]+)/', $userAgent, $m) === 1) {
            return match ($m[1]) {
                '10.0' => 'Windows 10',
                '6.3' => 'Windows 8.1',
                '6.2' => 'Windows 8',
                '6.1' => 'Windows 7',
                default => 'Windows',
            };
        }

        if (preg_match('/Android ([\d.]+)/', $userAgent, $m) === 1) {
            return 'Android '.$m[1];
        }

        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') || str_contains($userAgent, 'iPod')) {
            return 'iOS';
        }

        if (preg_match('/Mac OS X ([\d_.]+)/', $userAgent, $m) === 1) {
            return 'macOS';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        return '未知系统';
    }
}
