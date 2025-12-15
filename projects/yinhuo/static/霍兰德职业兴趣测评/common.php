<?php
// pc端支付的文本
$payPcContent = <<<BLOCK
<div class ='img-main'><img src="60ff821c03107e99e55ad63d99aa3f5b.jpg" /></div>
<div class ='img-main'><img src="366073404606ca5eb3aefbcc6902ba72.jpg" /></div>
<div class ='img-main'><img src="18b390bb65a6014b3fa43858a1f718d3.jpg" /></div>
BLOCK;


// 移动端支付描述
$payMobileContent = <<<BLOCK
<p><span style="color:#4d6fb0;">本测评依据霍兰德职业兴趣测试(<span style="color: #F6727E;">Self-Directed Search</span>)为理论基础，结合国内职业现状以及<span style="color:#F6727E;">中国社会文化背景</span>研发而成，并根据国内多家上市企业人力资源部的
<span style= color:#F6727E;">实际数据</span>进行职业推荐。</span></p>
BLOCK;

// 支付确认描述
$payConfirmDec = <<<BLOCK
<p>你的测评报告全文超过<span style="color:#F6727E;">5000</span>字！含性格类型分析、职业兴趣、求职晋升规划、霍兰德密码天赋解读等<span style="color:#F6727E;">10项</span>专业模块。</p><p>支付后可查看报告，并赠送不限时免费重测三次。</p>
BLOCK;

// 支付描述
$payDesc = <<<BLOCK
<p><span style="font-size: 18px;"><strong>你将获得：</strong></span></p>
<p><strong>一，专业的分析报告</strong></p>
<p>评测报告全文超5000字，数据化图表分析最适合你的工作，提供专属求职指导，98%的用户好评。</p>
<p><strong>二，3次免费重测权益</strong></p>
<p>付费后可享受三次免费重测的权益，4次报告均会保存。</p>
<p><strong>三，报告永久保存</strong></p>
<p><span style="text-wrap: wrap;">支付完成后，报告可永久保存，报告结果可供您随时查看。</span></p>
BLOCK;
return array(
	'extend' => array(

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