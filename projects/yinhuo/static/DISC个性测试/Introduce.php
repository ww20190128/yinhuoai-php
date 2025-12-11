<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-blue" style="margin-left: -12px !important;"><span>如果你在以下方面感到困惑：</span></p>
		
<div class="card-bg-purple"><p>人生路上找不到正确的职业方向？</p></div>
<div class="card-bg-orange"><p>工作中难以领导团队成员创出佳绩？</p></div>
<div class="card-bg-green"><p>人际交往中总是不经意地被人误解？</p></div>
<div class="card-bg-purple"><p>家庭生活中不知道如何与爱人、孩子有效沟通？</p></div>

<div class ='img-center'><img src="困惑.png" /></div>
<p>那么，请从<span class="undelline">DISC个性测评</span>开始：</p>

<div class="list-box">
   <p class="card-bg-purple"><span>运用DISC行为观察术读懂他人</span></p>
   <p class="card-bg-green"><span>理解自身与他人的独特之处</span></p>
   <p class="card-bg-orange"><span>掌握DISC七大核心改变原则</span></p>
   <p class="card-bg-purple"><span>挖掘自身与他人的潜力</span></p>
   <p class="card-bg-green"><span>建立属于你自己的社会支持系统</span></p>
</div>
<div class="bg-title"><span>那么，DISC是什么</span></div>
<div class ='img-center'><img src="img05.jpg" /></div>
<p>DISC理论是一种基于行为观察的个性分析工具，通过识别个体在不同情境下的行为模式，帮助人们<span class="undelline">理解自我</span>及<span class="undelline">他人的行为特点</span>。该理论由心理学家威廉·马斯顿于20世纪20年代提出，并经过长期发展与验证，广泛应用于<span class="undelline">团队建设</span>、<span class="undelline">职业发展</span>和<span class="undelline">人际关系管理</span>中。</p>
BLOCK;

// 理论依据
$theory = <<<BLOCK

BLOCK;

// 你将获得
$gain = array(
	'增强团队合作',
	'提高工作质量',
	'促进领导力发展',
	'有效管理冲突',
	'改善人际关系',
);

// 适合谁测
$fit = array(
	'希望改善人际关系的个体',
	'希望优化个人职业发展的人员',
	'需要提升团队管理与合作的领导者',
	'在家庭中需要提高沟通效果的成员'
);

// 部分参考文献
$document = array(
	'[1] Marston, W. M. (1928). Emotions of Normal People. Harcourt Brace & Company.',
	'[2] Goleman, D. (1995). Emotional Intelligence. Bantam Books.'
);

return array(
	'theme' => 'gray',
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);