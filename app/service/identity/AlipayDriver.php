<?php
namespace app\service\identity;

use app\service\AlipayIdentityService;

class AlipayDriver implements IdentityDriverInterface
{
    public function getKey(): string
    {
        return 'alipay';
    }

    public function getLabel(): string
    {
        return '支付宝实名';
    }

    public function getDescription(): string
    {
        return '<div class="space-y-1"><p><span class="font-medium">费用参考：</span>免费或按量计费，以支付宝开放平台为准</p><p><span class="font-medium">产品地址：</span><a href="https://opendocs.alipay.com/open/01bny6?ref=api" target="_blank" class="underline">支付宝身份验证产品介绍</a></p><p>支付宝实名认证能力，需配置应用与密钥。</p></div>';
    }

    public function getFields(): array
    {
        return [
            [
                'key' => 'alipay_identity_app_id',
                'label' => 'AppID',
                'type' => 'text',
                'placeholder' => '请输入 AppID'
            ],
            [
                'key' => 'alipay_identity_private_key',
                'label' => '应用私钥',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => '请输入应用私钥'
            ],
            [
                'key' => 'alipay_identity_public_key',
                'label' => '支付宝公钥',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => '请输入支付宝公钥'
            ]
        ];
    }

    public function getStartMessage(): string
    {
        return '请前往支付宝完成认证';
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
        $service = new AlipayIdentityService();
        $result = $service->initialize($realName, $idCard, $returnUrl);
        return [
            'url' => $result['url'],
            'biz_token' => $result['certify_id'],
            'provider' => $this->getKey()
        ];
    }

    public function checkStatus(string $bizToken): array
    {
        $service = new AlipayIdentityService();
        $passed = $service->query($bizToken);
        return [
            'status' => $passed ? 'success' : 'failed',
            'data' => ['passed' => $passed]
        ];
    }
}
