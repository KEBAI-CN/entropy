<?php
namespace app\service\docking_plugin;

use think\facade\Log;
use think\facade\Cache;

class RainbowCloudShopDockingPlugin implements DockingPluginInterface
{
    public function getCode(): string
    {
        return 'rainbow_cloud_shop';
    }

    public function getName(): string
    {
        return '彩虹云商城对接';
    }

    public function getType(): int
    {
        return 2;
    }

    public function getTags(): array
    {
        return ['彩虹云', '第三方', '远程'];
    }

    public function getContent(): string
    {
        return '彩虹云商城对接插件，支持远程商品拉取、下单与卡密回传。类型必须下单后是发卡。';
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
                'label' => '接口地址',
                'type' => 'text',
                'placeholder' => '例如：https://1.xbyos.cn',
                'required' => 1,
            ],
            [
                'key' => 'shop_docking_code',
                'label' => '分站用户名',
                'type' => 'text',
                'placeholder' => '请输入彩虹云商城分站用户名',
                'required' => 1,
            ],
            [
                'key' => 'docking_api_key',
                'label' => '分站密码',
                'type' => 'password',
                'placeholder' => '请输入彩虹云商城分站密码',
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
            Log::warning('RainbowCloudShop getStock failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function getUpstreamInfo($product): array
    {
        $params = $this->buildBaseAuthParams($product);
        $params['act'] = 'goodsdetails';
        $params['tid'] = $this->getCardProductId($product);

        $response = $this->request($product, 'POST', $params);

        $responseCode = (int)($response['code'] ?? -999);
        $payload = is_array($response['data'] ?? null) ? $response['data'] : $response;
        $hasStock = array_key_exists('stock', $payload);
        $hasPrice = array_key_exists('price', $payload);

        if (!is_array($response) || (!$hasStock && !$hasPrice && !in_array($responseCode, [0, 1], true))) {
            throw new \Exception('获取上游商品信息失败：' . $this->getErrorMessage($response));
        }

        $stockRaw = $payload['stock'] ?? null;
        $stock = $stockRaw === null ? 99999999 : (int)$stockRaw;

        return [
            'cost_price' => (float)($payload['price'] ?? 0),
            'stock' => max(0, $stock),
        ];
    }

    public function fetchUpstreamProducts(array $params): array
    {
        $mockProduct = [
            'docking_link' => (string)($params['docking_link'] ?? ''),
            'shop_docking_code' => (string)($params['shop_docking_code'] ?? ''),
            'docking_api_key' => (string)($params['docking_api_key'] ?? ''),
            'source_product_id' => 1,
        ];

        $request = $this->buildBaseAuthParams($mockProduct);
        $request['act'] = 'goodslist';
        if (trim((string)($params['name'] ?? '')) !== '') {
            $request['keyword'] = trim((string)$params['name']);
        }

        $response = $this->request($mockProduct, 'POST', $request);
        $responseCode = (int)($response['code'] ?? -999);
        $hasDataList = is_array($response['data'] ?? null);
        if (!is_array($response) || (!$hasDataList && !in_array($responseCode, [0, 1], true))) {
            throw new \Exception('彩虹云获取商品失败：' . $this->getErrorMessage($response));
        }

        $rawList = $response['data'] ?? [];
        if (!is_array($rawList)) {
            $rawList = [];
        }

        $records = [];
        foreach ($rawList as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (int)($item['tid'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $categoryId = (int)($item['cid'] ?? 0);
            $stockRaw = $item['stock'] ?? null;
            $stock = $stockRaw === null ? 99999999 : (int)$stockRaw;
            $records[] = [
                'id' => $id,
                'name' => (string)($item['name'] ?? ''),
                'price' => (float)($item['price'] ?? 0),
                'stock' => max(0, $stock),
                'is_docking' => 1,
                'status' => ((int)($item['close'] ?? 0) === 1) ? 0 : 1,
                'category' => [
                    'id' => $categoryId,
                    'name' => $categoryId > 0 ? ('分类' . $categoryId) : '未分类',
                ],
            ];
        }

        return [
            'data' => $records,
            'records' => $records,
            'count' => count($records),
        ];
    }

    public function fetchCardsForOrder($product, $order): array
    {
        $contact = trim((string)$this->readValue($order, 'contact', ''));
        $tradeNo = trim((string)$this->readValue($order, 'trade_no', ''));
        $quantity = max(1, (int)$this->readValue($order, 'quantity', 1));
        $input = $this->buildOrderInput($contact, $tradeNo);

        $this->writeDockingLog('fetch_cards_start', [
            'trade_no' => $tradeNo,
            'source_product_id' => $this->getCardProductId($product),
            'quantity' => $quantity,
            'input' => $input,
        ]);

        $cachedOrderId = 0;
        $cacheKey = '';
        $cardsCacheKey = '';
        $submitStateKey = '';
        if ($tradeNo !== '') {
            $cacheKey = 'rainbow:upstream_order_id:' . $tradeNo;
            $cardsCacheKey = 'rainbow:upstream_cards:' . $tradeNo;
            $submitStateKey = 'rainbow:submit_state:' . $tradeNo;
            $cachedOrderId = (int)Cache::get($cacheKey, 0);

            $cachedCards = Cache::get($cardsCacheKey, []);
            if (is_array($cachedCards) && !empty($cachedCards)) {
                $cachedCards = $this->filterCardLines($cachedCards);
                if (empty($cachedCards)) {
                    Cache::delete($cardsCacheKey);
                } else {
                    $this->writeDockingLog('hit_cached_cards', [
                        'trade_no' => $tradeNo,
                        'card_count' => count($cachedCards),
                    ]);
                    return $cachedCards;
                }
            }
        }
        if ($cachedOrderId > 0) {
            $this->writeDockingLog('hit_cached_order_id', [
                'trade_no' => $tradeNo,
                'cached_order_id' => $cachedOrderId,
            ]);
        }

        $searchInputs = array_values(array_unique(array_filter([$input, $contact, $tradeNo], static function ($value) {
            return is_string($value) && trim($value) !== '';
        })));

        if ($cachedOrderId <= 0) {
            $submitState = $submitStateKey !== '' ? Cache::get($submitStateKey, []) : [];
            if (is_array($submitState) && !empty($submitState)) {
                $cards = $this->searchCardsByOrderHints($product, $tradeNo, 0, $searchInputs, 3, 1);
                if (!empty($cards)) {
                    $this->rememberCards($cardsCacheKey, $cards);
                    return $cards;
                }

                throw new \Exception('彩虹云订单已提交过，但暂未取回卡密。为避免重复扣库存，已停止重复下单，请稍后补单');
            }

            $payParams = $this->buildBaseAuthParams($product);
            $payParams['act'] = 'pay';
            $payParams['tid'] = $this->getCardProductId($product);
            $payParams['input1'] = $input;
            $payParams['input'] = $input;
            $payParams['num'] = $quantity;

            if ($submitStateKey !== '') {
                Cache::set($submitStateKey, [
                    'trade_no' => $tradeNo,
                    'tid' => (int)$payParams['tid'],
                    'input' => $input,
                    'quantity' => $quantity,
                    'time' => date('Y-m-d H:i:s'),
                ], 1800);
            }

            try {
                $payRes = $this->request($product, 'POST', $payParams);
            } catch (\Throwable $e) {
                $this->writeDockingLog('pay_request_uncertain', [
                    'trade_no' => $tradeNo,
                    'error' => $e->getMessage(),
                    'inputs' => $searchInputs,
                ]);

                $cards = $this->searchCardsByOrderHints($product, $tradeNo, 0, $searchInputs, 3, 1);
                if (!empty($cards)) {
                    $this->rememberCards($cardsCacheKey, $cards);
                    return $cards;
                }

                throw new \Exception('彩虹云下单结果异常，已阻止重复提交：' . $e->getMessage());
            }

            $this->writeDockingLog('pay_response', [
                'trade_no' => $tradeNo,
                'response' => $payRes,
            ]);
            $payCode = (int)($payRes['code'] ?? -999);
            if (!is_array($payRes) || !in_array($payCode, [0, 1], true)) {
                if ($submitStateKey !== '') {
                    Cache::delete($submitStateKey);
                }
                throw new \Exception('彩虹云下单失败：' . $this->getErrorMessage($payRes));
            }

            $cards = $this->extractCardsFromPayload($payRes);
            if (!empty($cards)) {
                $this->writeDockingLog('pay_response_contains_cards', [
                    'trade_no' => $tradeNo,
                    'card_count' => count($cards),
                ]);
                $this->rememberCards($cardsCacheKey, $cards);
                return $cards;
            }

            $cachedOrderId = $this->extractOrderId($payRes);
            if ($cachedOrderId <= 0) {
                $cards = $this->searchCardsByOrderHints($product, $tradeNo, 0, $searchInputs, 3, 1);
                if (!empty($cards)) {
                    $this->rememberCards($cardsCacheKey, $cards);
                    return $cards;
                }

                throw new \Exception('彩虹云下单成功但未返回订单号，无法拉取卡密');
            }
            if ($cacheKey !== '') {
                Cache::set($cacheKey, $cachedOrderId, 86400);
            }
            $this->writeDockingLog('save_cached_order_id', [
                'trade_no' => $tradeNo,
                'cached_order_id' => $cachedOrderId,
            ]);
        }

        $cards = $this->searchCardsByOrderHints($product, $tradeNo, $cachedOrderId, $searchInputs, 10, 2);
        if (!empty($cards)) {
            $this->rememberCards($cardsCacheKey, $cards);
            return $cards;
        }

        throw new \Exception('彩虹云返回成功，但暂未获取到卡密，请稍后补单');
    }

    private function searchCardsByOrderHints($product, string $tradeNo, int $upstreamOrderId, array $inputs, int $maxAttempts, int $sleepSeconds): array
    {
        $searchParams = $this->buildBaseAuthParams($product);
        $searchParams['act'] = 'search';

        $variants = [];
        if ($upstreamOrderId > 0) {
            $params = $searchParams;
            $params['id'] = $upstreamOrderId;
            $variants[] = ['label' => 'id', 'value' => $upstreamOrderId, 'params' => $params];
        } else {
            foreach ($inputs as $input) {
                $input = trim((string)$input);
                if ($input === '') {
                    continue;
                }

                foreach (['input1', 'input', 'keyword', 'kw', 'qq'] as $field) {
                    $params = $searchParams;
                    $params[$field] = $input;
                    $variants[] = ['label' => $field, 'value' => $input, 'params' => $params];
                }
            }
        }

        if (empty($variants)) {
            return [];
        }

        $finishedWithoutCards = false;
        $maxAttempts = max(1, $maxAttempts);
        for ($i = 0; $i < $maxAttempts; $i++) {
            foreach ($variants as $variant) {
                try {
                    $searchRes = $this->request($product, 'POST', $variant['params']);
                } catch (\Throwable $e) {
                    $this->writeDockingLog('search_request_failed', [
                        'trade_no' => $tradeNo,
                        'attempt' => $i + 1,
                        'field' => $variant['label'],
                        'value' => $variant['value'],
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }

                $this->writeDockingLog('search_response', [
                    'trade_no' => $tradeNo,
                    'attempt' => $i + 1,
                    'field' => $variant['label'],
                    'value' => $variant['value'],
                    'upstream_order_id' => $upstreamOrderId,
                    'response' => $searchRes,
                ]);

                if (!is_array($searchRes)) {
                    continue;
                }

                $cards = $this->extractCardsFromSearchPayload($searchRes);
                if (!empty($cards)) {
                    $this->writeDockingLog('search_success_cards', [
                        'trade_no' => $tradeNo,
                        'attempt' => $i + 1,
                        'field' => $variant['label'],
                        'card_count' => count($cards),
                    ]);
                    return $cards;
                }

                $searchCode = (int)($searchRes['code'] ?? -999);
                if ($upstreamOrderId > 0 && (int)($searchRes['status'] ?? 0) === 1 && in_array($searchCode, [0, 1], true)) {
                    $finishedWithoutCards = true;
                }
            }

            if ($finishedWithoutCards) {
                $this->writeDockingLog('search_finish_without_cards', [
                    'trade_no' => $tradeNo,
                    'attempt' => $i + 1,
                    'upstream_order_id' => $upstreamOrderId,
                ]);
                break;
            }

            if ($i + 1 < $maxAttempts && $sleepSeconds > 0) {
                sleep($sleepSeconds);
            }
        }

        return [];
    }


    private function buildBaseAuthParams($product): array
    {
        $user = trim((string)$this->readValue($product, 'shop_docking_code', ''));
        $pass = trim((string)$this->readValue($product, 'docking_api_key', ''));
        $tid = $this->getCardProductId($product);

        if ($user === '' || $pass === '' || $tid <= 0) {
            throw new \Exception('彩虹云对接参数不完整');
        }

        return [
            'user' => $user,
            'pass' => $pass,
        ];
    }

    private function request($product, string $method, array $params)
    {
        $url = $this->buildApiUrl((string)$this->readValue($product, 'docking_link', ''));
        if ($url === '') {
            throw new \Exception('彩虹云接口地址不能为空');
        }

        $method = strtoupper($method);
        $requestParams = $params;
        if (isset($requestParams['act']) && trim((string)$requestParams['act']) !== '') {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'act=' . urlencode((string)$requestParams['act']);
            unset($requestParams['act']);
        }

        $ch = curl_init();
        if ($method === 'GET') {
            if (!empty($requestParams)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($requestParams);
            }
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestParams));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'User-Agent: Entropy-RainbowDocking/1.0']);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->writeDockingLog('http_curl_error', [
                'url' => $this->maskUrl($url),
                'error' => $curlErr,
            ]);
            throw new \Exception('彩虹云请求异常：' . $curlErr);
        }

        $rawResponse = is_string($response) ? $response : '';
        $decoded = $rawResponse !== '' ? json_decode($rawResponse, true) : null;
        if (is_array($decoded)) {
            $decoded['_http_code'] = $httpCode;
        }

        if ($httpCode >= 400 || $response === false || $rawResponse === '') {
            if (is_array($decoded) && $this->isSuccessfulPayload($decoded)) {
                return $decoded;
            }

            if ($rawResponse !== '' && $this->looksLikeCardResponse($rawResponse)) {
                return ['code' => 0, 'message' => $rawResponse, '_http_code' => $httpCode];
            }

            $this->writeDockingLog('http_error_response', [
                'url' => $this->maskUrl($url),
                'http_code' => $httpCode,
                'response' => mb_substr($rawResponse, 0, 800),
            ]);

            $message = is_array($decoded) ? $this->getErrorMessage($decoded) : '';
            throw new \Exception('彩虹云接口异常，HTTP ' . $httpCode . ($message !== '' && $message !== '未知错误' ? '：' . $message : ''));
        }

        if (is_array($decoded)) {
            return $decoded;
        }

        Log::warning('RainbowCloudShop non-json response: ' . mb_substr((string)$response, 0, 500));
        return ['code' => 0, 'message' => (string)$response];
    }

    private function buildApiUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            return '';
        }

        if (!preg_match('/^https?:\/\//i', $baseUrl)) {
            $baseUrl = 'https://' . $baseUrl;
        }

        $baseUrl = rtrim($baseUrl, '/');
        if (stripos($baseUrl, '/api.php') !== false) {
            return $baseUrl;
        }

        return $baseUrl . '/api.php';
    }

    private function extractOrderId(array $payload): int
    {
        $directId = (int)($payload['id'] ?? $payload['order_id'] ?? $payload['orderid'] ?? 0);
        if ($directId > 0) {
            return $directId;
        }

        $message = (string)($payload['message'] ?? $payload['msg'] ?? '');
        if ($message !== '' && preg_match('/(\d{4,})/', $message, $m)) {
            return (int)$m[1];
        }

        return 0;
    }

    private function extractCardsFromSearchPayload(array $payload): array
    {
        if ($this->payloadIndicatesFailure($payload)) {
            return [];
        }

        $priorityPaths = [
            ['data', 'kmdata'],
            ['kmdata'],
            ['data', 'cards'],
            ['cards'],
            ['data', 'kms'],
            ['kms'],
            ['data', 'km'],
            ['km'],
            ['data', 'card_list'],
            ['card_list'],
            ['data', 'card'],
            ['card'],
            ['data', 'message'],
            ['message'],
            ['data', 'msg'],
            ['msg'],
        ];

        $cards = [];
        foreach ($priorityPaths as $path) {
            $value = $this->getNestedValue($payload, $path);
            if ($value === null) {
                continue;
            }

            foreach ($this->normalizeToCardLines($value) as $line) {
                $cards[] = $line;
            }

            if (!empty($cards)) {
                break;
            }
        }

        return $this->filterCardLines($cards);
    }

    private function extractCardsFromPayload(array $payload): array
    {
        if ($this->payloadIndicatesFailure($payload)) {
            return [];
        }

        $candidates = [];

        $data = $payload['data'] ?? null;
        if (is_array($data)) {
            foreach (['cards', 'kms', 'km', 'card', 'card_list', 'kmdata'] as $key) {
                if (isset($data[$key])) {
                    $candidates[] = $data[$key];
                }
            }
        }

        foreach (['cards', 'kms', 'km', 'card', 'message', 'msg', 'kmdata'] as $key) {
            if (isset($payload[$key])) {
                $candidates[] = $payload[$key];
            }
        }

        $cards = [];
        foreach ($candidates as $item) {
            foreach ($this->normalizeToCardLines($item) as $line) {
                $cards[] = $line;
            }
        }

        return $this->filterCardLines($cards);
    }

    private function getNestedValue(array $payload, array $path)
    {
        $current = $payload;
        foreach ($path as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
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

        if (!is_scalar($value)) {
            return [];
        }

        $text = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r", '<br>', '<br/>', '<br />'], "\n", $text);
        $parts = preg_split('/\n+/', $text) ?: [];

        $lines = [];
        foreach ($parts as $part) {
            $line = trim(strip_tags($part));
            if ($line === '') {
                continue;
            }
            if (preg_match('/^(下单成功|订单号|订单状态|请求成功|success)[:：]?/iu', $line)) {
                continue;
            }
            if (preg_match('/卡密[:：]\s*(.+)$/u', $line, $m)) {
                $line = trim($m[1]);
            }
            if ($this->isValidCardLine($line)) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function buildOrderInput(string $contact, string $tradeNo): string
    {
        if ($contact !== '') {
            return $contact;
        }

        if ($tradeNo !== '') {
            return $tradeNo;
        }

        return 'entropy_order_' . time();
    }

    private function rememberCards(string $cacheKey, array $cards): void
    {
        $cards = $this->filterCardLines($cards);
        if ($cacheKey === '' || empty($cards)) {
            if ($cacheKey !== '') {
                Cache::delete($cacheKey);
            }
            return;
        }

        Cache::set($cacheKey, array_values($cards), 86400);
    }

    private function isSuccessfulPayload(array $payload): bool
    {
        if ($this->payloadIndicatesFailure($payload)) {
            return false;
        }

        $code = (int)($payload['code'] ?? -999);
        if (in_array($code, [0, 1], true)) {
            return true;
        }

        return $this->extractOrderId($payload) > 0
            || !empty($this->extractCardsFromPayload($payload))
            || !empty($this->extractCardsFromSearchPayload($payload));
    }

    private function looksLikeCardResponse(string $response): bool
    {
        $text = trim(strip_tags($response));
        if ($text === '') {
            return false;
        }

        return preg_match('/(卡密|kmdata|下单成功|购买成功|订单号)/iu', $text) === 1
            && preg_match('/(fatal error|exception|stack trace|internal server error|not found)/iu', $text) !== 1;
    }

    private function filterCardLines(array $cards): array
    {
        $result = [];
        foreach ($cards as $card) {
            if (is_array($card)) {
                foreach ($this->filterCardLines($card) as $nestedCard) {
                    $result[] = $nestedCard;
                }
                continue;
            }

            if (!is_scalar($card)) {
                continue;
            }

            $line = trim((string)$card);
            if ($this->isValidCardLine($line)) {
                $result[] = $line;
            }
        }

        return array_values(array_unique($result));
    }

    private function isValidCardLine(string $line): bool
    {
        $line = trim(strip_tags($line));
        if ($line === '') {
            return false;
        }

        if (preg_match('/^(下单成功|订单号|订单状态|请求成功|success)[:：]?/iu', $line)) {
            return false;
        }

        return !$this->isFailureText($line);
    }

    private function payloadIndicatesFailure(array $payload): bool
    {
        $code = $payload['code'] ?? null;
        if ($code !== null && is_numeric($code) && (int)$code < 0) {
            return true;
        }

        $status = $payload['status'] ?? null;
        if ($status !== null && is_numeric($status) && (int)$status < 0) {
            return true;
        }

        foreach (['message', 'msg', 'error'] as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key]) && $this->isFailureText((string)$payload[$key])) {
                return true;
            }
        }

        $data = $payload['data'] ?? null;
        if (is_array($data)) {
            foreach (['message', 'msg', 'error'] as $key) {
                if (isset($data[$key]) && is_scalar($data[$key]) && $this->isFailureText((string)$data[$key])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isFailureText(string $text): bool
    {
        $text = trim(strip_tags($text));
        if ($text === '') {
            return false;
        }

        return preg_match('/(订单不存在|商品不存在|卡密不存在|不存在该订单|查无此单|未找到订单|没有找到订单|库存不足|余额不足|下单失败|购买失败|支付失败|查询失败|请求失败|操作失败|接口异常|系统异常|参数错误|账号错误|密码错误|登录失败|登陆失败|未返回订单号|暂未获取到卡密|没有卡密|无卡密|无数据|\b(?:error|fail|failed|exception)\b)/iu', $text) === 1;
    }

    private function maskUrl(string $url): string
    {
        return preg_replace('/([?&](?:pass|user)=)[^&]*/i', '$1***', $url) ?? $url;
    }

    private function writeDockingLog(string $event, array $context = []): void
    {
        try {
            $logFile = runtime_path() . 'log/rainbow_docking_' . date('Ymd') . '.log';
            $line = '[' . date('Y-m-d H:i:s') . '] [' . $event . '] ' . json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
        } catch (\Throwable $e) {
            Log::warning('RainbowCloudShop writeDockingLog failed: ' . $e->getMessage());
        }
    }

    private function getErrorMessage($payload): string
    {
        if (is_array($payload)) {
            $msg = (string)($payload['msg'] ?? $payload['message'] ?? '');
            if ($msg !== '') {
                return $msg;
            }
        }
        return '未知错误';
    }

    private function readValue($target, string $key, $default = null)
    {
        if (is_array($target)) {
            return $target[$key] ?? $default;
        }

        if (is_object($target)) {
            if (isset($target->$key)) {
                return $target->$key;
            }
            if (method_exists($target, 'getData')) {
                $value = $target->getData($key);
                return $value !== null ? $value : $default;
            }
        }

        return $default;
    }
}
