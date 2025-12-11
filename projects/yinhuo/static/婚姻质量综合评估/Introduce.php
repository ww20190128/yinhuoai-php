<?php

/**
 * <p class="report-tag-gray" style="margin-left: -12px !important;"><span>为什么要做婚姻健康测试</span></p>
<p>没有人对婚姻是百分百满意的，再完美的婚姻，你也会觉得“缺点什么”； </p>
<p>《婚姻健康测试》帮你定位婚姻中的缺失与未被满足的部分；</p>
<p>《婚姻健康测试》了解你目前的婚姻状况，识别婚姻中可能隐藏的问题，有助于预防未来婚姻中的风险因素；	</p>	
<p>《婚姻健康测试》为你打开幸福婚姻的大门，在你幸福婚姻的路上为你助力。</p>s
 */
// 简介
$recommend = <<<BLOCK
		

<p class="report-tag-gray" style="margin-left: -12px !important;"><span>你是否正遭遇这样的痛苦</span></p>
<p class="card-bg-purple">我为这个家付出这么多，为什么ta对我的爱却越来越少？</p>

<p class="card-bg-orange">婚前你侬我侬，婚后两人就没有激情了一天都说不到两句话！</p>

<p class="card-bg-green">ta永远都不主动沟通，我受够了冷暴力.最近我总是疑神疑鬼，猜测ta是不是出轨了..</p>

<p>“其实，没有100%完美的婚姻，“有问题”的婚姻，才是真实的。当你看清这一点，并愿意去挖掘问题背后的本质，你的婚姻才会越来越好。</p>


<p class="report-tag-gray" style="margin-left: -12px !important;"><span>那么，你现在的婚姻质量如何？</span></p>

<div class ='img-main'><img src="111.jpg" /></div>
<p class="card-bg-orange">感情越来越淡，却不知如何提升与另一半的感情浓度？</p>

<p class="card-bg-purple">是否觉得结婚后，矛盾越来越多，沟通越来越少？</p>

<p>本测评将从影响婚姻质量的11个方面，全面地帮你分析目前的婚姻状态，并提供幸福小贴士，来帮助你提高婚姻质量。</p>
	<div class ='img-main'><img src="img100.jpg" /></div>	

BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测评主要依据Olson婚姻质量问卷（OlsonEnrich Marital inventory），结合美园明尼苏达大学奥尔松1981年以其1970年编制的“婚前预测问卷”（PMI）为基础编制。</p>
BLOCK;
$gain = array(
    '评估你的婚姻质量，分析你的婚姻状况',
	'多维度详细分析你的婚姻的各种因素',
	'明晰潜在感情问题，避免情感危机',
	'定制专属你的婚姻提升指南',
	'提供针对性的心理学建议，帮你收获更美满的生活',
);

// 适合谁测
$fit = array(

);
// 部分参考文献
$document = array(

);

return array(
	'theme' => 'pink',
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
