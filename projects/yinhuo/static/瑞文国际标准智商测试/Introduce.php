<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-blue" style="margin-left: -12px !important;"><span>学习好 = 智商高？</span></p>
<div class ='img-center'><img src="img01.png" /></div>
<p>有些人在学习中能迅速掌握知识，成绩优秀，而有些人则进步缓慢，成绩不佳。</p>
<p class="report-tag-purple" style="margin-left: -12px !important;"><span>工作出色 = 智商高？</span></p>
<p>在工作中，有些人总是表现出色，而有些人尽管努力了也难很以达到他人的水平。</p>
<p>例如：相同的道理，有些人几分钟就能领悟，而有些人可能在错误的方向上挣扎多年才会顿悟。</p>

<p class="report-tag-gray" style="margin-left: -12px !important;"><span>难道是我不够努力？</span></p>
<p>并不完全如此，智力差异也是一个重要因素。由于每个人的生理结构和发育水平不同，智力表现也有所差异。</p>
		
<div class ='img-center'><img src="瑞文.png" /></div>
<p>瑞文智商测试将从<span class="undelline">知觉识别</span>、<span class="undelline">比较推理</span>、<span class="undelline">判断推理</span>、<span class="undelline">比拟嵌套</span>以及<span class="undelline">抽象推理</span>等五个维度来综合评估您的智力水平，解决您的疑惑。</p>

BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>瑞文智商测评的理论基础主要源于英国心理学家斯皮尔曼的智力理论。斯皮尔曼教授将智力分为<span class="undelline">G因素</span>和<span class="undelline">S因素</span>。</p>
<p><span class="undelline">G因素（general factor）</span>是指一般智力因素，在所有智力活动中都有体现，虽然每个人都有G因素，但水平各有异。</p>
<p><span class="undelline">S因素（special factor）</span>是指个体的特殊智力因素，每个人的S因素也有所不同。</p>
BLOCK;


$gain = array(
	'深入了解你的智力水平，识别出你的强项和弱项，并明确哪些领域需要提升。',
	'发现智力发展中的薄弱环节，并提供针对性的指导建议，促进你的智力全面发展。',
	'了解你在群体中的智力表现，比较自己与他人的优势和劣势。',
);

// 适合谁测
$fit = array(
	'对智力测试感兴趣的人',
	'希望评估自己智力水平的人',
	'希望挖掘自己在智力优势的人',
	'需要找到自己智力发展方向的人',
);
// 部分参考文献
$document = array(
	'[1] Progressive Matrices[M]/Encyclopedia of Clinical Neuropsychology. 2017.'
);

return array(
	'theme' => 'orange', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
