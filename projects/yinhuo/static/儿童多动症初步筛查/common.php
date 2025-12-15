<?php
$dimensionDesc = <<<EOT
	<div class ='img-main'><img src="儿童多动症-维度.png" /></div>
<p>儿童/青少年多动障碍通常从多动冲动、注意障碍两个维度进行评估,以下是您的孩子在这两个维度上的具体表现：</p>
EOT;
return array (
		'title' => '儿童青少年多动情况初步评估',
		'totalTitle' => '多动情况初步评估',
		'totalIcon' => 'fa-street-view',
// 		'totalImage' => '儿童多动症-维度.png', // zongjieguo_image
		'levelList' => array ( //  0  低      20  较低     40  中等    60  高     100
			'正常' => array (
				'threshold' => 0,
				'explain' => '<p>根据您的选择得分，您的孩子尚未达到注意力缺陷多动障碍（多动症）的水平，表现属于正常范围。</p>
<p>有时，孩子因精力充沛、过度兴奋或好奇心强而可能表现出好动、冲动或注意力不集中的情况。然而，只要他们在游戏或兴趣活动中能够集中注意力，并且在老师的指导下能够认真上课，尤其是在社交和学业方面没有出现显著的功能障碍，这些都属于正常现象。</p>
<p>这种正常的活跃行为通常会随着孩子的成长逐渐减轻。如果您因某些原因感到担忧，或者在孩子入学时出现了注意力不集中或多动的适应问题，建议您带孩子去正规的医院儿科进行进一步的评估。</p>',
			),
		),
		'dimensionSet' => array(
			'title' => '多动障碍维度解析',
			'icon' => 'fa-sliders',
			'desc' => $dimensionDesc,
		),
		'dimensionList' => array(
			'多动冲动' => array(
				'levelList' => array ( 
					'正常' => array (
						'threshold' => 0,
						'explain' => '<p>多动和冲动的主要特征包括无法静坐、过度活跃、频繁与他人发生冲突以及难以控制自己的行为。</p>',
					),
					'偏高' => array (
						'threshold' => 50,
						'explain' => '<p>多动和冲动的主要特征包括无法静坐、过度活跃、频繁与他人发生冲突以及难以控制自己的行为。</p>',
					),
				),
			),
			'注意缺陷' => array(
				'levelList' => array ( 
					'正常' => array (
						'threshold' => 0,
						'explain' => '<p>注意力缺陷通常表现为在集中注意力、认知能力和反应速度方面存在不足。</p>',
					),
					'偏高' => array (
						'threshold' => 50,
						'explain' => '<p>注意力缺陷通常表现为在集中注意力、认知能力和反应速度方面存在不足。</p>',
					),
				),
			),
		),
		'extendRead' => array (
			'title' => '关于儿童多动症（ADHD）',
			'title_icon_tag' => 'fa-book',
			'content' => <<<EOT
<div class ='img-main'><img src="儿童多动症-介绍.png" /></div>
<p>每位家长都应对儿童多动症有所了解。虽然儿童在成长过程中通常表现出活跃好动，但这并不总是多动症的表现。有些孩子的行为失控则可能是多动症的症状。</p>
<div class ='title-underline'><span>多动症的主要表现</span></div>
				
<div class="combine-box-blue" style="margin-top: 0 !important;margin-bottom: -1px !important;">
	<div class="title">
		<span class="name">过度活跃</span>
	</div>
	<div class="content">过度活跃的表现通常是无法静止下来，经常显得躁动不安，不论在什么场合都难以安静，往往会大声喧哗，四处摆动手脚。</div>
</div>
<div class="combine-box-blue" style="margin-top: 0 !important;margin-bottom: -1px !important;">
	<div class="title">
		<span class="name">冲动行为</span>
	</div>
	<div class="content">冲动行为表现为在说话时不经过深思熟虑，常常会打断他人谈话，抢答问题。</div>
</div>
<div class="combine-box-blue" style="margin-top: 0 !important;margin-bottom: -1px !important;">
	<div class="title">
		<span class="name">注意力不足</span>
	</div>
	<div class="content">注意力不足的表现包括难以集中注意力，不能专心听讲，做事粗心，容易分心。</div>
</div>
				
<div class ='title-underline'><span>多动症与一般儿童活跃的区别</span></div>

<p>通常，活跃的孩子在需要安静的时候能够静下心来，比如在上课时能够专心听讲，并且遵守老师的安排。</p>
<p>而多动症孩子在各种环境中都难以静止，即使在课堂上也无法保持安静，难以集中注意力听讲，不遵守老师的指令，也无法完成布置的任务。</p>
				
<div class ='title-underline'><span>多动症的原因是什么？</span></div>				

<p>研究表明，大多数儿童多动症的发生与遗传、早产、家庭环境不良、以及父母的教育方法有关。</p>
<div class="title-wireframe"><span>儿童多动症的饮食建议</span></div>				

<div class="title2-bg"><span>1、增加锌的摄入</span></div>
<p>多摄入含锌食物有助于儿童的生长发育。锌缺乏可能导致发育迟缓、食欲减退，还可能影响学习成绩。</p>
<div class="title2-bg"><span>2、增加钙的摄入</span></div>
<p>增加钙的摄入可以帮助儿童保持镇静。</p>
<div class="title2-bg"><span>3、增加铁的摄入</span></div>
<p>铁的缺乏可能导致情绪波动，并加重多动症的症状。</p>
<div class="title2-bg"><span>4、减少铝的摄入</span></div>
<p>过多摄入铝可能导致记忆力下降和消化问题。</p>
				
<div class ='img-main'><img src="儿童多动症-治疗.png" /></div>				

<div class ='card-bg-purple'>治疗儿童多动症是一个长期过程，需要耐心和有效的教育引导。应避免体罚，因为这不仅不会改善孩子的症状，还可能使情况变得更糟，影响孩子的健康成长。</div>
						
EOT
  		),
); 		
