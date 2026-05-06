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
     * 
     * 
     * https://openspeech.bytedance.com/api/v1/tts
     */
    private function getToken($request)
    {
    	$ak = "AKLTZTM1NWJhNDJlNjI4NDk3ZGE4MzllZWJlZGZhZWJkYmU";  // AK
    	$sk = "T1dReU5tSTVZekEzWkdRNU5EZG1PV0kyT0RkaVpUVmpZV1EzWVdFMlpqSQ==";  // SK
$Service = "iam";
$Version = "V1";
$Region = "cn-north-1";
$Host = "iam.volcengineapi.com";
$ContentType = "application/x-www-form-urlencoded";


$action = "ListUsers";
		$method = "POST";

$body ="";


    	$credential = array(
    		'accessKeyId' => $ak,
    		'secretKeyId' => $sk,
    		'service' => $Service,
    		'region' => $Region,
    	);
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
    	$token = sprintf("HMAC-SHA256 Credential=%s, SignedHeaders=%s, Signature=%s", $credential['accessKeyId'] . '/' . $credentialScope, $signedHeaderStr, $signature);
    	return $token;
    }

    /**
     * 通过v3版本的接口合成
     *
     * @return string
     */
    public function runByV1($text, $speaker, $ttsParams = array(), $resourceId = 'seed-tts-1.0')
    {
    	$request = array(
    		'app' => array(
    			'appid' => 'appid123',
    			'token' => 'access_token',
    			'cluster' => 'volcano_tts', // 业务集群
    		),
    		'user' => array(
    			'uid' => 'uid123', // 用户标识
    		),
    		'audio' => array(
    			'voice_type' => $speaker,
    			'encoding' => 'mp3',
    		),
    		'request' => array(
    			'reqid' => 1,
    			'text' => $text,
    			'operation' => 'query',
    			
    		),
    	);
    	$token = $this->getToken($request);

print_r($token);exit;
//     	$accessKey = "AKLTZTM1NWJhNDJlNjI4NDk3ZGE4MzllZWJlZGZhZWJkYmU";  // AK
//     	$secretKey = "T1dReU5tSTVZekEzWkdRNU5EZG1PV0kyT0RkaVpUVmpZV1EzWVdFMlpqSQ==";  // SK
//     	$token = $this->getToken($accessKey, $secretKey); // 生成官方要求的Token

		$url = "https://openspeech.bytedance.com/api/v1/tts";

		
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
    		'additions' => json_encode($additions, JSON_UNESCAPED_UNICODE),
    		'audio_params' => $audioParams,
    	);
    	$postParams = array(
    		'req_params' => $postParams,
    	);
    	$volcConf = self::$instance->frame->conf['volcengine'];
 
		$apiUrl = "https://openspeech.bytedance.com/api/v3/tts/unidirectional";
    	$responseContent = ''; // 请求的内容
    	$ch = curl_init();
    	curl_setopt_array($ch, [
    	CURLOPT_URL => $apiUrl,
    	CURLOPT_POST => true,
    	CURLOPT_POSTFIELDS => json_encode($postParams, JSON_UNESCAPED_UNICODE),
    	CURLOPT_HTTPHEADER => [
    		"Content-Type: application/json",
    		"Accept: application/octet-stream", // 接收二进制流
    		"X-Api-Key: {$volcConf['X-Api-Key']}", // 使用火山引擎控制台获取的APP ID，
    		"X-Api-Resource-Id: {$resourceId}", // 服务的资源信息 ID
    		'Connection: keep-alive',
    	],
    
    	CURLOPT_RETURNTRANSFER => false, // 关闭自动拼接，启用流式回调
    	CURLOPT_BINARYTRANSFER => true,  // 处理二进制数据（关键，音频是二进制）
    	CURLOPT_WRITEFUNCTION => function ($ch, $chunkData) use (&$responseContent) {
    		if (empty($chunkData)) {
    			return strlen($chunkData); // 必须返回接收的字节数，否则 cURL 会中断
    		}
    		$responseContent .= $chunkData;
    		return strlen($chunkData);
    	}
    	]);
    	try {
    		$response = curl_exec($ch);
    		if (curl_errno($ch)) {
    			return false;
    		}
    		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    		

    		if ($httpCode !== 200) {
    			return false;
    		}
    	} catch (Exception $e) {
    		return false;
    	} finally {
    		curl_close($ch); // 关闭 cURL 资源
    	}
    	preg_match_all('/\{\s*"code":.*?\}(?=\s*\{|\s*$)/s', $responseContent, $ttsContentArr);
    	$content = '';
    	$subtitles = array();
    	if (!empty($ttsContentArr['0'])) foreach ($ttsContentArr['0'] as $row) {
    		$row = trim($row);
    		$rowArr = empty($row) ? array() : json_decode($row, true);
    		if (empty($rowArr)) {
    			continue;
    		}
    		if (!empty($rowArr['sentence'])) { // 句子
    			$words = empty($rowArr['sentence']['words']) ? array() : $rowArr['sentence']['words'];
    			if (empty($words)) {
    				continue;
    			}
    			$text = $rowArr['sentence']['text'];
    			$wordArr = array();
    			foreach ($words as $word) {
    				$wordArr[] = array(
    					'startTime' => $word['startTime'],
    					'endTime' => $word['endTime'],
    					'word' => $word['word'],
    				);
    			}
    			$subtitles[] = array(
    				'text' => $text,
    				'words' => $wordArr,
    			);
    			
    		} elseif (!empty($rowArr['data'])) {
    			$subContent = base64_decode($rowArr['data']);
    			if (empty($subContent)) {
    				continue;
    			}
    			$content .= $subContent;
    		}
    	}
    	
    	// 字幕分段
    	$subtitles = processSubtitle($subtitles);
    	return array(
    		'size' => mb_strlen($content),
    		'content' => $content,
    		'resourceId' => $resourceId,
    		'speaker' => $speaker,
    		'subtitles' => $subtitles,
    	);
    }
    
    /**
     * 通过v3版本的接口合成
     *
     * @return string
     */
    public function runByV3bak($text, $speaker, $ttsParams = array(), $resourceId = 'seed-tts-1.0')
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
    	$volcConf = self::$instance->frame->conf['volcengine'];
 
		$apiUrl = "https://openspeech.bytedance.com/api/v3/tts/unidirectional";
    	$responseContent = ''; // 请求的内容
    	$ch = curl_init();
    	curl_setopt_array($ch, [
    	CURLOPT_URL => $apiUrl,
    	CURLOPT_POST => true,
    	CURLOPT_POSTFIELDS => json_encode($postParams, JSON_UNESCAPED_UNICODE),
    	CURLOPT_HTTPHEADER => [
    		"Content-Type: application/json",
    		"Accept: application/octet-stream", // 接收二进制流
    		"x-api-key: {$volcConf['appId']}", // 使用火山引擎控制台获取的APP ID，
    		"X-Api-Resource-Id: {$resourceId}", // 服务的资源信息 ID
    		'Connection: keep-alive',
    	],
    	
    	CURLOPT_RETURNTRANSFER => false, // 关闭自动拼接，启用流式回调
    	CURLOPT_BINARYTRANSFER => true,  // 处理二进制数据（关键，音频是二进制）
    	CURLOPT_WRITEFUNCTION => function ($ch, $chunkData) use (&$responseContent) {
    		if (empty($chunkData)) {
    			return strlen($chunkData); // 必须返回接收的字节数，否则 cURL 会中断
    		}
    		$responseContent .= $chunkData;
    		return strlen($chunkData);
    	}
    	]);
    	try {
    		$response = curl_exec($ch);
    		if (curl_errno($ch)) {
    			return false;
    		}
    		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    		if ($httpCode !== 200) {
    			return false;
    		}
    	} catch (Exception $e) {
    		return false;
    	} finally {
    		curl_close($ch); // 关闭 cURL 资源
    	}
    	preg_match_all('/\{\s*"code":.*?\}(?=\s*\{|\s*$)/s', $responseContent, $ttsContentArr);
    	$content = '';
    	$subtitles = array();
    	if (!empty($ttsContentArr['0'])) foreach ($ttsContentArr['0'] as $row) {
    		$row = trim($row);
    		$rowArr = empty($row) ? array() : json_decode($row, true);
    		if (empty($rowArr)) {
    			continue;
    		}
    		if (!empty($rowArr['sentence'])) { // 句子
    			$words = empty($rowArr['sentence']['words']) ? array() : $rowArr['sentence']['words'];
    			if (empty($words)) {
    				continue;
    			}
    			$text = $rowArr['sentence']['text'];
    			$wordArr = array();
    			foreach ($words as $word) {
    				$wordArr[] = array(
    					'startTime' => $word['startTime'],
    					'endTime' => $word['endTime'],
    					'word' => $word['word'],
    				);
    			}
    			$subtitles[] = array(
    				'text' => $text,
    				'words' => $wordArr,
    			);
    			
    		} elseif (!empty($rowArr['data'])) {
    			$subContent = base64_decode($rowArr['data']);
    			if (empty($subContent)) {
    				continue;
    			}
    			$content .= $subContent;
    		}
    	}
    	
    	// 字幕分段
    	$subtitles = processSubtitle($subtitles);
    	return array(
    		'size' => mb_strlen($content),
    		'content' => $content,
    		'resourceId' => $resourceId,
    		'speaker' => $speaker,
    		'subtitles' => $subtitles,
    	);
    }
    
}