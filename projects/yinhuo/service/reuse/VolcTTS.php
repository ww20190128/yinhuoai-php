<?php
namespace service\reuse;
/**
 * 火山引擎TTS V3（最新版）PHP完整实现
 * 文档参考：https://www.volcengine.com/docs/6561/152078
 * 适配2025-2026官方最新规范
 * 
 * api-key-20260110212945
 * 	
API Key
d294de9a-a197-42e4-8a00-e29eaa05a0df
 */


class VolcTTS  extends \service\ServiceBase{
    // 核心配置（替换为你的真实信息）
    private $accessKey = "AKLTZTM1NWJhNDJlNjI4NDk3ZGE4MzllZWJlZGZhZWJkYmU";    // AccessKey ID
    private $secretKey = "T1dReU5tSTVZekEzWkdRNU5EZG1PV0kyT0RkaVpUVmpZV1EzWVdFMlpqSQ==";    // SecretKey
    private $appKey = "d294de9a-a197-42e4-8a00-e29eaa05a0df";       // TTS应用AppKey
    private $apiHost = "openspeech.bytedance.com";
    private $apiPath = "/api/v3/tts/unidirectional"; // V3版本专属路径
    private $apiUrl;
    private $resourceId = "";   // V3强制要求：资源ID（关键新增）

    /**
     * 单例
     *
     * @var object
     */
    private static $instance;
    /**
     * 单例模式
     *
     * @return VolcTTS
     */
    public static function singleton()
    {
    	if (!isset(self::$instance)) {
    		self::$instance = new VolcTTS();
    		$self = self::$instance;
    		$self->apiUrl = "https://{$self->apiHost}{$self->apiPath}";
    	
    		// https://openspeech.bytedance.com/api/v3/tts/unidirectional
    		// https://openspeech.bytedance.com/api/v3/tts/unidirectional
    
    	}
    	return self::$instance;
    }

    /**
     * 设置鉴权信息
     * @param string $accessKey
     * @param string $secretKey
     * @param string $appKey
     */
    public function setAuth($accessKey, $secretKey, $appKey, $resourceId = '8114861806') {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->appKey = $appKey;
        $this->resourceId = '8114861806'; // 绑定资源ID
    }

    /**
     * 生成V3签名（适配ResourceId）
     * @param array $body 请求体
     * @return array 签名请求头
     */
    private function generateSignature($body) {
        $timestamp = gmdate('Ymd\THis\Z');
        $nonce = uniqid('', true);
        $bodyStr = json_encode($body, JSON_UNESCAPED_UNICODE);
        
        $signString = implode("\n", [
            "POST",
            $this->apiPath,
            $timestamp,
            $nonce,
            $this->appKey,
            $bodyStr
        ]);
        
        $signature = base64_encode(hash_hmac('sha256', $signString, $this->secretKey, true));
        
        $authorization = sprintf(
            "HMAC-SHA256 Credential=%s, SignedHeaders=content-type;x-date;x-nonce;x-app-key, Signature=%s",
            $this->accessKey,
            $signature
        );

        return [
            "Content-Type: application/json; charset=utf-8",
            "X-Date: {$timestamp}",
            "X-Nonce: {$nonce}",
            "X-App-Key: {$this->appKey}",
            "Authorization: {$authorization}"
        ];
    }


    /**
     * V3版本语音合成核心方法
     * @param string $text 合成文本
     * @param array $options 自定义参数
     * @param string $savePath 音频保存路径
     * @return array 合成结果
     */
    public function synthesize($text, $options = [], $savePath = "./tts_v3.mp3") {
         // V3版本默认参数（新增ResourceId）
        $defaultOptions = [
            "Action" => "CreateTtsTask",       
            "Version" => "2024-01-01",         
            "Text" => $text,
            "VoiceType" => "ai_qingnian_xiaoyue",
            "Language" => "zh-CN",
            "ResourceId" => 'seed-tts-1.0', // 核心修复：传入ResourceId
            "AudioConfig" => [                 
                "Format" => "mp3",
                "SampleRate" => 24000,
                "Bitrate" => 128000,
                "Speed" => 1.0,                // 倍速0.3~5.0
                "Volume" => 1.0,
                "Pitch" => 1.0,
                "Emotion" => "neutral"
            ],
            "EnableWordTimestamp" => false,
            "AppKey" => $this->appKey          
        ];

        // 合并自定义参数
        if (!empty($options['AudioConfig'])) {
            $defaultOptions['AudioConfig'] = array_merge($defaultOptions['AudioConfig'], $options['AudioConfig']);
            unset($options['AudioConfig']);
        }
        $params = array_merge($defaultOptions, $options);

        // 生成签名头
        $headers = $this->generateSignature($params);

        // 发送请求
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,      // 生产环境强制开启SSL验证
            CURLOPT_TIMEOUT => 60,               // V3超时时间延长至60s
            CURLOPT_CONNECTTIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
print_r($response);exit;

        // 处理响应
        if ($error) {
            return ["code" => -1, "msg" => "请求失败：{$error}"];
        }

        // V3版本错误码处理
        if ($httpCode != 200) {
            $errorInfo = json_decode($response, true) ?: ["msg" => $response];
            return [
                "code" => $httpCode,
                "msg" => "V3接口返回异常：{$errorInfo['msg']}",
                "request_id" => $errorInfo['request_id'] ?? ""
            ];
        }

        // 保存音频文件
        if (!file_put_contents($savePath, $response)) {
            return ["code" => -2, "msg" => "音频文件保存失败，请检查路径权限"];
        }

        return [
            "code" => 0,
            "msg" => "V3版本合成成功",
            "save_path" => $savePath,
            "file_size" => filesize($savePath) . " bytes",
            "speed_setting" => $params['audio_config']['speed'] // 确认倍速设置
        ];
    }
}
