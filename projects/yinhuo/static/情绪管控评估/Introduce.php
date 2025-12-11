<?php
// 简介
$recommend = <<<BLOCK
<p>不同的人格，具备不同的情绪管控水平</p>
<div class ='img-main'><img src="img28.jpg" /></div>	
<p>金庸笔下的侠客们展现了各自独特的行为风格。</p>

<p>例如，杨过的处事方式：</p>

<p class="card-bg-purple">机智灵活，随机应变，不拘泥于细节</p>

<p>而郭靖的处事方式：</p>

<p class="card-bg-orange">坚守原则，忠于本心，始终如一</p>

<p>他们行为上的差异主要源于他们<span class="undelline">情绪管控</span>的能力不同。</p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>明尼苏达大学的心理学者马克·施耐德（Mark Snyder）进行了广泛的研究，探索了人格特质与环境适应之间的关系，并开发了《情绪管控测评量表》。</p>

<p>他的研究发现，<span class="undelline">高情绪管控者</span>会根据外部环境调整自己的行为，而<span class="undelline">低情绪管控者</span>则更依赖内心的价值观和原则。这种差异在社交场合中表现得尤为明显。</p>

<p class="card-bg-orange">你是否好奇自己在情绪管控方面的水平？</p>
<p class="card-bg-purple">不同的情绪管程度在社交、恋爱以及工作中会有什么不同的表现？
<p>通过这次测试，你将能找到答案。</p>

BLOCK;
$gain = array(
	
);

// 适合谁测
$fit = array(
	
);
// 部分参考文献
$document = array();

return array(
	'theme' => 'white', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);