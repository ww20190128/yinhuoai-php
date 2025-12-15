<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-blue" style="margin-left: -12px !important;"><span>在工作中，你是否会遇到这些困惑</span></p>
<div class="card-bg-purple"><p>升职无望，加薪缓慢，同事都当领导，而自己一直止步不前？</p></div>
<div class="card-bg-orange"><p>工作效率总是达不到预期，每天加班却不如别人高效？</p></div>
<div class="card-bg-green"><p>常常感到迷失，不清楚怎样努力才能取得理想的成果？</p></div>
<div class="card-bg-purple"><p>对自己的核心竞争力缺乏清晰认知，屡次遭到否定，面对挑战时缺乏信心。？</p></div>
<div class ='img-center'><img src="困惑.png" /></div>	
<p>你并非比别人差，而是还未找到属于自己的突破口——<span class="undelline">职场性格优势</span>。</p>
<p>每个人的性格都是独一无二的，这些特质不仅可以助你事业成功，也可能限制你的发展。了解并发挥你的职场性格优势，同时弥补弱点，能够让你轻松应对工作挑战，事业也会如顺风之舟，迅速驶向成功的彼岸。</p>

<div class="bg-title"><span>PDP是什么</span></div>
<p>PDP性格优势诊断系统（Professional Dyna-Metric Programs）也被称为<span class="undelline">五种动物性格</span>测试。它将人群分为<span class="undelline">支配型</span>、<span class="undelline">外向型</span>、<span class="undelline">耐心型</span>、<span class="undelline">精确型</span>、<span class="undelline">整合型</span>五种性格类型。为了形象地描述这些性格类型，这五类人群分别被称为“<span class="undelline">老虎</span>”、“<span class="undelline">孔雀</span>”、“<span class="undelline">考拉</span>”、“<span class="undelline">猫头鹰</span>”、“<span class="undelline">变色龙</span>”。</p>
<div class ='img-center'><img src="img04.jpg" /></div>
<p>该系统主要用于衡量个体的行为特征、活力、动能、压力承受力及精力状态。</p>
<p>这一系统被全球500强企业及国内众多上市公司广泛用于人才评估，帮助挖掘潜在管理者，提高企业整体运营效率。</p>
<p>你是“老虎”、“孔雀”、“考拉”，“猫头鹰”还是“变色龙”？</p>
<p>探索属于你的职场性格，找到你的<span class="undelline">职场闪光点</span>吧！</p>
BLOCK;

// 理论依据
$theory = <<<BLOCK

BLOCK;

// 你将获得
$gain = array(
    '了解自已' => '认识自己的优点和不足，明确行为特征，识别性格盲区',
    '职场建议' => '提供个性化的职场优势分析，揭示职业兴趣，给出职场人际关系和职业规划的建议',
);

// 适合谁测
$fit = array(
    '希望寻找职业发展方向的人',
    '希望提升工作效率的职场人士',
    '处于职场感到前途迷茫的人',
    '想要改善自身职场优势的员工'
);

// 部分参考文献
$document = array(
    '[1] Furnham, A. (2006). The Psychology of Behaviour at Work: The Individual in the Organization. Psychology Press.',
    '[2] Baker, M. (2001). Personality Assessment and Employment Decisions. HRD Press.'
);

return array(
    'theme' => 'pink',
    'recommend' => $recommend,
    'theory' => $theory,
    'gain' => $gain,
    'fit' => $fit,
    'document' => $document,
);
