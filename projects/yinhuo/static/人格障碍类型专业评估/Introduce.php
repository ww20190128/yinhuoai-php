<?php
// 简介
$recommend = <<<BLOCK
<div class="bg-title"><span>什么是人格障碍</span></div>
<div class ='img-main'><img src="人格障碍002.jpg" /></div>
<p>人格障碍是指行为模式明显<span class="undelline">偏离正常</span>并且<span class="undelline">深根固蒂</span>的特征。</p>
<p>大多数人或多或少都会受到人格障碍的影响。</p>
<p>据国外研究数据，人格障碍的患病率大多在<span class="undelline">2%</span>到<span class="undelline">10%</span>之间。这只是确诊数据，实际上，很多人格障碍者未曾接受诊断，因此未被统计。此外，还有很多人受到人格障碍影响但未察觉或未感受到。</p>


<div class="bg-title"><span>人格障碍的种类</span></div>
<div class ='img-main' style="margin-top: -30px !important;margin-bottom: -30px !important;"><img src="img09.png" /></div>
<p class="report-tag-gray" style="margin-left: -27px !important;"><span>常见的行为表现：</span></p>
<div class="list-warper sm">
<p>对他人过度怀疑或者不信任。</p>
<p>对社交活动缺乏兴趣，与人际往来极少。</p>
<p>情绪波动剧烈，喜欢吸引他人关注。</p>
<p>对自身评价过高，自信心强，喜爱别人的赞美。</p>
<p>不遵守规则，无视道德规范，易冲动和暴力。</p>
<p>情绪和行为不稳定，人际关系紧张。</p>
<p>社交恐惧，缺乏自信，消极自我认知。</p>
<p>容易依赖他人，对被遗弃感到敏感，需要额外关爱和照顾。</p>
<p>完美主义倾向和强烈的控制欲，固执且刻板。</p>
<p>表面上妥协接受，实际却有攻击性和敌对情感。</p>
<p>经常持悲观消极态度，对问题陷入悲伤抑郁。</p>
<p>对奇异或超自然现象敏感，表现出独特的行为习惯。</p>
</div>

BLOCK;
// 理论依据
$theory = <<<BLOCK
<div class ='img-main'><img src="人格障碍001.jpg" /></div>
<p>本测评依托于人格诊断问卷（PDQ），该问卷由美国Hyler博士根据DSM—III的诊断标准制定，并由我们专业的心理评估团队结合了中国人思维习惯进行了本土化调整，涵盖了抑郁型和被动攻击型等12种人格障碍。</p>

BLOCK;
$gain = array(
	'专业评估人格障碍的报告',
	'12种人格状态的不良状况分析',
	'12种人格状态的改善建议',
);

// 适合谁测
$fit = array(
	'希望深入了解自己的人',
	'感觉自己有多重面貌的人',
	'希望了解自己人格特点的人',
	'有时难以理解自己行为的人',
	'经常感受到内心冲突和矛盾的人',
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
