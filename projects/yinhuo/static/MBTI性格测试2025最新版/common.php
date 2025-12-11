<?php
/**
 * 通用配置
 * 
 *   	'payDescPc'         => $this->payDescPc,
        	'payDescMobile'     => $this->payDescMobile,
        	'ui_payBtnText'     => $this->ui_payBtnText,
        	'ui_payBtnColor'    => $this->ui_payBtnColor,
        	'ui_btnColor'     	=> $this->ui_btnColor,
        	'payConfirmDec'    	=> $this->payConfirmDec,
        	'payMainImg'        => $this->payMainImg,
        	'payActivityName'  	=> $this->payActivityName,
        	'payDesc'           => $this->payDesc,
 */
// 答题页面，底部文字
$bottomInfo = <<<BLOCK
<p class="font14-center" style="font-weight: 600 !important;">完成测试后，您将获得</p>
<ul class="list-paddingleft-2" style="list-style-type: disc;">
	<li><p>获取您的4字母类型测试结果</p></li>
	<li><p>发现适合于您性格类型的职业</p></li>
	<li><p>知悉您的偏好优势和类型描述</p></li>
	<li><p>评估您与恋人的长期相处情况</p></li>
    <li><p>了解您的沟通风格和学习风格</p></li>
	<li><p>查看与您分享同一性格的名人</p></li>
</ul>
</b>
<p class="font12-center">所有内容基于卡尔·荣格 (Carl Jung) 和伊莎贝尔·布里格斯·迈尔斯 (lsabel Briggs Myers)的MBTI理论实证</p>
BLOCK;

// pc端支付的文本
$payPcContent = <<<BLOCK
<div class ='img-main'><img src="ac90beecb1a86f234759290ec9f47c3f.jpg" /></div>
<div class ='img-main'><img src="6eb9508a3d47e47e51b110095f14a3f2.jpg" /></div>
<div class ='img-main'><img src="f68b9bbb9ca32adeadf0b01a44da756a.jpg" /></div>
BLOCK;

// 移动端支付描述
$payMobileContent = <<<BLOCK
<p>
	<span style="color: rgb(77, 111, 176); text-wrap: wrap;">MBTI人格测试在全球盛行了一个多世纪，是最正统的</span>
    <span style="text-wrap: wrap; color: rgb(246, 114, 126);">心理学人格测评系统</span>
    <span style="color: rgb(77, 111, 176); text-wrap: wrap;">。本测评以</span>
    <span style="text-wrap: wrap; color: rgb(246, 114, 126);">迈尔斯布里格斯类型指标 （MBTI）</span>
    <span style="color: rgb(77, 111, 176); text-wrap: wrap;">&nbsp;和现代心理学创始人荣格的</span>
    <span style="text-wrap: wrap; color: rgb(246, 114, 126);">《心理类型》</span>
    <span style="color: rgb(77, 111, 176); text-wrap: wrap;">理论为基础，并基于中国文化背景研发，更符合中国人测评，用于了解自己的</span>
    <span style="text-wrap: wrap; color: rgb(246, 114, 126);">性格、爱情观、择业观</span>
    <span style="color: rgb(77, 111, 176); text-wrap: wrap;">。</span>
</p>
BLOCK;

// 支付确认描述
$payConfirmDec = <<<BLOCK
<p style="text-wrap: wrap;">你的测评报告全文超过<span style="color: rgb(246, 114, 126);">6000</span>字！含MBTI人格类型评估与分析、恋爱性格分析、爱情婚姻走向、职场性格分析、职业天赋评估、专属心理学建议等<span style="color: rgb(246, 114, 126);">20项</span>专业模块。</p>
<p style="text-wrap: wrap;">支付后可查看报告，并赠送不限时免费重测三次。</p>
BLOCK;

// 支付描述
$payDesc = <<<BLOCK
<p><span style="font-size: 18px;"><strong>你将获得：</strong></span></p>
<p><strong>一，专业的分析报告</strong></p>
<p>评测报告全文超5000字，含MBTI人格类型评估与分析、恋爱性格分析、爱情婚姻走向、职场性格分析、职业天赋评估、专属心理学建议等20项专业模块，98%的用户好评。</p>
<p><strong>二，3次免费重测权益</strong></p>
<p>付费后可享受三次免费重测的权益，4次报告均会保存。</p>
<p><strong>三，报告永久保存</strong></p>
<p><span style="text-wrap: wrap;">支付完成后，报告可永久保存，报告结果可供您随时查看。</span></p>
BLOCK;
return array(
	'extend' => array(
		'styleType' => 1, // A B C D  E   5个选项横向排列
		'payStyleType' => 1,
		'bottomInfo' => $bottomInfo,
	),
	'payPcContent' => $payPcContent, // pc端支付的简介
	'payMobileContent' => $payMobileContent, // 移动端支付的简介
	'payConfirmDec' => $payConfirmDec,
	'payDesc' => $payDesc,
	'priceList' => array( // 不同价格区间配置
		'68.00' 	=> 39.90, // 基层
// 			'68.00' 	=> 0.01, // 基层
		'98.00'		=> 49.90, // 完整解读-价格
		'158.00'	=> 68.80, // 完整解读pro
	),
);