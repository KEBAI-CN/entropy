<?php
namespace app\service\payment;

interface PaymentDriverInterface
{
    public function getKey(): string;
    public function getLabel(): string;
    public function getDescription(): string;
    public function getTypes(): array;
    public function getIcon(): string;
    public function getIconUrl(): string;
    public function pay($orderId, $amount, $title, array $config, string $type): array;
    public function verifyNotify(array $params, array $config, string $type): bool;
    public function refund($tradeNo, $amount, array $config, string $type): bool;
    public function queryOrder($orderId, array $config, string $provider): bool;
    public function handleNotify(array $config);
}
