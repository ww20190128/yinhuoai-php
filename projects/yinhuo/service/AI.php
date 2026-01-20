<?php
namespace service;

/**
 * AI 逻辑类
 * 
 * @author 
 */
class AI extends ServiceBase
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return AI
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AI();
        }
        return self::$instance;
    }

    
    /**
     * 聊天
     * 
     * @return array
     */
    public function chat()
    {
    	$apiUrl = 'https://ark.cn-beijing.volces.com/api/v3/responses';
    	$params = array(
    		'model' => 'doubao-seed-1-8-251228',
    		'input' => array(
    			array(
    				'role' => 'user',
    				'content' => array(
    					array(
    						'type' => 'input_image',
    						'image_url' => 'https://ark-project.tos-cn-beijing.volces.com/doc_image/ark_demo_img_1.png',
    					),
    					array(
    						'type' => 'input_text',
    						'text' => '你看见了什么？',
    					),
    				),			
    			)
    		),
    	);

    	$ARK_API_KEY = '38078d13-166f-4194-8fa1-1c0bd4ba2084';
    	$bodyStr = json_encode($params, JSON_UNESCAPED_UNICODE);
    	$ch = curl_init();
    	curl_setopt_array($ch, array(
	    	CURLOPT_URL            => $apiUrl,
	    	CURLOPT_POST           => true,
	    	CURLOPT_POSTFIELDS     => $bodyStr,
	    	CURLOPT_HTTPHEADER     => array(
	    		'Content-Type: application/json; charset=utf-8',
	    		'Authorization: Bearer ' . $ARK_API_KEY,
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
    	$response = empty($response) ? array() : json_decode($response, true);
    	$output = empty($response['output']) ? array() : $response['output'];
    	$textList = array();
    	foreach ($output as $row) {
    		if (empty($row['type']) || $row['type'] != 'message' || empty($row['content'])) {
    			continue;
    		}
    		$content = $row['content'];
    		foreach ($content as $value) {
    			if (empty($value['text']) && $value['type'] != 'output_text') {
    				continue;
     			}
     			$textList[] = $value['text'];
    		}
    	}
    	$text = implode(',', $textList);
    	return array(
    		'text' => $text,
    	);
    }
    
}