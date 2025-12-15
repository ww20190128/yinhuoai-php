<?php
// 简介
$recommend = <<<BLOCK
<p class="card-bg-purple">总是上班迟到，工作报告总是拖到最后一刻才完成……</p>
<p class="card-bg-orange">晚上明明疲惫得不行，却还不停地刷手机不肯入睡</p>
<p class="card-bg-green">早晨闹钟响了一遍又一遍，你仍然赖在床上</p>
<p class="card-bg-purple">作业总是拖到最后一刻才开始做</p>

<p><span class="undelline">这些现象都表明你可能存在拖延行为。</span></p>

<p>拖延，也称为拖沓，指的是将原本计划好的任务推迟到之后才完成，这种行为被称为“拖延症”。拖延者往往会对任务和决策感到焦虑，而拖延本身则是应对这种焦虑的方式。</p>
<div class ='img-main'><img src="sleep.png" /></div>
<p>拖延症是一种普遍且复杂的现象。研究发现，约有<span class="undelline">25%</span>的成年人承认拖延是他们生活中的重大问题，而40%的人认为拖延已经给他们的经济带来了损失。</p>

<p>拖延症如同一个隐形的敌人，逐渐破坏我们的生活。我们渴望改变，但却常常难以摆脱拖延所带来的困扰。</p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测试基于Aitken拖延问卷（Aitken procrastination inventory， API），并结合中国文化背景进行研发，旨在深入探索和评估你的拖延行为。测试将评估你的拖延程度，并提供具体的改进建议。</p>
BLOCK;
$gain = array(
	
);

// 适合谁测
$fit = array(
	
);
// 部分参考文献
$document = array();

return array(
	'theme' => 'purple', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
