<?php
namespace app\service\docking_plugin;

use think\facade\Log;
use think\facade\Cache;

class DujiaoNextOpenApiDockingPlugin implements DockingPluginInterface
{
    private const PATH_PRODUCTS = '/api/v1/upstream/products';
    private const PATH_ORDERS = '/api/v1/upstream/orders';
    private const POLL_RETRY_TIMES = 10;
    private const POLL_INTERVAL_MICROSECONDS = 2000000;

    public function getCode(): string
    {
        return 'dujiao_next_openapi';
    }

    public function getName(): string
    {
        return '独角数卡Next对接';
    }

    public function getType(): int
    {
        return 3;
    }

    public function getTags(): array
    {
        return ['独角数卡', 'OpenAPI', '远程'];
    }

    public function getContent(): string
    {
        return '适配 Dujiao-Next 站点对接 Open API，支持签名鉴权、商品同步、库存读取、远程下单与轮询取卡。';
    }

    public function requiresRemoteConfig(): bool
    {
        return true;
    }

    public function getConfigFields(): array
    {
        return [
            [
                'key' => 'docking_link',
                'label' => '站点地址',
                'type' => 'text',
                'placeholder' => '请输入上游站点地址，如 https://demo.com',
                'required' => 1,
            ],
            [
                'key' => 'shop_docking_code',
                'label' => 'API Key',
                'type' => 'password',
                'placeholder' => '请输入 Dujiao-Next API Key',
                'required' => 1,
            ],
            [
                'key' => 'docking_api_key',
                'label' => 'API Secret',
                'type' => 'password',
                'placeholder' => '请输入 Dujiao-Next API Secret',
                'required' => 1,
            ],
        ];
    }

    public function supports($product): bool
    {
        return (int)$this->readValue($product, 'is_docking', 0) === 1
            && (int)$this->readValue($product, 'docking_type', 0) === $this->getType()
            && (int)$this->readValue($product, 'source_product_id', 0) > 0
            && trim((string)$this->readValue($product, 'docking_link', '')) !== ''
            && trim((string)$this->readValue($product, 'shop_docking_code', '')) !== ''
            && trim((string)$this->readValue($product, 'docking_api_key', '')) !== '';
    }

    public function getCardProductId($product): int
    {
        return (int)$this->readValue($product, 'source_product_id', $this->readValue($product, 'id', 0));
    }

    public function getStock($product): int
    {
        try {
            $info = $this->getUpstreamInfo($product);
            return (int)($info['stock'] ?? 0);
        } catch (\Throwable $e) {
            Log::warning('DujiaoNextOpenApi getStock failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function getUpstreamInfo($product): array
    {
        $skuId = $this->getCardProductId($product);
        if ($skuId <= 0) {
            throw new \Exception('未配置上游 SKU');
        }

        $sku = $this->findSkuById($product, $skuId);
        if ($sku === null) {
            throw new \Exception('未找到对应上游 SKU');
        }

        return [
            'cost_price' => (float)($sku['price_amount'] ?? 0),
            'stock' => $this->normalizeStock($sku),
        ];
    }

    public function fetchUpstreamProducts(array $params): array
    {
        $mockProduct = [
            'docking_link' => (string)($params['docking_link'] ?? ''),
            'docking_api_key' => (string)($params['docking_api_key'] ?? ''),
            'shop_docking_code' => (string)($params['shop_docking_code'] ?? ''),
        ];
        $keyword = trim((string)($params['name'] ?? ''));
        $page = 1;
        $pageSize = max(1, min(100, (int)($params['size'] ?? 100)));
        $records = [];
        $seen = [];

        do {
            $response = $this->request($mockProduct, 'GET', self::PATH_PRODUCTS, [], [
                'page' => $page,
                'page_size' => $pageSize,
            ]);
            $items = is_array($response['items'] ?? null) ? $response['items'] : [];
            foreach ($items as $productItem) {
                if (!is_array($productItem)) {
                    continue;
                }
                $productName = $this->pickLocalizedText($productItem['title'] ?? '', '');
                $category = $this->resolveCategoryInfo($productItem);
                $skus = is_array($productItem['skus'] ?? null) ? $productItem['skus'] : [];
                foreach ($skus as $sku) {
                    if (!is_array($sku)) {
                        continue;
                    }
                    $skuId = (int)($sku['id'] ?? 0);
                    if ($skuId <= 0 || isset($seen[$skuId])) {
                        continue;
                    }

                    $skuName = $this->buildSkuDisplayName($productName, $sku['spec_values'] ?? []);
                    if ($keyword !== '' && mb_stripos($skuName, $keyword) === false && mb_stripos((string)$productName, $keyword) === false) {
                        continue;
                    }

                    $seen[$skuId] = true;
                    $records[] = [
                        'id' => $skuId,
                        'name' => $skuName,
                        'price' => (float)($sku['price_amount'] ?? $productItem['price_amount'] ?? 0),
                        'stock' => $this->normalizeStock($sku),
                        'is_docking' => 1,
                        'status' => ((bool)($productItem['is_active'] ?? false) && (bool)($sku['is_active'] ?? false)) ? 1 : 0,
                        'category' => $category,
                    ];
                }
            }

            $total = (int)($response['total'] ?? 0);
            $currentPage = (int)($response['page'] ?? $page);
            $currentPageSize = max(1, (int)($response['page_size'] ?? $pageSize));
            $hasMore = $total > ($currentPage * $currentPageSize) && !empty($items);
            $page++;
        } while ($hasMore);

        return [
            'data' => $records,
            'records' => $records,
            'count' => count($records),
        ];
    }

    public function fetchCardsForOrder($product, $order): array
    {
        $skuId = $this->getCardProductId($product);
        $quantity = max(1, (int)$this->readValue($order, 'quantity', 1));
        $tradeNo = trim((string)$this->readValue($order, 'trade_no', ''));
        $contact = trim((string)$this->readValue($order, 'contact', ''));

        if ($skuId <= 0) {
            throw new \Exception('未配置上游 SKU');
        }

        $orderCacheKey = $tradeNo !== '' ? 'dujiao_next:upstream_order_id:' . $tradeNo : '';
        $cardsCacheKey = $tradeNo !== '' ? 'dujiao_next:upstream_cards:' . $tradeNo : '';
        $submitStateKey = $tradeNo !== '' ? 'dujiao_next:submit_state:' . $tradeNo : '';

        if ($cardsCacheKey !== '') {
            $cachedCards = Cache::get($cardsCacheKey, []);
            if (is_array($cachedCards) && !empty($cachedCards)) {
                $cachedCards = $this->filterCardLines($cachedCards);
                if (!empty($cachedCards)) {
                    return $cachedCards;
                }
                Cache::delete($cardsCacheKey);
            }
        }

        $orderId = $orderCacheKey !== '' ? (int)Cache::get($orderCacheKey, 0) : 0;
        if ($orderId > 0) {
            $cards = $this->pollCardsByOrderId($product, $orderId);
            if (!empty($cards)) {
                $this->rememberCards($cardsCacheKey, $cards);
                return $cards;
            }

            throw new \Exception('上游订单已提交过，但暂未获取到交付内容。为避免重复下单，已停止重复提交，请稍后补单');
        }

        if ($submitStateKey !== '') {
            $submitState = Cache::get($submitStateKey, []);
            if (is_array($submitState) && !empty($submitState)) {
                throw new \Exception('上游订单已提交过，但暂未确认结果。为避免重复下单，已停止重复提交，请稍后补单');
            }
        }

        $payload = [
            'sku_id' => $skuId,
            'quantity' => $quantity,
        ];
        if ($tradeNo !== '') {
            $payload['downstream_order_no'] = $tradeNo;
            $payload['trace_id'] = $tradeNo;
        }
        if ($contact !== '') {
            $payload['manual_form_data'] = [
                'contact' => $contact,
            ];
        }

        if ($submitStateKey !== '') {
            Cache::set($submitStateKey, [
                'trade_no' => $tradeNo,
                'sku_id' => $skuId,
                'quantity' => $quantity,
                'time' => date('Y-m-d H:i:s'),
            ], 1800);
        }

        try {
            $createResponse = $this->request($product, 'POST', self::PATH_ORDERS, $payload);
        } catch (\Throwable $e) {
            throw new \Exception('上游下单结果异常，已阻止重复提交：' . $e->getMessage());
        }

        if (($createResponse['ok'] ?? false) !== true) {
            if ($submitStateKey !== '') {
                Cache::delete($submitStateKey);
            }
            throw new \Exception($this->buildApiErrorMessage($createResponse, '上游下单失败'));
        }

        $orderId = (int)($createResponse['order_id'] ?? 0);
        if ($orderId <= 0) {
            throw new \Exception('上游下单成功但未返回订单 ID');
        }

        if ($orderCacheKey !== '') {
            Cache::set($orderCacheKey, $orderId, 86400);
        }

        $cards = $this->pollCardsByOrderId($product, $orderId);
        if (!empty($cards)) {
            $this->rememberCards($cardsCacheKey, $cards);
            return $cards;
        }

        throw new \Exception('上游下单成功，但暂未获取到交付内容，请稍后补单');
    }

    private function pollCardsByOrderId($product, int $orderId): array
    {
        for ($i = 0; $i < self::POLL_RETRY_TIMES; $i++) {
            $detailResponse = $this->request($product, 'GET', self::PATH_ORDERS . '/' . $orderId);
            if (($detailResponse['ok'] ?? false) !== true) {
                throw new \Exception($this->buildApiErrorMessage($detailResponse, '查询上游订单失败'));
            }

            $status = strtolower((string)($detailResponse['status'] ?? ''));
            $cards = $this->extractCardsFromOrderDetail($detailResponse);
            if (!empty($cards)) {
                return $cards;
            }

            if ($status === 'canceled') {
                throw new \Exception('上游订单已取消');
            }

            if (in_array($status, ['delivered', 'completed'], true)) {
                break;
            }

            usleep(self::POLL_INTERVAL_MICROSECONDS);
        }

        return [];
    }

    private function findSkuById($product, int $targetSkuId): ?array
    {
        $page = 1;
        $pageSize = 100;

        do {
            $response = $this->request($product, 'GET', self::PATH_PRODUCTS, [], [
                'page' => $page,
                'page_size' => $pageSize,
            ]);
            $items = is_array($response['items'] ?? null) ? $response['items'] : [];
            foreach ($items as $productItem) {
                if (!is_array($productItem)) {
                    continue;
                }
                $skus = is_array($productItem['skus'] ?? null) ? $productItem['skus'] : [];
                foreach ($skus as $sku) {
                    if (!is_array($sku)) {
                        continue;
                    }
                    if ((int)($sku['id'] ?? 0) === $targetSkuId) {
                        return $sku;
                    }
                }
            }

            $total = (int)($response['total'] ?? 0);
            $currentPage = (int)($response['page'] ?? $page);
            $currentPageSize = max(1, (int)($response['page_size'] ?? $pageSize));
            $hasMore = $total > ($currentPage * $currentPageSize) && !empty($items);
            $page++;
        } while ($hasMore);

        return null;
    }

    private function request($product, string $method, string $path, array $body = [], array $query = []): array
    {
        $baseUrl = $this->normalizeBaseUrl((string)$this->readValue($product, 'docking_link', ''));
        $apiKey = trim((string)$this->readValue($product, 'shop_docking_code', ''));
        $apiSecret = trim((string)$this->readValue($product, 'docking_api_key', ''));

        if ($baseUrl === '' || $apiKey === '' || $apiSecret === '') {
            throw new \Exception('Dujiao-Next 对接参数不完整');
        }

        $method = strtoupper($method);
        $bodyJson = empty($body) ? '' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($bodyJson === false) {
            throw new \Exception('请求数据编码失败');
        }

        $timestamp = (string)time();
        $bodyMd5 = md5($bodyJson);
        $signString = $method . "\n" . $path . "\n" . $timestamp . "\n" . $bodyMd5;
        $signature = hash_hmac('sha256', $signString, $apiSecret);

        $url = $baseUrl . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Dujiao-Next-Api-Key: ' . $apiKey,
            'Dujiao-Next-Timestamp: ' . $timestamp,
            'Dujiao-Next-Signature: ' . $signature,
            'User-Agent: Entropy-DujiaoNextDocking/1.0',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new \Exception('请求异常：' . $curlErr);
        }

        if ($response === false || $response === '') {
            throw new \Exception('上游响应为空');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new \Exception('上游响应格式无效：' . mb_substr((string)$response, 0, 300));
        }

        if ($httpCode >= 400) {
            throw new \Exception($this->buildApiErrorMessage($decoded, 'HTTP ' . $httpCode));
        }

        return $decoded;
    }

    private function buildApiErrorMessage(array $response, string $fallback): string
    {
        $message = trim((string)($response['error_message'] ?? $response['message'] ?? ''));
        $errorCode = trim((string)($response['error_code'] ?? ''));
        if ($message !== '' && $errorCode !== '') {
            return $message . ' [' . $errorCode . ']';
        }
        if ($message !== '') {
            return $message;
        }
        if ($errorCode !== '') {
            return $fallback . ' [' . $errorCode . ']';
        }

        return $fallback;
    }

    private function extractCardsFromOrderDetail(array $detail): array
    {
        $fulfillment = is_array($detail['fulfillment'] ?? null) ? $detail['fulfillment'] : [];
        $payload = $fulfillment['payload'] ?? null;
        $deliveryData = $fulfillment['delivery_data'] ?? null;

        $cards = [];
        foreach ([$payload, $deliveryData] as $source) {
            foreach ($this->normalizeToCardLines($source) as $line) {
                $cards[] = $line;
            }
        }

        return array_values(array_unique(array_filter($cards, static function ($line) {
            return is_string($line) && trim($line) !== '';
        })));
    }

    private function normalizeToCardLines($value): array
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $item) {
                $result = array_merge($result, $this->normalizeToCardLines($item));
            }
            return $result;
        }

        if (is_object($value)) {
            return $this->normalizeToCardLines((array)$value);
        }

        if (!is_scalar($value)) {
            return [];
        }

        $text = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r", '<br>', '<br/>', '<br />'], "\n", $text);
        $parts = preg_split('/\n+/', $text) ?: [];

        $lines = [];
        foreach ($parts as $part) {
            $line = trim(strip_tags((string)$part));
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function rememberCards(string $cacheKey, array $cards): void
    {
        $cards = $this->filterCardLines($cards);
        if ($cacheKey === '' || empty($cards)) {
            return;
        }

        Cache::set($cacheKey, $cards, 86400);
    }

    private function filterCardLines(array $cards): array
    {
        $filtered = [];
        foreach ($cards as $card) {
            if (is_array($card)) {
                foreach ($this->filterCardLines($card) as $nestedCard) {
                    $filtered[] = $nestedCard;
                }
                continue;
            }

            if (!is_scalar($card)) {
                continue;
            }

            $line = trim((string)$card);
            if ($line !== '') {
                $filtered[] = $line;
            }
        }

        return array_values(array_unique($filtered));
    }

    private function normalizeStock(array $sku): int
    {
        $stockStatus = strtolower((string)($sku['stock_status'] ?? ''));
        $stockQuantity = (int)($sku['stock_quantity'] ?? 0);
        if ($stockStatus === 'unlimited' || $stockQuantity < 0) {
            return 99999999;
        }

        return max(0, $stockQuantity);
    }

    private function buildSkuDisplayName(string $productName, $specValues): string
    {
        $specLabel = '';
        if (is_array($specValues) && !empty($specValues)) {
            $pairs = [];
            foreach ($specValues as $key => $value) {
                $pairs[] = trim((string)$key) . ':' . trim((string)$value);
            }
            $specLabel = implode(' / ', array_filter($pairs));
        }

        return $specLabel !== '' ? ($productName . ' [' . $specLabel . ']') : $productName;
    }

    private function resolveCategoryInfo(array $productItem): array
    {
        $categoryId = (int)($productItem['category_id'] ?? $productItem['categoryId'] ?? 0);
        $categoryName = $this->extractCategoryName($productItem);

        if ($categoryName === '') {
            $categoryName = $categoryId > 0 ? ('上游分类 #' . $categoryId) : '未分类';
        }

        return [
            'id' => $categoryId,
            'name' => $categoryName,
        ];
    }

    private function extractCategoryName(array $productItem): string
    {
        $candidates = [
            $productItem['category_name'] ?? null,
            $productItem['categoryName'] ?? null,
            $productItem['category_title'] ?? null,
            $productItem['categoryTitle'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $name = $this->pickLocalizedText($candidate, '');
            if ($name !== '') {
                return $name;
            }
        }

        $nestedCandidates = [
            $productItem['category'] ?? null,
            $productItem['category_info'] ?? null,
            $productItem['categoryInfo'] ?? null,
            $productItem['product_category'] ?? null,
            $productItem['productCategory'] ?? null,
        ];

        foreach ($nestedCandidates as $candidate) {
            $name = $this->extractNameFromCategoryNode($candidate);
            if ($name !== '') {
                return $name;
            }
        }

        $categories = $productItem['categories'] ?? null;
        if (is_array($categories)) {
            foreach ($categories as $category) {
                $name = $this->extractNameFromCategoryNode($category);
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return '';
    }

    private function extractNameFromCategoryNode($category): string
    {
        if (is_string($category)) {
            return trim($category);
        }

        if (!is_array($category)) {
            return '';
        }

        foreach (['name', 'title', 'label', 'category_name', 'categoryName'] as $key) {
            $name = $this->pickLocalizedText($category[$key] ?? null, '');
            if ($name !== '') {
                return $name;
            }
        }

        return '';
    }

    private function pickLocalizedText($value, string $default = ''): string
    {
        if (is_string($value)) {
            return trim($value) !== '' ? trim($value) : $default;
        }

        if (is_array($value)) {
            foreach (['zh-CN', 'zh_CN', 'zh', 'en'] as $langKey) {
                if (!empty($value[$langKey]) && is_scalar($value[$langKey])) {
                    return trim((string)$value[$langKey]);
                }
            }
            foreach ($value as $item) {
                if (is_scalar($item) && trim((string)$item) !== '') {
                    return trim((string)$item);
                }
            }
        }

        return $default;
    }

    private function readValue($product, string $key, $default = null)
    {
        if (is_array($product)) {
            return $product[$key] ?? $default;
        }

        if (is_object($product)) {
            if (isset($product->$key)) {
                return $product->$key;
            }
            if (method_exists($product, 'getData')) {
                $value = $product->getData($key);
                return $value !== null ? $value : $default;
            }
        }

        return $default;
    }

    private function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            return '';
        }

        if (!preg_match('/^https?:\/\//i', $baseUrl)) {
            $baseUrl = 'https://' . $baseUrl;
        }

        $baseUrl = rtrim($baseUrl, '/');
        if (preg_match('#/api/v1/upstream$#i', $baseUrl)) {
            return preg_replace('#/api/v1/upstream$#i', '', $baseUrl) ?: $baseUrl;
        }

        return $baseUrl;
    }
}
