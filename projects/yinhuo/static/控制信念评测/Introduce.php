<?php
// 简介
$recommend = <<<BLOCK
	
<p class="card-bg-purple">有人遇到困难时，总是归咎于运气不佳；</p>

<p class="card-bg-orange">而另一些人则会积极分析原因，寻找解决办法。</p>

<p>你是相信自己掌控生活，还是觉得外部因素如运气、环境决定了你的命运走向？</p>
<div class ='img-main'><img src="img122.png" /></div>
<p>心理学将人们分为两类：<span class="undelline">内控型</span>与<span class="undelline">外控型</span>。
<p>不同个体对自我控制感的认知差异显著，而这种认知会直接影响其<span class="undelline">情绪、动机、行为</span>等，进而对<span class="undelline">生活、工作、学习和健康</span>产生深远的间接作用。</p>

<p>那么，你的控制信念是哪种类型？怎样的生活态度更有益于身心健康？通过此测试，你将找到答案。</p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测评基于美国心理学家罗特教授的《控制信念量表》，并结合中国文化背景开发。旨在帮助你更好地了解自己的控制信念水平。</p>		
BLOCK;
$gain = array(
	
);

// 适合谁测
$fit = array(
	
);
// 部分参考文献
$document = array();

return array(
	'theme' => 'orange', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);