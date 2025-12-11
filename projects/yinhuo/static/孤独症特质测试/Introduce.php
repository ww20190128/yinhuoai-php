<?php
// 简介
$recommend = <<<BLOCK
<p>似乎不知不觉中，越来越多的人感到孤独，孤独仿佛成为了日常的一部分。<span class="undelline">为什么孤独感在人群中越来越普遍？</span></p>
<div class ='img-center'><img src="img22.png" /></div>	
<p>常言道，年纪越大，越容易感到孤单。而随着我们的成长，孤独感似乎也从未远离我们。</p>

<p>数据显示：近80%的人时常感到孤独；超过70%的人认为“<span class="undelline">孤独已成为常态</span>”。</p>

<p>研究表明，与20年前相比，人们的孤独感明显增加，尤其是我国的老年人，随着时间的推移，孤独感也呈上升趋势。</p>

<p class ='color-bule'>孤独逐渐成为现代生活中不可忽视的心理状态。</p>

<p><span class="undelline">孤独感是一种内心封闭的状态</span>，当一个人感受到与外界隔绝或被排斥时，常常会产生这种负面的情绪。尤其当渴望交际却无法满足时，孤独感就会愈发强烈。</p>
BLOCK;

// 理论依据
$theory = <<<BLOCK
<p>本测评基于UCLA孤独量表，并结合中国文化背景设计，精准评估测试者的孤独感指数，帮助您了解自己的孤独程度，并提供针对性的指导与建议。</p>
BLOCK;

$gain = array(
	'您的孤独感指数精准评估',
	'提供针对性的指导与建议',
);

// 适合谁测
$fit = array(
	
);

// 部分参考文献
$document = array();

return array(
	'theme' => 'purple', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
