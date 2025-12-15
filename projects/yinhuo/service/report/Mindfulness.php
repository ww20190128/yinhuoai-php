<?php
namespace service\report;

/**
 * 正念指数测试
 * 
 * @author 
 */
class Mindfulness extends \service\report\ReportBase
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
     * @return Mindfulness
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Mindfulness();
        }
        return self::$instance;
    }
    
    /**
     * 解读
     *
     * @return string
     */
    private static function formatExplain($type, $conf)
    {
		$conf['描述'] = self::toHtmlTag($conf['描述']);
    	return <<<EOT
<p><strong>释义：</strong>{$conf['释义']}</p>
<p><strong>解析：</strong>{$conf['解析']}</p>
{$conf['描述']}
EOT;
    }
    
    /**
     * 获取作答结果
     *观察
1, 6, 11, 15, 20, 26, 31, 36 共8题
描述
2, 7, 12R, 16R, 22R, 27, 32, 37  共8题
觉知地行动
5R, 8R, 13R, 18R, 23R, 28R, 34R, 38R  共8题
不判断
3R, 10R, 14R, 17R, 25R, 30R, 35R, 39R  共8题
不行动
4, 9, 19, 21, 24, 29, 33 共7题
--------------------------------------------------------

链接：https://wenku.baidu.com/view/de7fbdd7f7335a8102d276a20029bd64793e6250.html
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo)
    { 
    	$map = array(
    		'觉察能力' => array(
    			'positive' => '1, 6, 11, 15, 20, 26, 31, 36',
    			'negative' => '',
    		),
    		'描述能力' => array(
    			'positive' => '2, 7, 27, 32, 37',
    			'negative' => '12, 16, 22',
    		),
    		'行动知觉' => array(
    			'positive' => '',
    			'negative' => '5, 8, 13, 18, 23, 28, 34, 38',
    		),
    		'不评判' => array(
    			'positive' => '',
    			'negative' => '3, 10, 14, 17, 25, 30, 35, 39',
    		),
    		'不妄动' => array(
    			'positive' => '4, 9, 19, 21, 24, 29, 33',
    			'negative' => '',
    		),
		);
    	$totalScoreMap = array(); // 每种类型的总分
    	$positiveIndexMap = array();
    	$negativeIndexMap = array();
    	foreach ($map as $type => $value) {
    		// 正向得分
    		$positiveIndexList = empty($value['positive']) ? array() : array_map('intval', explode(',', $value['positive']));
    		// 负向得分
    		$negativeIndexList = empty($value['negative']) ? array() : array_map('intval', explode(',', $value['negative']));
    		foreach ($positiveIndexList as $key => $index) {
    			$positiveIndexMap[$index] = $type;
    		}
    		foreach ($negativeIndexList as $key => $index) {
    			$negativeIndexMap[$index] = $type;
    		}
    		
    	}
    	ksort($positiveIndexMap);
    	ksort($negativeIndexMap);

    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];

    	// 统计各项得分
    	$scoreMap = array();
    	$totalScoreMap = array();
    	// [{"name":"非常符合"},{"name":"符合"},{"name":"不确定"},{"name":"不符合"},{"name":"非常不符合"}]
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		if (isset($positiveIndexMap[$question['index']])) { // 正向题
    			$type = $positiveIndexMap[$question['index']]; // 测试的类型
    			$score = array(5, 4, 3, 2, 1)[$userAnswer];
    		}  elseif (isset($negativeIndexMap[$question['index']])) { // 负向题
    			$type = $negativeIndexMap[$question['index']]; // 测试的类型
    			$score = array(1, 2, 3, 4, 5)[$userAnswer];
    		}
    		if (empty($scoreMap[$type])) {
    			$scoreMap[$type] = $score;
    		} else {
    			$scoreMap[$type] += $score;
    		}
    		if (empty($totalScoreMap[$type])) {
    			$totalScoreMap[$type] = 5;
    		} else {
    			$totalScoreMap[$type] += 5;
    		}
    	}
  
    	$percentList = array();

    	$userScore = 0; // 用户得分
    	foreach ($totalScoreMap as $type => $totalScore) {
    		$percentList[$type] = array(
    			'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type], // 用户该项得分
    			'totalScore' => $totalScore,
    			'percent' => 0,
    			'levelName' => '一般',
    		);
    		$userScore += $percentList[$type]['score'];
    		// 占比
    		$percentList[$type]['percent'] = self::getPercent($percentList[$type]['score'], $percentList[$type]['totalScore'], 0);
    		
    		if ($percentList[$type]['percent'] >= 85) {
    			$percentList[$type]['levelName'] = '较高';
    		} elseif ($percentList[$type]['percent'] >= 65) {
    			$percentList[$type]['levelName'] = '高';
    		} elseif ($percentList[$type]['percent'] >= 50) {
    			$percentList[$type]['levelName'] = '一般';
    		} elseif ($percentList[$type]['percent'] >= 40) {
    			$percentList[$type]['levelName'] = '偏低';
    		} else {
    			$percentList[$type]['levelName'] = '较低';
    		}
    	}
    	
    	// 根据占比排序
    	uasort($percentList, array(self::$instance, 'sortByPercent'));
		$percent = self::getPercent($userScore, array_sum($totalScoreMap));
		
		$commonConf = getStaticData($testPaperInfo['name'], 'common');
    	if (empty($commonConf['levelList'])) {
    		return false;
    	}
    	$levelList = $commonConf['levelList']; // 等级配置
    	// 根据阀值由小到大排序
    	$commonSv = \service\Common::singleton();
    	uasort($levelList, array($commonSv, 'sortByThreshold'));
    	$levelConf = array();
    	foreach ($levelList as $levelName => $row) {
    		if ($percent >= $row['threshold']) {
    			$row['levelName'] = $levelName;
    			$levelConf = $row;
    		} else {
    			break;
    		}
    	}
    	return array(
    		'percentList' => $percentList,
    		'levelConf' => $levelConf,
    		'percent' => intval($percent),
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
    	$levelConf = $answerResult['levelConf'];
		$total_result_scoring = array(
			'paper_tile' => '正念水平测试',	
		 	'jifen_guize' => 1,
		 	'setting' => array(
		 		'jifen_guize' => 1,
		 		'title' => '你的正念水平',
		 		'title_icon_type' => 1,
		 		'title_icon_image' => 'fa-check-circle',
		 		'title_icon_color' => '#f6727e',
		 		'icon2_touming' => '#FEDDDD',
		 	),
		 	'last_percent' => $answerResult['percent'],
		 	'content' => array(
		 		'name' => $levelConf['levelName'],
		 		'jianyi' => $levelConf['suggest'],
		 		'result_explain' => $levelConf['explain'],
		 	),
		);
		$commonConf = getStaticData($testPaperInfo['name'], 'common');
    	$id = 1;
    	$weiduList = array();
    	$meshList = array();
    
    	foreach ($percentList as $type => $row) {
    		if (empty($commonConf['dimensionList'][$type])) {
    			continue;
    		}
    		$conf = $commonConf['dimensionList'][$type];
    		$meshList[] = array(
    			'id' => $id++,
    			'weidu_name' => $type,
    			'total_score' => $row['totalScore'],
    			'jifen_type' => 1,
    			'last_percent' => $row['percent'],
    		);
    	
    		$weiduList[] = array(
    			'id' => $id++,
    			'weidu_name' => $type,
    			'extend' => array(
    				'tubiao_color' => '#f6727e',
    			),
    			'danweidu_type' => 3,
    			'weidu_result' => array(
    				'id' => $id++,
    				'name' => $row['levelName'],
    				'result_explain' => self::formatExplain($type, $conf),
    			),
    			'user_score' => $row['score'],
    			'last_percent' => $row['percent'],
    		);
        }

        $danweiduDesc = <<<EOT
<div class='img-center'><img src="正念-维度.png" /></div>
<p>以下是你在觉察能力、描述能力、行动知觉、不评判、不妄动五个维度上的具体水平分析：</p>   
EOT;
        $commonSv = \service\Common::singleton();
    	$reportModel = array(
    		'total_result_scoring' => $total_result_scoring,
    		'danweidu' => array(
    			'weiduList' => $weiduList,
    			'setting' => array(
    				'dan_weidu_type' => 3,
    				'title' => '正念保持维度解析',
    				'title_icon_tag' => 'fa-sliders',
    				'jianjie' => $commonSv::replaceImgSrc($danweiduDesc, 'report'),
    			),
    		),
    		'duoweidu_mesh' => array(
    			'meshList' => $meshList,
    			'setting' => array(
    				'mesh_type' => 6,
    				'title' => '正念保持维度分布图',
    				'title_icon_tag' => 'fa-pie-chart',
    				'tubiao_yangshi' => 1,
    				'jianjie' => '<p>保持正念的能力与觉察能力、描述能力、行动知觉、不评判、不妄动的水平有关，以下是你在这5个维度上的网状分析图：</p>',
    				'title_icon_color' => '#f6727e',
    				'icon_touming_color' => 'rgba(245,122,134,0.5)',
    			),
    		),
    		'extend_read' => self::componentExtendRead($commonConf['extendRead']['title'], $commonConf['extendRead']['content']),
    	);
   		return $reportModel;
    }

}