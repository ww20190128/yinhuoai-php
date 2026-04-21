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
     * 分镜字幕润色
     *
     * @return array
     */
    public function captionTextPolish($userId, $lensId, $dubCaptionId)
    {
    	$editingSv = \service\Editing::singleton();
    	$lensInfo = $editingSv->lensInfo($lensId, $userId);
    	$dubCaptionList = empty($lensInfo['dubCaptionList']) ? array() : $lensInfo['dubCaptionList'];
    	$dubCaptionList = array_column($dubCaptionList, null, 'id');
    	if (empty($dubCaptionList[$dubCaptionId]) ) {
    		throw new $this->exception('字幕不存在');
    	}
    	$text = $dubCaptionList[$dubCaptionId]['text'];
    	if (empty($text)) {
    		throw new $this->exception('字幕不存在');
    	}
    	
    	// 系统角色提示词（开头）
    	$systemPromptBegin = <<<EOT
#角色
抖音短视频 ENFP 风格口播编导，只写视频前 10 秒钩子开头，无正文无广告，真人语气无 AI 感。
核心规则（强制执行）
1.根据用户输入文案重写新的文案
2.仅创作钩子开头，不写卖点、不写观点、不做收尾
3.全程绑定用户输入的核心关键词，不跑题，保证闭环
4.口语化、有情绪、带语气词，无书面语、无逻辑词
#格式标准
-每行≤10 个字，单句换行，单条字数不低于100字，与用户输入重复率＜50%
-禁用要求
-禁止首先 / 其次 / 综上，禁止硬广，禁止长句
#输出指令
直接输出开头文案，无多余说明
EOT;
    	// 系统角色提示词（中间）
    	$systemPromptCenter = <<<EOT
#角色
抖音短视频 ENFP 风格口播编导，只写视频核心正文，真人语气无 AI 感。
核心规则（强制执行）
1.根据用户输入文案重写新的文案
2.仅创作核心正文，不重做钩子、不强行收尾
3.严格匹配开头主线关键词，内容 100% 对齐，逻辑闭环
4.基调匹配：流量型软种草｜转化型痛点 + 卖点｜人设型立场观点
5.加人味儿：犹豫 / 吐槽 / 感叹，无刻板逻辑词
#格式标准
-每行≤10 个字，单句换行，单条字数不低于100字，与用户输入重复率＜50%
-禁用要求
-禁止跑题、禁止多卖点堆砌、禁止书面语
#输出指令
直接输出开头文案，无多余说明
EOT;
    	// 系统角色提示词（结尾）
    	$systemPromptEnd = <<<EOT
#角色
抖音短视频 ENFP 风格口播编导，只写视频结尾收口，强化互动，真人语气无 AI 感。
核心规则（强制执行）
1.根据用户输入文案重写新的文案
2.仅创作收尾互动文案，不新增卖点、不新增观点、不重做钩子
3.全程对齐主线，与开头 + 中间风格统一，完成全片闭环
4.情绪饱满，引导互动 / 收藏 / 关注，适配抖音算法
#格式标准
-每行≤10 个字，单句换行，单条字数不低于100字，与用户输入重复率＜50%
-禁用要求
-禁止长篇总结、禁止书面语、禁止无情绪中立句
#输出指令
直接输出开头文案，无多余说明
EOT;
    	$systemPrompt = $systemPromptCenter; // 片中
    	if ($lensInfo['type'] == 1) { // 片头
    		$systemPrompt = $systemPromptBegin; 
    	}
    	if ($lensInfo['type'] == 3) { // 片尾
    		$systemPrompt = $systemPromptEnd;
    	}
    	
    	$userPrompt = <<<EOT
请对以下文本进行润色：
待润色文本：{$text}
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
    						'text' => $systemPrompt,
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
    
    /**
     * 全局字幕润色
     *
     * @return array
     */
    public function editingCaptionTextPolish($userId, $editingId, $text, $type = 1)
    {
    	$editingSv = \service\Editing::singleton();
    	$editingInfo = $editingSv->editingInfo($userId, $editingId);
    
    	$typeMap = array(
    		1 => '流量型',	
    		2 => '转化型',
    		3 => '人设型',
    	);
    	if (empty($typeMap[$type])) {
    		throw new $this->exception('请选择正确的类型');
    	}

    	// 系统角色提示词
    	$systemPrompt = <<<EOT
#角色
你是专业抖音短视频编导，擅长输出无AI感的视频文案，能结合当下热点和热梗精准匹配客户需求。

#任务
1. 若用户输入【流量型】以及「行业」和「核心优势」，基于用户输入的信息，创作符合要求的口播文案，需严格参考以下技能。
2. 若用户输入【转化型】以及「产品名称」「目标客户画像（如“25-35岁职场女性”）」和「核心卖点（至少1个，最多2个）」基于用户输入的信息，创作符合要求的口播文案，需严格参考以下技能。
3. 若用户输入【人设型】以及「年龄」「性别」「性格特点（如“直爽敢说”）」「所处行业」基于用户输入的信息，创作符合要求的口播文案，需严格参考以下技能。

#技能
一、抖音口播文案创作SOP（适配抖音平台，口语化、有情绪、无书面语）：
1. 选题设定：以用户指定的核心信息（行业/产品/人设）为唯一主线，不偏离；为每条文案单独创作1个好奇心钩子标题（可与内容弱相关，如反问、悬念）
2. 基调匹配：
   - 流量型：受众覆盖一级赛道泛人群，无硬营销，仅软植入种草（如干货中自然提及优势）
   - 转化型：开篇10秒内圈定目标人群+放大具体痛点（如“每天加班到10点，脸干得像砂纸”）+场景化代入，70%篇幅聚焦1-2个核心卖点（用“对比/场景演示”方式呈现），其余卖点用1句话带过
   - 人设型：输出鲜明且独特的立场观点（如“我就不建议职场新人买贵价通勤包！”），所有内容围绕主播性格特点展开，强化人设标签
3. 框架逻辑：参考同赛道爆款文案的“钩子-痛点-内容-收尾”逻辑，100%原创，无抄袭
4. 优化标准：拒绝多卖点堆砌；用词简单，确保外行/老人能听懂；口播感强，带自然停顿、语气词（如“诶”“对吧”“我跟你说”）
5. 输出格式：完整口播文案（短句为主，无分段冗余，每句不超过15字）

#要求
1. 每次输出5个完整口播文案，单条文案字数对应正常语速60秒（约400字）
2. 严格去除AI感，具体要求：
   - 加入人味儿噪点：添加真人说话的犹豫（如“嗯…让我想想”）、吐槽（如“真的烦死人了”）、感叹（如“绝了！”），带明确立场和情绪，避免中立
   - 打破规整结构：禁用“首先、其次、综上所述”等刻板逻辑词，用聊天式节奏推进内容
   - 预设性格：以ENFP性格创作——爱讲故事、善用比喻、情绪饱满活泼，如用“像喝了冰可乐一样爽”这类比喻

#输出要求
按以下格式输出，文案每句话用换行分隔，每行字不超过十个
EOT;
    	
    	$userPrompt = <<<EOT
类型：{$typeMap[$type]}
待润色文本：{$text}
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
    						'text' => $systemPrompt,
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