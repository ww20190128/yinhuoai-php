<?php
// 简介
$recommend = <<<BLOCK
给您的心理进行一次<span class="undelline">全面的健康检查</span>——关注心理状态，避免<span class="undelline">心理亚健康</span>。

<p class="report-tag-blue" style="margin-left: -12px !important;"><span>您是否有以下症状：</span></p>
<div class="card-bg-purple">
<p>- 失眠频繁，夜间睡眠不足</p>
<p>- 工作效率低下</p>
<p>- 精神萎缩，兴趣缺乏</p>
<p>- 经常情绪低落</p>
<p>- 经常感到紧张和焦虑</p>
<p>- 经常提不起劲</p>
</div>
<p class="color-red">这些都是<span class="undelline">心理亚健康</span>的常见表现！</p>

<div class ='img-center'><img src="img12.jpg" /></div>
<p>本测评旨在帮助你：<span class="undelline">确认是否处于心理亚健康状态</span></p>
BLOCK;
// 理论依据
$theory = <<<BLOCK
<p>本测试依据SCL—90量表，该量表被广泛应用于国际医疗机构及国内顶级医院，其科学性和可靠性在业界得到了充分验证。</p>
BLOCK;
$gain = array(
	'获取您的心理健康综合报告',
	'对您的心理状态进行详细分析',
	'专业系统化的方法来缓解症状'
);

// 适合谁测
$fit = array(
	
);
// 部分参考文献
$document = array(
	'[1] Derogatis LR, Lipman RS, Covi L. (1973). SCL-90: An Outpatient Psychiatric Rating Scale-Preliminary Report. Psychopharmacology Bulletin, 9(1), 13-28.',
	'[2] 童辉杰. (2010). SCL—90量表及其常模20年变迁之研究. 心理科学（04），928—930.',
	'[3] Aben, I., Verhey, F., Lousberg, R., Lodder, J., Honig, A. (2002). Validity of the Beck Depression Inventory, Hospital Anxiety and Depression Scale, SCL-90, and Hamilton Depression Rating Scale as Screening Instruments for Depression in Stroke Patients. Psychosomatics, 43(5), 386-393.',
	'[4] Holi, M. M., Sammallahti, P. R., Aalberg, V. A. (2010). A Finnish Validation Study of the SCL-90. Acta Psychiatrica Scandinavica, 97(1), 42-46.',
	'[5] Bech, P., Bille, J., Møller, S. B., Hellström, L. C., & Ostergaard, S. D. (2014). Psychometric Validation of the Hopkins Symptom Checklist (SCL-90) Subscales for Depression, Anxiety, and Interpersonal Sensitivity. Journal of Affective Disorders, 160, 98-103.',
);

return array(
	'theme' => 'cyan', // 主题色{ }
	'recommend' => $recommend,
	'theory' => $theory,
	'gain' => $gain,
	'fit' => $fit,
	'document' => $document,
);
