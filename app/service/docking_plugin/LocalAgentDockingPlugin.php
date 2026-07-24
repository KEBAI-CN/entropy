<?php
namespace app\service\docking_plugin;

use app\model\Product;
use app\service\DockingChainService;
use think\facade\Db;

class LocalAgentDockingPlugin implements DockingPluginInterface
{
    public function getCode(): string
    {
        return 'local_agent';
    }

    public function getName(): string
    {
        return '站内代理对接';
    }

    public function getType(): int
    {
        return 0;
    }

    public function getTags(): array
    {
        return ['站内', '代理', '免远程配置'];
    }

    public function getContent(): string
    {
        return '站内代理对接，直接复用本地上游商品与卡密库存，无需配置远程接口。';
    }

    public function requiresRemoteConfig(): bool
    {
        return false;
    }

    public function getConfigFields(): array
    {
        return [];
    }

    public function supports($product): bool
    {
        return (int)$this->readValue($product, 'is_docking', 0) === 1
            && (int)$this->readValue($product, 'source_product_id', 0) > 0
            && (int)$this->readValue($product, 'docking_type', 0) <= 0;
    }

    public function getCardProductId($product): int
    {
        try {
            $targetProduct = DockingChainService::getFulfillmentProduct($product);
            return (int)($targetProduct->id ?? 0);
        } catch (\Throwable $e) {
            trace('Local agent docking card product resolve failed: ' . $e->getMessage(), 'warning');
            return 0;
        }
    }

    public function getStock($product): int
    {
        $sourceProductId = $this->getCardProductId($product);
        if ($sourceProductId <= 0) {
            return 0;
        }

        return (int)Db::name('card_keys')
            ->where('product_id', $sourceProductId)
            ->where('status', 0)
            ->whereNull('delete_time')
            ->count();
    }

    public function getUpstreamInfo($product): array
    {
        $directSourceProductId = (int)$this->readValue($product, 'source_product_id', 0);
        if ($directSourceProductId <= 0) {
            return [
                'cost_price' => 0,
                'stock' => 0,
            ];
        }

        $sourceProduct = Product::find($directSourceProductId);
        if (!$sourceProduct) {
            return [
                'cost_price' => 0,
                'stock' => 0,
            ];
        }

        try {
            DockingChainService::assertLocalDockingSourceIsSelfOperated($sourceProduct);
        } catch (\Throwable $e) {
            trace('Local agent docking upstream invalid: ' . $e->getMessage(), 'warning');
            return [
                'cost_price' => 0,
                'stock' => 0,
                'error' => $e->getMessage(),
            ];
        }

        $downstreamShopId = (int)Db::name('shops')
            ->where('user_id', (int)$this->readValue($product, 'user_id', 0))
            ->value('id');

        return [
            'cost_price' => DockingChainService::resolveUpstreamSupplyCostForShop($sourceProduct, $downstreamShopId),
            'stock' => $this->getStock($product),
            'ladder_pricing' => $sourceProduct->ladder_pricing ?? [],
        ];
    }

    public function fetchUpstreamProducts(array $params): array
    {
        return [];
    }

    public function fetchCardsForOrder($product, $order): array
    {
        return [];
    }

    private function readValue($product, string $field, $default = null)
    {
        if (is_array($product)) {
            return $product[$field] ?? $default;
        }

        if (is_object($product)) {
            return $product->{$field} ?? $default;
        }

        return $default;
    }
}
