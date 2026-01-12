<?php
namespace service\reuse;

/**
 * 火山引擎语音合成(TTS)封装类
 * 适配官方文档：https://www.volcengine.com/docs/6561/79823?lang=zh
 * 核心修复：严格匹配官方签名规则，解决401/3001签名验证错误
 */
class VolcTTS extends \service\ServiceBase
{
    // 官方接口地址（非流式调用固定）
    private $apiUrl = 'https://openspeech.bytedance.com/api/v1/tts';
    
    // 核心配置（替换为控制台的真实值）
    private $appId = '3753670442';       // TTS应用AppID
    private $cluster = 'volcano_tts';    // 集群（免费音色用online_tts）

    /**
     * 单例
     * @var object
     */
    private static $instance;
    
    /**
     * 单例模式
     * @return VolcTTS
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new VolcTTS();
        }
        return self::$instance;
    }

    /**
     * 核心合成方法（修复签名规则）
     * @param string $text 待合成文本（≤1024字节）
     * @param array $customParams 自定义音频参数
     * @return array 合成结果
     * @throws \Exception
     */
    public function run($text, $customParams = array())
    {  
        // ========== 1. 前置参数校验 ==========
        if (empty($text)) {
            throw new \Exception("合成文本不能为空");
        }
        if (mb_strlen($text, 'UTF-8') > 1024) {
            throw new \Exception("合成文本长度超过限制（最大1024字节）");
        }

        // ========== 2. 核心凭证（必须替换为控制台的真实值） ==========
        $accessKey = "AKLTZTM1NWJhNDJlNjI4NDk3ZGE4MzllZWJlZGZhZWJkYmU";  // AK
        $secretKey = "T1dReU5tSTVZekEzWkdRNU5EZG1PV0kyT0RkaVpUVmpZV1EzWVdFMlpqSQ==";  // SK
        $token = $this->generateToken($accessKey, $secretKey); // 生成官方要求的Token

        // ========== 3. 合并音频参数 ==========
        $defaultAudioParams = array(
            'voice_type'    => 'zh_female_qingxin', // 免费通用音色（替换BV700避免权限问题）
            'encoding'      => 'mp3',
            'rate'          => 24000,
            'speed_ratio'   => 1.0,
            'volume_ratio'  => 1.0,
            'pitch_ratio'   => 1.0,
            'silence_duration' => 125
        );
        $audioParams = array_merge($defaultAudioParams, $customParams);
        
        // ========== 4. 构造请求体（严格按官方格式） ==========
        $reqId = $this->generateUniqueReqId();
        $params = array(
            'app' => array(
                'appid'   => $this->appId,
                'token'   => $token,       // 核心：使用生成的Token
                'cluster' => $this->cluster
            ),
            'user' => array(
                'uid' => 'volc_tts_' . uniqid()
            ),
            'audio' => $audioParams,
            'request' => array(
                'reqid'     => $reqId,
                'text'      => $text,
                'operation' => 'query',
                'text_type' => 'plain'
            )
        );
        $bodyStr = json_encode($params, JSON_UNESCAPED_UNICODE);

        // ========== 5. 发送请求（严格匹配官方头格式） ==========
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $bodyStr,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Bearer ' . $token, // 核心修复：空格分隔，无分号！
                'Accept: application/json'
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10
        ));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // ========== 6. 异常处理 ==========
        if ($curlError) {
            throw new \Exception("CURL请求失败：{$curlError}");
        }
        if ($httpCode !== 200) {
            throw new \Exception("HTTP请求失败，状态码：{$httpCode}，响应：{$response}");
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("响应解析失败：{$response}");
        }
        
        if (isset($result['code']) && $result['code'] !== 3000) {
            $msg = $result['message'] ?? '未知错误';
            throw new \Exception("TTS合成失败：{$msg}（错误码：{$result['code']}）");
        }

        // ========== 7. 返回结果 ==========
        return array(
            'reqid'        => $reqId,
            'code'         => $result['code'],
            'audio_base64' => $result['data'] ?? '',
            'duration'     => $result['addition']['duration'] ?? 0,
            'raw_response' => $result
        );
    }

    /**
     * 生成火山引擎TTS官方要求的Token（核心修复）
     * @param string $ak AccessKey
     * @param string $sk SecretKey
     * @return string 签名后的Token
     */
    private function generateToken($ak, $sk)
    {
        $timestamp = time();
        $nonce = rand(100000, 999999);
        // 官方签名规则：ak + timestamp + nonce 拼接后HMAC-SHA256加密
        $signStr = $ak . $timestamp . $nonce;
        $signature = hash_hmac('sha256', $signStr, $sk, true);
        $signatureBase64 = base64_encode($signature);
        
        // 构造Token（官方固定格式）
        return sprintf(
            "version=2020-10-01&ak=%s&timestamp=%s&nonce=%s&signature=%s",
            $ak,
            $timestamp,
            $nonce,
            $signatureBase64
        );
    }

    /**
     * 生成唯一reqid
     * @return string UUID
     */
    private function generateUniqueReqId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * 保存音频到本地
     * @param string $base64Data 音频Base64
     * @param string $savePath 保存路径
     * @return string 绝对路径
     * @throws \Exception
     */
    public function saveAudio($base64Data, $savePath)
    {
        $audioData = base64_decode($base64Data, true);
        if ($audioData === false) {
            throw new \Exception("音频Base64解码失败");
        }
        
        $dir = dirname($savePath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \Exception("创建目录失败：{$dir}");
        }
        
        if (!file_put_contents($savePath, $audioData)) {
            throw new \Exception("保存音频失败：{$savePath}");
        }
        
        return realpath($savePath);
    }
}