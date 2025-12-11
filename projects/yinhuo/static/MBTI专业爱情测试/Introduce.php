<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-red" style="margin-left: -10px !important;"><span>你是否还对爱情感到迷茫？</span></p>
<p class="card-bg-orange">如何才能找到那个“对的人”？</p>		
<p class="card-bg-purple">我的性格适合和什么类型的人在一起？</p>
<p class="card-bg-gray">怎么才能拥有一段不会分手的感情？</p>
<p class="card-bg-green">我现在的伴侣真的是“对的人”吗？</p>
<div class ='img-main'><img src="111.jpg" /></div>			
		
<p>我们常常谈论“理想伴侣”，努力寻找那个“对的人”，但却总是与他们擦肩而过。</p>

<p>这是因为，我们对自己的“理想伴侣”并没有清晰的认知，因此也难以判断眼前的ta是否就是那个“对的人”。</p>
<div class ='img-main'><img src="112.png" /></div>		
<p>从“爱情性格理论”的角度来看，你的理想伴侣类型实际上是隐藏在你的个性与爱情偏好之中。</p>

<p>了解你的爱情性格，将帮助你明确你真正想要的，并适合你的恋人是什么样的人。</p>

BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测评基于迈尔斯布里格斯性格类型指标（MBTI）和美国人类学家 Helen Fisher 的爱情气质理论，结合性格维度与四种爱情气质类型，帮助你发现你的理想伴侣形象。</p>
BLOCK;
$gain = array(
  	'为你量身打造专属的恋爱建议',
    '从四大维度深入分析你的爱情偏好以及个性',
	'挖掘适合你的理想伴侣有哪些特点',
	'提供针对性的恋爱冲突化解策略',
);

// 适合谁测
$fit = array(
	'渴望一段甜蜜恋情的人',
	'想要一段稳固感情的人',
	'希望找到合适伴侣的人',
	'不清楚自己在感情中表现的人',
);

// 部分参考文献
$document = array(
  
);

return array(
    'theme' => 'pink', // 主题色
    'recommend' => $recommend,
    'theory' => $theory,
    'gain' => $gain,
    'fit' => $fit,
    'document' => $document,
);