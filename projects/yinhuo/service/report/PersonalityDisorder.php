<?php
namespace service\report;

/**
 * 人格障碍
 * 
 * @author 
 */
class PersonalityDisorder extends \service\report\ReportBase
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
     * @return PersonalityDisorder
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PersonalityDisorder();
        }
        return self::$instance;
    }

    /**
     * 是否超过阀值
     * 
     * @return string
     */
    private static function formatAnode($conf, $type, $scoreRow)
    {
    	$list = array();
    	if (empty($scoreRow['anode'])) { // 正常
    		$list[] = "你的{$type}指数得分显示在正常范围内，你几乎没有抑郁型人格的迹象或特征。";
    		$list = array_merge($list, $conf['解析']);
    	} else { // 超过阀值
    		$list = $conf['解析'];
    		$list[] = "你的{$type}指数得分已经达到了筛查标准，应引起重视。你表现出一些依赖型人格的特征，这可能会对你的职业发展、社交活动、个人生活和伴侣关系等方面造成影响和限制。";
			$list[] = "是否确诊为依赖型人格障碍，需要评估这些依赖型人格特征对你生活造成的损害或限制的程度，并且需要精神科医生进行面诊评估。";
    		
    	}
    	return self::toHtmlTag($list);
    }
    
    /**
     * 解读
     *
     * @return string
     */
    private static function formatExplain2($staticData)
    {
		$staticData['建议'] = self::toHtmlTag($staticData['建议']);
		$staticData['解读'] = self::toHtmlTag($staticData['解读']);
    	return "{$staticData['建议']}{$staticData['解读']}";
    }
    
    /**
     * 获取作答结果
     *
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo)
    { 
    	$map =array(
    		'偏执型' => array( // 偏执型
        		'indexList' => '11, 24, 37, 50, 62, 85, 96',
        		'minSorce' => '4'
    		),
    		'依赖型' => array( // 依赖型
    			'indexList' => '2, 15, 27, 40, 53, 65, 82, 88',
    			'minSorce' => '5'
    		),
    		'强迫型' => array( // 强迫型
    			'indexList' => '3, 16, 29, 41, 54, 66, 81, 89, 99, 104, 105',
    			'minSorce' => '4'
    		),
    		'回避型' => array( // 回避型
    			'indexList' => '1, 13, 26, 39, 52, 83, 87, 98',
    			'minSorce' => '4'
    		),
    		'分裂型' => array( // 分裂型
    			'indexList' => '10, 23, 36, 48, 61, 72, 74, 86',
    			'minSorce' => '5'
    		),
    		'自恋型' => array( // 自恋型
    			'indexList' => ' 5, 18, 31, 44, 57, 68, 73, 79, 92, 103',
    			'minSorce' => '5'
    		),
    		'被动攻击型' => array( // 被动攻击型
    			'indexList' => '7, 21, 35, 49, 63, 77, 91',
    			'minSorce' => '4'
    		),
    		'边缘型' => array( // 边缘型
    			'indexList' => '6, 19, 32, 106, 45, 58, 69, 78, 93, 100, 101',
    			'minSorce' => '5'
    		),
    		'分裂样型' => array( // 分裂样
    			'indexList' => '9, 22, 34, 47, 60, 71, 95',
    			'minSorce' => '4'
    		),
    		'反社会型' => array( // 反社会型
    			'indexList' => '107, 8, 20, 33, 46, 59, 75, 94',
    			'minSorce' => '3'
    		),
    		'抑郁型' => array( // 抑郁型
    			'indexList' => '14, 28, 42, 56, 70, 84, 97',
    			'minSorce' => '5'
    		),
    		'表演型' => array( // 表演型
    			'indexList' => '4, 17, 30, 43, 55, 67, 80, 90, 102',
    			'minSorce' => '5'
    		),
    		// 可以考虑将其与“偏执型”或“回避型”相关联，可以将其归入“回避型”，因为回避型人格障碍通常涉及逃避和掩饰真实情感和行为。
// 		    '掩饰性' => array( // 备注：特殊类型， 该类型没配置。
// 		        'indexList' => '12, 25, 38, 51',
// 		        '标准' => '12、25、38回答否时计分；51回答是时计分≧2'
// 		    ),
		);
    	$totalScoreMap = array(); // 每种类型的总分
    	$indexMap = array();
    	foreach ($map as $type => $row) {
    		$indexList = empty($row['indexList']) ? array() : array_map('intval', explode(',', $row['indexList']));
    		foreach ($indexList as $key => $index) {
    			$indexMap[$index] = $type;
    		}
    		//$totalScoreMap[$type] = count($indexList0) + count($indexList1);
    	}
    	krsort($indexMap);
   
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    	
    	// 统计各项得分
    	$scoreMap = array();
    	$totalScoreMap = array();
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		if (!isset($indexMap[$question['index']])) { // 该题不计分
    			continue;
    		}
    		$type = $indexMap[$question['index']]; // 测试的类型
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		if (empty($totalScoreMap[$type])) {
    			$totalScoreMap[$type] = 1;
    		} else {
    			$totalScoreMap[$type] += 1;
    		}
    		if ($userAnswer == 0) { // 回答 A 是
    			if (empty($scoreMap[$type])) {
    				$scoreMap[$type] = 1;
    			} else {
    				$scoreMap[$type] += 1;
    			}
    		}
    	}
    	$percentList = array();
    	$userScore = 0;
    	$thresholdScore = 0; // 阀值得分
    	foreach ($totalScoreMap as $type => $totalScore) {
    		$percentList[$type] = array(
    			'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type], // 用户该项得分
    			'totalScore' => $totalScore,
    			'anode' => 0, // 0 阴性  1 阳性
    			'levelName' => '正常', // 过低，过高，正常
    		);
    		// 占比
    		$percentList[$type]['percent'] = self::getPercent($percentList[$type]['score'], $percentList[$type]['totalScore']);
	    	if ($percentList[$type]['score'] >= $map[$type]['minSorce']) { // 阳
	    		$percentList[$type]['anode'] = 1;
	    	} 
	    	$userScore += $percentList[$type]['score'];
	    	$thresholdScore += $map[$type]['minSorce'];
    	}
    	// 阀值
    	$totalScore = array_sum($totalScoreMap); // 总分
    	$thresholdPercent = empty($totalScore) ? 0 : intval(100 * $thresholdScore / $totalScore);
    	// 根据占比排序
    	uasort($percentList, array(self::$instance, 'sortByPercent'));
    	$userScorePercent = self::getPercent($userScore, $totalScore);
    	$differenceValue = $userScorePercent - $thresholdPercent;
    	$levelName = '正常';
    	$levelDesc = '你的分值正常';
    	if ($differenceValue >= 30) {
    		$levelName = '过高';
    		$levelDesc = '你的分值过高，已经高于筛查的阈值。';
    	} elseif ($differenceValue >= 10) {
    		$levelName = '偏高';
    		$levelDesc = '你的分值偏高，已经高于筛查的阈值。';
    	}
    	return array(
    		'percentList' => $percentList, // 用户得分情况
    		'userScore' => $userScore, // 用户得分
    		'thresholdPercent' => $thresholdPercent, // 阀值占比
    		'userScorePercent' => empty($totalScore) ? 0 : intval(100 * $userScore / $totalScore), // 得分占比
    		'levelName' => $levelName,
    		'levelDesc' => $levelDesc,
    	);
    }
    
    /**
     * 获取报告
     * 
     * @return array
     */
    public function getReport($testOrderInfo, $testPaperInfo)
    {
    	$answerResult = $this->getAnswerResult($testOrderInfo, $testPaperInfo);
    	$percentList = $answerResult['percentList'];
    	
    	$levelDesc = self::titleUnderline($answerResult['levelDesc']);
    	$leveContent = self::content('总体来看，你在社会活动、学习工作、人际交往和情感关系中的某些方面，可能会表现出一些不适宜的人格特质。这些特质可能对你的思维、认知、反应和情感关系造成明显的痛苦或对你的身心健康带来损害。请查看下面的具体维度解析，以了解你可能存在的非适应性人格特质。');
    	
    	
    	$total_result_scoring = array(
    		'paper_tile' => '人格障碍类型专业评估',
    		'jifen_guize' => 1, 
    		'setting' => array(
    			'title' => '人格障碍程度',
    			'title_icon_type' => 1,
    			'jifen_guize' => 1,
    			'title_icon_color' => '#4687ff',
    			'icon2_touming' => '#F1E423',
    		),
    		'last_percent' => $answerResult['userScorePercent'],
    		'renqun_percent' => 0,
    		'content' => array(
    			'name' => $answerResult['levelName'],
    			'zhuyi' => '<p>人格适应是指个体的稳定行为倾向与社会结构及互动过程相协调的状态。拥有健康人格的人，能顺应社会环境，积极地学习并完成社会活动任务，妥善处理家庭、学校和社会中的各种人际关系，从而在社会中体现自我价值，获得相应的社会认可与地位。</p>',
    			'result_explain' => "{$levelDesc}{$leveContent}",
    		),
    	);
    	$staticData = getStaticData($testPaperInfo['name'], '配置');
    	$id = 1;
    	$weiduList = array();
    	$meshList = array();
    	if (is_iteratable($percentList)) foreach ($percentList as $type => $row) {
    		$conf = empty($staticData['类型'][$type]) ? array() : $staticData['类型'][$type];
    		if (empty($conf)) {
    			continue;
    		}
    		$weiduList[] = array(
    			'id' => $id++,
    			'weidu_name' => $type,
    			'total_score' => $row['totalScore'], // 总分
    			'extend' => array('tubiao_color' => '#F6727E'),	
    			'danweidu_type' => 3,	
    			'weidu_result' => array(
    				'name' => $row['levelName'],
    				'zhuyi' => '<p>【诱因】</p>' . self::toHtmlTag($conf['诱因']),
    				'jianyi' => self::toHtmlTag($conf['建议']),
    				'result_explain' => self::formatAnode($conf, $type, $row), // 是否超过阀值
    			),
    			'last_percent' => $row['percent'],
    			'user_score' => $row['score'], // 用户分数
    		);
    		
    		$meshList[] = array(
    			'id' => $id++,
    			'weidu_name' => $type,
    			'total_score' => $row['totalScore'], // 总分
    			'extend' => array('tubiao_color' => '#F6727E', 'jian_jie' => $conf['简介']),
    			'mesh_type' => 2,
    			'jifen_type' => 1,
    			'last_percent' => $row['percent'],
    		);
    	}

    	// 详细解读
    	$imgMain = self::imgMain('大五人格-生理.png');
    	$desc = self::content('下列是你12种人格类型的评估分数，能够一定程度上反映你在这些人格类型中的不良表现程度。');
		$duoweidu_mesh_jianjie = "{$imgMain}{$desc}";
		
		$duoweidu_mesh_xiangxijieda = self::tabBox('解释', '<p>人格障碍是指个体的思维、认知、反应及情感关系的模式导致显著痛苦或功能损害。它是一种长期存在并影响生活各方面的行为模式。</p>
		<p>人格障碍的最终确诊需要精神科医生面诊，综合评估个人生活、工作、情感和人际关系等各方面情况。</p>');
		
		$imgMain = self::imgMain('创伤-治疗.png');
		$desc = self::content('下列是你的各人格类型的得分、状态分析、特征表现、诱因、认知测写及改善建议。', '', 'sm');
		$danweidu_jianjie = "{$imgMain}{$desc}";
    
   		$reportModel = array(
   			'total_result_scoring' => $total_result_scoring,
   			'danweidu' => array(
   				'weiduList' => $weiduList,
   				'setting' => array(
	    			'dan_weidu_type' => 3,
	    			'title' => '单项人格障碍分析',
	    			'jianjie' => $danweidu_jianjie,
    			),
   			),
   			'duoweidu_mesh' => array(
	    		'meshList' => $meshList,
	    		'setting' => array(
		    		'mesh_type' => 2,
		    		'title' => '人格障碍类型评估',
		    		'jianjie' => $duoweidu_mesh_jianjie,
	    			'xiangxijieda' => $duoweidu_mesh_xiangxijieda,  
	    		),	
    		),
   			'extend_read' => self::componentExtendRead('人格障碍评估理论基础', $staticData['关于测评你需要了解的']),
   		);
   		return $reportModel;
    }

}