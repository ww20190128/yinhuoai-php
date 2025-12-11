<?php
namespace service\report;

/**
 * 盖洛普
 *
 * @author
 */
class Gallup extends \service\report\ReportBase
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
     * @return Gallup
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Gallup();
        }
        return self::$instance;
    }
    
    /**
     * 解读
     * 
     * @return string
     */
    private static function formatExplain($elementType, $elementRow)
    {
    	$marginBottom = self::marginBottom(50);
    	$desc = self::contentBg($elementRow['描述']);
    	// 优势
    	$index = 1;
    	$advantageList = array();
    	if (!empty($elementRow['优势'])) foreach ($elementRow['优势'] as $title => $content) {
    		$advantageList[] = self::combineBox($title, $content, $index, count($elementRow['优势']) >= 2 ? true : false, 0, 'red');
    		$index++;
    	}
    	$advantageList = implode('', $advantageList);
    	
    	// 盲点
    	$weaknessList = array();
    	$index = 1;
    	if (!empty($elementRow['盲点'])) foreach ($elementRow['盲点'] as $title => $content) {
    		$weaknessList[] = self::combineBox($title, $content, $index, count($elementRow['盲点']) >= 2 ? true : false, 0);
    		$index++;
    	}
    	$weaknessList = implode('', $weaknessList);
    	
    	// 如何更好地发挥优势
    	$exploitList = array();
    	$index = 1;
    	if (!empty($elementRow['发挥优势'])) foreach ($elementRow['发挥优势'] as $title => $list) {
    		$color = array('red', 'blue', 'green', 'orange')[$index - 1];
    		
    		
    		$exploitList[] = self::tagSuspension($title, $color, -20);
    		$subIndex = 1;
    		foreach ($list as $subTitle => $content) {
    			$exploitList[] = self::titleDot($subTitle);
    			$exploitList[] = self::content($content, 20);
    			
    			//$exploitList[] = self::combineBox($subTitle, self::toHtmlTag($content), $subIndex, count($list) >= 2 ? true : false);
    			$subIndex++;
    		}
    		
    		$index++;
    	}
    	$exploitList = implode('', $exploitList);
    	
    	// 如何领导具有“回顾”天赋的人
  
    	$leadMethodLiList = self::contentBg(self::toHtmlTag($elementRow['领导方法'], 'li'), 'purple');
    	// 职业推荐
    	$indexTitle1 = self::titleBigRound("{$elementType}的优势", 'sm');
    	$indexTitle2 = self::titleBigRound("{$elementType}的盲点", 'sm');
    	$indexTitle3 = self::titleBigRound('如何发挥优势', 'sm');
    	$indexTitle4 = self::titleBigRound("如何领导“{$elementType}”天赋的人", 'sm');
    	$indexTitle5 = self::titleBigRound('职业推荐', 'sm');
    	
    	$recommendCard = self::listBox(explode('、', $elementRow['职业推荐']) );
     	return
    	<<<EOT
{$desc}{$marginBottom}
{$indexTitle1}{$advantageList}{$marginBottom}
{$indexTitle2}{$weaknessList}{$marginBottom}
{$indexTitle3}{$exploitList}{$marginBottom}
{$indexTitle4}{$leadMethodLiList}{$marginBottom}
{$indexTitle5}{$recommendCard}
EOT;
    }
    
    /**
     * 获取作答答案
     *
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo, &$percentList = array())
    {
    	$scoreTable = getStaticData($testPaperInfo['name'], '评分表');
    	
    	// A = 100  O = 10  B = 1    AO = 110 AB = 101  OB = 11
    	$indexMap = array();
    	$typeMap = array();
    	foreach ($scoreTable as $index => $list) {
    		foreach ($list as $type => $val) {
    			$optionKeys = array();
    			if ($val == 100) { // 选择A
    				$optionKeys = array(0, 1);
    			} elseif ($val == 10) { // 选择 居中
    				$optionKeys = array(2);
    			} elseif ($val == 1) { // 选择  B 
    				$optionKeys = array(3, 4);
    			} elseif ($val == 111) { // 选择  A 0 B
    				$optionKeys = array(0, 1, 2, 3, 4);
    			} elseif ($val == 110) { // 选择  AO
    				$optionKeys = array(0, 1, 2);
    			} elseif ($val == 11) { // 选择  OB
    				$optionKeys = array(2, 3, 4);
    			} elseif ($val == 101) { // 选择  AB
    				$optionKeys = array(0, 1, 3, 4);
    			}
    			foreach ($optionKeys as $optionKey) {
    				$indexMap[$index][$optionKey] = $type;
    			}
    			$typeMap[$type][$index] = $index;
    		}
    	}
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    
    	// 统计各项得分
    	$scoreMap = array();
    	$totalScoreMap = array(); // 每种类型的总分
    	$answerScoreMap = array(3, 2, 1, 2, 3); // 特别同意  比较同意  居中   比较同意   特别同意
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		if (!isset($answerList[$question['id']])) {
    			continue;
    		}
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		if (empty($indexMap[$question['index']][$userAnswer])) {
    			continue;
    		}
    		$type = $indexMap[$question['index']][$userAnswer]; // 测试的类型
    		if (empty($scoreMap[$type])) {
    			$scoreMap[$type] = $answerScoreMap[$userAnswer];
    		} else {
    			$scoreMap[$type] += $answerScoreMap[$userAnswer];
    		}
    	}
	    $percentList = array();
	    foreach ($typeMap as $type => $indexs) {
	    	$totalScore = count($indexs) * max($answerScoreMap);
	    	$percentList[$type] = array(
	    		'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type],
	    		'totalScore' => $totalScore,
	    		'typeName' => $type,
	    		'percent' => self::getPercent(empty($scoreMap[$type]) ? 0 : $scoreMap[$type], $totalScore),
	    	);
	    }
	    // 根据占比排序
	    uasort($percentList, array(self::$instance, 'sortByPercent'));
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
    	// 获取测试结果
    	$answerResult = $this->getAnswerResult($testOrderInfo, $testPaperInfo);
    	$percentList = $answerResult['percentList'];
		$groupConf = getStaticData($testPaperInfo['name'], '维度');
		$elementConf = getStaticData($testPaperInfo['name'], '元素');
		
    
		$elementMap = array();
		foreach ($elementConf as $elementType => $row) {
			$elementMap[$row['所属类型']][$elementType] = $row;
		}
    	$duoweiduList = array();
    	$childWeiduList = array();
    	$elementSort = 1;
    	$commonSv = \service\Common::singleton();
    	if (is_iteratable($percentList)) foreach ($percentList as $elementType => $percentRow) {
    		if (empty($elementConf[$elementType])) {
    			continue;
    		}
    		$elementRow = $elementConf[$elementType]; // 元素配置
    		if (empty($groupConf[$elementRow['所属类型']])) {
    			continue;
    		}
    		$groupRow = $groupConf[$elementRow['所属类型']]; // 维度配置
    		$childWeiduList[$elementType] = array(
    			'weidu_name' => $elementType,
    			'p_weidu_name' => $elementRow['所属类型'],
    			'p_extend' => array(
    				'weidu_color' => $groupRow['维度颜色'] ,
    				'sort_icon' => $commonSv::formartImgUrl($groupRow['排序图标'], 'report'),		
    			),
    			'extend' => array(
    				'weidu_color' => $elementRow['颜色'],	
    				'content1' => self::formatExplain($elementType, $elementRow),
    			),
    			'jianjie' => self::toHtmlTag($elementRow['简介']),	
    			'avg_score' => $elementRow['平均分'],	
    			'last_percent' => $percentRow['percent'],
    			'score_sort' => $elementSort, // 分值排序
    		);
    		$elementSort++;
    	}
    	if (is_iteratable($groupConf)) foreach ($groupConf as $groupType => $groupRow) {
    		if (empty($elementMap[$groupType])) {
    			continue;
    		}
    		// 元素
    		$elementList = $elementMap[$groupType];
    		$childList = array();
    		$score = 0;
    		$totalScore = 0;
    		if (is_iteratable($elementList)) foreach ($elementList as $elementType => $elementRow) {
    			$percentRow = empty($percentList[$elementType]) ? array() : $percentList[$elementType];
    			if (empty($percentRow) || empty($childWeiduList[$elementType])) {
    				continue;
    			}
    			$childList[] = $childWeiduList[$elementType];
    			$score += $percentRow['score'];
    			$totalScore += $percentRow['totalScore'];
    		}
    		$duoweiduList[] = array(
    			'weidu_name' => $groupType,
    			'extend' => array(
    				'content1' => self::toHtmlTag($groupRow['描述']),
    				'sort_icon' => $commonSv ::formartImgUrl($groupRow['排序图标'], 'report'),
    				'weidu_icon' => $commonSv ::formartImgUrl($groupRow['维度图标'], 'report'),	
    				'weidu_color' => $groupRow['维度颜色'],
    				'weidu_title_color' => $groupRow['标题颜色'],
    			),
    			'jianjie' => self::toHtmlTag('<span style="text-wrap: wrap;">' . $groupRow['描述']),
    			'weidu_percent' => self::getPercent($score, $totalScore), // 维度占比
    			'childList' => $childList
    		);
    	}
    	
    	$report = array(
    		'duoweidu' => array(
    			'duoweiduList' => $duoweiduList,
    			'childWeiduList' => array_values($childWeiduList),
    			'setting' => array(
    				'duo_weidu_type' => 2,
    				'title' => '你最突出的五项优势天赋',
    				'jianjie' => self::toHtmlTag('您是独一无二的存在。独特的克利夫顿优势34个特质让您与众不同。这是您的才干基因，根据您对评估的回答以等级顺序显示！'),
    				'cgjs' => '下面是你最强的五个优势天赋，是最接近你的本能和潜意识的自然反应，对你的思维、感觉和行为方式都具有强大的主导作用，也是你最依赖、最高频使用的行为模式或行为惯性，最具有潜能优势的天赋。',
    			),
    		),
    	);
    	return $report;
    }
}