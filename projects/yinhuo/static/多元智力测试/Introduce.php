<?php
// 简介
$recommend = <<<BLOCK

<p>智力通常被定义为人类<span class="undelline">学习新知识</span>和<span class="undelline">适应环境变化</span>的能力。它包括<span class="undelline">观察</span>、<span class="undelline">记忆</span>、<span class="undelline">想象</span>以及<span class="undelline">思考</span>等多方面的能力。智力的高低在一定程度上决定了一个人在社会中的成就。</p>
<div class ='img-center'><img src="img14.jpg" /></div>
<p>传统的智力理论认为，智力主要集中在<span class="undelline">语言能力</span>和<span class="undelline">逻辑数学能力</span>，并且是一种可以通过标准化测验量化的综合能力。</p>
<p>为了更全面地理解人类的智力，加德纳于1983年提出了多元智能理论。他将智力划分为8大类型：
<div class ='img-center'><img src="img13.jpg" /></div>		
加德纳认为这些智能相互独立，代表了人类处理信息的不同方式。</p>
BLOCK;

// 理论依据
$theory = <<<BLOCK
<p>本测评基于加德纳博士的多元智能理论，并结合中国文化背景精心设计，能够全面评估测试者在八大智能领域的表现。</p>
BLOCK;

$gain = array(
   '一份个性化的智力结构图，助您更好地展现自己的能力',
	'识别出您自身的优势智能，提升潜能',
	'指导您在生活和工作中发挥长处，提升潜能',
	'详细了解你的每个智能维度，并获得相应的建议'
);

// 适合谁测
$fit = array(

);

// 部分参考文献
$document = array(
	
);

return array(
    'theme' => 'purple', // 主题色{ }
    'recommend' => $recommend,
    'theory' => $theory,
    'gain' => $gain,
    'fit' => $fit,
    'document' => $document,
);
