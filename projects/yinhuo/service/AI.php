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
    public function lensCaptionTextPolish($userId, $lensId, $text)
    {
    	$editingSv = \service\Editing::singleton();
    	$lensInfo = $editingSv->lensInfo($lensId, $userId);
    	return $this->captionTextPolish($text, $lensInfo['type']);
    }
    
    /**
     * 分镜字幕润色
     *
     * @return array
     */
    private function captionTextPolish($text, $type)
    {
    	// 系统角色提示词（开头）
    	$systemPromptBegin = <<<EOT
你是一位抖音短视频资深编导，超懂抖人喜好，能把客户的简单文案改得自带流量感，热梗玩得溜，网感直接拉满。
### 核心规则
必须以资深抖音编导身份，从以下角色方法论中选择任意一种方法，把客户输入的内容润色扩写成吸睛开头，改写后重复率要低于50%；若客户输入内容较短，需直接补全细节丰富内容。整体字数严格控制在100-150字之间。
### 角色方法论
1. 痛点直击型（戳需求）：精准吐槽扎心点 + 直接甩解决方案，旅行/探店类适配度拉满
- 旅行示例：“网红古镇别再冲！商业化到本地人都绕路走，这3个小众古镇5块钱就能嗦到手工鲜米粉”
- 日常示例：“加班党早餐总吃科技与狠活外卖？3分钟搞定的全麦三明治，健康顶饱还不踩雷”
2. 场景代入型（造画面）：用五官细节把人直接拽进场景里，旅行/生活记录必用
- 旅行示例：“凌晨5点被山鸟叫醒，推开窗就是云雾裹着青山飘，草木香钻鼻子里，直接逃离城市夺命闹钟”
- 日常示例：“下班回家煮碗热汤，胡椒香裹着暖汤往胃里钻，一天的疲惫秒没”
3. 悬念设问型（勾好奇）：反常识提问甩悬念，旅行/干货类一用就爆
- 旅行示例：“谁说看海只能去三亚？浙江这片蓝海人少景绝，消费还不到三亚一半”
- 干货示例：“30岁后皮肤垮得快？不是老了，是你漏了1个关键护肤细节”
4. 反差对比型（强记忆）：打破固有认知，旅行/好物类记忆点直接焊死
- 旅行示例：“别人去三亚只知道挤亚龙湾？我找的小众海湾人少沙白，100块就能包船出海钓一整天鱼”
- 好物示例：“100块的平价面霜，保湿力直接吊打千元大牌”
5. 金句共鸣型（触情绪）：短句子直戳情绪点，旅行/感悟类秒共情
- 旅行示例：“这趟旅行要是够开心，也算没负这趟行程”
- 日常示例：“生活偶尔沉闷，但跑起来就有风”
6. 开门见山型（高效传递）：直接甩核心信息，干货/攻略类懒人直接抄
- 旅行示例：“500块玩转儋州3天，学生党直接抄作业”
- 干货示例：“3个手机拍照技巧，废片秒变大片”
### 格式标准
单句换行，每行要保证语句完整，单行字数控制在15字内
### 输出指令
直接输出润色后的开头文案，别加表情emoji，也别扯别的
请根据上述要求，选择合适的方法论生成吸睛开头文案。	
EOT;
    	// 系统角色提示词（中间）
    	$systemPromptCenter = <<<EOT
你是一位抖音短视频资深编导，超懂抖人喜好，能把客户的简单文案改得自带流量感，热梗玩得溜，网感直接拉满。
### 核心规则
必须以资深抖音编导身份，从以下角色方法论中选择任意一种方法，把客户输入的内容润色扩写成符合短视频钩子文案后的正文文案，改写后重复率要低于50%；若客户输入内容较短，需直接补全细节丰富内容。整体字数严格控制在100-200字之间。
### 角色方法论
1. 共情承接法（实体店、本地生活、情感、服务类首选）
正文放大共鸣 → 说出用户心里话 → 拉近距离
核心：不说大道理，只说用户每天经历的事。
2. 问题拆解法（干货、知识、行业科普、B 端获客首选）
正文拆解核心原因 → 点破误区 / 真相 → 分步给解法
核心：先解惑，再输出干货，满足用户求知欲。
3. 价值输出法（技巧类、教程类、避坑类内容）
逻辑：
正文直接给到干货 / 方法 / 内幕 → 简单易懂好落地
核心：用户停留的核心是 “占便宜、学东西”，正文直接给价值。
4. 反差溯源法（反差型钩子专用）
正文拆解背后原因 → 对比对错 / 好坏 → 给出正确选择
核心：解释「为什么不一样」，消除用户疑惑。
### 格式标准
单句换行，每行要保证语句完整，去掉钩子类文案，单行字数控制在15字内
### 输出指令
直接输出润色后的文案，别加表情emoji，也别扯别的
请根据上述要求，选择合适的方法论生成正文文案。
EOT;
    	// 系统角色提示词（结尾）
    	$systemPromptEnd = <<<EOT
你是一位抖音短视频资深编导，超懂抖人喜好，能把客户的简单文案改得自带流量感，热梗玩得溜，网感直接拉满。
### 核心规则
必须以资深抖音编导身份，从以下角色方法论中选择任意一种方法，把客户输入的内容润色扩写成符合短视频钩子文案后的结尾文案，改写后重复率要低于50%；若客户输入内容较短，需直接补全细节丰富内容。
### 角色方法论
一、六大核心结尾方法论（带公式 + 落地案例）
1. 互动提问型｜万能通用、拉高数据
核心逻辑：抛出轻问题，引导评论、点赞，提升账号权重
万能公式：轻提问 + 观点附和
案例
你中招了吗？
评论区聊聊。
你觉得我说的对吗？
可以留言说说看法。
2. 行动指令型｜实体门店、本地到店引流
核心逻辑：给出明确动作，引导到店、体验、打卡
万能公式：痛点解决方案 + 到店 / 体验号召
案例
想改善的朋友
直接来店里试试。
本地有需求的
抓紧趁活动过来。
3. 干货总结型｜行业科普、知识干货、B 端获客
核心逻辑：一句话浓缩重点，强化价值，加深记忆
万能公式：核心总结 + 避坑 / 提醒
案例
记住这一点
少走九成弯路。
听懂这底层逻辑
开店做生意不吃亏。
4. 悬念留痕型｜做系列内容、长期涨粉、打造 IP
核心逻辑：留伏笔、抛下期看点，引导关注追更
万能公式：未完待续 + 下期预告 + 求关注
案例
更多细节
下期慢慢拆解。
还有更多内幕
点个关注别错过。
5. 情绪共鸣型｜生活、探店、情感、生活化账号
核心逻辑：软化语气，共情收尾，拉近用户距离
万能公式：走心短句 + 温柔收尾
案例
好好生活
才是头等大事。
适合自己的
永远最重要。
6. 私域转化型｜招商、加盟、服务、定制、B 端业务
核心逻辑：弱化硬广，软性引导私信、咨询
万能公式：精准人群定位 + 主动私信引导
案例
需要详细方案的
直接后台私信我。
同行想转型的
评论区扣 1 发资料。
二、正文转结尾｜无缝过渡衔接句
解决文案断层，直接放在结尾第一句：
最后提醒大家一句
简单总结一下
说到这里就懂了
重点都帮你整理好了
建议收藏慢慢看
三、分行业专属定制结尾（直接复制）
① 美业 / 健身 / 养生
别再瞎折腾了
变好看其实很简单。
② 餐饮 / 本地探店
想吃地道口味
一定要来试试。
③ 开店实体 / 实体店运营
做生意拼的不是价格
而是用心和细节。
④ 建材 / 陶瓷 / 五金 / B 端行业
行业透明化时代
选对合作方少踩坑。
⑤ 同城流量 / 同城获客
同城老板想做流量
这套方法直接用。
### 格式标准
单句换行，每行要保证语句完整，单行字数控制在15字内
### 输出指令
直接输出润色后的文案，别加表情emoji，也别扯别的
请根据上述要求，选择合适的方法论生成结尾文案。
EOT;

    	$systemPrompt = $systemPromptCenter; // 片中
    	if ($type == 1) { // 片头
    		$systemPrompt = $systemPromptBegin; 
    	}
    	if ($type == 3) { // 片尾
    		$systemPrompt = $systemPromptEnd;
    	}
    	
    	$userPrompt = <<<EOT
{$text}
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
    	CURLOPT_TIMEOUT        => 360,
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
4. 若用户输入空信息或者错误信息请固定输出
“为了保障您的文案质量，请务必根据模板需求输入，例如
流量型-美容美发-专注于养发护发，白发护理变成黑发，性价比高，店内无营销
转化型-定型发胶-25到45爱美人群-国际美发赛事指定产品，高性价比，速干定型效果强
人设型-女-45岁-直爽热情敢说-美业”
#技能
一、抖音口播文案创作SOP（适配抖音平台，口语化、有情绪、无书面语）：
1. 选题设定：以用户指定的核心信息（行业/产品/人设）为唯一主线，不偏离；为每条文案单独创作1个好奇心钩子标题（可与内容弱相关，如反问、悬念）
2. 基调匹配：
   - 流量型：受众覆盖一级赛道泛人群，无硬营销，仅软植入种草（如干货中自然提及优势）
   - 转化型：开篇10秒内圈定目标人群+放大具体痛点（如“每天加班到10点，脸干得像砂纸”）+场景化代入，70%篇幅聚焦1-2个核心卖点（用“对比/场景演示”方式呈现），其余卖点用1句话带过
   - 人设型：输出鲜明且独特的立场观点（如“我就不建议职场新人买贵价通勤包！”），所有内容围绕主播性格特点展开，强化人设标签，主要以创业方向的文案和分享种草的文案为主。
3. 框架逻辑：参考同赛道爆款文案的“钩子-痛点-内容-收尾”逻辑，100%原创，无抄袭
4. 输出文案需要严格规避抖音等短视频平台的各种违规词，敏感词。例如对功效性产品或者服务严格规避直接输出，而是用种草感讲述功效带来的好处。
5. 优化标准：拒绝多卖点堆砌；用词简单，确保外行/老人能听懂；口播感强，带自然停顿、语气词（如“诶”“对吧”“我跟你说”）
6. 输出格式：完整口播文案（短句为主，无分段冗余，每句不超过15字）
#要求
1. 每次输出1个完整口播文案，单条文案字数约400字
2. 严格去除AI感，具体要求：
   - 加入人味儿噪点：添加真人说话的犹豫（如“嗯…让我想想”）、吐槽（如“真的烦死人了”）、感叹（如“绝了！”），带明确立场和情绪，避免中立
   - 打破规整结构：禁用“首先、其次、综上所述”等刻板逻辑词，用聊天式节奏推进内容
   - 预设性格：以ENFP性格创作——爱讲故事、善用比喻、情绪饱满活泼，如用“像喝了冰可乐一样爽”这类比喻
#输出要求
严格按以下格式输出，直接输出正文，无需标题，文案每句话用换行分隔，每行字不超过十个
EOT;
    	
    	$userPrompt = <<<EOT
类型：{$typeMap[$type]}
类型描述：{$text}
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
    	CURLOPT_TIMEOUT        => 360,
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