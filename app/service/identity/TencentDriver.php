<?php
namespace app\service\identity;

use app\service\TencentCloudService;

class TencentDriver implements IdentityDriverInterface
{
    public function getKey(): string
    {
        return 'tencent';
    }

    public function getLabel(): string
    {
        return '腾讯云慧眼';
    }

    public function getDescription(): string
    {
        return '<div class="space-y-1"><p><span class="font-medium">费用参考：</span>约 1.00元/次（以腾讯云官网为准）</p><p><span class="font-medium">产品地址：</span><a href="https://cloud.tencent.com/product/faceid" target="_blank" class="underline">https://cloud.tencent.com/product/faceid</a></p><p>腾讯云提供的实名认证服务，需开通慧眼产品并配置密钥。</p></div>';
    }

    public function getFields(): array
    {
        return [
            [
                'key' => 'tencent_faceid_secret_id',
                'label' => 'SecretId',
                'type' => 'text',
                'placeholder' => '请输入 SecretId'
            ],
            [
                'key' => 'tencent_faceid_secret_key',
                'label' => 'SecretKey',
                'type' => 'password',
                'placeholder' => '请输入 SecretKey'
            ],
            [
                'key' => 'tencent_faceid_rule_id',
                'label' => 'RuleId',
                'type' => 'text',
                'placeholder' => '请输入 RuleId',
                'tip' => '规则ID可在腾讯云慧眼控制台查看'
            ]
        ];
    }

    public function getStartMessage(): string
    {
        return '请扫描二维码完成认证';
    }

    public function getStatusMessage(string $status): string
    {
        if ($status === 'success') {
            return '认证成功';
        }
        if ($status === 'pending') {
            return '认证进行中';
        }
        return '认证失败';
    }

    public function startAuth(string $realName, string $idCard, string $returnUrl, string $mobile = ''): array
    {
        $service = new TencentCloudService();
        $result = $service->detectAuth($idCard, $realName, $returnUrl);
        return [
            'url' => $result['url'],
            'biz_token' => $result['biz_token'],
            'provider' => $this->getKey()
        ];
    }

    public function checkStatus(string $bizToken): array
    {
        $service = new TencentCloudService();
        $result = $service->getDetectInfo($bizToken);
        return [
            'status' => $result['status'] ?? 'failed',
            'data' => $result
        ];
    }
}
