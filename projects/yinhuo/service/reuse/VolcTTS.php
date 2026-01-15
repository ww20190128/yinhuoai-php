<?php
namespace service\reuse;

/**
 * 火山引擎语音合成(TTS)
 */
class VolcTTS extends \service\ServiceBase
{
	
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
     * 获取v1 请求的头信息
     * 
     * @return array
     */
    private function getRequestHeader()
    {
    	$ak = "AKLTZTM1NWJhNDJlNjI4NDk3ZGE4MzllZWJlZGZhZWJkYmU";  // AK
    	$sk = "T1dReU5tSTVZekEzWkdRNU5EZG1PV0kyT0RkaVpUVmpZV1EzWVdFMlpqSQ==";  // SK
    	//     	$token = $this->getToken($accessKey, $secretKey); // 生成官方要求的Token

    	$credential = array(
    		'accessKeyId' => $ak,
    		'secretKeyId' => $sk,
    		'service' => $Service,
    		'region' => $Region,
    	);
    	// 初始化签名结构体
    	$query = array_merge($query, array(
    		'Action' => $action,
    		'Version' => $Version
    	));
    	ksort($query);
    	$requestParam = array(
    		'body' => $body, // body是http请求需要的原生body
    		'host' => $Host,
    		'path' => '/',
    		'method' => $method,
    		'contentType' => $ContentType,
    		'date' => gmdate('Ymd\THis\Z'),
    		'query' => $query
    	);
    
    	// 第三步：接下来开始计算签名。在计算签名前，先准备好用于接收签算结果的 signResult 变量，并设置一些参数。
    	// 初始化签名结果的结构体
    	$xDate = $requestParam['date'];
    	$shortXDate = substr($xDate, 0, 8);
    	$xContentSha256 = hash('sha256', $requestParam['body']);
    	$signResult = [
    		'Host' => $requestParam['host'],
    		'X-Content-Sha256' => $xContentSha256,
    		'X-Date' => $xDate,
    		'Content-Type' => $requestParam['contentType']
    	];
    	// 第四步：计算 Signature 签名。
    	$signedHeaderStr = join(';', ['content-type', 'host', 'x-content-sha256', 'x-date']);
    	$canonicalRequestStr = join("\n", [
    		$requestParam['method'],
    		$requestParam['path'],
    		http_build_query($requestParam['query']),
    		join("\n", ['content-type:' . $requestParam['contentType'], 'host:' . $requestParam['host'], 'x-content-sha256:' . $xContentSha256, 'x-date:' . $xDate]),
    		'',
    		$signedHeaderStr,
    		$xContentSha256
    	]);
    	$hashedCanonicalRequest = hash("sha256", $canonicalRequestStr);
    	$credentialScope = join('/', array($shortXDate, $credential['region'], $credential['service'], 'request'));
    	$stringToSign = join("\n", ['HMAC-SHA256', $xDate, $credentialScope, $hashedCanonicalRequest]);
    	$kDate = hash_hmac("sha256", $shortXDate, $credential['secretKeyId'], true);
    	$kRegion = hash_hmac("sha256", $credential['region'], $kDate, true);
    	$kService = hash_hmac("sha256", $credential['service'], $kRegion, true);
    	$kSigning = hash_hmac("sha256", 'request', $kService, true);
    	$signature = hash_hmac("sha256", $stringToSign, $kSigning);
    	$signResult['Authorization'] = sprintf("HMAC-SHA256 Credential=%s, SignedHeaders=%s, Signature=%s", $credential['accessKeyId'] . '/' . $credentialScope, $signedHeaderStr, $signature);
    	$header = array_merge($header, $signResult);
    	
    }

    /**
     * 通过v1版本的接口合成
     * 
     * @return
     */
    public function runByV1($text, $voiceType)
    {  
$a = $this->getRequestHeader();
//     	$accessKey = "AKLTZTM1NWJhNDJlNjI4NDk3ZGE4MzllZWJlZGZhZWJkYmU";  // AK
//     	$secretKey = "T1dReU5tSTVZekEzWkdRNU5EZG1PV0kyT0RkaVpUVmpZV1EzWVdFMlpqSQ==";  // SK
//     	$token = $this->getToken($accessKey, $secretKey); // 生成官方要求的Token

        $defaultAudioParams = array(
            'voice_type'    => $voiceType, // 免费通用音色（替换BV700避免权限问题）
            'encoding'      => 'mp3',
            'rate'          => 24000,
            'speed_ratio'   => 1.0,
            'volume_ratio'  => 1.0,
            'pitch_ratio'   => 1.0,
            'silence_duration' => 125
        );
        $audioParams = array_merge($defaultAudioParams, $customParams);
        
        $a = $this->getRequestHeader();
        
        print_r($a);exit;

        $reqId = $this->getUniqueReqId();
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

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $bodyStr,
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Bearer;' . $token,
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

  
print_r($response);exit;
    }

    /**
     * 通过v3版本的接口合成
     *
     * @return string
     */
    public function runByV3($text, $speaker, $ttsParams = array(), $resourceId = 'seed-tts-1.0')
    {
    	$additions = array(
    		'silence_duration' => 0, // 设置该参数可在句尾增加静音时长，范围0~30000ms。
    		'enable_language_detector' => true, // 自动识别语种
    		'disable_markdown_filter' => true, // 是否开启markdown解析过滤，
    		'enable_latex_tn' => true,
    		'max_length_to_filter_parenthesis' => 100, // 是否过滤括号内的部分，0为不过滤，100为过滤
    		'cache_config' => array(
    			'text_type' => 1,
    			//'use_cache' => true,
    		),
    	);
    	if (!empty($ttsParams['language'])) { // 明确语种
    		$additions['explicit_language'] = $ttsParams['language'];
    	}
    	$audioParams = array(
    		'format' 		=> 'mp3',
    		'sample_rate'	=> 24000,
    		'enable_timestamp' => true,
    	);
    	if (!empty($ttsParams['speechRate'])) { // 语速，取值范围[-50,100]，100代表2.0倍速，-50代表0.5倍数
    		$audioParams['speech_rate'] = $ttsParams['speechRate'];
    	}
    	if (!empty($ttsParams['loudnessRate'])) { // 音量，取值范围[-50,100]，100代表2.0倍音量，-50代表0.5倍音量（mix音色暂不支持）
    		$audioParams['loudness_rate'] = $ttsParams['loudnessRate'];
    	}
    	$postParams = array(
    		'text' => $text,
    		'speaker' => $speaker,
    		'additions' => json_encode($additions),
    		'audio_params' => $audioParams,
    	);
    	
    	$postParams = array(
    		'req_params' => $postParams,
    	);
    	$appId = 'd294de9a-a197-42e4-8a00-e29eaa05a0df';
    	$url = "https://openspeech.bytedance.com/api/v3/tts/unidirectional";
    	
    	$ch = curl_init();
    	curl_setopt_array($ch, [
    	CURLOPT_URL => $url,
    	CURLOPT_POST => true,
    	CURLOPT_POSTFIELDS => json_encode($postParams, JSON_UNESCAPED_UNICODE),
    	CURLOPT_HTTPHEADER => [
    		"Content-Type: application/json",
    		"Accept: application/octet-stream", // 接收二进制流
    		"x-api-key: {$appId}", // 使用火山引擎控制台获取的APP ID，
    		"X-Api-Resource-Id: {$resourceId}", // 服务的资源信息 ID
    		'Connection: keep-alive',
    	],
    	CURLOPT_RETURNTRANSFER => false, // 关闭自动拼接，启用流式回调
    	CURLOPT_BINARYTRANSFER => true,  // 处理二进制数据（关键，音频是二进制）
    	CURLOPT_WRITEFUNCTION => function ($ch, $chunkData) {
    		// $chunkData：本次收到的音频分片（二进制）
    		// 避免空数据：服务端可能返回空分片，需过滤
    		if (empty($chunkData)) {
    			return strlen($chunkData); // 必须返回接收的字节数，否则 cURL 会中断
    		}
    		
    		echo $chunkData . "\n";
    		
    		$subResult = json_decode($chunkData, true);
    		$subContent = empty($subResult['data']) ? '' : base64_decode($subResult['data']);
    		if (!empty($subContent)) {
    			// 示例1：实时保存到文件（边接收边写入，无需等待全量数据）
    			$savePath = "/data/www/test/666.mp3";
    			file_put_contents($savePath, $subContent, FILE_APPEND); // 追加写入
    		}
    	
    		
    	
    		// 示例2：实时输出到前端（如果是 Web 场景，可直接播放）
    		// echo $chunkData;
    		// flush(); // 强制输出缓冲区，前端可实时播放
    	
    		// 示例3：统计进度（可选）
    		static $totalBytes = 0;
    		$totalBytes += strlen($subContent);
    		//echo "已接收：{$totalBytes} 字节\n";
    	
    		// 必须返回本次接收的字节数！否则 cURL 会认为出错并终止请求
    		return strlen($chunkData);
    	}
    	]);
    	// 4. 执行请求并处理异常
    	try {
    		$response = curl_exec($ch);
    		if (curl_errno($ch)) {
    			return false;
    		}
    		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    		if ($httpCode !== 200) {
    			throw new Exception("API 请求失败，状态码：{$httpCode}");
    		}
    		echo "流式接收完成！音频已保存到 tts_stream.mp3\n";
    	} catch (Exception $e) {
    		echo "错误：" . $e->getMessage() . "\n";
    	} finally {
    		curl_close($ch); // 关闭 cURL 资源
    	}
    	return ;
    	
    	

    	
    	$ch = curl_init();
    	curl_setopt_array($ch, array(
    		CURLOPT_URL            => $url,
    		CURLOPT_POST           => true,
    		CURLOPT_POSTFIELDS     => json_encode($postParams, JSON_UNESCAPED_UNICODE),
    		CURLOPT_HTTPHEADER     => array(
    			"x-api-key: {$appId}", // 使用火山引擎控制台获取的APP ID，
    			"X-Api-Resource-Id: {$resourceId}", // 服务的资源信息 ID
    			'Connection: keep-alive',
    			'Content-Type: application/json',
    		),
    		CURLOPT_RETURNTRANSFER => true,
    		CURLOPT_SSL_VERIFYPEER => false,
    		CURLOPT_SSL_VERIFYHOST => false,
    		CURLOPT_TIMEOUT        => 80,
    		CURLOPT_CONNECTTIMEOUT => 10
    	));
    	$response = curl_exec($ch);
    	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	$curlError = curl_error($ch);
    	curl_close($ch);
    	$response = empty($response) ? array() : explode("\n", $response);
    	$content = '';
    	$duration = 0;
    	if (!empty($response)) foreach ($response as $row) {
    		$rowArr = empty($row) ? array() : json_decode($row, true);
    		if (!empty($rowArr['data'])) {
    			$subContent = base64_decode($rowArr['data']);
    			if (empty($subContent)) {
    				
    				echo "xx\n";
    				continue;
    			}
    			$content .= $subContent;
    		} elseif (!empty($rowArr['sentence'])) {
    			$sentence = $rowArr['sentence'];
    			if (!empty($sentence['words'])) foreach ($sentence['words'] as $word) {
    				if (!empty($word['endTime']) && $word['endTime'] >= $duration) {
    					$duration = $word['endTime'];
    				}
    			}
    		}
    	}
    	echo mb_strlen($content) . "\n";
    	return array(
    		'size' => mb_strlen($content),
    		'content' => $content,
    		'duration' => $duration,
    		'resourceId' => $resourceId,
    		'speaker' => $speaker,
    	);
    }
    
}