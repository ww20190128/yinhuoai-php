<?php
/**
 * 通用配置
 */
// pc端支付的文本
$payPcContent = <<<BLOCK
<div class ='img-main'><img src="865931cd8bd2f05370b39e0aaadb466d.jpg" /></div>

BLOCK;

// 支付确认描述
$payConfirmDec = <<<BLOCK
<p style="text-wrap: wrap;">你的盖洛普优势报告全文超过<span style="color: rgb(246, 114, 126);">5000</span>字！含盖洛普<span style="color: rgb(246, 114, 126);">34</span>种才干分析图表（多组图表）、优势基因评估、盲点分析、职业发展优势、就业的正确选择、管理能力及影响力的专属成长建议等多个模块。</p>
<p style="text-wrap: wrap;">支付后可查看报告，并赠送不限时免费重测三次。</p>
BLOCK;

// 支付描述
$payDesc = <<<BLOCK
<p>根据克利夫顿30年的调研总结出的盖洛普优势测试，从<span style="color: rgb(246, 114, 126);">执行力、战略思维、影响力、关系建立</span>四大维度分别检测你在<span style="color: rgb(246, 114, 126);">专注、战略、统率、交往</span>等34才干中的能力分布，并为你提供相应的才干分析以及能力提升建议。</p>
<p><span style="text-wrap: wrap;">支付完成后，报告可永久保存，报告结果可供您随时查看。</span></p>
BLOCK;

$payMobileContent = '';
return array(
	'extend' => array(
		'bottomText' => "完成后获取详细的职业选择、副业优势报告",
	),
	'payPcContent' => $payPcContent, // pc端支付的简介
	'payMobileContent' => $payMobileContent, // 移动端支付的简介
	'payConfirmDec' => $payConfirmDec,
	'payDesc' => $payDesc,
	
);