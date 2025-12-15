<?php
// 简介
$recommend = <<<BLOCK

<p class="report-tag-gray" style="margin-left: -12px !important;"><span>面对职业选择，你是否常感困惑？</span></p>
<div class ='img-center'><img src="困惑.png" /></div>
<div class="card-bg-purple"><p>不清楚自己到底适合从事哪类职业？</p></div>
<div class="card-bg-green"><p>求职困难，找到专业对口的工作更加困难？</p></div>
<div class="card-bg-orange"><p>在工作中无法发挥自己的优势、特长？</p></div>
<div class="card-bg-purple"><p>我的兴趣和天赋究竟适合哪个领域？</p></div>

<p class="report-tag-blue" style="margin-left: -12px !important;"><span>如何找到一份适合自己的工作？</span></p>
<p class="color-red border-left">正确的选择往往比努力更重要！</p>

<p>美国著名职业心理学家约翰·霍兰德认为“兴趣是人类行为的重要驱动力，选择符合自己兴趣的职业，能够提升工作的积极性，并提高事业成功的几率。”</p>
		
<p>霍兰德将人格分为六类：<span class="undelline">现实型</span>、<span class="undelline">社会型</span>、<span class="undelline">企业型</span>、<span class="undelline">常规型</span>、<span class="undelline">研究型</span>和<span class="undelline">艺术型</span>，每个人的性格都是这六种类型的不同组合，且每个人的职业倾向和性格有着密切的关系。</p>
<div class ='img-main'><img src="img07.jpg" /></div>

<p>本测试通过这六大维度为你提供准确的职业兴趣分析，<span class="undelline">帮助你找到最适合的职业方向，做出明智的职业选择</span>。</div>
BLOCK;

// 理论依据
$theory = <<<BLOCK
<p>本测评基于《霍兰德职业兴趣测试（Self-Directed Search）》，并结合霍华德·加德纳提出的「<span class="undelline">多元智力理论</span>」，同时参照国内职业现状和社会文化背景研发而成。</p>
<p>本测评报告中生成的“职业推荐”来源于世界500强企业数据库，并结合国内上市公司的人力资源数据进行优化，为你提供可靠的职业方向建议。</p>
BLOCK;

$gain = array(
	'详尽的职业规划和发展建议',
	'权威数据分析，精准的职业推荐',
	'认识你的职业兴趣、能力特长与天赋',
	'辅助你在升学、就业、职业转换中做出明智的决策',
);

// 适合谁测
$fit = array(
	'工作方向感到迷茫的人',
	'处于职业转型阶段的人',
	'对现有工作感到不满的人',
	'需了解自身职业优势及兴趣的人',
	'企业人力资源相关领域的职业人员',
);

// 部分参考文献
$document = array(
	'[1]张晶. 霍兰德职业兴趣测验在大学生职业生涯规划中的应用[J]. 中国培训. 2016.',
	'[2]罗峥，薛海平，李姝颖，邢悦盈，职业兴趣测验在高中生专业选择指导中的应用[J]. 教育科学研究. 2019.',
	'[3]Schelfhout Stijn, Wille Bart. The effects of vocational interest on study results: Student person environment fit and program interest diversity[J]. PloS one. 2019.',
);

return array(
	'theme' => 'gray', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);