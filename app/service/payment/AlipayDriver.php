<?php
namespace app\service\payment;

use think\Exception;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\RSA\PublicKey;

class AlipayDriver implements PaymentDriverInterface
{
    public function getKey(): string
    {
        return 'alipay';
    }

    public function getLabel(): string
    {
        return '支付宝';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getTypes(): array
    {
        $commonFields = [
            [
                'key' => 'app_id',
                'label' => 'AppID',
                'type' => 'text',
                'placeholder' => '请输入 AppID',
                'required' => true
            ],
            [
                'key' => 'private_key',
                'label' => '应用私钥',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => '请输入应用私钥',
                'required' => true
            ],
            [
                'key' => 'public_key',
                'label' => '支付宝公钥',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => '请输入支付宝公钥',
                'required' => true
            ]
        ];

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
                'placeholder' => '请输入易支付接口地址',
                'required' => true
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
            ]
        ];

        $payproFields = [
            [
                'key' => 'api_url',
                'label' => '接口地址',
                'type' => 'text',
                'placeholder' => '请输入超级支付接口地址',
                'required' => true
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
            ]
        ];

        return [
            [
                'key' => 'pc',
                'label' => '电脑网站支付',
                'modes' => [0],
                'default_mode' => 0,
                'fields' => $commonFields,
                'default_config' => []
            ],
            [
                'key' => 'h5',
                'label' => 'H5移动支付',
                'modes' => [0],
                'default_mode' => 0,
                'fields' => $commonFields,
                'default_config' => []
            ],
            [
                'key' => 'face',
                'label' => '当面付',
                'modes' => [1],
                'default_mode' => 1,
                'fields' => $commonFields,
                'default_config' => []
            ],
            [
                'key' => 'epay',
                'label' => '易支付',
                'modes' => [0, 1],
                'default_mode' => 0,
                'fields' => $epayFields,
                'default_config' => ['transfer_api_version' => 'v1', 'transfer_sign_type' => 'MD5']
            ],
            [
                'key' => 'paypro',
                'label' => '超级支付',
                'modes' => [0, 1],
                'default_mode' => 0,
                'fields' => $payproFields,
                'default_config' => ['sign_type' => 'MD5']
            ]
        ];
    }

    public function getIcon(): string
    {
        return 'ri:alipay-fill';
    }

    public function getIconUrl(): string
    {
        return '';
    }

    public function pay($orderId, $amount, $title, array $config, string $type): array
    {
        if ($type === 'epay') {
            return $this->payEpay($orderId, $amount, $title, $config, 'alipay');
        }

        if ($type === 'paypro') {
            return $this->payPaypro($orderId, $amount, $title, $config, 'alipay');
        }

        return $this->payAlipay($orderId, $amount, $title, $config, $type);
    }

    public function verifyNotify(array $params, array $config, string $type): bool
    {
        if ($type === 'epay') {
            return $this->verifyEpay($params, $config);
        }

        if ($type === 'paypro') {
            return $this->verifyPaypro($params, $config);
        }

        return $this->verifyAlipay($params, $config);
    }

    public function refund($tradeNo, $amount, array $config, string $type): bool
    {
        if ($type === 'epay') {
            return $this->refundEpay($tradeNo, $amount, $config);
        }

        if ($type === 'paypro') {
            return $this->refundPaypro($tradeNo, $amount, $config);
        }

        return $this->refundAlipay($tradeNo, $amount, $config);
    }

    public function queryOrder($orderId, array $config, string $provider): bool
    {
        if ($provider === 'epay') {
            return $this->queryEpayOrder($orderId, $config);
        }

        if ($provider === 'paypro') {
            return $this->queryPayproOrder($orderId, $config);
        }

        if ($provider === 'alipay' || $provider === '') {
            return $this->queryAlipayOrder($orderId, $config);
        }

        return false;
    }

    public function handleNotify(array $config)
    {
        return false;
    }

    protected function payEpay($orderId, $amount, $title, $config, $actualProvider = 'alipay')
    {
        if ($this->isEpayV2($config)) {
            return $this->payEpayV2($orderId, $amount, $title, $config, $actualProvider);
        }

        $api_url = $config['api_url'] ?? ($config['url'] ?? ($config['pay_epay_url'] ?? ''));
        $pid = $config['pid'] ?? ($config['pay_epay_pid'] ?? '');
        $key = $config['key'] ?? ($config['pay_epay_key'] ?? '');

        if (empty($api_url) || empty($pid) || empty($key)) {
            throw new Exception("易支付配置不完整(api_url={$api_url},pid={$pid})");
        }
        if (!preg_match('#^https?://#i', $api_url)) {
            throw new Exception("易支付接口地址必须以 http:// 或 https:// 开头，当前值: {$api_url}");
        }

        $api_url = $this->normalizeGatewayBaseUrl($api_url, true);

        $clientIp = request()->ip();
        if (empty($clientIp)) {
            $clientIp = '127.0.0.1';
        }

        $callbackBaseUrl = $this->resolveCallbackBaseUrl($config);
        $returnUrl = $config['return_url'] ?? (($config['frontend_url'] ?? $callbackBaseUrl) . "/payment/callback");
        $returnUrl = $this->appendReturnUrlWithOrderNo($returnUrl, $orderId);

        $data = [
            "pid" => $pid,
            "type" => $actualProvider,
            "out_trade_no" => $orderId,
            "notify_url" => $config['notify_url'] ?? ($callbackBaseUrl . "/api/v3/payment/notify/epay"),
            "return_url" => $returnUrl,
            "name" => $title,
            "money" => $amount,
            "sitename" => $config['site_name'] ?? 'Entropy',
            "clientip" => $clientIp,
            "device" => 'pc'
        ];

        ksort($data);
        reset($data);
        $signStr = '';
        foreach ($data as $k => $v) {
            if ($v !== '' && $k !== 'sign' && $k !== 'sign_type') {
                $signStr .= $k . '=' . $v . '&';
            }
        }
        $signStr = substr($signStr, 0, -1);
        $data['sign'] = md5($signStr . $key);
        $data['sign_type'] = 'MD5';

        $query = http_build_query($data);

        if (($config['mode'] ?? 0) == 1) {
            $url = $api_url . 'mapi.php';
            $verifySsl = $this->shouldVerifyPaymentSsl($config, $api_url);
            $headers = [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer: ' . $api_url
            ];
            $response = $this->httpRequestWithSslFallback($url, $query, $headers, 'POST', 30, 1, $verifySsl);
            $res = json_decode($response, true);


            if (!$res) {
                $response = $this->httpRequestWithSslFallback($url . '?' . $query, null, ['User-Agent: Entropy/1.0'], 'GET', 30, 1, $verifySsl);
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


        }

        $url = $api_url . 'submit.php?' . $query;

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
        ], 'POST', 30, 1, $verifySsl);
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

    protected function payAlipay($orderId, $amount, $title, $config, $type = 'pc')
    {
        $appId = $config['app_id'] ?? ($config['appid'] ?? ($config['pay_alipay_appid'] ?? ''));
        $privateKey = $config['private_key'] ?? ($config['pay_alipay_private_key'] ?? '');

        if (empty($appId) || empty($privateKey)) {
            throw new Exception("支付宝配置不完整");
        }

        $method = 'alipay.trade.page.pay';
        $productCode = 'FAST_INSTANT_TRADE_PAY';

        if ($type === 'h5' || $type === 'wap') {
            $method = 'alipay.trade.wap.pay';
            $productCode = 'QUICK_WAP_WAY';
        } else if ($type === 'face') {
            $method = 'alipay.trade.precreate';
            $productCode = 'FACE_TO_FACE_PAYMENT';
        }

        $callbackBaseUrl = $this->resolveCallbackBaseUrl($config);
        $params = [
            'app_id' => $appId,
            'method' => $method,
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $config['notify_url'] ?? ($callbackBaseUrl . "/api/payment/notify/alipay"),
            'biz_content' => json_encode([
                'out_trade_no' => $orderId,
                'product_code' => $productCode,
                'total_amount' => $amount,
                'subject' => $title,
            ], JSON_UNESCAPED_UNICODE)
        ];

        if ($method !== 'alipay.trade.precreate') {
            $returnUrl = $config['return_url'] ?? (($config['frontend_url'] ?? $callbackBaseUrl) . "/payment/callback");
            $params['return_url'] = $this->appendReturnUrlWithOrderNo($returnUrl, $orderId);
        }



        $params['sign'] = $this->generateAlipaySign($params, $privateKey);

        if (($config['mode'] ?? 0) == 1 && $method !== 'alipay.trade.precreate') {
            $url = 'https://openapi.alipay.com/gateway.do?' . http_build_query($params);
            return ['pay_url' => $url];
        }

        if ($method === 'alipay.trade.precreate') {
            $url = 'https://openapi.alipay.com/gateway.do?charset=utf-8';
            $query = http_build_query($params);
            $response = $this->httpRequest($url, $query, [], 'POST');
            $res = json_decode($response, true);
            $responseKey = str_replace('.', '_', $method) . '_response';

            if (isset($res[$responseKey]) && $res[$responseKey]['code'] == '10000') {
                return ['pay_url' => $res[$responseKey]['qr_code']];
            } else {
                throw new Exception("支付宝当面付请求失败: " . ($res[$responseKey]['sub_msg'] ?? $response));
            }
        }

        $html = "<form id='alipaysubmit' name='alipaysubmit' action='https://openapi.alipay.com/gateway.do?charset=utf-8' method='POST'>";
        foreach ($params as $key => $val) {
            $html .= "<input type='hidden' name='" . $key . "' value='" . str_replace("'", "&apos;", $val) . "'/>";
        }
        $html .= "<input type='submit' value='ok' style='display:none;'></form>";
        $html .= "<script>document.forms['alipaysubmit'].submit();</script>";

        return ['html' => $html];
    }

    protected function payPaypro($orderId, $amount, $title, $config, $paytypeCode = 'alipay')
    {
        $apiUrl = $this->normalizeGatewayBaseUrl((string)($config['api_url'] ?? ''), false);
        $pid = (string)($config['pid'] ?? '');
        $signType = strtoupper((string)($config['sign_type'] ?? 'MD5'));

        if ($apiUrl === '' || $pid === '') {
            throw new Exception("超级支付配置不完整");
        }

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
            'paytype_code' => $paytypeCode,
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

        $data['sign'] = $this->generatePayproSign($data, $config, $signType);

        $verifySsl = $this->shouldVerifyPaymentSsl($config, $apiUrl);
        $response = $this->httpRequestWithSslFallback($apiUrl . '/openapi/pay/create', http_build_query($data), [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'User-Agent: Entropy/1.0'
        ], 'POST', 30, 1, $verifySsl);

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

    protected function generatePayproSign(array $params, array $config, string $signType): string
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

    protected function verifyPaypro(array $params, array $config): bool
    {
        $sign = (string)($params['sign'] ?? '');
        if ($sign === '') {
            return false;
        }

        $signType = strtoupper((string)($params['sign_type'] ?? ($config['sign_type'] ?? 'MD5')));
        $params2 = $params;
        unset($params2['sign']);

        $signStr = $this->buildPayproSignString($params2);
        if ($signType === 'RSA') {
            $publicKey = (string)($config['public_key'] ?? '');
            if ($publicKey === '') {
                return false;
            }
            $verified = $this->verifyPayproWithRsa($signStr, $sign, $publicKey);
            return $verified;
        }

        $key = (string)($config['key'] ?? '');
        if ($key === '') {
            return false;
        }

        $localSign = strtoupper(md5($signStr . '&key=' . $key));
        return hash_equals($localSign, strtoupper($sign));
    }

    protected function queryPayproOrder($orderId, $config)
    {
        $apiUrl = $this->normalizeGatewayBaseUrl((string)($config['api_url'] ?? ''), false);
        $pid = (string)($config['pid'] ?? '');
        if ($apiUrl === '' || $pid === '') {
            return false;
        }

        $signType = strtoupper((string)($config['sign_type'] ?? 'MD5'));
        $data = [
            'pid' => $pid,
            'out_trade_no' => $orderId,
            'timestamp' => (string)time(),
            'sign_type' => $signType,
        ];
        $data['sign'] = $this->generatePayproSign($data, $config, $signType);

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
        } catch (\Throwable) {
            return false;
        }
    }

    protected function refundPaypro($tradeNo, $amount, $config)
    {
        $apiUrl = $this->normalizeGatewayBaseUrl((string)($config['api_url'] ?? ''), false);
        $pid = (string)($config['pid'] ?? '');
        if ($apiUrl === '' || $pid === '') {
            throw new Exception("超级支付配置不完整");
        }

        $signType = strtoupper((string)($config['sign_type'] ?? 'MD5'));
        $data = [
            'pid' => $pid,
            'out_trade_no' => $tradeNo,
            'refund_amount' => number_format((float)$amount, 2, '.', ''),
            'refund_reason' => '系统退款',
            'timestamp' => (string)time(),
            'sign_type' => $signType,
        ];
        $data['sign'] = $this->generatePayproSign($data, $config, $signType);

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

    protected function buildPayproSignString(array $params): string
    {
        $params = array_filter($params, function ($value, $key) {
            return !in_array($key, ['sign', 'sign_type'], true) && $value !== '' && $value !== null && !is_array($value);
        }, ARRAY_FILTER_USE_BOTH);
        ksort($params);
        return urldecode(http_build_query($params));
    }

    protected function isEpayV2(array $config): bool
    {
        return strtolower(trim((string)($config['transfer_api_version'] ?? ''))) === 'v2'
            || strtoupper(trim((string)($config['transfer_sign_type'] ?? ''))) === 'RSA';
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

    protected function signPayproWithRsa(string $content, string $privateKey): string
    {
        $privateKey = $this->formatPemKey($privateKey, 'PRIVATE KEY');
        $signature = '';
        $result = openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$result) {
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

    protected function generateAlipaySign($params, $privateKey)
    {
        ksort($params);
        $stringToBeSigned = "";
        foreach ($params as $k => $v) {
            if (!empty($v) && "@" != substr($v, 0, 1)) {
                $stringToBeSigned .= "$k=$v&";
            }
        }
        $stringToBeSigned = substr($stringToBeSigned, 0, -1);

        if (strpos($privateKey, '-----BEGIN') === false) {
            $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($privateKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";
        }

        if (function_exists('openssl_sign') && function_exists('openssl_pkey_get_private')) {
            $res = \openssl_pkey_get_private($privateKey);
            if ($res) {
                $algo = defined('OPENSSL_ALGO_SHA256') ? OPENSSL_ALGO_SHA256 : 'sha256WithRSAEncryption';
                $result = \openssl_sign($stringToBeSigned, $sign, $res, $algo);

                if (PHP_VERSION_ID < 80000 && function_exists('openssl_free_key')) {
                    \openssl_free_key($res);
                }

                if ($result) {
                    return base64_encode($sign);
                }
            }
        }

        try {
            $key = PublicKeyLoader::load($privateKey);
            if (!$key instanceof PrivateKey) {
                throw new Exception("加载私钥失败: 不是有效的RSA私钥");
            }
            $key = $key->withHash('sha256');
            $key = $key->withPadding(RSA::SIGNATURE_PKCS1);
            $sign = $key->sign($stringToBeSigned);
            return base64_encode($sign);
        } catch (\Exception $e) {
            throw new Exception("签名生成失败(phpseclib): " . $e->getMessage());
        }
    }

    protected function refundAlipay($tradeNo, $amount, $config)
    {
        $appId = $config['app_id'] ?? ($config['appid'] ?? ($config['pay_alipay_appid'] ?? ''));
        $privateKey = $config['private_key'] ?? ($config['pay_alipay_private_key'] ?? '');

        if (empty($appId) || empty($privateKey)) {
            throw new Exception("支付宝配置不完整");
        }

        $outRequestNo = 'refund_' . $tradeNo . '_' . time() . '_' . rand(1000, 9999);

        $params = [
            'app_id' => $appId,
            'method' => 'alipay.trade.refund',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode([
                'out_trade_no' => $tradeNo,
                'refund_amount' => $amount,
                'out_request_no' => $outRequestNo,
            ], JSON_UNESCAPED_UNICODE)
        ];

        $params['sign'] = $this->generateAlipaySign($params, $privateKey);

        $url = 'https://openapi.alipay.com/gateway.do?charset=utf-8';
        $response = $this->httpRequest($url, http_build_query($params), [], 'POST');
        $result = json_decode($response, true);
        $responseKey = 'alipay_trade_refund_response';

        if (isset($result[$responseKey]) && $result[$responseKey]['code'] == '10000') {
            return true;
        }

        $msg = $result[$responseKey]['sub_msg'] ?? ($result[$responseKey]['msg'] ?? '未知错误');
        throw new Exception("支付宝退款失败: " . $msg);
    }

    protected function refundEpay($tradeNo, $amount, $config)
    {
        $api_url = $config['api_url'] ?? ($config['url'] ?? ($config['pay_epay_url'] ?? ''));
        $pid = $config['pid'] ?? ($config['pay_epay_pid'] ?? '');
        $key = $config['key'] ?? ($config['pay_epay_key'] ?? '');

        if (empty($api_url) || empty($pid) || empty($key)) {
            throw new Exception("易支付配置不完整");
        }

        $api_url = $this->normalizeGatewayBaseUrl($api_url, true);

        $queryParams = [
            'act' => 'order',
            'pid' => $pid,
            'key' => $key,
            'out_trade_no' => $tradeNo
        ];

        $queryResponse = $this->httpRequest($api_url . 'api.php?' . http_build_query($queryParams));
        $queryResult = json_decode($queryResponse, true);
        $epayTradeNo = null;

        if (isset($queryResult['code']) && $queryResult['code'] == 1) {
            $epayTradeNo = $queryResult['trade_no'] ?? null;
        }

        if (!$epayTradeNo) {
            $queryParams['act'] = 'query';
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
            'key' => $key,
            'trade_no' => $epayTradeNo,
            'money' => $amount
        ];

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

    protected function verifyEpay($params, $config)
    {
        $pid = trim((string)($params['pid'] ?? ''));
        $configPid = trim((string)($config['pid'] ?? ($config['pay_epay_pid'] ?? '')));
        if ($pid !== '' && $configPid !== '' && $pid !== $configPid) {
            return false;
        }

        $sign = $params['sign'] ?? '';
        if (empty($sign)) return false;
        $signType = strtoupper((string)($params['sign_type'] ?? ($this->isEpayV2($config) ? 'RSA' : 'MD5')));
        $signStr = $this->buildEpaySignString($params);

        if ($signType === 'RSA') {
            $publicKey = (string)($config['public_key'] ?? '');
            if ($publicKey === '') {
                return false;
            }
            return $this->verifyEpayWithRsa($signStr, (string)$sign, $publicKey);
        }

        $key = $config['key'] ?? ($config['pay_epay_key'] ?? '');
        if (empty($key)) return false;
        $mySign = md5($signStr . $key);

        return hash_equals($mySign, (string)$sign);
    }

    protected function verifyAlipay($params, $config)
    {
        $appId = trim((string)($params['app_id'] ?? ''));
        $configAppId = trim((string)($config['app_id'] ?? ($config['appid'] ?? ($config['pay_alipay_appid'] ?? ''))));
        if ($appId !== '' && $configAppId !== '' && $appId !== $configAppId) {
            return false;
        }

        $sellerId = trim((string)($params['seller_id'] ?? ''));
        $configSellerId = trim((string)($config['seller_id'] ?? ''));
        if ($sellerId !== '' && $configSellerId !== '' && $sellerId !== $configSellerId) {
            return false;
        }

        $publicKey = $config['public_key'] ?? ($config['pay_alipay_public_key'] ?? '');
        if (empty($publicKey)) {
            return false;
        }

        $sign = $params['sign'] ?? '';
        $params2 = $params;
        unset($params2['sign']);
        unset($params2['sign_type']);

        ksort($params2);
        $stringToBeSigned = "";
        foreach ($params2 as $k => $v) {
            if (!empty($v) && "@" != substr($v, 0, 1)) {
                $stringToBeSigned .= "$k=$v&";
            }
        }
        $stringToBeSigned = substr($stringToBeSigned, 0, -1);

        if (strpos($publicKey, '-----BEGIN') === false) {
            $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($publicKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        } else {
            if (strpos($publicKey, "\n") === false) {
                $publicKey = str_replace(
                    ['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----'],
                    ['', ''],
                    $publicKey
                );
                $publicKey = trim($publicKey);
                $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
                    wordwrap($publicKey, 64, "\n", true) .
                    "\n-----END PUBLIC KEY-----";
            }
        }

        if (function_exists('openssl_verify') && function_exists('openssl_pkey_get_public')) {
            $res = \openssl_pkey_get_public($publicKey);
            if ($res) {
                $algo = defined('OPENSSL_ALGO_SHA256') ? OPENSSL_ALGO_SHA256 : 'sha256WithRSAEncryption';
                $result = (bool)\openssl_verify($stringToBeSigned, base64_decode($sign), $res, $algo);

                if (PHP_VERSION_ID < 80000 && function_exists('openssl_free_key')) {
                    \openssl_free_key($res);
                }

                return $result;
            }
        }

        try {
            $key = PublicKeyLoader::load($publicKey);
            if (!$key instanceof PublicKey) {
                return false;
            }
            $key = $key->withHash('sha256');
            $key = $key->withPadding(RSA::SIGNATURE_PKCS1);
            return $key->verify($stringToBeSigned, base64_decode($sign));
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function queryEpayOrder($orderId, $config)
    {
        if ($this->isEpayV2($config)) {
            return $this->queryEpayV2Order($orderId, $config);
        }

        $api_url = $config['api_url'] ?? ($config['url'] ?? ($config['pay_epay_url'] ?? ''));
        $pid = $config['pid'] ?? ($config['pay_epay_pid'] ?? '');
        $key = $config['key'] ?? ($config['pay_epay_key'] ?? '');

        if (empty($api_url) || empty($pid) || empty($key)) {
            return false;
        }

        $api_url = $this->normalizeGatewayBaseUrl($api_url, true);

        $params = [
            'act' => 'order',
            'pid' => $pid,
            'key' => $key,
            'out_trade_no' => $orderId
        ];

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
                $url = $api_url . 'api.php?' . http_build_query($params);
                $response = $this->httpRequest($url);
                $res = json_decode($response, true);

                if (isset($res['code']) && $res['code'] == 1) {
                    if (isset($res['status']) && $res['status'] == 1) {
                        return true;
                    }
                }
            }
        } catch (\Exception) {
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
            ], 'POST', 30, 1, $verifySsl);
            $result = json_decode($response, true);
            if (!is_array($result) || (int)($result['code'] ?? -1) !== 0) {
                return false;
            }
            $status = (string)($result['status'] ?? ($result['data']['status'] ?? ''));
            $tradeStatus = strtoupper((string)($result['trade_status'] ?? ($result['data']['trade_status'] ?? '')));
            return $status === '1' || in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'], true);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function queryAlipayOrder($orderId, $config)
    {
        $appId = $config['app_id'] ?? ($config['appid'] ?? ($config['pay_alipay_appid'] ?? ''));
        $privateKey = $config['private_key'] ?? ($config['pay_alipay_private_key'] ?? '');

        if (empty($appId) || empty($privateKey)) {
            return false;
        }

        $params = [
            'app_id' => $appId,
            'method' => 'alipay.trade.query',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode([
                'out_trade_no' => $orderId,
            ], JSON_UNESCAPED_UNICODE)
        ];

        try {
            $params['sign'] = $this->generateAlipaySign($params, $privateKey);
        } catch (\Exception $e) {
            return false;
        }

        $url = 'https://openapi.alipay.com/gateway.do?charset=utf-8';
        $query = http_build_query($params);
        $response = $this->httpRequest($url, $query, [], 'POST');
        $res = json_decode($response, true);
        $responseKey = 'alipay_trade_query_response';

        if (isset($res[$responseKey])) {
            $data = $res[$responseKey];
            if ($data['code'] == '10000') {
                $status = $data['trade_status'];
                if ($status === 'TRADE_SUCCESS' || $status === 'TRADE_FINISHED') {
                    return true;
                }
            }
        }

        return false;
    }

    protected function httpRequest($url, $data = null, $headers = [], $method = 'GET', $timeout = 30, $retries = 1, ?bool $verifySslOverride = null)
    {
        $url = $this->assertSafeHttpUrl($url);
        $verifySsl = $verifySslOverride ?? $this->shouldVerifySsl($url);
        $attempt = 0;
        $lastError = '';

        while ($attempt <= $retries) {
            $attempt++;
            try {
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

                if (function_exists('shell_exec')) {
                    $cmd = 'curl -s -X ' . escapeshellarg($method);
                    $cmd .= ' --connect-timeout ' . intval($timeout) . ' -m ' . intval($timeout);
                    $cmd .= " --proto '=http,https'";
                    if (!$verifySsl) {
                        $cmd .= ' -k';
                    }
                    foreach ($headers as $header) {
                        $cmd .= ' -H ' . escapeshellarg($header);
                    }
                    if ($data !== null && $method === 'POST') {
                        $cmd .= ' -d ' . escapeshellarg($data);
                    }
                    $cmd .= ' ' . escapeshellarg($url);
                    $output = @shell_exec($cmd);
                    if ($output !== null && $output !== false) {
                        return $output;
                    }
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

            } catch (\Exception $e) {
                $lastError = $e->getMessage();

                if ($attempt <= $retries) {
                    sleep(1);
                    continue;
                }
            }
        }

        throw new Exception("HTTP Request Failed after " . ($retries + 1) . " attempts. Last Error: " . $lastError);
    }

    protected function httpRequestWithSslFallback($url, $data = null, $headers = [], $method = 'GET', $timeout = 30, $retries = 1, bool $verifySsl = true)
    {
        try {
            return $this->httpRequest($url, $data, $headers, $method, $timeout, $retries, $verifySsl);
        } catch (\Throwable $e) {
            if ($verifySsl && $this->isSslCertificateError($e)) {
                return $this->httpRequest($url, $data, $headers, $method, $timeout, $retries, false);
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
