<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-blue" style="margin-left: -12px !important;"><span>你是否有这样的体验？</span></p>
<p class="card-bg-purple">在与他人意见不合时容易妥协，害怕被拒绝，不敢表达自己的看法。</p>
<p class="card-bg-green">遇到喜欢的人时，第一反应是觉得自己配不上，对自己进行责怪，感觉自己不够好。</p>
<p class="card-bg-orange">缺乏自信，担心自己搞砸事情。</p>

<p>这些表现其实是“低自尊”的征兆。</p>
<div class ='img-center'><img src="img25.jpg" /></div>	
<p>自尊是我们对自己看法的核心组成部分。自尊水平高的人能够<span class="undelline">自我肯定</span>，保持<span class="undelline">乐观积极</span>的态度，而自尊水平低的人则容易<span class="undelline">产生焦虑</span>、<span class="undelline">抑郁</span>等负面情绪。</p>

<p>低自尊的成因有很多，最显著的是：</p>
<p class="card-bg-purple">原生家庭中的负能量过多。</p>
<p class="card-bg-green">成长过程中缺乏支持和赞赏。</p>

<p>这些因素最终导致了对自我的不喜欢和否定。</p>

<p>在“<span class="undelline">怀疑自己</span>——<span class="undelline">搞砸事情</span>——<span class="undelline">彻底否定自己</span>”的恶性循环中，我们的自尊水平会不断下降，成为人生中最大的限制。</p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本次测评将通过专业的方式全面评估你的自尊水平，并分析同类典型人物。通过对你的自尊状态的了解，我们将提供专业的心理学建议，帮助你摆脱低自尊的负面影响，提升自尊水平。</p>
BLOCK;
$gain = array(
	'准确的自尊心水平评估',
	'提供专业的心理学建议',
);

// 适合谁测
$fit = array(

);
// 部分参考文献
$document = array();

return array(
	'theme' => 'cyan', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
