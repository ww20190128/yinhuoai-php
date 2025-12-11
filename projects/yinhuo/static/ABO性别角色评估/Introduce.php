<?php
// 简介
$recommend = <<<BLOCK
<div class="title2-dot">你是否听说过ABO性别理论？</div>

<p>ABO性别理论将性别分类为以下三种类型：</p>
<div class="card-bg-purple"><span>A - Alpha: 强势、果断、行动力强</span></div>
<div class="card-bg-green"><span>B - Beta: 平衡、稳重、不偏不倚</span></div>
<div class="card-bg-orange"><span>O - Omega: 温柔、顺从、富有包容力</span></div>

<p>根据ABO理论将性别分为以下五种性别类型：</p>
<div class ='img-main'><img src="abo.png" /></div>


<p>与传统性别观念不同，ABO性别理论允许<span class="undelline">男性展现柔和和体贴的一面</span>，而<span class="undelline">女性同样可以拥有果敢和勇猛的特质</span>。</p>
<div class ='img-main'><img src="abo2.png" /></div>
<p class="report-tag-blue" style="margin-left: -12px !important;"><span>你想知道自己属于哪一种ABO性别吗？</span></p>
<div class="card-bg-green"><p>充满领导力的Alpha？</p></div>
<div class="card-bg-purple"><p>温柔细腻的Omega？</p></div>
<div class="card-bg-orange"><p>务实理性的Beta？</p></div>
<p>快来测试一下，找出答案吧！</p>
BLOCK;

// 理论依据
$theory = <<<BLOCK
<p>本测评基于贝姆的双性化理论（Bem's Sex-Role Inventory），通过对传统性别角色的现代化优化，衍生出ABO性别角色的3种主要类型，帮助我们更好地理解我们的性别特质。</p>
BLOCK;

// 你将获得
$gain = array(
    '洞悉你的ABO性别类型' => '根据测评结果，清晰了解你的性别特征与类别，确定你的ABO性别类型。',
    '全方位分析你的性别气质' => '深入剖析你的性别气质，了解你是偏男性化还是女性化特质，具体分析各方面表现。',
    '增强性别特质优势' => '针对性别气质中的优势，提供心理学建议，帮助你发挥性别优势，同时补足弱势。',
);

// 适合谁测
$fit = array(
    '希望突破性别限制的人',
	'想要更好地理解自我与他人的人',
    '在社会中感受到压迫或不适的人',
	'对自我认知有兴趣的心理学爱好者',
);

// 部分参考文献
$document = array(
    '[1]Bem, S. L. (1974). The Measurement of Psychological Androgyny. Journal of Consulting and Clinical Psychology, 42(2), 155-162.',
    '[2]Eagly, A. H., & Wood, W. (1999). The Origins of Sex Differences in Human Behavior: Evolved Dispositions versus Social Roles. American Psychologist, 54(6), 408-423.'
);

return array(
    'theme' => 'orange',
    'recommend' => $recommend,
    'theory' => $theory,
    'gain' => $gain,
    'fit' => $fit,
    'document' => $document,
);
