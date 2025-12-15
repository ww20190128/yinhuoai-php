<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-gray" style="margin-left: -12px !important;"><span>工作不称心，生活不舒畅？</span></p>
<p class="card-bg-orange">也许从一开始，你努力的方向就并非自己内心真正所向往的，又或者你还没发现怎样才能在生活中获得更多掌控权。</p>
<p class="card-bg-purple">每个人都有着独特的价值观念，只有充分了解自己的<span class="undelline">价值观</span>，才能够真正做到随心而为。 </p>
<p class="card-bg-green">如果你知道自己的价值观是什么，那么做决定将再也不是一件困难的事情</p>
	
		
<p>洛伊·迪士尼、谢洛姆·施瓦茨（Shalom H. Schwartz）等人从1992年开始致力于编制“Schwartz价值观量表”，确定了十种动机各不相同的价值观，并进一步阐述了它们之间的动态关系：</p>
<div class ='img-main'><img src="价值观-地图.png" /></div>	
	
<p>价值观与你的职业选择、工作满意度及工作表现密切相关。</p>
<p>当你的职业选择与自己的核心价值观相背离时，不仅不能给自己带来成就感和满足感，甚至还会让自己陷入矛盾和痛苦之中。</p>

BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测评根据施瓦茨价值观完整版SVS版量表，结合中国职场文化进行研发，旨在测试符合你内心深处价值观的真正动力源泉，让你更深入地了解自己，引导你选择更有价值感、更快乐的人生。</p>
BLOCK;
$gain = array(
    '详细的报告' => '测试结果中你会看到属于自己的价值观',
	'专业的指导建议' => '如何在工作和生活中应用自己价值观的专属建议',
);

// 适合谁测
$fit = array(
	'想知道自己价值观的人',
  	'时常感到矛盾和纠结的人',
  	'想获得自己在职业发展建议的人',
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
