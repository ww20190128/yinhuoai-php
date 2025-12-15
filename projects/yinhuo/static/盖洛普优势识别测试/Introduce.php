<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-blue" style="margin-left: -12px !important;"><span>什么是优势？</span></p>
<p>优势并非仅仅是智商或某项特定技能，而是与生俱来的一种内在潜质。</p>

<p>它表现为<span class="undelline">特定的思维</span>、<span class="undelline">情感</span>和<span class="undelline">行为模式</span>，自然且经常地出现，像刻在基因中的能力，无需特别练习就能出类拔萃。</p>
<div class ='img-main'><img src="优秀.png" /></div>
<p>许多人一生都在努力弥补自己的短板，但实际上，从不足变到平庸所需的努力，远远超过从优秀迈向卓越的精力。</p>
<div class="card-bg-purple">
<p>“唯有借助优势，才能通向卓越之路。”</p>
<p style="text-align: right !important;">——现代管理学大师彼得·德鲁克</p>
</div>

<p class="report-tag-purple" style="margin-left: -12px !important;"><span>如何识别自己的优势？</span></p>
<div class ='img-center'><img src="img40.png" /></div>
<p><p><span class="undelline">盖洛普优势测试</span>从多个维度深入分析，帮助你识别核心优势与盲点，助力你发挥优势，并减少劣势对你的影响。</p>
<p class="report-tag-red" style="margin-left: -12px !important;"><span>如何发挥自己的优势？</span></p>		

<p><span class="undelline">盖洛普优势测试</span>全面分析你的34项才干，提供量身定制的成长方案，助你发挥优势，  突破自我，实现持续发展，并促进个人成长。</p>
<div class ='img-main'><img src="优势2.png" /></div>
BLOCK;

// 理论依据
$theory = <<<BLOCK
<p>本测评由盖洛普公司前董事长、优势心理学创始人唐纳德·克利夫顿带领团队，对全球200万名成功人士进行调研，耗时30年研究总结而成。</p>
<p>测评涵盖<span class="undelline">执行力</span>、<span class="undelline">战略思维</span>、<span class="undelline">影响力</span>及<span class="undelline">关系建立</span>四大领域，帮助你评估自己在专注、战略、领导和交际等34项才干中的表现，并提供相应的提升建议。</p>
<p>迄今为止，已有超过2600万人参与测试，它不仅能帮助个人发现自身优势，也是众多世界500强企业团队建设的核心工具之一。</p>
BLOCK;

$gain = array(
    '优势与盲点' => '通过盖洛普34项优势的深入分析，帮助你识别核心优势与盲点，助力你发挥优势，并减少劣势对你的影响。',
    '职业发展方向' => '根据你的优势，为你量身定制职业发展建议，帮助你在职场中找到最适合自己的方向，释放潜力。',
    '成长策略' => '根据个人才干评估，提供量身定制的成长方案，助你发挥优势，突破自我，实现持续发展。',
);

// 适合谁测
$fit = array(
	'渴望发掘自身潜力和天赋的人',
	'希望了解自己思维模式与偏好的人',
	'想要深入了解自己并获得成长的人',
	'希望激发潜能、拓展更多可能性的人',
);

// 部分参考文献
$document = array(

);

return array(
	'theme' => 'blue', // 青色
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
