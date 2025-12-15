<?php
// 简介
$recommend = <<<BLOCK
<p>愤怒的情绪会使个体更易冲动和具攻击性，从而导致人际冲突和关系破裂。长期愤怒还可能增加<span class="undelline">高血压</span>和<span class="undelline">心脑血管疾病</span>的风险。</p>
<div class ='img-center'><img src="img15.png" /></div>
<p>虽然愤怒看似是负面的情绪，但实际上它也有其<span class="undelline">积极</span>的一面。</p>
<p>适度的愤怒能增强个人的力量和勇气，有助于有效地进行自我抗争和维护权益。同时，合理表达愤怒也有助于提升个人的<span class="undelline">说服力</span>和<span class="undelline">威信</span>，这是一种从祖先遗传下来的“隐性技能”。</p>
<p>因此，在适当的情况下展现愤怒，并以合适的方式表达出来，例如积极应对和解决问题，是有益的。</p>
<p>然而，过度的易怒和冲动可能会让你与世界为敌，或陷入持久的沮丧，这会<span class="undelline">显著影响你的生活质量和体验</span>。</p>

<div class ='img-center'><img src="易怒.jpg" /></div>
<p>了解你自己的易怒程度，是控制这一情绪的关键。</p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测试基于加州大学尔湾分校的诺瓦克（Raymond W. Novaco）教授开发的量表，并结合了中国文化背景进行调整。它旨在帮助你准确评估自己的易怒水平，并理解愤怒情绪的主要根源。</p>
BLOCK;
$gain = array(
	'理解愤怒情绪的根源',
	'为你提供个性化的建议',
	'改善你的人际关系和生活质量'
);

// 适合谁测
$fit = array(
	'希望获得稳定情绪的人',
	'希望改善人际关系的人',
	'希望提高生活质量的人'
);

// 部分参考文献
$document = array(
	// 未填充
);

return array(
	'theme' => 'orange', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
