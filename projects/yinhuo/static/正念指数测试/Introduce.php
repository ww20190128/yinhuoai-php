<?php
// 简介
$recommend = <<<BLOCK
<p>想要获得一个充实而幸福的人生，<span class="undelline">情绪管理</span>是关键。情商（EQ）是实现这一目标的重要能力，而<span class="undelline">正念</span>则是提升情商的有效工具。</p>
<div class="bg-title"><span>什么是正念？</span></div>
<p><span class="undelline">正念</span>源自佛教，意指一种<span class="undelline">稳定的心态</span>。正念是一种有意识的状态，它让我们在没有过多情绪干扰和判断的情况下，专注于当前的时刻。正念在现代心理治疗中扮演着重要角色。</p>
<div class ='img-center'><img src="img11.jpg" /></div>	
<p class="report-tag-blue" style="margin-left: -12px !important;"><span>高正念水平的人有哪些独特之处？</span></p>		
<p>研究发现，正念水平高的人对疼痛的敏感性较低。例如，同样是被热水烫到，有些人会感到剧痛，而高正念水平的人则可能感觉不明显。提升正念可以有效缓解疼痛。</p>
<p>高正念水平的人还具备<span class="undelline">较强的抗压能力</span>。他们和普通人一样面临压力，但知道如何积极应对，而不是自责或强迫自己。他们理解焦虑和担忧没有帮助，因此能够冷静处理这些情绪。</p>
<p>科学研究显示，正念对情商（EQ）有显著影响。它帮助你与情绪保持距离，使你不被情绪直接主导。你可以选择自己想要的情绪，就像在超市挑选商品一样。</p>
		
<div class ='img-main'><img src="img10.jpg" /></div>		
<p>正念是提升自我意识和自我调节的良好方式。随着这些技能的发展，你的<span class="undelline">社交能力</span>、<span class="undelline">自我激励</span>和<span class="undelline">同情心</span>也将显著提升。</p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测评基于露丝·贝尔（Ruth Baer）教授的5因素理论，并结合中国文化和语言特点研发而成。</p>
BLOCK;
$gain = array(
	'自己真实的正念水平',
	'正念五个维度上的深度解读',
	'针对性的提升建议',
	'帮助你增强正念，减少无谓的思绪，远离烦恼'
);

// 适合谁测
$fit = array(
	
);
// 部分参考文献
$document = array(

);

return array(
	'theme' => 'orange', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
