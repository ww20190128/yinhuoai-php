<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-purple" style="margin-left: -12px !important;"><span>MBTI 16种人格探索</span></p>
<p>人们常说：<span class="undelline">性格决定命运</span>，了解你的性格可以帮助你找到最适合的<span class="undelline">工作</span>、<span class="undelline">爱情</span>和<span class="undelline">婚姻</span>。</p>

<p>在职业和事业上，往往<span class="undelline">选择比努力更重要</span>。同样在爱情和婚姻中，性格决定关系的成败，合适的伴侣决定幸福。</p>

<div class="card-bg-purple">
<p>“世上最难的事便是认识自己。许多简单的道理，往往因缺乏自我认知而错失良机。”</p>
<p style="text-align: right !important;margin-bottom: 0px !important;">——泰勒斯</p></div>

<p>了解自己的性格，不仅是为了接受命运，更是为了提升自我认知，理解他人，化解生活中的矛盾与冲突，最终获得职业成功与婚姻幸福。</p>
<div class ='img-main'><img src="01.jpg" /></div>
		
<div class="card-bg-purple"><p>你的性格究竟如何？</p></div>
<div class="card-bg-orange"><p>什么样的伴侣与你最相配？</p></div>
<div class="card-bg-green"><p>你的性格优势是什么？</p></div>
<div class="card-bg-purple"><p>哪些职业最符合你？</p></div>

<p>MBTI测试将人格划分为16种类型，及<span class="undelline">动力</span>、<span class="undelline">信息处理</span>、<span class="undelline">决策模式</span>和<span class="undelline">生活方式</span>四大关键维度。</p>
<p>通过本测试将综合分析你的爱情性格与职业性格，帮助你找到最佳职业方向与人生伴侣。</p>
<div class ='img-center'><img src="mbti01.png" /></div>
BLOCK;

// 理论依据
$theory = <<<BLOCK
<p>本测评基于迈尔斯布里格斯类型指标（MBTI）以及荣格的《心理类型》理论，同时参考临床心理学家Alexander Avila的恋爱类型理论，并结合中国的文化背景研发而成。</p>
<p>MBTI人格测试已在全球流行逾百年，是经典的心理学人格分析工具。通过MBTI测试深入了解自己的性格、爱情观与择业观。</p>
BLOCK;

// 你将获得
$gain = array(
	'发现你的性格类型' => '通过内外向、直觉、情感与行动风格测试，确定你的MBTI人格类型，分析你的优势与不足。',
	'专业的职业指南' => '分析你的职场优势与短板、评估你的人际互动模式，并提供适合你的行业及职位推荐。',
	'解析你的爱情观与婚姻观' => '揭示你在恋爱中的特质、潜在的爱情伴侣类型，并提供婚姻未来的趋势和个性化建议。',
);

// 适合谁测
$fit = array(
	'希望更加了解自己的人',
	'对自我认知感到困惑的人',
	'有志于改变和提升自我的人',
	'希望深入探索内在驱动力的人',
);

// 部分参考文献
$document = array();

return array(
	'theme' => 'blue', // 主题色{ }  紫褐色
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
