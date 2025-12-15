<?php
// 简介
$recommend = <<<BLOCK
<p>您的孩子是否特别活泼好动？</p>
<p>当孩子表现出过于活跃、容易发脾气、注意力不集中、粗心大意、学习困难或在社交上表现不佳时，很多家长会担心孩子可能患有“多动症”。</p>
<p>国际上通常称这种情况为注意力缺陷多动障碍 (Attention Deficit Hyperactivity Disorder，简称 ADHD)。</p>
<div class ='img-main'><img src="adhd.png" /></div>	
<p>实际上，大多数孩子的好动和调皮与真正的 ADHD 症状有着本质上的不同。</p>
<p>普通的好动会随着年龄的增长逐渐改善，而如果是 ADHD 的症状，通常在没有专业干预下难以自行消失。因此，及早发现和及时治疗至关重要。</p>
<p>本测评适用于4至17岁的青少年，能够提供对ADHD症状的初步筛查参考，但最终的确诊应以专业医疗机构的诊断为准。</p>
BLOCK;

// 理论依据
$theory = <<<BLOCK

BLOCK;

$gain = array(
    // 留空，未修改内容
);

// 适合谁测
$fit = array(
    // 留空，未修改内容
);

// 部分参考文献
$document = array(
    // 留空，未修改内容
);

return array(
    'theme' => 'pink', // 主题色{ }
    'recommend' => $recommend,
    'theory' => $theory,
    'gain' => $gain,
    'fit' => $fit,
    'document' => $document,
);
