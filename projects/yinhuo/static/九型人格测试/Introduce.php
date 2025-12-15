<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-gray" style="margin-left: -12px !important;"><span>你了解自己的人格特质吗？</span></p>
<p>你的<span class="undelline">性格优势</span>、<span class="undelline">缺点</span>，你的<span class="undelline">社交</span>、<span class="undelline">情感模式</span>，你与其他人之间的关系.</p>
<p>这些，都可以在<span class="undelline">九型人格</span>中找到答案。</p>
<div class="bg-title"><span>什么是九型人格</span></div>		
<p>九型人格，是大众熟知的人格类型系统。它拥有两千多年历史的古老理论，它根据人的思维模式、情绪反应和行为特点，把人分为九类性格：</p>
<div class ='img-main'><img src="img03.jpg" /></div>
<p>这一理论被广泛应用于<span class="undelline">个人成长</span>、<span class="undelline">职业规划</span>、<span class="undelline">社交技巧</span>、<span class="undelline">婚姻</span>、<span class="undelline">亲子关系</span>、<span class="undelline">企业管理</span>、<span class="undelline">销售</span>以及<span class="undelline">心理辅导</span>等众多领域。</p>

BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>九型人格是一个近年来倍受美国斯坦福大学等国际知名大学MBA学员推崇并成为现今最热门的课程之一。全球500强企业的管理阶层均有研习九型性格，并以此<span class="undelline">培训员工</span>，<span class="undelline">建立团队</span>，<span class="undelline">提高执行力</span>。</p>
<p>本测评以九型人格理论 (Enneagram) 为基础，由我们的研发团队根据中国文化背景进行开发编写。</p>
BLOCK;
$gain = array(
    '精准分析你的人格类型',
	'绘制你专属的人格画像',
	'代表色彩与人生课题分析',
	'性格的形成原因及深入剖析',
	'爱情观分析及理想伴侣的推荐',
	'职业发展方向与合适的工作类型',
	'人际交往、顺境、逆境分析及应对',
);

// 适合谁测
$fit = array(
  	'时常感到矛盾和纠结的人',
  	'想知道自己性格优劣势的人',
  	'想获得自己在职业发展建议的人',
  	'寻求婚恋方向，寻求情感建议的人',
	'热衷于探索自我，追求内在成长的人',
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
