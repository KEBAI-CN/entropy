<?php
namespace app\service\docking_plugin;

use think\facade\Log;
use think\facade\Cache;

class CrossSiteDockingPlugin implements DockingPluginInterface
{
    public function getCode(): string
    {
        return 'cross_site';
    }

    public function getName(): string
    {
        return '同系统对接';
    }

    public function getType(): int
    {
        return 1;
    }

    public function getTags(): array
    {
        return ['官方', '跨站', '远程'];
    }

    public function getContent(): string
    {
        return '同系统跨站对接，适用于对接同架构站点商品并自动获取库存与下单发卡。';
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
                'label' => '对接地址',
                'type' => 'text',
                'placeholder' => '请输入对接站点地址（如 https://example.com）',
                'required' => 1,
            ],
            [
                'key' => 'shop_docking_code',
                'label' => '店铺标识',
                'type' => 'text',
                'placeholder' => '请输入对接店铺标识',
                'required' => 1,
            ],
            [
                'key' => 'docking_api_key',
                'label' => '密钥',
                'type' => 'password',
                'placeholder' => '请输入API密钥',
                'required' => 1,
            ],
        ];
    }

    public function supports($product): bool
    {
        $isDocking = (int)$this->readValue($product, 'is_docking', 0) === 1;
        $dockingType = (int)$this->readValue($product, 'docking_type', 0);
        $sourceProductId = (int)$this->readValue($product, 'source_product_id', 0);
        $dockingLink = trim((string)$this->readValue($product, 'docking_link', ''));
        $apiKey = trim((string)$this->readValue($product, 'docking_api_key', ''));

        return $isDocking && $dockingType === $this->getType() && $sourceProductId > 0 && $dockingLink !== '' && $apiKey !== '';
    }


    public function getCardProductId($product): int
    {
        if (is_array($product)) {
            return (int)($product['source_product_id'] ?? $product['id'] ?? 0);
        }

        if (is_object($product)) {
            if (isset($product->source_product_id)) {
                return (int)$product->source_product_id;
            }
            if (isset($product->id)) {
                return (int)$product->id;
            }
        }

        return 0;
    }

    public function getStock($product): int
    {
        try {
            $info = $this->fetchDockingProduct($product);
            return (int)($info['stock'] ?? 0);
        } catch (\Throwable $e) {
            Log::warning('Cross-site getStock failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function getUpstreamInfo($product): array
    {
        $info = $this->fetchDockingProduct($product);

        return [
            'cost_price' => isset($info['price']) ? (float)$info['price'] : 0.0,
            'stock' => (int)($info['stock'] ?? 0),
        ];
    }

    public function fetchUpstreamProducts(array $params): array
    {
        $baseUrl = $this->normalizeBaseUrl((string)($params['docking_link'] ?? ''));
        $apiKey = trim((string)($params['docking_api_key'] ?? ''));
        $shopCode = trim((string)($params['shop_docking_code'] ?? ''));
        $name = trim((string)($params['name'] ?? ''));
        $size = max(1, min(200, (int)($params['size'] ?? 100)));

        if ($baseUrl === '' || $apiKey === '' || $shopCode === '') {
            throw new \Exception('跨站对接参数不完整');
        }

        $lastError = '';
        foreach ($this->buildBaseUrlCandidates($baseUrl) as $candidateBaseUrl) {
            try {
                $res = $this->requestDockingProducts($candidateBaseUrl, [
                    'api_key' => $apiKey,
                    'shop_code' => $shopCode,
                    'name' => $name,
                    'size' => $size,
                ]);

                $raw = $res['data'] ?? [];
                $list = is_array($raw['data'] ?? null) ? $raw['data'] : (is_array($raw['records'] ?? null) ? $raw['records'] : (is_array($raw) ? $raw : []));

                $records = [];
                foreach ($list as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $id = (int)($item['id'] ?? 0);
                    if ($id <= 0) {
                        continue;
                    }
                    $records[] = [
                        'id' => $id,
                        'name' => (string)($item['name'] ?? ''),
                        'price' => (float)($item['price'] ?? 0),
                        'stock' => max(0, (int)($item['stock'] ?? 0)),
                        'is_docking' => (int)($item['is_docking'] ?? 1),
                        'status' => (int)($item['status'] ?? 1),
                        'ladder_pricing' => $item['ladder_pricing'] ?? [],
                        'category' => [
                            'id' => (int)($item['category']['id'] ?? 0),
                            'name' => (string)($item['category']['name'] ?? '未分类'),
                        ],
                    ];
                }

                return [
                    'data' => $records,
                    'records' => $records,
                    'count' => count($records),
                ];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                continue;
            }
        }

        throw new \Exception($lastError !== '' ? $lastError : '未获取到上游商品列表');
    }

    public function fetchCardsForOrder($product, $order): array
    {
        $baseUrl = $this->normalizeBaseUrl($this->readValue($product, 'docking_link', ''));
        $apiKey = trim((string)$this->readValue($product, 'docking_api_key', ''));
        $sourceProductId = $this->getCardProductId($product);

        if ($baseUrl === '' || $apiKey === '' || $sourceProductId <= 0) {
            throw new \Exception('跨站对接参数不完整');
        }

        $apiUrl = rtrim($baseUrl, '/') . '/api/order/create';
        $rawContact = trim((string)$this->readValue($order, 'contact', ''));
        $localTradeNo = trim((string)$this->readValue($order, 'trade_no', ''));
        $contact = $this->buildAgentAutoOrderContactBySlug($product, $rawContact, $localTradeNo);
        $upstreamOrderKey = $localTradeNo !== '' ? 'cross_site:upstream_trade_no:' . $localTradeNo : '';
        $cardsCacheKey = $localTradeNo !== '' ? 'cross_site:upstream_cards:' . $localTradeNo : '';
        $submitStateKey = $localTradeNo !== '' ? 'cross_site:submit_state:' . $localTradeNo : '';

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

        $upstreamTradeNo = $upstreamOrderKey !== '' ? trim((string)Cache::get($upstreamOrderKey, '')) : '';
        if ($upstreamTradeNo !== '') {
            $cards = $this->fetchRemoteCards($baseUrl, $upstreamTradeNo, $apiKey);
            if (!empty($cards)) {
                $this->rememberCards($cardsCacheKey, $cards);
                return $cards;
            }

            throw new \Exception('远程订单已提交过，但暂未获取到卡密。为避免重复下单，已停止重复提交，请稍后补单');
        }

        if ($submitStateKey !== '') {
            $submitState = Cache::get($submitStateKey, []);
            if (is_array($submitState) && !empty($submitState)) {
                throw new \Exception('远程订单已提交过，但暂未确认结果。为避免重复下单，已停止重复提交，请稍后补单');
            }
        }

        $postData = [
            'product_id' => $sourceProductId,
            'quantity' => (int)($this->readValue($order, 'quantity', 1)),
            'contact' => $contact,
            'payment_method_id' => 'docking_balance',
        ];

        $headers = [
            'X-Docking-Token: ' . $apiKey,
            'Accept: application/json',
            'User-Agent: Entropy-Docking/1.0',
        ];

        Log::info('Docking Create Order: URL=' . $apiUrl . ' Data=' . json_encode($postData, JSON_UNESCAPED_UNICODE));

        if ($submitStateKey !== '') {
            Cache::set($submitStateKey, [
                'trade_no' => $localTradeNo,
                'product_id' => $sourceProductId,
                'quantity' => (int)$postData['quantity'],
                'contact' => $contact,
                'time' => date('Y-m-d H:i:s'),
            ], 1800);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new \Exception('远程下单结果异常，已阻止重复提交：' . $curlErr);
        }

        if ($httpCode >= 400) {
            throw new \Exception('远程下单结果异常，已阻止重复提交：HTTP ' . $httpCode);
        }

        if (!$response) {
            throw new \Exception('远程下单结果异常，已阻止重复提交：Empty Response from Remote');
        }

        $res = json_decode($response, true);
        if (!is_array($res)) {
            throw new \Exception('远程下单结果异常，已阻止重复提交：远程响应格式无效');
        }

        if ((int)($res['code'] ?? 0) !== 200) {
            if ($submitStateKey !== '') {
                Cache::delete($submitStateKey);
            }
            throw new \Exception('Remote Order Failed: ' . ($res['msg'] ?? 'Unknown Remote Error'));
        }

        $data = $res['data'] ?? [];
        $cards = $this->filterCardLines(is_array($data) ? (array)($data['cards'] ?? []) : []);
        if (!empty($cards)) {
            $this->rememberCards($cardsCacheKey, $cards);
            return $cards;
        }

        $upstreamTradeNo = is_array($data) ? trim((string)($data['trade_no'] ?? '')) : '';
        if ($upstreamTradeNo === '') {
            throw new \Exception('远程下单成功但未返回订单号，无法拉取卡密');
        }
        if ($upstreamOrderKey !== '') {
            Cache::set($upstreamOrderKey, $upstreamTradeNo, 86400);
        }

        $cards = $this->fetchRemoteCards($baseUrl, $upstreamTradeNo, $apiKey);
        if (!empty($cards)) {
            $this->rememberCards($cardsCacheKey, $cards);
            return $cards;
        }

        throw new \Exception('远程订单已提交成功，但暂未获取到卡密，请稍后补单');
    }

    private function fetchDockingProduct($product): array
    {
        $baseUrl = $this->normalizeBaseUrl($this->readValue($product, 'docking_link', ''));
        $apiKey = trim((string)$this->readValue($product, 'docking_api_key', ''));
        $shopCode = trim((string)$this->readValue($product, 'shop_docking_code', ''));
        $sourceProductId = $this->getCardProductId($product);

        if ($baseUrl === '' || $apiKey === '' || $sourceProductId <= 0) {
            throw new \Exception('跨站对接参数不完整');
        }

        $lastError = '';
        foreach ($this->buildBaseUrlCandidates($baseUrl) as $candidateBaseUrl) {
            try {
                $res = $this->requestDockingProducts($candidateBaseUrl, [
                    'ids' => $sourceProductId,
                    'api_key' => $apiKey,
                    'shop_code' => $shopCode,
                ], 12);

                $raw = $res['data'] ?? [];
                $items = is_array($raw['data'] ?? null) ? $raw['data'] : (is_array($raw['records'] ?? null) ? $raw['records'] : (is_array($raw) ? $raw : []));
                if (!is_array($items) || empty($items)) {
                    throw new \Exception('未获取到上游商品信息');
                }

                $item = $items[0];
                if (!is_array($item)) {
                    throw new \Exception('上游商品数据格式异常');
                }

                if (($item['status'] ?? 0) != 1) {
                    return [
                        'price' => (float)($item['price'] ?? 0),
                        'stock' => 0,
                    ];
                }

                return [
                    'price' => (float)($item['price'] ?? 0),
                    'stock' => (int)($item['stock'] ?? 0),
                ];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                continue;
            }
        }

        throw new \Exception($lastError !== '' ? $lastError : '未获取到上游商品信息');
    }

    private function fetchRemoteCards(string $baseUrl, string $tradeNo, string $token): array
    {
        $url = rtrim($baseUrl, '/') . '/api/order/query?keyword=' . urlencode($tradeNo);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Docking-Token: ' . $token,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return [];
        }

        $res = json_decode($response, true);
        if (!is_array($res) || (int)($res['code'] ?? 0) !== 200) {
            return [];
        }

        $records = $res['data']['records'] ?? [];
        if (!is_array($records) || empty($records)) {
            return [];
        }

        $cards = $records[0]['cards'] ?? [];
        if (!is_array($cards)) {
            return [];
        }

        return $this->filterCardLines($cards);
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

    private function requestDockingProducts(string $baseUrl, array $queryParams, int $timeout = 30): array
    {
        $url = rtrim($baseUrl, '/') . '/api/docking/products/fetch';
        $query = http_build_query($queryParams);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Entropy-Docking-Client/1.0');

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new \Exception('请求异常: ' . $curlErr);
        }

        if ($httpCode >= 400 || !$response) {
            throw new \Exception('同步失败(HTTP ' . $httpCode . '): 请检查对接配置和网络');
        }

        $res = json_decode($response, true);
        if (!is_array($res) || (int)($res['code'] ?? 0) !== 200) {
            throw new \Exception((string)($res['msg'] ?? '远程返回异常'));
        }

        return $res;
    }

    private function buildBaseUrlCandidates(string $baseUrl): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        $candidates = [$baseUrl];

        $host = (string)(parse_url($baseUrl, PHP_URL_HOST) ?? '');
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $candidates[] = 'http://host.docker.internal:' . ((int)(parse_url($baseUrl, PHP_URL_PORT) ?? 80));
            $candidates[] = str_replace('://localhost', '://127.0.0.1', $baseUrl);
        }

        $normalized = [];
        foreach ($candidates as $item) {
            $item = rtrim((string)$item, '/');
            if ($item === '' || in_array($item, $normalized, true)) {
                continue;
            }
            $normalized[] = $item;
        }

        return $normalized;
    }

    private function buildAgentAutoOrderContactBySlug($product, string $contact, string $tradeNo): string
    {
        $sourceProductId = (int)$this->readValue($product, 'source_product_id', 0);
        if ($sourceProductId > 0) {
            $sourceProduct = \app\model\Product::find($sourceProductId);
            if ($sourceProduct) {
                $slug = trim((string)(\app\model\Shop::where('user_id', (int)$sourceProduct->user_id)->value('slug') ?? ''));
                if ($slug !== '') {
                    return $slug;
                }
            }
        }

        if ($contact !== '') {
            return $contact;
        }

        if ($tradeNo !== '') {
            return $tradeNo;
        }

        return 'entropy_order_' . time();
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

        if (!preg_match('/^http(s)?:\/\//i', $baseUrl)) {
            $baseUrl = 'http://' . $baseUrl;
        }

        return rtrim($baseUrl, '/');
    }
}
