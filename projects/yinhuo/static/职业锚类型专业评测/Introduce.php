<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-gray" style="margin-left: -12px !important;"><span>您是否有如下困扰？</span></p>
<div class ='img-main'><img src="img02.jpg" /></div>
<p class="border-left"><span>待遇不佳、职位低：</span></p>
<p class="card-bg-purple">在工作了几年后，发现自己依旧在原地踏步，看着周围的同事晋升加薪，而自己却依旧领取微薄的薪水。</p>
<p class="border-left">加班过多：</p>
<p class="card-bg-purple">工作似乎永无止境，会议不断，差旅频繁，即使是休息时间，也难以摆脱接听电话的束缚。</p>
<p class="border-left">工作压力大：</p>
<div class="card-bg-purple">
<p>销售目标越来越高，工作量不断增加，除了工作制服，几乎没有机会穿上自己喜爱的衣物。</p>
</div>
<p class="report-tag-purple" style="margin-left: -12px !important;"><span>为什么会这样？</span></p>
<p>社会压力不断增大，使得许多人在面对工作时总是表现出苦闷和不满。</p>
<p>无论是抱怨加班多还是待遇差，大家的职业幸福感似乎都在下降。</p>
<p>这种情况与我们的<span class="undelline">职业价值观</span>有密切关系。</p>
<p><span class="undelline">职业价值观</span>，也称为<span class="undelline">职业锚</span>或职业系留点，是在职业选择和发展过程中不可或缺的核心因素。它指的是在面临选择时，您所坚守的、无法放弃的职业中的重要价值。职业锚反映了<span class="undelline">个人能力</span>、<span class="undelline">动机</span>和<span class="undelline">价值观</span>的结合，一旦确定，通常不易改变。</p>
<p class="report-tag-blue" style="margin-left: -12px !important;"><span>如何破局？</span></p>
<p>职业锚测评通过分析过去行为和未来目标，帮助您更好地认识自我，并做出符合价值观的职业决策。</p>
<p>了解职业锚，让您在选择时更清楚自己的能力和方向，确保工作与价值观匹配，进而让您的职业成为长期追求。</p>
<p>职业锚测评是国际上<span class="undelline">应用广泛</span>且<span class="undelline">效果显著</span>的职业测评工具之一，帮助个人和组织规划职业生涯。</p>
		
BLOCK;
//style="margin-left: -12px !important;"
// 理论依据
$theory = <<<BLOCK
<p>职业锚测评（Career Anchor Questionnaire）由美国麻省理工学院斯隆管理学院的著名职业指导专家埃德加·H·施恩（Edgar H. Schein）教授领导的研究小组开发。</p>
		
<p>该理论基于对斯隆管理学院44名MBA毕业生长达12年的职业生涯研究，包括面谈、跟踪调查、公司调查和人才测评等多种方式，总结出八种职业锚。</p>
BLOCK;
$gain = array(
    '了解自己优势与盲区' => '识别您的核心优势与盲点，帮助您充分发挥长处并改进不足之处。',
    '专业的职业指导建议' => '为您推荐适合的职业方向，助你您职业生涯中充分发挥潜力，实现个人价值。',
);

// 适合谁测
$fit = array(
	'对职业发展感到迷茫的人',
	'希望深入了解自己并获得成长的人',
	'希望激发潜力、探索更多可能性的人',
);
// 部分参考文献
$document = array(

);

return array(
	'theme' => 'gray', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
