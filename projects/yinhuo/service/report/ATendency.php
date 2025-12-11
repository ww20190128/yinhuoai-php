<?php
namespace service\report;

/**
 * A型人格倾向
 * 
 * @author 
 */
class ATendency extends \service\report\ReportBase
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
     * @return ATendency
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ATendency();
        }
        return self::$instance;
    }

    /**
     * 解读
     * 
     * @return string
     */
    private static function formatExplain($userTypeName, $staticData)
    {
    	$content = self::contentBg($staticData['总结'], 'purple');
    	$advantageBox = self::combineBox("优点", $staticData['优点'], '', '', 0, 'red');
    	$deficiencyBox = self::combineBox("不足", $staticData['不足'], '', '', 0, 'blue');
    	$title2 = self::titleUnderline('你的人格解析');
    	return
    	<<<EOT
{$advantageBox}{$deficiencyBox}
{$title2}{$content}
EOT;
    }
    
    /**
     * 解读
     *
     * @return string
     */
    private static function formatExplain2($staticData)
    {
    	$suggestContent = self::contentBg($staticData['建议'], 'purple');
    	$readContent = self::contentBg($staticData['解读'], 'purple');
    	return "{$suggestContent}{$readContent}";
    }
    
    /**
     * 获取作答结果
     *
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo)
    { 
    	$map = array(
    		'TH' => array( // 25分      TH(Time & Hurry) 时间匆忙感
    			'0' => '2, 3, 6, 7, 10, 11, 19, 21, 22, 26, 29, 34, 38, 40, 42, 44, 46, 50, 53, 55, 58', // 回答“是”得分的题目
    			'1' => '14, 16, 30, 54', // 回答“否”得分的题目
    		),
    		'CH' => array( // 25分       CH(Competition & Hostility) 争强好胜
    			'0' => '1, 4, 5, 9, 12, 15, 17, 23, 25, 27, 28, 31, 32, 35, 39, 41, 47, 57, 59, 60',
    			'1' => '18, 36, 45, 49, 51',
    		),
    		'L' => array( // 掩饰分(测谎题, 满分10分)
    			'0' => '8, 20, 24, 43, 56',
    			'1' => '13, 33, 37, 48, 52',
    		),
		);
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    	
    	$indexMapA = array(); // 回答“是”的题序
    	$indexMapB = array(); // 回答“否”的题序
    	$totalScoreMap = array(); // 每种类型的总分
    	$indexMap = array();
    	foreach ($map as $type => $row) {
    		$indexList0 = array_map('intval', explode(',', $row['0'])); // 回答“是”得分的题目
    		$indexList1 = array_map('intval', explode(',', $row['1'])); // 回答“否”得分的题目
    		foreach ($indexList0 as $key => $index) {
    			$indexMapA[$index] = $type;
    			$indexMap[$index]['0'] = $type;
    		}
    		foreach ($indexList1 as $key => $index) {
    			$indexMapB[$index] = $type;
    			$indexMap[$index]['1'] = $type;
    		}
    		$totalScoreMap[$type] = count($indexList0) + count($indexList1);
    	}
    	
    	// 统计各项得分
    	$scoreMap = array();
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		$userAnswer = $answerList[$question['id']]; // 作答的答案
    		if ($userAnswer == 0) { // 回答: A 是
    			if (!isset($indexMapA[$question['index']])) {
    				continue;
    			}
    			if (empty($scoreMap[$indexMapA[$question['index']]])) {
    				$scoreMap[$indexMapA[$question['index']]] = 1;
    			} else {
    				$scoreMap[$indexMapA[$question['index']]] += 1;
    			}
    		} else { // 回答 B 否
    			if (!isset($indexMapB[$question['index']])) {
    				continue;
    			}
    			if (empty($scoreMap[$indexMapB[$question['index']]])) {
    				$scoreMap[$indexMapB[$question['index']]] = 1;
    			} else {
    				$scoreMap[$indexMapB[$question['index']]] += 1;
    			}
    		}
    	}
 
    	/**
    	 * 评分标准
    	 * TYPE = TH + CH  // TH(Time & Hurry):时间匆忙感     CH(Competition & Hostility):争强好胜
    	 * A 型 TYPE ≥ 37
    	 * A-型 30 ≤ TYPE < 37
    	 * M型    27 ≤ TYPE < 29
    	 * B-型 20 ≤ TYPE < 27
    	 * B 型 TYPE ≤ 19
    	 * 
    	 * TH  等级：偏低   中等水平  较高 
    	 * CH  等级：偏低   中等水平  较高
    	 * 人格类型：A型人格，B型倾向，典型B型
    	 */
    	$userScore = (empty($scoreMap['TH']) ? 0 : $scoreMap['TH']) + (empty($scoreMap['CH']) ? 0 : $scoreMap['CH']);
    	$userTypeName = 'A型人格'; 
    	$userType = 'A'; // 整体人格类型
    	if ($userScore <= 19) {
    		$userTypeName = '典型B型';
    		$userType = 'B';
    	} elseif ($userScore < 27) { 
    		$userTypeName = 'B型倾向';
    		$userType = 'B';
    	} elseif ($userScore < 29) { // 居中
    		$userType = 'M';
    		$userTypeName = 'B型倾向';
    		$userType = 'B';
    	} elseif ($userScore < 37) {
    		$userTypeName = 'A型人格';
    	} else {
    		$userTypeName = 'A型人格';
    	}
    	$percentList = array();
    	
    	$typeMap = array('TH' => '时间紧迫感', 'CH' => '竞争心理');
    	foreach ($totalScoreMap as $type => $totalScore) {
    		$percentList[$type] = array(
    			'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type],
    			'totalScore' => $totalScore,
    			'typeName' => empty($typeMap[$type]) ? '' : $typeMap[$type],
    			'level' => '中等水平',
    		);
    		// 占比
    		$percentList[$type]['percent'] = self::getPercent($percentList[$type]['score'], $percentList[$type]['totalScore']);
	    	if ($percentList[$type]['percent'] < 30) {
	    		$percentList[$type]['level'] = '偏低';
	    	} elseif ($percentList[$type]['percent'] > 70) {
	    		$percentList[$type]['level'] = '较高';
	    	}
    	}
    	
    	// 根据占比排序
    	uasort($percentList, array(self::$instance, 'sortByPercent'));
    	$totalScore = array_sum($totalScoreMap);
    	return array(
    		'userType' => $userType, // 人格类型
    		'userTypeName' => $userTypeName, // 人格类型名称
    		'percentList' => $percentList, // 用户得分情况
    		'userScore' => $userScore, // 用户得分
    		'userScorePercent' => empty($totalScore) ? 0 : intval(100 * $userScore / $totalScore), // 得分占比
    	);
    }
    
    /**
     * 获取报告
     * 
     * @return array
     */
    public function getReport($testOrderInfo, $testPaperInfo)
    {
    	// 获取结果
    	$answerResult = $this->getAnswerResult($testOrderInfo, $testPaperInfo);
    	$percentList = $answerResult['percentList'];
    	$staticData = getStaticData($testPaperInfo['name'], 'common');
    	
    	$id = 1;
    	$weiduList = array();
    	if (is_iteratable($percentList)) foreach ($percentList as $type => $scoreRow) {
    		if (empty($scoreRow['typeName'])) {
    			continue;
    		}
    		$weiduList[] = array(
    			'id' => $id++,
    			'weidu_name' => $scoreRow['typeName'],
    			'total_score' => $scoreRow['totalScore'], // 总分
    			'extend' => array('tubiao_color' => '#1cbbb4'),
    			'danweidu_type' => 3,	
    			'weidu_result' => array(
    				'name' => $scoreRow['level'],
    				'result_explain' => self::formatExplain2($staticData[$scoreRow['typeName']][$scoreRow['level']]),
    			),
    			'last_percent' => $scoreRow['percent'],
    			'user_score' => $scoreRow['score'], // 用户分数
    		);
    	}
  
    	$imgMain = self::imgMain('霍兰德-职业兴趣.png');
    	$jianjie = <<<EOT
{$imgMain}
<p>急性子（A型人格）的行为表现可以分为以下两个子维度，以下是你在每个维度上的具体得分：</p>
EOT;
   		return array(
   			'total_result_scoring' => array(
	    		'paper_tile' => 'A型人格倾向评估：你是个急性子吗？',
	    		'jifen_guize' => 1,
	    		'setting' => array(
	    			'jifen_guize' => 1,
	    			'title' => '你的A-B人格倾向',
	    			'title_icon_image' => 'fa-user-circle-o',
	    			'title_icon_type' => 3,
	    			'title_icon_color' => '#408d07',
	    			'l_weidu_title' => 'B型人格',
	    			'r_weidu_title' => 'A型人格',
	    			'icon2_touming' => '#FEDDDD',
	    		),
	    		'last_percent' => $answerResult['userScorePercent'], // 占比
	    		'renqun_percent' => 0, // 在人群中的占比
	    		'content' => array(
	    			'name' => $answerResult['userTypeName'] . '人格',
	    			'result_explain' => self::formatExplain($answerResult['userTypeName'], $staticData[$answerResult['userType']]),
	    		),
	    	),
   			'danweidu' => array(
   				'weiduList' => $weiduList,
   				'setting' => array(
   					'dan_weidu_type' => 3,
   					'title' => '急性子维度解析',
   					'title_icon_tag' => 'fa-clock-o',
   					'jianjie' => $jianjie,
   				),
   			),
   			'extend_read' => self::componentExtendRead('人格整合与提升', $staticData['人格整合与提升'], 'fa-level-up'),
   		);
    }

}