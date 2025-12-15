<?php
// 答题页面，底部文字
$bottomInfo = <<<BLOCK
<p class="tips-title">测试完成，您将获取</p>
<ul class=" list-paddingleft-2">
	<li><p>您的智商得分及排名</p></li>
    <li><p>大脑各项能力指数及缺陷</p></li>
    <li><p>左右脑训练专家建议</p></li>
    <li><p>遗传基因及后天提升方法</p></li>
</ul>"
BLOCK;

// pc端支付的文本
$payPcContent = <<<BLOCK
<div class='img-main'><img src="865931cd8bd2f05370b39e0aaadb466d.jpg" /></div>
<div class='img-main'><img src="704e52d221311e269767681456cee3fb.jpg" /></div>
<div class='img-main'><img src="1d76d343ff11a59495c4a2497c5a0478.jpg" /></div>
BLOCK;
// 移动端支付描述
$payMobileContent = <<<BLOCK
<p><span style=\"font-size: 18px;\"><strong>你将获得：</strong></span></p>
<p><strong>一，专业的分析报告</strong></p>
<p>评测报告全文超5000字，多种数据化图表展示，权威评估你的智力！</p>
<p><strong>二，3次免费重测权益</strong></p>
<p>付费后可享受三次免费重测的权益，4次报告均会保存。</p>
<p><strong>三，报告永久保存</strong></p>
<p>支付完成后，报告可永久保存，报告结果可供您随时查看。</p>
BLOCK;

// 支付确认描述
$payConfirmDec = <<<BLOCK
你的测评报告全文超过2000字!专业测评精准识别你的ABO性别类型和信息素;深度剖析你的内在性别气质;提供心理学技巧和建议;助你发挥性别优势;弥补性别弱势。支付后可直接查看报告。
BLOCK;

// 支付描述
$payDesc = <<<BLOCK
<p><span style="color:#4d6fb0;">本测评依据<span style="color: #F6727E;">贝姆双性化测验(Bem&#39;s Sex-Role in-ventory)</span> 理论为基础，根据生理性别总结出三种<span style="color: #F6727E;">性别角色类型</span>，现由我们测评研发团队基于中国文化背景研发，更符合中国年青人测评，用于了解自己的<span style="color: #F6727E;">ABO心理性别</span>，发挥自己的内在性别优势。</span></p>
BLOCK;
return array (
	'levelList1' => array (
		'高' => array (
			'threshold' => 75,
			'explain' => '<p>在你身上有着很高的男性化气质的体现，因此你总是给人以刚强的印象，做事作风果断、积极、主动、敢于冒险，热爱刺激，同时也好交际，情绪稳定成熟。</p>'
		),
		'较高' => array (
			'threshold' => 50,
			'explain' => '<p>你的男性化气质较高，一些男性化定义的气质在你身上都有所体现，活泼并擅长交际，不轻易服输，有组织力，能较好地控制好情绪。</p>'
		),
		
		'较低' => array (
			'threshold' => 25,
			'explain' => '<p>你的男性化气质不太明显，较少体现男性化特质的你，在人群中习惯安于一隅，不会锋芒过露，对事情也不会轻易冲动莽撞。</p>',
		),
		'低' => array (
			'threshold' => 0,
			'explain' => '<p>你的男性化气质不明显。</p>',
		),
	),
	'levelList2' => array (
		'高' => array (
			'threshold' => 75,
			'explain' => '<p>在你身上有着很高的女性化气质的体现，因此你总是给人以温和的印象，人际能力十分强，这有赖于你较常人敏感、以及同理心高的特质，待人宽容，工作负责的你在人群中十分受欢迎。</p>'
		),
		'较高' => array (
			'threshold' => 50,
			'explain' => '<p>你的女性化气质较高，一些女性化定义的气质在你身上都有所体现，生活中懂得察言观色，工作中认真负责，是个很好的交友对象。</p>'
		),
		'较低' => array (
			'threshold' => 25,
			'explain' => '<p>你的女性化气质不太明显，较少体现女性化特质的你，不太容易感情用事，较低的敏感度和同理心。</p>',
		),
		'低' => array (
			'threshold' => 0,
			'explain' => '<p>你的女性化气质不明显。</p>',
		),
	),
	'extend' => array(
		'bottomInfo' => $bottomInfo,
	),
	'payPcContent' => $payPcContent, // pc端支付的简介
	'payMobileContent' => $payMobileContent, // 移动端支付的简介
	'payConfirmDec' => $payConfirmDec,
	'payDesc' => $payDesc,
	// 不同价格区间
	'price1' => 43.90, // 完整解读-价格
	'originalPrice1' => 98, // 完整解读-原价
	'price2' => 49.90, // 完整解读pro-价格
	'originalPrice2' => 188, // 完整解读pro-原价
);