<?php
namespace app\service\payment;

use think\Exception;
use think\facade\Log;

class QqpayDriver implements PaymentDriverInterface
{
    public function getKey(): string
    {
        return 'qqpay';
    }

    public function getLabel(): string
    {
        return 'QQ支付';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getTypes(): array
    {
        $epayFields = [
            [
                'key' => 'transfer_api_version',
                'label' => '接口版本',
                'type' => 'radio',
                'options' => [
                    ['label' => 'V1', 'value' => 'v1'],
                    ['label' => 'V2', 'value' => 'v2']
                ],
                'required' => false,
                'scope' => 'config',
                'tip' => '易支付接口版本，默认 V1。'
            ],
            [
                'key' => 'api_url',
                'label' => '接口地址',
                'type' => 'text',
                'placeholder' => '例如：https://pay.example.com/',
                'required' => true,
                'tip' => '易支付接口地址'
            ],
            [
                'key' => 'pid',
                'label' => '商户PID',
                'type' => 'text',
                'placeholder' => '请输入商户PID',
                'required' => true
            ],
            [
                'key' => 'key',
                'label' => '商户密钥',
                'type' => 'text',
                'placeholder' => '请输入商户密钥',
                'required' => true,
                'transfer_versions' => ['v1'],
                'visible_when' => [
                    'field' => 'transfer_api_version',
                    'value' => 'v1'
                ]
            ],
            [
                'key' => 'private_key',
                'label' => 'RSA私钥',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => '请输入商户后台生成的 RSA 私钥',
                'required' => true,
                'tip' => 'V2 使用，RSA 签名时必填',
                'transfer_versions' => ['v2'],
                'visible_when' => [
                    'field' => 'transfer_api_version',
                    'value' => 'v2'
                ]
            ],
            [
                'key' => 'public_key',
                'label' => '平台公钥',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => '请输入平台公钥（用于回调验签）',
                'required' => false,
                'transfer_versions' => ['v2'],
                'visible_when' => [
                    'field' => 'transfer_api_version',
                    'value' => 'v2'
                ]
            ],
            [
                'key' => 'skip_ssl_verify',
                'label' => '跳过SSL校验',
                'type' => 'switch',
                'active_value' => 1,
                'inactive_value' => 0,
                'required' => false,
                'tip' => '仅网关证书链异常或自签证书时开启，生产环境建议修复证书。'
            ],
            [
                'key' => 'epay_type',
                'label' => '通道标识',
                'type' => 'text',
                'placeholder' => '例如：qqpay/alipay/wxpay',
                'tip' => '对应易支付 type 参数，留空默认使用支付方式标识'
            ]
        ];

        $payproFields = [
            [
                'key' => 'api_url',
                'label' => '接口地址',
                'type' => 'text',
                'placeholder' => '例如：https://pay.example.com',
                'required' => true,
                'tip' => '超级支付接口地址'
            ],
            [
                'key' => 'pid',
                'label' => '商户PID',
                'type' => 'text',
                'placeholder' => '请输入商户PID',
                'required' => true
            ],
            [
                'key' => 'key',
                'label' => 'MD5密钥',
                'type' => 'text',
                'placeholder' => '使用 MD5 签名时请输入商户密钥',
                'required' => false,
                'visible_when' => [
                    'field' => 'sign_type',
                    'value' => 'MD5'
                ]
            ],
            [
                'key' => 'private_key',
                'label' => 'RSA私钥',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => '使用 RSA 签名时请输入商户私钥',
                'required' => false,
                'visible_when' => [
                    'field' => 'sign_type',
                    'value' => 'RSA'
                ]
            ],
            [
                'key' => 'public_key',
                'label' => 'RSA公钥',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => '使用 RSA 验签时请输入平台公钥',
                'required' => false,
                'visible_when' => [
                    'field' => 'sign_type',
                    'value' => 'RSA'
                ]
            ],
            [
                'key' => 'sign_type',
                'label' => '签名类型',
                'type' => 'radio',
                'options' => [
                    ['label' => 'MD5', 'value' => 'MD5'],
                    ['label' => 'RSA', 'value' => 'RSA']
                ],
                'required' => true
            ],
            [
                'key' => 'channel_id',
                'label' => '网关ID',
                'type' => 'text',
                'placeholder' => '可选，不填由平台自动分配',
                'required' => false
            ],
            [
                'key' => 'skip_ssl_verify',
                'label' => '跳过SSL校验',
                'type' => 'switch',
                'active_value' => 1,
                'inactive_value' => 0,
                'required' => false,
                'tip' => '仅网关证书链异常或自签证书时开启，生产环境建议修复证书。'
            ],
            [
                'key' => 'paypro_type',
                'label' => '通道标识',
                'type' => 'text',
                'placeholder' => '例如：alipay/wxpay/qqpay',
                'tip' => '对应超级支付 paytype_code 参数，留空默认使用支付方式标识'
            ]
        ];

        return [
            [
                'key' => 'epay',
                'label' => '易支付通道',
                'modes' => [0, 1],
                'default_mode' => 0,
                'fields' => $epayFields,
                'default_config' => [
                    'transfer_api_version' => 'v1',
                    'transfer_sign_type' => 'MD5',
                    'epay_type' => 'qqpay'
                ]
            ],
            [
                'key' => 'paypro',
                'label' => '超级支付通道',
                'modes' => [0, 1],
                'default_mode' => 0,
                'fields' => $payproFields,
                'default_config' => [
                    'sign_type' => 'MD5',
                    'paypro_type' => 'qqpay'
                ]
            ]
        ];
    }

    public function getIcon(): string
    {
        return '';
    }

    public function getIconUrl(): string
    {
        return 'https://pay.slmsns.com/static/icon/qqpay.png';
    }

    public function pay($orderId, $amount, $title, array $config, string $type): array
    {
        if ($type === 'paypro') {
            $actualProvider = $config['paypro_type'] ?? $this->getKey();
            return $this->payPaypro($orderId, $amount, $title, $config, $actualProvider);
        }

        $actualProvider = $config['epay_type'] ?? $this->getKey();
        return $this->payEpay($orderId, $amount, $title, $config, $actualProvider);
    }

    public function verifyNotify(array $params, array $config, string $type): bool
    {
        if ($type === 'paypro') {
            return $this->verifyPaypro($params, $config);
        }

        return $this->verifyEpay($params, $config);
    }

    public function refund($tradeNo, $amount, array $config, string $type): bool
    {
        if ($type === 'paypro') {
            return $this->refundPaypro($tradeNo, $amount, $config);
        }

        return $this->refundEpay($tradeNo, $amount, $config);
    }

    public function queryOrder($orderId, array $config, string $provider): bool
    {
        if ($provider === 'paypro') {
            return $this->queryPayproOrder($orderId, $config);
        }

        return $this->queryEpayOrder($orderId, $config);
    }

    public function handleNotify(array $config)
    {
        return false;
    }

    protected function payEpay($orderId, $amount, $title, $config, $actualProvider)
    {
        if ($this->getEpaySignType($config) === 'RSA') {
            return $this->payEpayV2($orderId, $amount, $title, $config, $actualProvider);
        }

        $api_url = (string)($config['api_url'] ?? ($config['url'] ?? ($config['pay_epay_url'] ?? '')));
        $pid = $config['pid'] ?? ($config['pay_epay_pid'] ?? '');
        $signType = $this->getEpaySignType($config);

        if (empty($api_url) || empty($pid)) {
            throw new Exception("易支付配置不完整");
        }

        $api_url = $this->normalizeGatewayBaseUrl($api_url, true);

        $clientIp = request()->ip();
        if (empty($clientIp)) {
            $clientIp = '127.0.0.1';
        }

        $data = [
            "pid" => $pid,
            "type" => $actualProvider,
            "out_trade_no" => $orderId,
            "notify_url" => $config['notify_url'] ?? (request()->domain() . "/api/v3/payment/notify/qqpay"),
            "return_url" => $config['return_url'] ?? (($config['frontend_url'] ?? request()->domain()) . "/payment/callback"),
            "name" => $title,
            "money" => $amount,
            "sitename" => $config['site_name'] ?? 'Entropy',
            "clientip" => $clientIp,
            "device" => 'pc'
        ];

        $data['sign_type'] = $signType;
        $data['sign'] = $this->signEpay($data, $config, $signType);

        $query = http_build_query($data);

        if (($config['mode'] ?? 0) == 1) {
            $url = $api_url . 'mapi.php';
            $verifySsl = $this->shouldVerifyPaymentSsl($config, $api_url);
            $headers = [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer: ' . $api_url
            ];
            $response = $this->httpRequestWithSslFallback($url, $query, $headers, 'POST', 30, $verifySsl);
            $res = json_decode($response, true);

            if (!$res || !isset($res['code']) || $res['code'] != 1) {
                Log::error("Epay Mapi Failed. URL: $url Response: " . substr($response, 0, 1000));
            }

            if (!$res) {
                $response = $this->httpRequestWithSslFallback($url . '?' . $query, null, ['User-Agent: Entropy/1.0'], 'GET', 30, $verifySsl);
                $res = json_decode($response, true);
            }

            if (isset($res['code']) && $res['code'] == 1) {
                if (!empty($res['qrcode'])) {
                    return ['pay_url' => $res['qrcode']];
                } elseif (!empty($res['url'])) {
                    return ['pay_url' => $res['url']];
                } elseif (!empty($res['payurl'])) {
                    return ['pay_url' => $res['payurl']];
                } elseif (!empty($res['urlscheme'])) {
                    return ['pay_url' => $res['urlscheme']];
                }
            }

            Log::error("Epay Mapi Failed. Response: " . substr($response, 0, 500) . " Query: " . $query);
        }

        $url = $api_url . 'submit.php?' . $query;
        Log::info("PaymentService: payEpay - Final Return URL: " . ($data['return_url'] ?? 'N/A'));
        return ['pay_url' => $url];
    }

    protected function payEpayV2($orderId, $amount, $title, array $config, string $actualProvider): array
    {
        $apiUrl = (string)($config['api_url'] ?? ($config['url'] ?? ($config['pay_epay_url'] ?? '')));
        $pid = (string)($config['pid'] ?? ($config['pay_epay_pid'] ?? ''));
        $privateKey = (string)($config['private_key'] ?? '');

        if ($apiUrl === '' || $pid === '' || $privateKey === '') {
            throw new Exception("易支付V2配置不完整(api_url={$apiUrl},pid={$pid})");
        }

        $apiUrl = $this->normalizeGatewayBaseUrl($apiUrl, false);
        $callbackBaseUrl = $this->resolveCallbackBaseUrl($config);
        $returnUrl = $config['return_url'] ?? (($config['frontend_url'] ?? $callbackBaseUrl) . "/payment/callback");
        $returnUrl = $this->appendReturnUrlWithOrderNo($returnUrl, $orderId);
        $clientIp = request()->ip() ?: '127.0.0.1';

        $data = [
            'pid' => $pid,
            'method' => (string)($config['method'] ?? 'jump'),
            'device' => (string)($config['device'] ?? 'pc'),
            'type' => (string)($config['epay_type'] ?? $actualProvider),
            'out_trade_no' => $orderId,
            'notify_url' => $config['notify_url'] ?? ($callbackBaseUrl . "/api/v3/payment/notify/epay"),
            'return_url' => $returnUrl,
            'name' => $title,
            'money' => $amount,
            'clientip' => $clientIp,
            'timestamp' => (string)time(),
            'sign_type' => 'RSA',
        ];

        foreach (['param', 'channel_id', 'merchant_id', 'fee_mode'] as $optionalKey) {
            if (($config[$optionalKey] ?? '') !== '') {
                $data[$optionalKey] = $config[$optionalKey];
            }
        }

        $data['sign'] = $this->signEpayWithRsa($this->buildEpaySignString($data), $privateKey);

        $verifySsl = $this->shouldVerifyPaymentSsl($config, $apiUrl);
        $response = $this->httpRequestWithSslFallback($apiUrl . '/api/pay/create', http_build_query($data), [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'User-Agent: Entropy/1.0',
        ], 'POST', 30, $verifySsl);
        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new Exception("易支付V2下单响应异常: " . substr((string)$response, 0, 500));
        }

        if ((int)($result['code'] ?? -1) !== 0) {
            throw new Exception("易支付V2下单失败: " . ($result['msg'] ?? '未知错误'));
        }

        $payInfo = $result['pay_info'] ?? '';
        if (is_array($payInfo)) {
            $payInfo = json_encode($payInfo, JSON_UNESCAPED_UNICODE);
        }
        $payInfo = (string)$payInfo;
        if ($payInfo === '') {
            throw new Exception("易支付V2下单失败: 未返回支付信息");
        }

        $payType = strtolower((string)($result['pay_type'] ?? ''));
        if ($payType === 'html') {
            return ['html' => $payInfo, 'pay_url' => $payInfo];
        }

        return ['pay_url' => $payInfo];
    }

    protected function getEpaySignType(array $config): string
    {
        $version = strtolower(trim((string)($config['transfer_api_version'] ?? '')));
        if ($version === 'v2') {
            return 'RSA';
        }
        if ($version === 'v1') {
            return 'MD5';
        }

        $signType = strtoupper(trim((string)($config['transfer_sign_type'] ?? ($config['sign_type'] ?? ''))));
        return in_array($signType, ['RSA', 'MD5'], true) ? $signType : 'MD5';
    }

    protected function signEpay(array $params, array $config, string $signType): string
    {
        $signStr = $this->buildEpaySignString($params);
        if (strtoupper($signType) === 'RSA') {
            $privateKey = (string)($config['private_key'] ?? '');
            if ($privateKey === '') {
                throw new Exception("易支付缺少 RSA 私钥");
            }
            return $this->signEpayWithRsa($signStr, $privateKey);
        }

        $key = (string)($config['key'] ?? ($config['pay_epay_key'] ?? ''));
        if ($key === '') {
            throw new Exception("易支付缺少商户密钥");
        }
        return md5($signStr . $key);
    }

    protected function buildEpaySignString(array $params): string
    {
        $params = array_filter($params, function ($value, $key) {
            return !in_array($key, ['sign', 'sign_type'], true) && $value !== '' && $value !== null && !is_array($value);
        }, ARRAY_FILTER_USE_BOTH);
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }
        return implode('&', $pairs);
    }

    protected function signEpayWithRsa(string $content, string $privateKey): string
    {
        $privateKey = $this->formatPemKey($privateKey, 'PRIVATE KEY');
        $signature = '';
        if (!openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception("易支付 RSA 签名失败");
        }
        return base64_encode($signature);
    }

    protected function verifyEpayWithRsa(string $content, string $sign, string $publicKey): bool
    {
        $publicKey = $this->formatPemKey($publicKey, 'PUBLIC KEY');
        return openssl_verify($content, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    protected function payPaypro($orderId, $amount, $title, $config, $actualProvider)
    {
        $apiUrl = (string)($config['api_url'] ?? '');
        $pid = (string)($config['pid'] ?? '');
        $signType = strtoupper((string)($config['sign_type'] ?? 'MD5'));

        if ($apiUrl === '' || $pid === '') {
            throw new Exception("超级支付配置不完整");
        }

        $apiUrl = $this->normalizeGatewayBaseUrl($apiUrl, false);

        $clientIp = request()->ip();
        if (empty($clientIp)) {
            $clientIp = '127.0.0.1';
        }

        $callbackBaseUrl = $this->resolveCallbackBaseUrl($config);
        $returnUrl = $config['return_url'] ?? (($config['frontend_url'] ?? $callbackBaseUrl) . "/payment/callback");
        $returnUrl = $this->appendReturnUrlWithOrderNo($returnUrl, $orderId);

        $data = [
            'pid' => $pid,
            'out_trade_no' => $orderId,
            'total_amount' => $amount,
            'subject' => $title,
            'paytype_code' => $actualProvider,
            'notify_url' => $config['notify_url'] ?? ($callbackBaseUrl . "/api/v3/payment/notify/paypro"),
            'return_url' => $returnUrl,
            'attach' => (string)($config['attach'] ?? ''),
            'client_ip' => $clientIp,
            'timestamp' => (string)time(),
            'sign_type' => $signType,
        ];

        if (!empty($config['channel_id'])) {
            $data['channel_id'] = (string)$config['channel_id'];
        }

        $data['sign'] = $this->signPaypro($data, $config, $signType);
        $verifySsl = $this->shouldVerifyPaymentSsl($config, $apiUrl);
        $response = $this->httpRequestWithSslFallback($apiUrl . '/openapi/pay/create', http_build_query($data), [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'User-Agent: Entropy/1.0'
        ], 'POST', 30, $verifySsl);

        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new Exception("超级支付下单响应异常: " . $response);
        }

        if ((int)($result['code'] ?? 0) !== 1) {
            throw new Exception("超级支付下单失败: " . ($result['msg'] ?? '未知错误'));
        }

        $payData = $result['data'] ?? [];
        $payUrl = $payData['pay_url'] ?? ($payData['url'] ?? ($payData['cashier_url'] ?? ''));
        if ($payUrl === '') {
            throw new Exception("超级支付下单失败: 未返回支付链接");
        }

        return ['pay_url' => $payUrl];
    }

    protected function refundEpay($tradeNo, $amount, $config)
    {
        $api_url = (string)($config['api_url'] ?? ($config['url'] ?? ($config['pay_epay_url'] ?? '')));
        $pid = $config['pid'] ?? ($config['pay_epay_pid'] ?? '');
        $signType = $this->getEpaySignType($config);

        if (empty($api_url) || empty($pid)) {
            throw new Exception("易支付配置不完整");
        }

        $api_url = $this->normalizeGatewayBaseUrl($api_url, true);

        $queryParams = [
            'act' => 'order',
            'pid' => $pid,
            'out_trade_no' => $tradeNo
        ];
        if ($signType === 'MD5') {
            $queryParams['key'] = $config['key'] ?? ($config['pay_epay_key'] ?? '');
        } else {
            $queryParams['sign_type'] = $signType;
            $queryParams['sign'] = $this->signEpay($queryParams, $config, $signType);
        }

        $queryResponse = $this->httpRequest($api_url . 'api.php?' . http_build_query($queryParams));
        $queryResult = json_decode($queryResponse, true);
        $epayTradeNo = null;

        if (isset($queryResult['code']) && $queryResult['code'] == 1) {
            $epayTradeNo = $queryResult['trade_no'] ?? null;
        }

        if (!$epayTradeNo) {
            $queryParams['act'] = 'query';
            if ($signType !== 'MD5') {
                unset($queryParams['sign']);
                $queryParams['sign'] = $this->signEpay($queryParams, $config, $signType);
            }
            $queryResponse = $this->httpRequest($api_url . 'api.php?' . http_build_query($queryParams));
            $queryResult = json_decode($queryResponse, true);
            if (isset($queryResult['code']) && $queryResult['code'] == 1) {
                $epayTradeNo = $queryResult['trade_no'] ?? null;
            }
        }

        if (!$epayTradeNo) {
            throw new Exception("无法查询到易支付订单号，无法退款: " . ($queryResult['msg'] ?? '订单不存在'));
        }

        $params = [
            'act' => 'refund',
            'pid' => $pid,
            'trade_no' => $epayTradeNo,
            'money' => $amount
        ];
        if ($signType === 'MD5') {
            $params['key'] = $config['key'] ?? ($config['pay_epay_key'] ?? '');
        } else {
            $params['sign_type'] = $signType;
            $params['sign'] = $this->signEpay($params, $config, $signType);
        }

        $response = $this->httpRequest($api_url . 'api.php?act=refund', http_build_query($params), [], 'POST');
        $result = json_decode($response, true);

        if (isset($result['code']) && $result['code'] == 1) {
            return true;
        }

        $msg = $result['msg'] ?? '未知错误';
        if (strpos($msg, '已全额退款') !== false || strpos($msg, '已退款') !== false || strpos($msg, '退款成功') !== false) {
            return true;
        }

        throw new Exception("退款失败: " . $msg);
    }

    protected function refundPaypro($tradeNo, $amount, $config)
    {
        $apiUrl = (string)($config['api_url'] ?? '');
        $pid = (string)($config['pid'] ?? '');
        if ($apiUrl === '' || $pid === '') {
            throw new Exception("超级支付配置不完整");
        }

        $apiUrl = $this->normalizeGatewayBaseUrl($apiUrl, false);
        $signType = strtoupper((string)($config['sign_type'] ?? 'MD5'));
        $data = [
            'pid' => $pid,
            'out_trade_no' => $tradeNo,
            'refund_amount' => number_format((float)$amount, 2, '.', ''),
            'refund_reason' => '系统退款',
            'timestamp' => (string)time(),
            'sign_type' => $signType,
        ];
        $data['sign'] = $this->signPaypro($data, $config, $signType);

        $response = $this->httpRequest($apiUrl . '/openapi/pay/refund', http_build_query($data), [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'User-Agent: Entropy/1.0'
        ], 'POST');
        $result = json_decode($response, true);

        if ((int)($result['code'] ?? 0) === 1) {
            return true;
        }

        throw new Exception("超级支付退款失败: " . ($result['msg'] ?? '未知错误'));
    }

    protected function verifyEpay($params, $config)
    {
        $pid = trim((string)($params['pid'] ?? ''));
        $configPid = trim((string)($config['pid'] ?? ($config['pay_epay_pid'] ?? '')));
        if ($pid !== '' && $configPid !== '' && $pid !== $configPid) {
            return false;
        }

        $sign = $params['sign'] ?? '';
        if (empty($sign)) return false;
        $signType = strtoupper((string)($params['sign_type'] ?? $this->getEpaySignType($config)));
        $signStr = $this->buildEpaySignString($params);

        if ($signType === 'RSA') {
            $publicKey = (string)($config['public_key'] ?? '');
            if ($publicKey === '') {
                return false;
            }
            return $this->verifyEpayWithRsa($signStr, $sign, $publicKey);
        }

        $key = $config['key'] ?? ($config['pay_epay_key'] ?? '');
        if (empty($key)) return false;
        return hash_equals(md5($signStr . $key), (string)$sign);
    }

    protected function verifyPaypro($params, $config): bool
    {
        $sign = (string)($params['sign'] ?? '');
        if ($sign === '') {
            return false;
        }

        $signType = strtoupper((string)($params['sign_type'] ?? ($config['sign_type'] ?? 'MD5')));
        $params2 = $params;
        unset($params2['sign'], $params2['type']);
        $signStr = $this->buildPayproSignString($params2);

        if ($signType === 'RSA') {
            $publicKey = (string)($config['public_key'] ?? '');
            if ($publicKey === '') {
                return false;
            }
            return $this->verifyPayproWithRsa($signStr, $sign, $publicKey);
        }

        $key = (string)($config['key'] ?? '');
        if ($key === '') {
            return false;
        }

        $localSign = strtoupper(md5($signStr . '&key=' . $key));
        return hash_equals($localSign, strtoupper($sign));
    }

    protected function queryEpayOrder($orderId, $config)
    {
        if ($this->getEpaySignType($config) === 'RSA') {
            return $this->queryEpayV2Order($orderId, $config);
        }

        $api_url = (string)($config['api_url'] ?? ($config['url'] ?? ($config['pay_epay_url'] ?? '')));
        $pid = $config['pid'] ?? ($config['pay_epay_pid'] ?? '');
        $signType = $this->getEpaySignType($config);

        if (empty($api_url) || empty($pid)) {
            return false;
        }

        $api_url = $this->normalizeGatewayBaseUrl($api_url, true);

        $params = [
            'act' => 'order',
            'pid' => $pid,
            'out_trade_no' => $orderId
        ];
        if ($signType === 'MD5') {
            $params['key'] = $config['key'] ?? ($config['pay_epay_key'] ?? '');
        } else {
            $params['sign_type'] = $signType;
            $params['sign'] = $this->signEpay($params, $config, $signType);
        }

        $url = $api_url . 'api.php?' . http_build_query($params);

        try {
            $response = $this->httpRequest($url);
            $res = json_decode($response, true);

            if (isset($res['code']) && $res['code'] == 1) {
                if (isset($res['status']) && $res['status'] == 1) {
                    return true;
                }
            } else {
                $params['act'] = 'query';
                if ($signType !== 'MD5') {
                    unset($params['sign']);
                    $params['sign'] = $this->signEpay($params, $config, $signType);
                }
                $url = $api_url . 'api.php?' . http_build_query($params);
                $response = $this->httpRequest($url);
                $res = json_decode($response, true);

                if (isset($res['code']) && $res['code'] == 1) {
                    if (isset($res['status']) && $res['status'] == 1) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Epay Query Failed: " . $e->getMessage());
        }

        return false;
    }

    protected function queryEpayV2Order($orderId, $config): bool
    {
        $apiUrl = (string)($config['api_url'] ?? ($config['url'] ?? ($config['pay_epay_url'] ?? '')));
        $pid = (string)($config['pid'] ?? ($config['pay_epay_pid'] ?? ''));
        $privateKey = (string)($config['private_key'] ?? '');
        if ($apiUrl === '' || $pid === '' || $privateKey === '') {
            return false;
        }

        $apiUrl = $this->normalizeGatewayBaseUrl($apiUrl, false);
        $data = [
            'pid' => $pid,
            'out_trade_no' => $orderId,
            'timestamp' => (string)time(),
            'sign_type' => 'RSA',
        ];
        $data['sign'] = $this->signEpayWithRsa($this->buildEpaySignString($data), $privateKey);

        try {
            $verifySsl = $this->shouldVerifyPaymentSsl($config, $apiUrl);
            $response = $this->httpRequestWithSslFallback($apiUrl . '/api/pay/query', http_build_query($data), [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'User-Agent: Entropy/1.0',
            ], 'POST', 30, $verifySsl);
            $result = json_decode($response, true);
            if (!is_array($result) || (int)($result['code'] ?? -1) !== 0) {
                return false;
            }
            $status = (string)($result['status'] ?? ($result['data']['status'] ?? ''));
            $tradeStatus = strtoupper((string)($result['trade_status'] ?? ($result['data']['trade_status'] ?? '')));
            return $status === '1' || in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true);
        } catch (\Throwable $e) {
            Log::error("Epay V2 Query Failed: " . $e->getMessage());
            return false;
        }
    }

    protected function queryPayproOrder($orderId, $config): bool
    {
        $apiUrl = (string)($config['api_url'] ?? '');
        $pid = (string)($config['pid'] ?? '');
        if ($apiUrl === '' || $pid === '') {
            return false;
        }

        $apiUrl = $this->normalizeGatewayBaseUrl($apiUrl, false);
        $signType = strtoupper((string)($config['sign_type'] ?? 'MD5'));
        $data = [
            'pid' => $pid,
            'out_trade_no' => $orderId,
            'timestamp' => (string)time(),
            'sign_type' => $signType,
        ];
        $data['sign'] = $this->signPaypro($data, $config, $signType);

        try {
            $response = $this->httpRequest($apiUrl . '/openapi/pay/query', http_build_query($data), [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'User-Agent: Entropy/1.0'
            ], 'POST');
            $result = json_decode($response, true);
            if ((int)($result['code'] ?? 0) !== 1) {
                return false;
            }
            $tradeStatus = strtoupper((string)($result['data']['trade_status'] ?? ''));
            return in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true);
        } catch (\Throwable $e) {
            Log::error("Paypro Query Failed: " . $e->getMessage());
            return false;
        }
    }

    protected function signPaypro(array $params, array $config, string $signType): string
    {
        $signType = strtoupper($signType);
        $signStr = $this->buildPayproSignString($params);

        if ($signType === 'RSA') {
            $privateKey = (string)($config['private_key'] ?? '');
            if ($privateKey === '') {
                throw new Exception("超级支付缺少 RSA 私钥");
            }
            return $this->signPayproWithRsa($signStr, $privateKey);
        }

        $key = (string)($config['key'] ?? '');
        if ($key === '') {
            throw new Exception("超级支付缺少 MD5 密钥");
        }

        return strtoupper(md5($signStr . '&key=' . $key));
    }

    protected function buildPayproSignString(array $params): string
    {
        $params = array_filter($params, function ($value, $key) {
            return !in_array($key, ['sign', 'sign_type'], true) && $value !== '' && $value !== null && !is_array($value);
        }, ARRAY_FILTER_USE_BOTH);
        ksort($params);
        return urldecode(http_build_query($params));
    }

    protected function signPayproWithRsa(string $content, string $privateKey): string
    {
        $privateKey = $this->formatPemKey($privateKey, 'PRIVATE KEY');
        $signature = '';
        if (!openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception("超级支付 RSA 签名失败");
        }
        return base64_encode($signature);
    }

    protected function verifyPayproWithRsa(string $content, string $sign, string $publicKey): bool
    {
        $publicKey = $this->formatPemKey($publicKey, 'PUBLIC KEY');
        return openssl_verify($content, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    protected function formatPemKey(string $key, string $type): string
    {
        if (strpos($key, '-----BEGIN') !== false) {
            return strpos($key, "\n") === false
                ? "-----BEGIN {$type}-----\n" . wordwrap(trim(str_replace([
                    "-----BEGIN {$type}-----",
                    "-----END {$type}-----"
                ], '', $key)), 64, "\n", true) . "\n-----END {$type}-----"
                : $key;
        }

        return "-----BEGIN {$type}-----\n" . wordwrap(trim($key), 64, "\n", true) . "\n-----END {$type}-----";
    }

    protected function resolveCallbackBaseUrl(array $config): string
    {
        $candidates = [
            $config['notify_url'] ?? '',
            $config['return_url'] ?? '',
            $config['frontend_url'] ?? '',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate === '') {
                continue;
            }

            $parts = parse_url($candidate);
            if ($parts === false) {
                continue;
            }

            $scheme = $parts['scheme'] ?? '';
            $host = $parts['host'] ?? '';
            if ($scheme === '' || $host === '') {
                continue;
            }

            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            return $scheme . '://' . $host . $port;
        }

        return rtrim(request()->domain(), '/');
    }

    protected function appendReturnUrlWithOrderNo(string $url, string $orderId): string
    {
        if (empty($url) || empty($orderId)) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        if (!isset($query['out_trade_no']) && !isset($query['trade_no'])) {
            $query['out_trade_no'] = $orderId;
        }

        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user = $parts['user'] ?? '';
        $pass = $parts['pass'] ?? '';
        $auth = $user !== '' ? $user . ($pass !== '' ? ':' . $pass : '') . '@' : '';
        $path = $parts['path'] ?? '';
        $queryString = http_build_query($query);
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        $prefix = $scheme !== '' ? $scheme . '://' : '';
        $base = $prefix . $auth . $host . $port . $path;

        return $queryString ? $base . '?' . $queryString . $fragment : $base . $fragment;
    }

    protected function httpRequest($url, $data = null, $headers = [], $method = 'GET', $timeout = 30, ?bool $verifySslOverride = null)
    {
        $url = $this->assertSafeHttpUrl($url);
        $verifySsl = $verifySslOverride ?? $this->shouldVerifySsl($url);
        if (function_exists('curl_init')) {
            $ch = \curl_init();
            \curl_setopt($ch, CURLOPT_URL, $url);
            \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            \curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            \curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
            \curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);
            if (defined('CURLOPT_PROTOCOLS')) {
                \curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
            }
            if (defined('CURLOPT_REDIR_PROTOCOLS')) {
                \curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
            }
            if ($method === 'POST') {
                \curl_setopt($ch, CURLOPT_POST, true);
                if ($data !== null) {
                    \curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
            }
            if (!empty($headers)) {
                \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            $result = \curl_exec($ch);
            $error = \curl_error($ch);
            $errno = \curl_errno($ch);
            \curl_close($ch);
            if ($errno) {
                throw new Exception("CURL Error ($errno): " . $error);
            }
            return $result;
        }

        $opts = [
            'http' => [
                'method' => $method,
                'timeout' => $timeout,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => $verifySsl,
                'verify_peer_name' => $verifySsl
            ]
        ];
        if (!empty($headers)) {
            $opts['http']['header'] = implode("\r\n", $headers);
        }
        if ($data !== null) {
            $opts['http']['content'] = $data;
        }
        $context = stream_context_create($opts);
        $result = @file_get_contents($url, false, $context);
        if ($result === false) {
            $error = error_get_last();
            throw new Exception("Stream Error: " . ($error['message'] ?? 'Unknown'));
        }
        return $result;
    }

    protected function httpRequestWithSslFallback($url, $data = null, $headers = [], $method = 'GET', $timeout = 30, bool $verifySsl = true)
    {
        try {
            return $this->httpRequest($url, $data, $headers, $method, $timeout, $verifySsl);
        } catch (\Throwable $e) {
            if ($verifySsl && $this->isSslCertificateError($e)) {
                return $this->httpRequest($url, $data, $headers, $method, $timeout, false);
            }

            throw $e;
        }
    }

    protected function isSslCertificateError(\Throwable $e): bool
    {
        $message = $e->getMessage();
        return stripos($message, 'CURL Error (60)') !== false
            || stripos($message, 'SSL certificate problem') !== false
            || stripos($message, 'self signed certificate') !== false;
    }

    protected function normalizeGatewayBaseUrl(string $url, bool $trailingSlash): string
    {
        $url = $this->assertSafeHttpUrl($url);
        $url = rtrim($url, '/');
        return $trailingSlash ? $url . '/' : $url;
    }

    protected function assertSafeHttpUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = (string)($parts['host'] ?? '');
        if ($url === '' || !in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new Exception('支付网关地址必须是完整的 http/https URL');
        }

        if (!$this->allowPrivateGatewayHost() && $this->isPrivateGatewayHost($host)) {
            throw new Exception('生产环境禁止使用本机或内网支付网关地址');
        }

        return $url;
    }

    protected function allowPrivateGatewayHost(): bool
    {
        return filter_var((string)env('PAYMENT_ALLOW_PRIVATE_GATEWAY', env('APP_DEBUG', false)), FILTER_VALIDATE_BOOLEAN);
    }

    protected function shouldVerifySsl(string $url): bool
    {
        $scheme = strtolower((string)(parse_url($url, PHP_URL_SCHEME) ?: ''));
        if ($scheme !== 'https') {
            return false;
        }

        return !filter_var((string)env('PAYMENT_SKIP_SSL_VERIFY', false), FILTER_VALIDATE_BOOLEAN);
    }

    protected function shouldVerifyPaymentSsl(array $config, string $url): bool
    {
        if (array_key_exists('skip_ssl_verify', $config) && $config['skip_ssl_verify'] !== '') {
            return !filter_var((string)$config['skip_ssl_verify'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->shouldVerifySsl($url);
    }

    protected function isPrivateGatewayHost(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
        }

        $records = @gethostbynamel($host) ?: [];
        foreach ($records as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return true;
            }
        }

        return false;
    }
}
