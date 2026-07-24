<?php
namespace app\service\shop_plugin;

interface ShopPluginInterface
{
    public function getCode(): string;
    public function getName(): string;
    public function getDescription(): string;
    public function getType(): string;
    public function getEntry(): string;
    public function getDefaultConfig(): array;
    public function getConfigSchema(): array;
    public function renderScript(array $config): string;
}
