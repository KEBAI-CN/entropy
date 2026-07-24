<?php
namespace app\service\identity;

use app\service\SettingService;

class OjwyunVideoDriver implements IdentityDriverInterface
{
    public function getKey(): string
    {
        return 'ojwyun_v1_video';
    }

    public function getLabel(): string
    {
        return 'OJW实人认证H5';
    }

    public function getDescription(): string
    {
        return '<div class="space-y-1"><p>使用OJW API v2 H5权威源实人认证。</p><p>价格：0.7/次</p><p>认证类型 1 为快捷认证，2 身份证OCR+活体+实人认证，3 填写/身份证OCR+活体+实人认证</p><p>可组合活体动作类型，如13为先远近后摇头，推荐用动作组合，默认为3（1：远近，2： 眨眼，3：摇头，4：点头，6：炫彩）</p><p>文档：<a href="https://face.ojwyun.cn/" target="_blank">https://face.ojwyun.cn/</a></p></div>';
    }

    public function getFields(): array
    {
        return [
            [
                'key' => 'ojwyun_face_key',
                'label' => 'API Key',
                'type' => 'password',
                'placeholder' => '请输入 API Key'
            ],
            [
                'key' => 'ojwyun_face_type',
                'label' => '认证类型',
                'type' => 'number',
                'min' => 1,
                'max' => 3,
                'step' => 1,
                'default' => 1,
                'placeholder' => '1 或 2 或 3'
            ],
            [
                'key' => 'ojwyun_face_living_type',
                'label' => '活体动作类型',
                'type' => 'text',
                'placeholder' => '例如 3 或 26'
            ],
            [
                'key' => 'ojwyun_face_page_title',
                'label' => '页面标题',
                'type' => 'text',
                'placeholder' => '例如 实名认证'
            ],
            [
                'key' => 'ojwyun_face_page_bg_color',
                'label' => '页面背景色',
                'type' => 'text',
                'placeholder' => '#eeeeee'
            ],
            [
                'key' => 'ojwyun_face_txt_bg_color',
                'label' => '按钮背景色',
                'type' => 'text',
                'placeholder' => '#cccccc'
            ]
        ];
    }

    public function getStartMessage(): string
    {
        return '请在新页面完成实人认证';
    }

    public function getStatusMessage(string $status): string
    {
        if ($status === 'success') {
            return '认证成功';
        }
        if ($status === 'pending') {
            return '认证进行中';
        }
        return '认证失败';
    }

    public function startAuth(string $realName, string $idCard, string $returnUrl, string $mobile = ''): array
    {
        $apiKey = $this->getApiKey();
        if ($apiKey === '') {
            throw new \Exception('未配置实名认证服务');
        }
        $type = $this->getSettingInt('ojwyun_face_type', 1);
        if (!in_array($type, [1, 2, 3], true)) {
            $type = 1;
        }
        $livingType = $this->getSettingString('ojwyun_face_living_type', '');
        $pageTitle = $this->getSettingString('ojwyun_face_page_title', '');
        $pageBgColor = $this->getSettingString('ojwyun_face_page_bg_color', '');
        $txtBgColor = $this->getSettingString('ojwyun_face_txt_bg_color', '');
        $params = [
            'name' => $realName,
            'idcard' => $idCard,
            'type' => (string)$type,
            'returnurl' => $returnUrl,
            'notifyurl' => $returnUrl
        ];
        if ($livingType !== '') {
            $params['livingType'] = $livingType;
        }
        if ($pageTitle !== '') {
            $params['pageTitle'] = $pageTitle;
        }
        if ($pageBgColor !== '') {
            $params['pageBgColor'] = $pageBgColor;
        }
        if ($txtBgColor !== '') {
            $params['txtBgColor'] = $txtBgColor;
        }
        $result = $this->request('/apiV2/initialize_1000', $params, $apiKey);
        $code = (string)($result['code'] ?? '');
        if ($code !== '200') {
            $msg = $result['msg'] ?? '实名认证失败';
            throw new \Exception($msg);
        }
        $url = $result['url'] ?? '';
        $token = $result['token'] ?? '';
        if ($url === '' || $token === '') {
            throw new \Exception('实名认证服务返回异常');
        }
        if (strpos($url, 'token=') === false) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'token=' . $token;
        }
        return [
            'url' => $url,
            'biz_token' => $token,
            'provider' => $this->getKey()
        ];
    }

    public function checkStatus(string $bizToken): array
    {
        $apiKey = $this->getApiKey();
        $result = $this->request('/apiV2/query_1000', [
            'token' => $bizToken
        ], $apiKey);
        $code = (string)($result['code'] ?? '');
        if ($code !== '200') {
            return [
                'status' => 'failed',
                'data' => $result
            ];
        }
        $success = $result['success'] ?? '';
        $passed = $success === true || $success === 1 || $success === '1' || $success === 'true';
        return [
            'status' => $passed ? 'success' : 'failed',
            'data' => $result
        ];
    }

    private function getApiKey(): string
    {
        return (string)SettingService::get('ojwyun_face_key');
    }

    private function getSettingString(string $key, string $default = ''): string
    {
        return (string)SettingService::get($key, $default);
    }

    private function getSettingInt(string $key, int $default = 0): int
    {
        $value = SettingService::get($key);
        if ($value === null || $value === '') {
            return $default;
        }
        return (int)$value;
    }

    private function request(string $path, array $params, string $apiKey): array
    {
        $url = 'https://face.ojwyun.cn' . $path;
        $payload = http_build_query($params);
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: KEYCODE ' . $apiKey
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if ($response === false || $error) {
                throw new \Exception($error ?: '实名认证服务请求失败');
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => $payload,
                    'timeout' => 10,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                throw new \Exception('实名认证服务请求失败');
            }
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new \Exception('实名认证服务返回异常');
        }
        return $decoded;
    }
}
