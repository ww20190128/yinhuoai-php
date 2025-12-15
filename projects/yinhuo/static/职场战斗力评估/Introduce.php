<?php
// 简介
$recommend = <<<BLOCK
<p>在现代社会，生活节奏迅速加快，很多人被迫加班。有些人是在压力下不得不工作，而有些人却持续工作而不知疲倦，仿佛工作本身就是最大的乐趣。</p>
<div class ='img-center'><img src="img30.png" /></div>	
<p>当一个人无法完全控制自己的行为和行动时，这可能就是工作成瘾的表现。</p>
<p>工作成瘾不仅对个人产生影响，还会破坏家庭生活。</p>
<p class ='card-bg-purple'>研究表明，与酗酒者的子女相比，有工作狂倾向的父母的孩子在抑郁方面的得分高出<span class="undelline">72%</span>。此外，工作狂的配偶通常感到婚姻不幸福。毕竟，与一个在情感和身体上都处于状态之外的人生活，是一种极其艰难的体验。</p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>早在50年前，美国心理学家韦恩·奥茨首次提出“工作狂（workaholic）”这一概念，他将其定义为<span class="undelline">一种无法控制的、持续工作的需求</span>。</p>
<p>为了区分积极的员工与那些有工作成瘾倾向的员工，卑尔根大学的科学家们开发了一个《工作狂量表》。</p>
<p>如果你想了解自己是否有工作狂的倾向，可以通过测试来发现你的真实水平！</p>
BLOCK;
$gain = array(
	
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
