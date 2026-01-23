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
    public function chat($userId, $info)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	// 1. 系统角色提示词（固定）
    	$systemPrompt = <<<EOT
你是一个专业的文本润色助手，现在有一个任务：短视频字幕文本润色
需严格遵守以下规则完成润色任务：
1. 润色原则：保留原文核心语义，仅优化措辞、语法、表达流畅度，不增减核心信息；
2. 长度控制：润色后的文本长度需严格贴近指定的建议长度，误差不超过±5个字；
3. 数量要求：按照指定数量生成独立的润色结果，每组结果仅包含润色后的纯文本；
4. 输出格式：**仅返回纯JSON数组**，无任何多余文字说明，无任何序号、说明、标点外的冗余内容，格式为[\"润色结果1\",\"润色结果2\",...\"润色结果10\"]；
5. 语言要求：使用中文，表达自然、符合日常用语习惯，无生僻词。";
EOT;
    	
    	$userPrompt = <<<EOT
请对以下文本进行润色：
待润色文本：{$info['text']}
建议润色后长度：{$info['suggestTextLen']} 字左右
需要生成的润色结果数量：{$info['num']} 个
EOT;
    	
    	$apiUrl = 'https://ark.cn-beijing.volces.com/api/v3/responses';
    	$params = array(
    		'model' => 'doubao-seed-1-8-251228',
    		'input' => array(
    			array(
    				'role' => 'system',
    				'content' => array(
    					array(
    						'type' => 'input_text',
    						'text' => $userPrompt,
    					),
    				),
    			),
    			array(
    				'role' => 'user',
    				'content' => array(
    					array(
    						'type' => 'input_text',
    						'text' => $userPrompt,
    					),
    				),			
    			),
    		),
    	);


    	$volcConf = $this->frame->conf['volcengine'];
    	$bodyStr = json_encode($params, JSON_UNESCAPED_UNICODE);
    	$ch = curl_init();
    	curl_setopt_array($ch, array(
	    	CURLOPT_URL            => $apiUrl,
	    	CURLOPT_POST           => true,
	    	CURLOPT_POSTFIELDS     => $bodyStr,
	    	CURLOPT_HTTPHEADER     => array(
	    		'Content-Type: application/json; charset=utf-8',
	    		'Authorization: Bearer ' . $volcConf['arkApiKey'],
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