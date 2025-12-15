<?php
// 简介
$recommend = <<<BLOCK

<p class="report-tag-blue" style="margin-left: -12px !important;"><span>什么是创伤性事件？</span></p>
<p>创伤事件是指那些严重威胁生命或身体安全的经历，或者目睹他人（如亲人、朋友）遭受重大伤害的场景。</p>
<p>例如：网络暴力、暴力讨债、遭遇火灾、地震、车祸、抢劫、殴打、性侵等情境，且当时会伴随强烈的恐惧和无助感。</p>
<div class ='img-center'><img src="img17.png" /></div>
<p>创伤后应激障碍是一种因创伤引发的严重心理失调，可能会影响<span class="undelline">日常生活和社会功能，甚至引发自杀风险</span>。</p>
<p>如果你或者你认识的人经历过创伤事件，并且在长时间后仍难以适应当前的生活，务必<span class="undelline">重视并尽早寻求专业治疗</span>。</p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>《创伤后应激障碍测评（PCL—C）》是一种广泛用于筛查创伤后应激障碍的专业评估工具，具备良好的信效度。</p>
BLOCK;

$gain = array(
	'评估自己是否具备创伤后应激障碍的典型症状',
 	'获得心理咨询师的个性化辅导和建议',
);

// 适合谁测
$fit = array(

);

// 部分参考文献
$document = array(

);

return array(
    'theme' => 'pink', // 主题色{ }
    'recommend' => $recommend,
    'theory' => $theory,
    'gain' => $gain,
    'fit' => $fit,
    'document' => $document,
);
