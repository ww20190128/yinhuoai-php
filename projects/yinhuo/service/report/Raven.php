<?php
namespace service\report;

/**
 * 瑞文国际标准智商测试
 * 
 * @author 
 */
class Raven extends \service\report\ReportBase
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
     * @return Raven
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Raven();
        }
        return self::$instance;
    }
    
    /**
     * 智商解读
     *
     * @return string
     */
    private static function formatIQ($userLevelConf)
    {
    	return
    	<<<EOT
<p>您的智商在同龄人中属于<span style="color: #f5576c !important;font-weight: 600 !important;">{$userLevelConf['等级描述']}</span>。瑞文智商测试主要评估<span style="color: #6699FF !important;font-weight: 600 !important;">知觉辨别、类比推理、比较推理、系列关系推理、抽象推理</span>等五个方面。以下报告将为您在这些维度上进行专业解读并提供详细的建议指导。</p>
EOT;
    }
    
    /**
     * 获取作答题目
     *
     * @return array
     */
    public function answerQuestionInfo($userId, $testOrderId, $onlyError = false)
    {
    	$orderSv = \service\Order::singleton();
    	$checkResult = $orderSv->checkTestOrderPay($testOrderId, $userId, true);
    	if (empty($checkResult['testComplete'])) {
    		throw new $this->exception('测试未完成，请继续答题');
    	}
    	if (!empty($checkResult['needPay'])) {
    		throw new $this->exception('订单未支付，请尽快支付');
    	}
    	$promotionInfo = empty($checkResult['testOrderInfo']['promotionInfo']) ? array() : $checkResult['testOrderInfo']['promotionInfo'];
    	$testPaperInfo = empty($checkResult['testOrderInfo']['testPaperInfo']) ? array() : $checkResult['testOrderInfo']['testPaperInfo'];
    	$payInfo = empty($checkResult['testOrderInfo']['payInfo']) ? array() : $checkResult['testOrderInfo']['payInfo'];
    	$testOrderInfo = empty($checkResult['testOrderInfo']['testOrderInfo']) ? array() : $checkResult['testOrderInfo']['testOrderInfo'];

    	// 作答记录
    	$userAnswerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];

    	// 获取题目
    	$testPaperSv = \service\TestPaper::singleton();
    	$testOrderQuestionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $userAnswerList);
    	
    	if (!empty($onlyError)) {
    		$questionList = empty($testOrderQuestionInfo['questionList']) ? array() : $testOrderQuestionInfo['questionList'];
    		if (is_iteratable($questionList)) foreach ($questionList as $key => $row) {
    			if ($row['userAnswer'] == $row['answer'] || empty($row['selections'])) {
    				unset($questionList[$key]);
    			}
    		}

    		$testOrderQuestionInfo['questionList'] = array_values($questionList);
    	}
   
    	return $testOrderQuestionInfo;   
    }
    
    /**
     * 获取测试结果
     *
     * @return array
     */
    public function getAnswerResult($testOrderInfo, $testPaperInfo)
    {
    	$answerQuestionInfo = $this->answerQuestionInfo($testOrderInfo['userId'], $testOrderInfo['id'], false);
    	$percentList = array(); // 每个题组得分占比
    	if (is_iteratable($answerQuestionInfo['questionList'])) foreach ($answerQuestionInfo['questionList'] as $row) {
    		if (empty($row['groupName'])) {
    			continue;
    		}
    		$selections = $row['selections'];
    		if (count($selections) == 1) { // 过滤引导题目
    			continue;
    		}
    		if (empty($percentList[$row['groupName']])) {
    			$percentList[$row['groupName']] = array(
    				'errorNum' => 0, // 错题数量
    				'correctNum' => 0,  // 正确题数量
    			);
    		}
    		if (!isset($row['userAnswer']) || !isset($row['answer'])) {
    			continue;
    		}
    		if ($row['userAnswer'] != $row['answer']) { // 答错了
    			$percentList[$row['groupName']]['errorNum']++;
    		} else {
    			$percentList[$row['groupName']]['correctNum']++;
    		}
    	}
    	foreach ($answerQuestionInfo['questionGroup'] as $row) {
    		if (empty($percentList[$row['name']])) {
    			$percentList[$row['name']] = array(
    				'errorNum' => 0,
    				'correctNum' => 0,
    				'percent' => 0,
    			);
    		} else {
    			$errorNum = $percentList[$row['name']]['errorNum'];
    			$correctNum = $percentList[$row['name']]['correctNum'];
    			$percentList[$row['name']]['percent'] = self::getPercent($correctNum, $correctNum + $errorNum, 0);
    		}
    	}
    	// 计算总题得分
    	$totalCorrectNum = 0; // 总答正确数量
    	$totalErrorNum = 0; // 总答错数量
    	foreach ($percentList as $key => $row) {
    		$totalErrorNum += $row['errorNum'];
    		$totalCorrectNum += $row['correctNum'];
    		$percentList[$key]['totalNum'] = $row['correctNum'] + $row['errorNum'];
    	}
    	// 用户整体正确占比
    	$userPercent = self::getPercent($totalCorrectNum, $totalCorrectNum + $totalErrorNum, 2);
		// 测评配置
    	$conf = getStaticData($testPaperInfo['name'], 'common');
    	$scoreTable = $conf['评分标准'];
    	$levelTable = $conf['智力等级'];
    	$userAge = $testOrderInfo['age']; // 用户选择的年龄
    	$keys = array_keys($scoreTable);
		if (empty($userAge)) {
			 throw new $this->exception('未选择年龄');
		}
    	$closest = null;
    	foreach ($keys as $key) {
    		if ($key <= $userAge) {
    			$closest = $key;
    		} else {
    			break;
    		}
    	}
		//(7) 96.67,(6) 95.00,(5) 91.67,(4) 86.67,(3) 78.33,(2) 66.67,(1) 61.67  (0)
    	$userScoreTable = empty($scoreTable[$closest]) ? array() : explode(',', $scoreTable[$closest]); // 用户的评分表
    	sort($userScoreTable);
    	// 档次 95 90 75 50  25  10  5
    	$userLevel = 0; // 用户智商等级  7 个等级
    	if ($userPercent < $userScoreTable['0']) { // 最低等级      低于  5%
    		$userLevel = 0; // 智商值
    		$percentMin = 0;
    		$percentMax = $userScoreTable['1'];
    	} elseif ($userPercent >= $userScoreTable['0'] && $userPercent < $userScoreTable['1']) { // 低于 10%
    		$userLevel = 1; // 智商值 
    		$percentMin = $userScoreTable['0'];
    		$percentMax = $userScoreTable['1'];
    	} elseif ($userPercent >= $userScoreTable['1'] && $userPercent < $userScoreTable['2']) { // 低于 25%
    		$userLevel = 2; // 智商值 
    		$percentMin = $userScoreTable['1'];
    		$percentMax = $userScoreTable['2'];
    	} elseif ($userPercent >= $userScoreTable['2'] && $userPercent < $userScoreTable['3']) { // 低于 50%
    		$userLevel = 3; // 智商值 
    		$percentMin = $userScoreTable['2'];
    		$percentMax = $userScoreTable['3'];
    	} elseif ($userPercent >= $userScoreTable['3'] && $userPercent < $userScoreTable['4']) { // 低于 75%
    		$userLevel = 4; // 智商值 
    		$percentMin = $userScoreTable['3'];
    		$percentMax = $userScoreTable['4'];
    	} elseif ($userPercent >= $userScoreTable['4'] && $userPercent < $userScoreTable['5']) { // 低于 90%
    		$userLevel = 5; // 智商值 
    		$percentMin = $userScoreTable['4'];
    		$percentMax = $userScoreTable['5'];
    	} elseif ($userPercent >= $userScoreTable['5'] && $userPercent < $userScoreTable['6']) { // 低于 95%
    		$userLevel = 6; // 智商值 
    		$percentMin = $userScoreTable['5'];
    		$percentMax = $userScoreTable['6'];
    	} elseif ($userPercent >= $userScoreTable['6']) { // 高于 95%
    		$userLevel = 7; // 智商值 
    		$percentMin = $userScoreTable['6'];
    		$percentMax = 100;
    	}
    	$userLevel = empty(array_keys($levelTable)[$userLevel]) ? array() : array_keys($levelTable)[$userLevel]; // 用户的智力等级
    	$userLevelConf = $levelTable[$userLevel]; // 该等级的配置数据
    	list($IQMin, $IQMax)  = explode('-', $userLevelConf['IQ']);
    	$IQ = $IQMin + ($userPercent - $percentMin) * ($IQMax - $IQMin) / ($percentMax - $percentMin);
    	return array(
    		'IQ' => $IQ,
    		'userPercent' => $userPercent,
    		'percentList' => $percentList,
    		'userLevel' => $userLevel,
    	);
    }
    
    /**
     * 获取报告
     *
     * @return array
     */
    public function getReport($testOrderInfo, $testPaperInfo)
    {	// 获取作答结果
    	$answerResult = $this->getAnswerResult($testOrderInfo, $testPaperInfo);
    	$percentList = $answerResult['percentList'];
    	// 测评配置
    	$conf = getStaticData($testPaperInfo['name'], 'common');
		$map = array(
			'测观察' => '类同比较',	
			'测分析' => '抽象推理',
			'测推理' => '知觉辨别',
			'测思维' => '比较推理',
			'测想象' => '系列推理',
		);
		$fantwoList = array();
		$id = 1;
		foreach ($percentList as $name => $row) {			
			$fantwoList[] = array(
				'id' => $id++,
				'weidu_name' => $map[$name],
				'fan_type' => 3,
				'last_percent' => $row['percent'], // 占比
			);
		}
		$fanthreeList = array();
		foreach ($conf['types'] as $name => $row) {
			$percentArr = empty($percentList[$row['groupName']]) ? array() : $percentList[$row['groupName']];
			$fanthreeList[] = array(
				'id' => $id++,
				'weidu_name' => $name,
				'total_score' => empty($percentArr['totalNum']) ? 0 : $percentArr['totalNum'],
				'extend' => array(
					'tubiao_color' => '#83a4f8',
				),	
				'fan_type' => 2,
				'jifen_type' => 1,
				'weidu_result' => array(
					'id' => $id++,
					'name' => "得分",
					'jianyi' => $row['指导建议'],
					'result_explain' => $row['描述'],
				),
				'last_percent' => empty($percentArr['percent']) ? 0 : $percentArr['percent'], // 占比
				'user_score' => empty($percentArr['correctNum']) ? 0 : $percentArr['correctNum'],
			);
		}
		$userLevelConf = $conf['智力等级'][$answerResult['userLevel']]; // 智力等级配置数据
		$fanthreeDesc = <<<EOT
<div class="img-main"><img src="大五人格-测试.png" /></div>
<p>以下是对您的智力五个维度的具体解析：</p>
EOT;
		$commonSv = \service\Common::singleton();
    	$reportModel = array(
    		'extend_read' => self::componentExtendRead('您的智力构成因素', $conf['你的智力构成因素']),
    		'fantwo' => array( // 动态
    			'fantwoList' => $fantwoList,
    			'setting' => array(
	    			'fan_type' => 3, // 网状图
    				'title' => '您的智力维度分布',
	    			'title_icon_tag' => 'fa-pie-chart',
	    			'jianjie' => self::toHtmlTag('以下是您的智力在每个维度中占比分布图：'),
	    		),
    		),
    		'fanthree' => array(
    			'fanthreeList' => $fanthreeList,
    			'setting' => array(
    				'fan_type' => 2,
    				'title' => '您的智商五大维度解读',
    				'title_icon_tag' => 'fa-list-alt',
    				'content' => $commonSv::replaceImgSrc($fanthreeDesc, 'report'),
    			),
    		),
    		'ruiwen_ord' => array(
    			'setting' => array(
					'title1' => '您的智商测试值',
					'title_icon_tag1' => 'fa-quora',
					'title2' => '适合您的职业',
					'title_icon_tag2' => 'fa-cc-mastercard',
					'zhuyi1' => '瑞文智力测试的结果可能会因受测者所处的环境和心理状态而有所影响，导致测试结果略有偏差。您可以点击页面顶部的“重测”按钮再次进行测试校对。为确保测试结果的准确性，建议两次测试需间隔48小时以上。',
		            'zhuyi2' => '智商高对于职业领域的成就有一定的提升作用，但同时影响职业成功的因素还有很多，如情商，逆商，职业性格，运气等，因此不应过于强调智商在工作中的重要性。',
					'share_image' => $commonSv::formartImgUrl('瑞文-分享.png', 'report'),
					'share_btn_text' => '分享我的测试结果',
				),
    			'ruiwenOrdInfo' => array(
    				'zhili_pinggu' => $answerResult['userLevel'],
    				'total_result_content' => self::formatIQ($userLevelConf),
    				'zhiye_tuijian' => '<p class="card-bg-purple">以下数据来源于大数据统计，分析了不同智商人群从事的职业，并为您推荐与您智商相近的适合职业。</p><p>此推荐仅供参考，旨在帮助您评估是否充分发挥了自己的智商天赋：</p>',
    			),
    			'professionInfo' => array(
    				'zhiliPinggu' => $answerResult['userLevel'],
    				'zhishang' => $answerResult['IQ'],
    				'job' => explode(',', $userLevelConf['职业推荐']),
    			),
    			'ageGroup' => array(
    				'min_age' => 14,
    				'max_age' => 15,
    			),
    		),
    		'cuoti_jiexi' => array(
    			'is_show' => 1,
    			'tishi' => $conf['错题集付费提示'], // 前端没有用到
    		)
    	);
    	return $reportModel;
    }

}