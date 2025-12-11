<?php
// 简介
$recommend = <<<BLOCK
<p class="report-tag-blue" style="margin-left: -12px !important;"><span>你在工作中，是否有以下感觉：</span></p>			
<p class="card-bg-purple">下班时感觉身体被“掏空”</p>
<p class="card-bg-orange">每天早上起床去上班心情都很沉重</p>
<p class="card-bg-green">感觉自己还没干什么就已身心俱疲</p>
<p class="card-bg-purple">每天都想“摸鱼”，总想应付了事</p>
<p class="card-bg-orange">感觉工作除了能拿到工资以外，缺少其他的意义</p>
<p class="card-bg-purple">感觉自己越来越滩以胜任现在的工作，常感到沮丧</p>
<div class ='img-main'><img src="职业倦怠-01.jpg" /></div>			
<p>你若占了两条以上，那么你有一定程度的职业倦怠了。</p>	
<p>这会影响到你的工作体验和职业发展。</p>	
<p>当然，最准确的辨别和分析还要做更权威和系统的测评。</p>	
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测评根据美国社会心理学家Maslach和Jaskson的三维度模型，结合中国的社会文化和语言习惯研发而成，可以很好地反映出测试者的职业倦怠情况。</p>
BLOCK;
$gain = array(
	'你将看到自己的总体职业倦怠程度以及在衰竭感、懈怠、挫败感三个维度上的得分情况',
	'根据你的具体情况对你提供有针对性的调整建议，帮你消除职业倦怠',
	'你将会获得更好的工作效率、自信和工作体验，从而提升你的升职空间',
);

// 适合谁测
$fit = array(
	
);
// 部分参考文献
$document = array(

);

return array(
	'theme' => 'blue',
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
