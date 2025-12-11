<?php
// 简介
$recommend = <<<BLOCK
<p>人们的生命中大约有三分之一的时间用于睡眠。</p>

<p>虽然睡眠对我们至关重要，但许多人却无法充分享受这一过程。</p>

<p>数据显示，中国的成人中有<span class="undelline">38.2%</span>面临失眠问题，意味着超过3亿人遭遇睡眠障碍。</p>

<p>特别是90后，<span class="undelline">84%</span>的人在睡眠方面受到了困扰，<span class="undelline">75%</span>的人在晚上11点后才入睡，三分之一则往往在凌晨1点才上床。</p>
<div class ='img-main'><img src="sleep.png" /></div>	
<p>你是否曾经：</p>

<p class="card-bg-purple">即使感到非常疲惫，仍然难以入睡；即使勉强入睡，也常常容易惊醒或过早醒来；</p>

<p class="card-bg-green">经常做恶梦，睡眠质量差，醒来仍感到疲惫；</p>

<p class="card-bg-orange">夜晚难以入眠，白天却感到困倦无力。</p>

<p>失眠不仅对你的日常生活和工作产生影响，还可能对健康造成威胁。</p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测评使用匹兹堡睡眠质量指数量表，依据专业评分标准评估你的睡眠质量，以帮助你了解自己的睡眠状况，并积极采取措施改善。</p>
BLOCK;
$gain = array(
	
);

// 适合谁测
$fit = array(
	
);
// 部分参考文献
$document = array();

return array(
	'theme' => 'blue', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
