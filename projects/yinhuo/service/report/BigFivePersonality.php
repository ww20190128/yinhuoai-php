<?php
namespace service\report;

/**
 * 大五人格专业测式
 * 
 * @author 
 */
class BigFivePersonality extends \service\report\ReportBase
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
     * @return BigFivePersonality
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BigFivePersonality();
        }
        return self::$instance;
    }

    /**
     * 拓展阅读
     * 
     * @return string
     */
    private static function formatExtendedReading($type, $staticData)
    {
    	$list = array();
    	foreach ($staticData['拓展阅读'] as $title => $row) {
    		$descContent = self::contentWireframe($row['描述']);
    		$mainImg = self::imgMain($row['图片']);
    		$list[] = 
    		<<<EOT
<div class="cb-a"><i class="fa fa-venus-mars"></i><span>{$type}与{$title}</span></div>
{$mainImg}
{$descContent}
EOT;
    	}
    	$cardSplit = self::cardSplit();
    	$list = implode($cardSplit, $list);
    	return $list;
    }
    
    /**
     * 解读
     *
     * @return string
     */
    private static function formatExplain($subType, $staticData)
    {
		$judgeContent = self::contentWireframe($staticData['判断']);
		$descContent = self::contentBg("<p><strong>{$subType} (Extraversion)</strong>" . $staticData['简介'], 'green');
    	return "{$descContent}{$judgeContent}";
    }
    
    /**
     * 获取作答结果
     *
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo)
    { 
    	$map = array(
		    '神经质' => array(
		        'positive' => '6, 11, 21, 26, 36, 41, 51, 56', // 正向
		        'negative' => '1, 16, 31, 46', // 反向
		        'subTypes' => array(
		        	'波动性' => array(
		        		'name' => 'Volatility',
		    			'positive' => '11, 36, 41, 51, 56',
                		'negative' => '1, 31, 46',
		        		'minPercent' => 45,
		    			'maxPercent' => 65,
		    		),
		        	'敏感性' => array(
		        		'name' => 'Withdrawal',
		    			'positive' => '6, 21, 26',
                		'negative' => '16',
		        		'minPercent' => 42,
		    			'maxPercent' => 62,
		    		),
		        ),
		    ),
		    '外向性' => array(
		        'positive' => '2, 7, 17, 22, 32, 37, 47, 52', // 正向
		        'negative' => '12, 27, 42, 57', // 反向
		    	'subTypes' => array(
		    		'热情' => array(
		    			'name' => 'Enthusiasm',
		    			'positive' => '2, 7, 22, 37, 47, 52',
                		'negative' => '12, 27, 42',
		        		'minPercent' => 37,
		    			'maxPercent' => 57,
		    		),
		    		'自信' => array(
		    			'name' => 'Assertiveness',
		    			'positive' => '17, 32',
                		'negative' => '57',
		        		'minPercent' => 44,
		    			'maxPercent' => 64,
		    		),
		    	),
		    ),
		    '开放性' => array(
		        'positive' => '13, 28, 43, 53, 58', // 正向
		        'negative' => '3, 8, 18, 23, 33, 38, 48', // 反向
		    	'subTypes' => array(
		    		'认知力' => array(
		    			'name' => 'Intellect',
		    			'positive' => '13, 43, 53, 58',
                		'negative' => '3, 8, 18, 23, 33, 38',
		        		'minPercent' => 54,
		    			'maxPercent' => 74,
		    		),
		    		'开放' => array(
		    			'name' => 'Openness',
		    			'positive' => '28',
                		'negative' => '48',
		        		'minPercent' => 61,
		    			'maxPercent' => 81,
		    		),
		    	),
		    ),
		    '宜人性' => array(
		        'positive' => '4, 19, 34, 49', // 正向
		        'negative' => '9, 14, 24, 29, 39, 44, 54, 59', // 反向
		    	'subTypes' => array(
		    		'共情' => array(
		    			'name' => 'Compassion',
		    			'positive' => '4, 19, 34, 49',
               	 		'negative' => '9, 14, 24, 29',
		        		'minPercent' => 47,
		    			'maxPercent' => 67,
		    		),
		    		'礼貌' => array(
		    			'name' => 'Politeness',
		    			'positive' => '',
                		'negative' => '39, 44, 54, 59',
		        		'minPercent' => 48,
		    			'maxPercent' => 68,
		    		),
		    	),
		    ),
		    '尽职性' => array(
		        'positive' => '5, 10, 20, 25, 35, 40, 50, 60', // 正向
		        'negative' => '15, 30, 45, 55', // 反向
		    	'subTypes' => array(
		    		'勤奋' => array(
		    			'name' => 'Industriousness',
		    			'positive' => '5, 10, 20, 35, 50, 60',
                		'negative' => '15, 30, 45',
		        		'minPercent' => 45,
		    			'maxPercent' => 65,
		    		),
		    		'条理性' => array(
		    			'name' => 'Orderliness',
		    			'positive' => '25, 40',
                		'negative' => '55',
		        		'minPercent' => 53,
		    			'maxPercent' => 73,
		    		),
		    	),
		    ),
		);
    	$totalScoreMap = array(); // 每种类型的总分
    	$positiveIndexMap = array();
    	$negativeIndexMap = array();
    	
    	$subTypeMap = array();
    	foreach ($map as $type => $row) {
    		$subTypes = empty($row['subTypes']) ? array() : $row['subTypes']; 
    		foreach ($subTypes as $subType => $value) {
    			// 正向得分
    			$positiveIndexList = empty($value['positive']) ? array() : array_map('intval', explode(',', $value['positive']));
    			// 负向得分
    			$negativeIndexList = empty($value['negative']) ? array() : array_map('intval', explode(',', $value['negative']));
    			foreach ($positiveIndexList as $key => $index) {
    				$positiveIndexMap[$index] = $subType;
    				$subTypeMap[$subType] = $type;
    			}
    			foreach ($negativeIndexList as $key => $index) {
    				$negativeIndexMap[$index] = $subType;
    				$subTypeMap[$subType] = $type;
    			}
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
    	$answerScoreMap = array(4, 3, 2 ,1, 0); // [{"name":"非常符合"},{"name":"符合"},{"name":"不确定"},{"name":"不符合"},{"name":"非常不符合"}]
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		if (!isset($answerList[$question['id']])) {
    			continue;
    		}
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		if (isset($positiveIndexMap[$question['index']])) { // 正向题
    			$subType = $positiveIndexMap[$question['index']]; // 测试的类型
    			$score = array(5, 4, 3, 2, 1)[$userAnswer];
    		}  elseif (isset($negativeIndexMap[$question['index']])) { // 负向题
    			$subType = $negativeIndexMap[$question['index']]; // 测试的类型
    			$score = array(1, 2, 3, 4, 5)[$userAnswer];
    		}
    		if (empty($scoreMap[$subType])) {
    			$scoreMap[$subType] = $score;
    		} else {
    			$scoreMap[$subType] += $score;
    		}
    		if (empty($totalScoreMap[$subType])) {
    			$totalScoreMap[$subType] = 5;
    		} else {
    			$totalScoreMap[$subType] += 5;
    		}
    	}
    	$userScoreMap = array();
    	foreach ($totalScoreMap as $subType => $totalScore) {
    		if (empty($subTypeMap[$subType])) {
    			continue;
    		}
    		$type = $subTypeMap[$subType];
    		$subConf = $map[$type]['subTypes'][$subType];
    		$userScoreMap[$type][$subType] = array(
    			'score' => empty($scoreMap[$subType]) ? 0 : $scoreMap[$subType], // 用户该项得分
    			'totalScore' => $totalScore,
    			'percent' => 0,
    			'name' => $subConf['name'],
		        'minPercent' => $subConf['minPercent'],
		    	'maxPercent' => $subConf['maxPercent'],
    		);
    		// 占比
    		$userScoreMap[$type][$subType]['percent'] = empty($userScoreMap[$type][$subType]['totalScore']) 
    			? 0 : intval(100 * $userScoreMap[$type][$subType]['score'] / $userScoreMap[$type][$subType]['totalScore']);
    	}
    	$percentList = array();
    	foreach ($userScoreMap as $type => $subTypeList) {
    		$score = 0;
    		$totalScore = 0;
    		$minPercent = 0;
    		$maxPercent = 0;
    		foreach ($subTypeList as $subType => $row) {
    			$score += $row['score'];
    			$totalScore += $row['totalScore'];
    			$minPercent += $row['minPercent'];
    			$maxPercent += $row['maxPercent'];
    		}
    		$percentList[$type] = array(
    			'percent' => empty($totalScore) ? 0 : intval(100 * $score / $totalScore),
    			'score' => $score,
    			'totalScore' => $totalScore,
    			'minPercent' => $minPercent * 0.5,
    			'maxPercent' => $maxPercent * 0.5,
    			'subTypeList' => $subTypeList,
    		);
    	}
    	// 根据占比排序
    	//uasort($userScoreMap, array(self::$instance, 'sortByPercent'));
    	return array(
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
    	$answerResult = $this->getAnswerResult($testOrderInfo, $testPaperInfo);
    	$percentList = $answerResult['percentList'];
    	$duoweiduParentList = array();
    	$id = 1;
    	$meshList = array();
    	foreach ($percentList as $type => $row) {
    		$staticData = getStaticData('大五人格专业测试', $type);
    		if (empty($staticData)) {
    			continue;
    		}
    		$child_duoweidu = array();
    		foreach ($row['subTypeList'] as $subType => $row) {
    			
    			$child_duoweidu[] = array(
    				'id' => $id++,
    				'weidu_name' => $subType,
    				'total_score' => $row['totalScore'],
    				'extend' => array(
    					'duorenshu' => array(
    						'max_score' => $row['maxPercent'],
    						'min_score' => $row['minPercent'],
    					), 
    					'weidu_color' => $staticData['字体颜色'],
    					'child_weidu_result' => 2,
    				),
    				'duoweidu_type' => 1,
    				'xiangxijieda' => '<p class="card-bg-purple">' . $staticData['子类'][$subType] . '</p>',
    				'user_score' => $row['score'],
    				'last_percent' => $row['percent'],
    			);
    		}
    		$duoweiduParentList[] = array(
    			'id' => $id++,
    			'weidu_name' => $type,
    			'total_score' => $row['totalScore'],
    			'extend' => array(
    				'weidu_show' => 2,
    				'weidu_color' => $staticData['字体颜色'],
    			),
    			'duoweidu_type' => 1,
    			'tuozhanyuedu' => self::formatExtendedReading($type, $staticData), // 拓展阅读
    			'weidu_result' => array(
    				'id' => $id++,
    				'name' => '适中',
    				'result_explain' => self::formatExplain($type, $staticData),
    			),
    			'user_score' => $row['score'],
    			'last_percent' => $row['percent'],
    			'child_duoweidu' => $child_duoweidu,	
    		);
    		$meshList[] = array(
    			'id' => $id++,
    			'weidu_name' => $type,
    			'total_score' => $row['totalScore'],
    			'extend' => array(),
    			'mesh_type' => 6,
    			'jifen_type' => 1,
    			'last_percent' => $row['percent'],
    		);
    	}
    	$duoweiduDesc = <<<EOT
<div class ='img-main'><img src="大五人格-测试.png" /></div>
<p>根据大五人格理论，人的性格包含外向性、宜人性、尽职性、神经质和开放性五种基本特质。每种特质都存在彼此对立的两级，而每个人的性格都是这五种特质不同程度的组合。</p>
<p>以下是你的五项人格特质概览：</p>				
EOT
;
		$commonSv = \service\Common::singleton();
    	$report = array(
    		'duoweidu' => array(
    			'duoweiduParentList' => $duoweiduParentList,
    			'setting' => array(
    				'duo_weidu_type' => 1,
    				'title' => '人格特质表现解析',
    				'title_icon_tag' => 'fa-bar-chart',
    				'jianjie' => '<p>以下是你在五种人格特质上的表现深度解析：</p>',
    				'show_type' => 1,
    			),
    		),
    		'duoweidu_mesh' => array(
    			'meshList' => $meshList,
    			'setting' => array(
    				'mesh_type' => 6,
    				'title' => '你的大五人格报告',
    				'title_icon_tag' => 'fa-cube',
    				'tubiao_yangshi' => 1,
    				'jianjie' => $commonSv::replaceImgSrc($duoweiduDesc, 'report'),
    				'title_icon_color' => '#f6727e',
    				'icon_touming_color' => 'rgba(245,122,134,0.5)',
    			),
    		),
    	);
    	
   		return $report;
    }

}