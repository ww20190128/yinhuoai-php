<?php
namespace service\report;

/**
 * 荣格
 * 
 * @author 
 */
class Jung extends \service\report\ReportBase
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
     * @return Jung
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Jung();
        }
        return self::$instance;
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
    	return $testOrderQuestionInfo;
    }
    
    /**
     * 用户匹配
     *
     * @return string
     */
    private static function formatRemark($reportMbtiEtt)
    {
    	return
    	<<<EOT
<div class="cb-a"><i class="fa fa-codepen"></i><span>当前影响你最多的荣格原型是</span></div>
<p>所有原型都存在于人们共通的集体无意识中，在不同的个人身上经常出现的原型会有所区别，此外个人在不同的境遇或阶段中还会接触到不同的原型。目前阶段，和你联系较多的原型是：</p>
<h6 style="text-align: center;"><span style="color: #b9a755;">{$reportMbtiEtt->name}</span></h6>
<p><img src="{$reportMbtiEtt->bgImg}" style="display: block; margin-left: auto; margin-right: auto;" /></p>
EOT;
    }
    
    /**
     * 详细
     *
     * @return string
     */
    private static function formatDesc($reportMbtiEtt)
    {
    	$coexistContentList = array();
    	for ($index = 1; $index <= 6; $index++) {
    		$coexistTitlePro = 'coexistTitle' . $index;
    		$coexistContentPro = 'coexistContent' . $index;
    		if (empty($reportMbtiEtt->$coexistTitlePro)) {
    			continue;
    		}
    		$coexistContentList[] = <<<EOT
<p><span style="font-size: .46rem; color: #b9a755;">{$reportMbtiEtt->$coexistTitlePro}</span></p>
{$reportMbtiEtt->$coexistContentPro}
EOT;
    	}
    	$coexistContentList = implode('', $coexistContentList);
    	return
    	<<<EOT
<div class="cb-a1"><i class="fa fa-snapchat-ghost"></i><span>{$reportMbtiEtt->name}</span></div>
<p><span style="font-size: .46rem; color: #b9a755;">{$reportMbtiEtt->desc}</span></p>
<p><img src="{$reportMbtiEtt->bgImg}" style="border-radius: 15px; display: block; margin-left: auto; margin-right: auto;" /></p>
<div class="cb-a1"><i class="fa fa-cog"></i><span>原型介绍</span></div>
<p><span style="font-size: .46rem; color: #b9a755;">{$reportMbtiEtt->archetypeTags}</span></p>
<p>{$reportMbtiEtt->archetypeDesc}</p>
<div class="cb-a1"><i class="fa fa-sun-o"></i><span>正面含义</span></div>
<p><span style="font-size: .46rem; color: #b9a755;">{$reportMbtiEtt->positiveTags}</span></p>
<p>{$reportMbtiEtt->positiveDesc}</p>
<div class="cb-a1"><i class="fa fa-snowflake-o"></i><span>负面含义</span></div>
<p><span style="font-size: .46rem; color: #b9a755;">{$reportMbtiEtt->negativeTags}</span></p>
<p>{$reportMbtiEtt->negativeDesc}</p>
<div class="cb-a1"><i class="fa fa-modx"></i><span>对你的影响</span></div>
{$reportMbtiEtt->influence}
<div class="cb-a1"><i class="fa fa-leaf"></i><span>共处的方法</span></div>
{$coexistContentList}
EOT;
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
    			$percentList[$row['name']]['percent'] = self::getPercent($correctNum, $correctNum + $errorNum, 2);
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
    {
    	// 获取作答结果
    	$answerResult = $this->getAnswerResult($testOrderInfo, $testPaperInfo);
    	$reportJungDao = \dao\ReportJung::singleton();
    	$reportJungEttList = $reportJungDao->readListByWhere();
    	$jifenPailieList = array(); 
    	$id = 1;
    	if (is_iteratable($reportJungEttList)) foreach ($reportJungEttList as $reportJungEtt) {
    		$jifenPailieList[] = array(
    			'id' => $id++,
    			'weidu_name' => $reportJungEtt->name,
    			'total_score' => 24,
    			'weidu_icon_color' => '#b8a755',
    			'jianjie' => $reportJungEtt->desc,
    			'last_percent' => 42,
    			'total_result_remark' => self::formatRemark($reportJungEtt),
    			'xiangxi' => self::formatDesc($reportJungEtt),
    		);
    	}
    	
    	$report = array(
    		'jifen_pl' => array(
	    		'jifenPailieList' => $jifenPailieList,
	    		'setting' => array(
		    		'pl_type' => 1,
		    		'title1' => '你在九个原型上的匹配度',
		    		'title_icon_tag1' => 'fa-sliders',
		    		'title2' => '原型特别深入解析',
		    		'title_icon_tag2' => 'fa-thermometer-4',
		    		'pl_jieshao' => '每个人的性格都有多种原型的影子，以下是你与每一种人格原型的匹配度：',
		    		'title2_jieshao' => '目前阶段，与你联系较多的原型是：',
		    	),
    		),
    	);
    	return $report;
    }

}