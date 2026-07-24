<?php
namespace app\service\identity;

use think\Exception;

class ManualDriver implements IdentityDriverInterface
{
    public function getKey(): string
    {
        return 'manual';
    }

    public function getLabel(): string
    {
        return '人工审核';
    }

    public function getDescription(): string
    {
        return '<div>系统内置人工审核流程，无需对接第三方。</div>';
    }

    public function getFields(): array
    {
        return [];
    }

    public function getStartMessage(): string
    {
        return '提交成功，请等待审核';
    }

    public function getStatusMessage(string $status): string
    {
        if ($status === 'success') {
            return '认证成功';
        }
        if ($status === 'pending') {
            return '认证进行中';
        }
        return '认证未通过或仍在进行中';
    }

    public function startAuth(string $realName, string $idCard, string $returnUrl, string $mobile = ''): array
    {
        throw new Exception('未配置实名认证服务');
    }

    public function checkStatus(string $bizToken): array
    {
        throw new Exception('未配置实名认证服务');
    }
}
