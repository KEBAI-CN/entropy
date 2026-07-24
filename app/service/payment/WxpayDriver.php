<?php
namespace app\service\payment;

use think\Exception;
use think\facade\Cache;

class WxpayDriver implements PaymentDriverInterface
{
    public function getKey(): string
    {
        return 'wxpay';
    }

    public function getLabel(): string
    {
        return '微信支付';
    }

    public function getDescription(): string
    {
        return '';
    }

    public function getTypes(): array
    {
        $commonFields = [
            [
                'key' => 'version',
                'label' => '接口版本',
                'type' => 'radio',
                'options' => [
                    ['label' => 'V2', 'value' => 'v2'],
                    ['label' => 'V3', 'value' => 'v3']
                ],
                'required' => false
            ],
            [
                'key' => 'is_sandbox',
                'label' => '沙箱模式',
                'type' => 'switch',
                'active_value' => 1,
                'inactive_value' => 0,
                'required' => false
            ],
            [
                'key' => 'app_id',
                'label' => 'AppID',
                'type' => 'text',
                'placeholder' => '请输入 AppID',
                'required' => true
            ],
            [
                'key' => 'mch_id',
                'label' => '商户号',
                'type' => 'text',
                'placeholder' => '请输入商户号',
                'required' => true
            ],
            [
                'key' => 'key',
                'label' => 'API密钥',
                'type' => 'text',
                'placeholder' => '请输入 API 密钥',
                'required' => true
            ],
            [
                'key' => 'linked_app_id',
                'label' => '绑定AppID',
                'type' => 'text',
                'placeholder' => '请输入绑定的小程序/公众号AppID',
                'required' => false
            ],
            [
                'key' => 'serial_no',
                'label' => '证书序列号',
                'type' => 'text',
                'placeholder' => '请输入证书序列号',
                'required' => false
            ],
            [
                'key' => 'private_key',
                'label' => '商户私钥',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => '请输入商户私钥',
                'required' => false
            ],
            [
                'key' => 'cert_public',
                'label' => '平台证书',
                'type' => 'textarea',
                'rows' => 4,
                'placeholder' => '请输入平台证书',
                'required' => false
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
                'key' => 'native',
                'label' => 'Native支付',
                'modes' => [1],
                'default_mode' => 0,
                'fields' => $commonFields,
                'default_config' => ['version' => 'v2', 'is_sandbox' => 0]
            ],
            [
                'key' => 'jsapi',
                'label' => 'JSAPI/小程序',
                'modes' => [1],
                'default_mode' => 0,
                'fields' => $commonFields,
                'default_config' => ['version' => 'v2', 'is_sandbox' => 0]
            ],
            [
                'key' => 'h5',
                'label' => 'H5支付',
                'modes' => [0],
                'default_mode' => 0,
                'fields' => $commonFields,
                'default_config' => ['version' => 'v2', 'is_sandbox' => 0]
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
        return 'ri:wechat-pay-fill';
    }

    public function getIconUrl(): string
    {
        return '';
    }

    public function pay($orderId, $amount, $title, array $config, string $type): array
    {
        if ($type === 'epay') {
            return $this->payEpay($orderId, $amount, $title, $config, 'wxpay');
        }

        if ($type === 'paypro') {
            return $this->payPaypro($orderId, $amount, $title, $config, 'wxpay');
        }

        return $this->payWxpay($orderId, $amount, $title, $config, $type);
    }

    public function verifyNotify(array $params, array $config, string $type): bool
    {
        if ($type === 'epay') {
            return $this->verifyEpay($params, $config);
        }

        if ($type === 'paypro') {
            return $this->verifyPaypro($params, $config);
        }

        return $this->verifyWxpay($params, $config);
    }

    public function refund($tradeNo, $amount, array $config, string $type): bool
    {
        if ($type === 'epay') {
            return $this->refundEpay($tradeNo, $amount, $config);
        }

        if ($type === 'paypro') {
            return $this->refundPaypro($tradeNo, $amount, $config);
        }

        throw new Exception("该支付方式暂不支持自动退款");
    }

    public function queryOrder($orderId, array $config, string $provider): bool
    {
        if ($provider === 'epay') {
            return $this->queryEpayOrder($orderId, $config);
        }

        if ($provider === 'paypro') {
            return $this->queryPayproOrder($orderId, $config);
        }

        return false;
    }

    public function handleNotify(array $config)
    {
        return $this->handleWxpayV3Notify($config);
    }

    protected function payEpay($orderId, $amount, $title, $config, $actualProvider = 'wxpay')
    {
        if ($this->isEpayV2($config)) {
            return $this->payEpayV2($orderId, $amount, $title, $config, $actualProvider);
        }

        $api_url = $config['api_url'] ?? ($config['url'] ?? ($config['pay_epay_url'] ?? ''));
        $pid = $config['pid'] ?? ($config['pay_epay_pid'] ?? '');
        $key = $config['key'] ?? ($config['pay_epay_key'] ?? '');

        if (empty($api_url) || empty($pid) || empty($key)) {
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
            "notify_url" => $config['notify_url'] ?? (request()->domain() . "/api/v3/payment/notify/epay"),
            "return_url" => $config['return_url'] ?? (($config['frontend_url'] ?? request()->domain()) . "/payment/callback"),
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

    protected function payWxpay($orderId, $amount, $title, $config, $type = 'native')
    {
        $version = $config['version'] ?? 'v2';

        if ($version === 'v3') {
            return $this->payWxpayV3($orderId, $amount, $title, $config, $type);
        }

        $appId = $config['app_id'] ?? ($config['appid'] ?? ($config['pay_wxpay_appid'] ?? ''));
        $mchId = $config['mch_id'] ?? ($config['mchid'] ?? ($config['pay_wxpay_mchid'] ?? ''));
        $mchId = $config['mch_id'] ?? ($config['mchid'] ?? ($config['pay_wxpay_mchid'] ?? ''));
        $key = $config['key'] ?? ($config['pay_wxpay_key'] ?? '');
        $linkedAppId = $config['linked_app_id'] ?? '';

        if (!empty($linkedAppId)) {
            $appId = $linkedAppId;
        }

        if (empty($appId) || empty($mchId) || empty($key)) {
            throw new Exception("微信支付配置不完整");
        }

        $isSandbox = !empty($config['is_sandbox']);

        if ($isSandbox) {
            $url = "https://api.mch.weixin.qq.com/sandboxnew/pay/unifiedorder";
            try {
                $sandboxKey = $this->getWxSandboxSignKey($mchId, $key);
                if ($sandboxKey) {
                    $key = $sandboxKey;
                }
            } catch (\Exception $e) {
                throw new Exception("获取微信沙箱密钥失败: " . $e->getMessage());
            }
        } else {
            $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        }

        if ($type === 'jsapi' && empty($config['openid'])) {
            $type = 'native';
        }

        $tradeType = 'NATIVE';
        if ($type === 'h5' || $type === 'mweb') {
            $tradeType = 'MWEB';
        } else if ($type === 'jsapi') {
            $tradeType = 'JSAPI';
        }

        $data = [
            'appid' => $appId,
            'mch_id' => $mchId,
            'nonce_str' => md5(uniqid()),
            'body' => $title,
            'out_trade_no' => $orderId,
            'total_fee' => (int)($amount * 100),
            'spbill_create_ip' => request()->ip(),
            'notify_url' => $config['notify_url'] ?? (request()->domain() . "/api/v3/payment/notify/wxpay"),
            'trade_type' => $tradeType
        ];

        if (!empty($config['attach'])) $data['attach'] = $config['attach'];
        if (!empty($config['detail'])) $data['detail'] = $config['detail'];
        if (!empty($config['goods_tag'])) $data['goods_tag'] = $config['goods_tag'];
        if (!empty($config['time_expire'])) $data['time_expire'] = $config['time_expire'];
        if ($tradeType === 'JSAPI' && empty($config['openid'])) {
            $tradeType = 'NATIVE';
            $data['trade_type'] = $tradeType;
        }

        if ($tradeType === 'JSAPI') {
            $data['openid'] = $config['openid'];
        }

        if ($tradeType === 'MWEB') {
            $data['scene_info'] = json_encode(['h5_info' => ['type' => 'Wap', 'wap_url' => request()->domain(), 'wap_name' => $config['site_name']]]);
        }

        ksort($data);
        $signStr = "";
        foreach ($data as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $signStr .= $k . "=" . $v . "&";
            }
        }
        $signStr .= "key=" . $key;
        $data['sign'] = strtoupper(md5($signStr));

        $xml = "<xml>";
        foreach ($data as $k => $v) {
            $xml .= "<$k><![CDATA[$v]]></$k>";
        }
        $xml .= "</xml>";

        $response = $this->httpRequest($url, $xml, [], 'POST');

        if (!$response) {
            throw new Exception("微信支付请求失败");
        }

        $result = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($result === false) {
            throw new Exception("微信支付响应解析失败: " . $response);
        }

        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
            if ($tradeType === 'NATIVE') {
                return ['pay_url' => (string)$result->code_url];
            } else if ($tradeType === 'MWEB') {
                $mwebUrl = (string)$result->mweb_url;
                $returnUrl = $config['return_url'] ?? (($config['frontend_url'] ?? '') . "/payment/callback");
                $returnUrl = $this->appendReturnUrlWithOrderNo($returnUrl, $orderId);

                if (!empty($returnUrl)) {
                    $redirectUrl = urlencode($returnUrl);
                    $mwebUrl .= '&redirect_url=' . $redirectUrl;

                }
                return ['pay_url' => $mwebUrl];
            }
            return ['data' => (array)$result];
        } else {
            throw new Exception("微信支付错误: " . $result->return_msg . " " . $result->err_code_des);
        }
    }

    protected function payWxpayV3($orderId, $amount, $title, $config, $type = 'native')
    {
        $appId = $config['app_id'] ?? ($config['appid'] ?? '');
        $mchId = $config['mch_id'] ?? ($config['mchid'] ?? '');
        $serialNo = $config['serial_no'] ?? '';
        $privateKey = $config['private_key'] ?? '';
        $linkedAppId = $config['linked_app_id'] ?? '';

        if (!empty($linkedAppId)) {
            $appId = $linkedAppId;
        }

        if (empty($appId) || empty($mchId) || empty($serialNo) || empty($privateKey)) {
            throw new Exception("微信支付V3配置不完整: 缺少AppID/商户号/证书序列号/商户私钥");
        }

        $url = "https://api.mch.weixin.qq.com/v3/pay/transactions/native";
        $tradeType = 'native';

        if ($type === 'h5' || $type === 'mweb') {
            $url = "https://api.mch.weixin.qq.com/v3/pay/transactions/h5";
            $tradeType = 'h5';
        } else if ($type === 'jsapi') {
            $url = "https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi";
            $tradeType = 'jsapi';
        }

        if ($tradeType === 'jsapi' && empty($config['openid'])) {
            $tradeType = 'native';
            $url = "https://api.mch.weixin.qq.com/v3/pay/transactions/native";
        }

        $data = [
            'appid' => $appId,
            'mchid' => $mchId,
            'description' => $title,
            'out_trade_no' => $orderId,
            'notify_url' => $config['notify_url'] ?? (request()->domain() . "/api/v3/payment/notify/wxpay"),
            'amount' => [
                'total' => (int)($amount * 100),
                'currency' => 'CNY'
            ]
        ];

        if (!empty($config['time_expire'])) $data['time_expire'] = $config['time_expire'];
        if (!empty($config['attach'])) $data['attach'] = $config['attach'];
        if (!empty($config['goods_tag'])) $data['goods_tag'] = $config['goods_tag'];
        if (!empty($config['detail'])) $data['detail'] = $config['detail'];
        if (!empty($config['scene_info'])) {
            $data['scene_info'] = $config['scene_info'];
        }
        if (!empty($config['settle_info'])) $data['settle_info'] = $config['settle_info'];

        if ($tradeType === 'h5') {
            if (!isset($data['scene_info'])) {
                $data['scene_info'] = [];
            }
            $data['scene_info']['payer_client_ip'] = request()->ip();
            $data['scene_info']['h5_info'] = ['type' => 'Wap'];
        } else if ($tradeType === 'jsapi') {
            $data['payer'] = ['openid' => $config['openid']];
        }

        $body = json_encode($data);
        $method = 'POST';
        $urlParts = parse_url($url);
        $path = $urlParts['path'] . ($urlParts['query'] ?? '');

        $auth = $this->generateWxpayV3Header($mchId, $serialNo, $privateKey, $method, $path, $body);

        $headers = [
            'Authorization: ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Entropy/1.0'
        ];

        $response = $this->httpRequest($url, $body, $headers, 'POST');
        $res = json_decode($response, true);

        if (isset($res['code']) && isset($res['message'])) {
            throw new Exception("微信支付V3下单失败: " . $res['message']);
        }

        if ($tradeType === 'native' && isset($res['code_url'])) {
            return ['pay_url' => $res['code_url']];
        } else if ($tradeType === 'h5' && isset($res['h5_url'])) {
            $h5Url = $res['h5_url'];
            $returnUrl = $config['return_url'] ?? (($config['frontend_url'] ?? '') . "/payment/callback");
            $returnUrl = $this->appendReturnUrlWithOrderNo($returnUrl, $orderId);
            if (!empty($returnUrl)) {
                $h5Url .= '&redirect_url=' . urlencode($returnUrl);
            }
            return ['pay_url' => $h5Url];
        } else if ($tradeType === 'jsapi' && isset($res['prepay_id'])) {
            return ['data' => $this->signWxpayV3Jsapi($appId, $res['prepay_id'], $privateKey)];
        }

        throw new Exception("微信支付V3响应异常: " . $response);
    }

    protected function payPaypro($orderId, $amount, $title, $config, $paytypeCode = 'wxpay')
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
        $verified = hash_equals($localSign, strtoupper($sign));

        return $verified;
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
        } catch (\Throwable $e) {
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

    protected function generateWxpayV3Header($mchId, $serialNo, $privateKey, $method, $path, $body = '')
    {
        $timestamp = time();
        $nonce = md5(uniqid());

        $message = "$method\n$path\n$timestamp\n$nonce\n$body\n";

        if (strpos($privateKey, '-----BEGIN') === false) {
            $privateKey = "-----BEGIN PRIVATE KEY-----\n" .
                wordwrap($privateKey, 64, "\n", true) .
                "\n-----END PRIVATE KEY-----";
        }

        $sign = '';
        if (function_exists('openssl_sign')) {
            openssl_sign($message, $sign, $privateKey, 'sha256WithRSAEncryption');
            $sign = base64_encode($sign);
        } else {
            throw new Exception("OpenSSL extension required for WxPay V3");
        }

        return sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",signature="%s",timestamp="%d",serial_no="%s"',
            $mchId, $nonce, $sign, $timestamp, $serialNo
        );
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

    protected function signWxpayV3Jsapi($appId, $prepayId, $privateKey) {
        $timestamp = time();
        $nonce = md5(uniqid());
        $package = "prepay_id=$prepayId";

        $message = "$appId\n$timestamp\n$nonce\n$package\n";

        if (strpos($privateKey, '-----BEGIN') === false) {
            $privateKey = "-----BEGIN PRIVATE KEY-----\n" .
                wordwrap($privateKey, 64, "\n", true) .
                "\n-----END PRIVATE KEY-----";
        }

        openssl_sign($message, $sign, $privateKey, 'sha256WithRSAEncryption');

        return [
            'appId' => $appId,
            'timeStamp' => (string)$timestamp,
            'nonceStr' => $nonce,
            'package' => $package,
            'signType' => 'RSA',
            'paySign' => base64_encode($sign)
        ];
    }

    protected function getWxSandboxSignKey($mchId, $apiKey)
    {
        $cacheKey = 'wxpay_sandbox_signkey_' . $mchId;
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $url = "https://api.mch.weixin.qq.com/sandboxnew/pay/getsignkey";
        $data = [
            'mch_id' => $mchId,
            'nonce_str' => md5(uniqid())
        ];

        ksort($data);
        $signStr = "";
        foreach ($data as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $signStr .= $k . "=" . $v . "&";
            }
        }
        $signStr .= "key=" . $apiKey;
        $data['sign'] = strtoupper(md5($signStr));

        $xml = "<xml>";
        foreach ($data as $k => $v) {
            $xml .= "<$k><![CDATA[$v]]></$k>";
        }
        $xml .= "</xml>";

        $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'User-Agent: Entropy/1.0'
        ];

        $response = $this->httpRequest($url, $xml, $headers, 'POST');

        libxml_use_internal_errors(true);
        $result = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xmlError = libxml_get_last_error();
        libxml_clear_errors();

        if ($result === false) {
            if (strpos($response, '<html') !== false || strpos($response, '<body') !== false) {
                $cleanMsg = strip_tags($response);
                $cleanMsg = preg_replace('/\s+/', ' ', $cleanMsg);
                throw new Exception("微信沙箱环境(V2)接口返回404，说明当前沙箱不可用。请在后台关闭【沙箱模式】开关以使用正式环境。详情: " . substr($cleanMsg, 0, 200));
            }
            throw new Exception("获取沙箱密钥失败(XML解析错误): " . ($xmlError ? $xmlError->message : substr($response, 0, 200)));
        }

        if ($result && $result->return_code == 'SUCCESS' && isset($result->sandbox_signkey)) {
            $sandboxKey = (string)$result->sandbox_signkey;
            Cache::set($cacheKey, $sandboxKey, 7200);
            return $sandboxKey;
        }

        throw new Exception("获取沙箱密钥失败: " . ($result->return_msg ?? $response));
    }

    protected function handleWxpayV3Notify($config)
    {
        $apiKey = $config['key'] ?? ($config['pay_wxpay_key'] ?? '');
        if (empty($apiKey) || strlen((string)$apiKey) !== 32) return false;

        $platformCert = (string)($config['cert_public'] ?? ($config['platform_cert'] ?? ''));
        $serialNo = strtoupper(trim((string)($config['serial_no'] ?? '')));
        if ($platformCert === '' || $serialNo === '') {
            return false;
        }

        $rawBody = request()->getInput();
        if (empty($rawBody)) return false;

        $headers = request()->header();
        if (!$this->verifyWxpayV3PlatformSignature($headers, $rawBody, $platformCert, $serialNo)) {
            return false;
        }

        $data = json_decode($rawBody, true);
        if (!$data || !isset($data['resource'])) return false;

        $resource = $data['resource'];
        $ciphertext = $resource['ciphertext'] ?? '';
        $nonce = $resource['nonce'] ?? '';
        $aad = $resource['associated_data'] ?? '';

        if (empty($ciphertext) || $nonce === '') return false;

        try {
            $ciphertextDecoded = base64_decode($ciphertext, true);
            if ($ciphertextDecoded === false || strlen($ciphertextDecoded) < 17) {
                return false;
            }
            $authTag = substr($ciphertextDecoded, -16);
            $ciphertextContent = substr($ciphertextDecoded, 0, -16);

            $decrypted = openssl_decrypt(
                $ciphertextContent,
                'aes-256-gcm',
                $apiKey,
                OPENSSL_RAW_DATA,
                $nonce,
                $authTag,
                $aad
            );

            if ($decrypted !== false && $decrypted !== '') {
                return json_decode($decrypted, true);
            }
        } catch (\Exception) {
        }

        return false;
    }

    protected function verifyWxpayV3PlatformSignature(array $headers, string $rawBody, string $platformCert, string $expectedSerialNo): bool
    {
        $signature = trim((string)$this->getHeaderValue($headers, 'Wechatpay-Signature'));
        $timestamp = trim((string)$this->getHeaderValue($headers, 'Wechatpay-Timestamp'));
        $nonce = trim((string)$this->getHeaderValue($headers, 'Wechatpay-Nonce'));
        $serial = strtoupper(trim((string)$this->getHeaderValue($headers, 'Wechatpay-Serial')));

        if ($signature === '' || $timestamp === '' || $nonce === '' || $serial === '') {
            return false;
        }

        if ($expectedSerialNo !== '' && strtoupper($expectedSerialNo) !== $serial) {
            return false;
        }

        if (!ctype_digit($timestamp) || abs(time() - (int)$timestamp) > 300) {
            return false;
        }

        $message = $timestamp . "\n" . $nonce . "\n" . $rawBody . "\n";
        $publicKey = $this->formatPemCertificate($platformCert);
        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            return false;
        }

        return openssl_verify($message, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    protected function getHeaderValue(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string)$key, $name) !== 0) {
                continue;
            }

            if (is_array($value)) {
                $value = reset($value);
            }

            return is_scalar($value) ? (string)$value : '';
        }

        return '';
    }

    protected function formatPemCertificate(string $certificate): string
    {
        $certificate = trim($certificate);
        if ($certificate === '') {
            return '';
        }

        if (strpos($certificate, '-----BEGIN CERTIFICATE-----') !== false) {
            return str_replace(["\r\n", "\r"], "\n", $certificate);
        }

        return "-----BEGIN CERTIFICATE-----\n" . wordwrap(preg_replace('/\s+/', '', $certificate), 64, "\n", true) . "\n-----END CERTIFICATE-----";
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

    protected function verifyWxpay($params, $config)
    {
        $mchId = trim((string)($params['mch_id'] ?? ($params['mchid'] ?? '')));
        $configMchId = trim((string)($config['mch_id'] ?? ($config['mchid'] ?? ($config['mchId'] ?? ($config['pay_wxpay_mchid'] ?? '')))));
        if ($mchId !== '' && $configMchId !== '' && $mchId !== $configMchId) {
            return false;
        }

        $appId = trim((string)($params['appid'] ?? ($params['app_id'] ?? '')));
        $configAppId = trim((string)($config['linked_app_id'] ?? ($config['app_id'] ?? ($config['appid'] ?? ($config['pay_wxpay_appid'] ?? '')))));
        if ($appId !== '' && $configAppId !== '' && $appId !== $configAppId) {
            return false;
        }

        $key = $config['key'] ?? ($config['pay_wxpay_key'] ?? '');
        if (empty($key)) return false;

        $sign = $params['sign'] ?? '';
        if (empty($sign)) return false;

        if (!is_array($params)) return false;

        ksort($params);
        $signStr = "";
        foreach ($params as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $signStr .= $k . "=" . $v . "&";
            }
        }
        $signStr .= "key=" . $key;
        $mySign = strtoupper(md5($signStr));

        return hash_equals($mySign, (string)$sign);
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
        } catch (\Exception $e) {
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
