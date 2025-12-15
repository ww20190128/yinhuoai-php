<?php
// 简介
$recommend = <<<BLOCK
<div class="card-bg-purple"><p>自信，是人类运用和驾驭宇宙无穷智慧的唯一管道，是所有‘奇迹’的根基，是所有科学法则无法分析的玄妙神迹的发源地。</p>
<p style="text-align: right!important;">——拿破仑·希尔</p></div>



<p>英国有句谚语：“<span class="undelline">自信与自立是坚强的柱石</span>。”
<div class="border-blue">
<p>当一个人深信自己能行的时候，虽然他未必马上就能成功，但他拥有更多的积极心态以及掌控自己生活的意图，经过一些尝试和坚持，最后他达成心愿的可能性是比较大的。
<p>而当一个人深信自己不行时，那么他往往也确实无法成功，因为在尝试之前，他已经准备好了接受失败的结果。这足以说明自信心对我们工作、学习、生活和发展的重要性。</p>
</div>
<div class ='img-main'><img src="img01.png" /></div>
<p>你有多自信？几乎所有人都了解自信心的概念和重要性，但是对于自己的自信心水平却经常拿捏不准，因此，他们的观念和应对方式容易在缺乏自信和盲目自信之间徘徊，而这并非运用自信的最佳方式。</p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测试依据心理学家H·J·艾森克和格林·威尔逊的《自信心程度测试》，结合中国文化背景进行开发而成。</p>
BLOCK;
$gain = array(
	'评估你当前的自信水平',
	'获得相应的指导建议',
	'帮助你调整自信心至理想的水平'
);

// 适合谁测
$fit = array(
	
);
// 部分参考文献
$document = array();

return array(
	'theme' => 'gray', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);