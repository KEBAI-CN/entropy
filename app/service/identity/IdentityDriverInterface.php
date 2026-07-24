<?php
namespace app\service\identity;

interface IdentityDriverInterface
{
    public function getKey(): string;
    public function getLabel(): string;
    public function getDescription(): string;
    public function getFields(): array;
    public function getStartMessage(): string;
    public function getStatusMessage(string $status): string;
    public function startAuth(string $realName, string $idCard, string $returnUrl, string $mobile = ''): array;
    public function checkStatus(string $bizToken): array;
}
