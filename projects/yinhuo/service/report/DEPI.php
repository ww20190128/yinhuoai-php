<?php
namespace service\report;

/**
 * 抑郁指数评估「医用版」
 * 
 * @author 
 */
class DEPI extends \service\report\ReportBase
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return DEPI
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DEPI();
        }
        return self::$instance;
    }
 
    /**
     * 获取作答结果
     *
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo)
    { 
    	$map = array(
		    '躯体化' => array(
		    	'indexs' => '1, 4, 12, 27, 40, 42, 48, 49, 52, 53, 56, 58', // 题目
		    	'成年人平均分' => 1.37,
		    	'患者平均分' => 2.39,
		    	'SD' => 0.48, // 上下浮动
		    	'档次划分' => '',
		    ),
		    '强迫症状' => array(
		    	'indexs' => '3, 9, 10, 28, 38, 45, 46, 51, 55, 65',
		    	'成年人平均分' => 1.62,
		    	'患者平均分' => 2.92,
		    	'SD' => 0.58,
		    ),
		    '人际关系敏感' => array(
		    	'indexs' => '6, 21, 34, 36, 37, 41, 61, 69, 73',
		    	'成年人平均分' => 1.65,
		    	'患者平均分' => 2.60,
		    	'SD' => 0.61,
		    ),
		    '抑郁' => array(
		    	'indexs' => '5, 14, 15, 20, 22, 26, 29, 30, 31, 32, 54, 71, 79',
		    	'成年人平均分' => 1.50,
		    	'患者平均分' => 3.17,
		    	'SD' => 0.59,
		    ),
		    '焦虑' => array(
		    	'indexs' => '2, 17, 23, 33, 39, 57, 72, 78, 80, 86',
		    	'成年人平均分' => 1.39,
		    	'患者平均分' => 2.59,
		    	'SD' => 0.43,
		    ),
		    '敌对' => array(
		    	'indexs' => '11, 24, 63, 67, 74, 81',
		    	'成年人平均分' => 1.46, // 1.48
		    	'患者平均分' => 2.13,
		    	'SD' => 0.55,
		    ),
		    '恐惧' => array(
		    	'indexs' => '13, 25, 47, 50, 70, 75, 82',
		    	'成年人平均分' => 1.23,
		    	'患者平均分' => 2.05,
		    	'SD' => 0.41,
		    ),
		    '偏执' => array(
		    	'indexs' => '8, 18, 43, 68, 76, 83',
		    	'成年人平均分' => 1.43,
		    	'患者平均分' => 2.28,
		    	'SD' => 0.57,
		    ),
		    '精神病性' => array(
		    	'indexs' => '7, 16, 35, 62, 77, 84, 85, 87, 88, 90',
		    	'成年人平均分' => 1.29,
		    	'患者平均分' => 1.94,
		    	'SD' => 0.42,
		    ),
		    '综合' => array(
		    	'indexs' => '19, 44, 59, 60, 64, 66, 89',
		    	'成年人平均分' => 1.44, // 1.37
		    	'患者平均分' => '2.56',
		    	'SD' => 0.48,
		    ),
		);
    	// {"name":"没有","img":""},{"name":"很轻","img":""},{"name":"中等","img":""},{"name":"偏重","img":""},{"name":"严重","img":""}
    	$optionScoreMap = array(0, 1, 2, 3, 4); // 选项分值
    	$totalScoreMap = array(); // 每种类型的总分
    	$indexMap = array();
    	foreach ($map as $type => $row) {
    		if (empty($row['indexs'])) {
    			continue;
    		}
    		$indexList = array_map('intval', explode(',', $row['indexs']));
    		foreach ($indexList as $key => $index) {
    			$indexMap[$index] = $type;
    		}
    		$totalScoreMap[$type] = count($indexList) * max($optionScoreMap);
    	}
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];

    	$commonConf = getStaticData($testPaperInfo['name'], 'common');

    	$scoreMap = array();
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		$type = $indexMap[$question['index']]; // 测试的类型
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		$userAnswerScore = $optionScoreMap[$userAnswer]; // 用户此题的得分
    		if (empty($scoreMap[$type])) {
    			$scoreMap[$type] = $userAnswerScore;
    		} else {
    			$scoreMap[$type] += $userAnswerScore;
    		}
    	}
    	$percentList = array();
    	$userTotalScore = 0; // 用户总得分
    	foreach ($totalScoreMap as $type => $totalScore) {
    		if (empty($map[$type]['indexs'])) {
    			continue;
    		}
    		$indexList = array_map('intval', explode(',', $map[$type]['indexs']));
    		$percentList[$type] = array(
    			'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type],
    			'totalScore' => $totalScore,
    		);
    		$percentList[$type]['divisorScore'] = number_format($percentList[$type]['score'] / count($indexList), 2); // 用户该项的因子
    		$percentList[$type]['adultsAvgScore'] = $map[$type]['成年人平均分'];
    		$percentList[$type]['patientsAvgScore'] = $map[$type]['患者平均分'];
    		$userTotalScore += $percentList[$type]['score'];
    		// 症状
    		$symptom = '正常';
    		if ($percentList[$type]['divisorScore'] < 2) {
    			$symptom = '正常';
    		} elseif ($percentList[$type]['divisorScore'] < 3) {
    			$symptom = '有症状';
    		} else {
    			$symptom = '症状明显';
    		}
    		$percentList[$type]['symptom'] = $symptom;
    	}
    	// 总得分
    	// 阳性项目数：单项分≥2的项目数，表示受检者在多少项目上呈有“病状〞。
    	// 阴性项目数：单项分=1的项目数，表示受检者“无病症〞的项目有多少。
    	// 阳性病症均分：〔总分－阴性项目数〕/阳性项目数，表示受检者在“有病症〞项目中的平均得分。反映受检者自我感觉不佳的项目，其严重程度终究介于哪个围。
  		$totalScore = array_sum($totalScoreMap);
  		// 0 ~ 4
  		$userAvgScore = number_format(empty($totalScore) ? 0 : $userTotalScore / $totalScore, 2); // 总均分
  		$userScorePercent = empty($totalScore) ? 0 : intval(100 * $userTotalScore / $totalScore); // 用户总分百分比
  		
  		$levelAvg = 100 / count($commonConf['整体诊断']);

  		$levelMap = array();
  		$index = 1; // 从 0 ~  100 均分6个等级
  		foreach ($commonConf['整体诊断'] as $levelName => $value) {
  			$levelMap[$levelName] = ceil($index * $levelAvg);
  			$index++;
  		}
  		$overallLevel = '';
  		foreach ($levelMap as $levelName => $limitValue) {
  			if ($limitValue >= $userScorePercent) {
  				$overallLevel = $levelName;
  				break;
  			}
  		}
    	return array(
    		'overallLevel' => $overallLevel,
    		'userScorePercent' => $userScorePercent, // 用户得分占比
    		'userTotalScore' => $userTotalScore, // 用户总得分
    		'totalScore' => $totalScore, // 用户总得分
    		'percentList' => $percentList,
    	);
    }
    
    /**
     * 获取报告
     * 
     * @return array
     */
    public function getReport($testOrderInfo, $testPaperInfo)
    {
    	// 获取作答结果
    	$answerResult = $this->getAnswerResult($testOrderInfo, $testPaperInfo);
    	$percentList = $answerResult['percentList'];
    	$staticData = getStaticData($testPaperInfo['name'], 'common');
    	// 总体情况
    	$id = 1;
    	
    	$meshList = array();
    	$fanthreeList = array();
    	$abnormal_mesh_list = array(); // 因子分超过2
    	if (is_iteratable($percentList)) foreach ($percentList as $type => $scoreRow) {
    		if (empty($staticData['类型'][$type])) {
    			continue;
    		}
    		$conf = $staticData['类型'][$type];
    		$meshModel = array(
    			'id' => $id++,
    			'weidu_name' => $type,
    			'total_score' => $scoreRow['totalScore'], // 总分
    			'extend' => array(
    				'adults_avg_score' => $scoreRow['adultsAvgScore'], 
    				'patients_avg_score' => $scoreRow['patientsAvgScore']
    			),
    			'mesh_type' => 1,	
    			'last_percent' => $scoreRow['divisorScore'],
    		);
    		$meshList[] = $meshModel;
    		$jianyi = ''; // 建议
    		$resultExplain = array();
    		$resultExplain[] = $conf['简介'];
    		if ($scoreRow['symptom'] == '正常' && !empty($conf[$scoreRow['symptom']])) {
    			$resultExplain[$scoreRow['symptom']] = '根据您的测试结果，' . $conf[$scoreRow['symptom']];
    		} elseif (!empty($conf['有症状'])) {
    			$resultExplain[] =  '根据您的测试结果，' . $conf['有症状'];
    			if (!empty($conf['建议'])) {
    				$jianyi = $conf['建议'];
    			}
    		}
    		$fanthreeList[] = array(
    			'id' => $id++,
    			'weidu_name' => $type,
    			'total_score' => $scoreRow['score'], // 用户得分
    			'extend' => array(
    				'duorenshu' => array(
    					'max_score' => '', 
    					'min_score' => ''
    				)
    			),
    			'fan_type' => 1,
    			'weidu_result' => array(
    				'id' => $id++,
    				'name' => $scoreRow['symptom'], // 症状
    				'jianyi' => self::toHtmlTag($jianyi),
    				'result_explain' => self::toHtmlTag($resultExplain)
    			),
    			'last_percent' => $scoreRow['divisorScore'],
    			'user_score' => $scoreRow['score'],
    		);
    		if ($scoreRow['divisorScore'] >= 2) {
    			$abnormal_mesh_list[] = $meshModel;
    		}
    	}
		$fanthreeDesc = <<<EOT
<div class ='img-main'><img src="抑郁症-量表.png" /></div>
<p>以下是在每个具体症状维度上的详细解析，您需要特别关注出现症状的维度。</p>		
EOT
;	
		$commonSv = \service\Common::singleton();
   		$reportModel = array(
   			'total_result_scoring' => array(
	    		'paper_tile' => 'scl-90',
	    		'jifen_guize' => 2, // 计分规则 
	    		'setting' => array(
	    			'jifen_guize' => 2,
	    			'zongjieguo_image' => $commonSv::formartImgUrl('001.png', 'report'),
	    			'title' => '您的抑郁程度',
	    			'title_icon_image' => 'fa-line-chart',
	    		),
	    		'last_percent' => $answerResult['userTotalScore'], // 占比
	    		'content' => array(
	    			'name' => $answerResult['overallLevel'], // 等级
	    			'zhuyi' => self::toHtmlTag('以上自评结果展示了您最近一周的情绪状况，仅作为参考使用，不能作为专业心理诊断的依据。'), // 温馨提示
	    			'result_explain' => self::toHtmlTag($staticData['整体诊断'][$answerResult['overallLevel']]),
	    		),
	    	),
   			'duoweidu_mesh' => array(
   				'meshList' => $meshList,
   				'abnormal_mesh_list' => $abnormal_mesh_list, // 异常维度
   				'setting' => array(
   					'mesh_type' => 1,
   					'title' => '您的精神症状评估',
   					'title_icon_tag' => 'fa-user-md',
   					'jianjie' => self::toHtmlTag(array(
					    '以下为您在测评中各个常见心理症状的具体得分情况。每个症状维度的满分为4分，分数越高表示症状越显著。通常，得分超过2分表示存在阳性症状，需要进一步评估。同时提供了成年人和心理障碍患者在每个维度上的平均得分以供参考。',
					    '需要注意的是：这些结果基于普遍人群常模，不一定完全适用于您的情况，必须通过专业医师的分析和调整，才能得出适合您的准确结果。',
					))
   				),
   			),
   			'extend_read' => self::componentExtendRead('关于SCL90自测量表', $staticData['关于SCL90自测量表']),
   			'fanthree' => array(
   				'fanthreeList' => $fanthreeList,
   				'setting' => array(
   					'fan_type' => 1,
	    			'title' => '具体症状情况如下',
	    			'title_icon_tag' => 'fa-free-code-camp',
	    			'jianjie' => $commonSv::replaceImgSrc($fanthreeDesc, 'report')
	    		),
   			),
   		);
   		return $reportModel;
    }

}