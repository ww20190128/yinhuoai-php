<?php
// 简介
$recommend = <<<BLOCK
<p>你是否常常用这样的方式评价他人？</p>
<div class="card-bg-purple"><p>她脾气暴躁，但心地善良，是个急性子</p></div>
<div class="card-bg-orange"><p>她的脾气很好，做事慢条斯理，很温和</p></div>


<p>我们习惯用二分法对人的性格进行简单分类，比如脾气好坏、急性子还是慢性子。这种分类帮助我们迅速了解对方性格，并制定相应的相处策略。</p>

<div class ='img-main'><img src="img06.jpg" /></div>
<p><span class="undelline">A型人格</span>的人通常有很高的自我要求和强烈的进取心，富有毅力、敢于挑战、善于竞争、办事高效，他们在事业上有较高的成就机会，但也可能忽视健康。</p>

<p><span class="undelline">B型人格</span>的人则比较宽和、耐心，做事有条不紊，遇事谨慎，对生活知足，事业心相对较低，但他们懂得享受平凡的快乐生活。</p>

BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>早在20世纪中叶，美国心脏病专家弗里德曼（Meyer Friedman）和罗森曼（Roy H. Rosenman）合作研究了<span class="undelline">A型行为</span>，并编写了《A型行为鉴别测试》，用于预警“急性子”们注意身心健康的风险。</p>

<p>本测评基于该理论研究，并结合最新心理学成果设计而成。</p>
<p>A型和B型人格理论广泛应用于心理健康和行为研究中，对理解个体性格及其对健康的影响具有重要价值。</p>
BLOCK;
$gain = array(
  '帮助你了解自己的性格类型，发挥性格优势。',
  '提供针对性的改善建议，有助于优化认知观念和行为模式，促进身心健康。',
);

// 适合谁测
$fit = array(
	'关心个人身心健康的人',
    '希望了解自己性格类型的人',
    '想要优化自身认知和行为模式的人',
);

// 部分参考文献
$document = array(
    '[1] Friedman, M., & Rosenman, R. H. (1974). Type A Behavior and Your Heart. New York: Knopf.',
    '[2] Matthews, G., & Deary, I. J. (1998). Personality Traits. Cambridge University Press.'
);

return array(
    'theme' => 'orange', // 主题色{ }
    'recommend' => $recommend,
    'theory' => $theory,
    'gain' => $gain,
    'fit' => $fit,
    'document' => $document,
);
