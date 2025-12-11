<?php
namespace service\report;

/**
 * ReportBase
 * 
 * @author 
 */
class ReportBase extends \service\ServiceBase
{
	use HtmlTitle;
	use HtmlTag;
	use HtmlContent;
	use HtmlImg;
	
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return ReportBase
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportBase();
        }
        return self::$instance;
    }
    
    /**
     * 获取作答结果(scoreValue 包含选项得分)
     *
     * @return array
     */
    public static function getAnswerResultByScoreValue($testOrderInfo, $testPaperInfo)
    {
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    	$commonConf = getStaticData($testPaperInfo['name'], 'common');
    	$totalScore = 0; // 总分
    	$score = 0; // 得分
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		if (!isset($answerList[$question['id']])) {
    			continue;
    		}
    		$selections = empty($question['selections']) ? array() : $question['selections'];
    		$optionScoreMap = empty($question['scoreValue']) ? array() : str_split($question['scoreValue']); // 每个选项的得分
    		if (empty($optionScoreMap) || count($optionScoreMap) != count($selections)) {
    			continue;
    		}
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		$userAnswerScore = $optionScoreMap[$userAnswer]; // 用户此题的得分
    		$totalScore += max($optionScoreMap);
    		$score += $userAnswerScore;
    	}
    	$percent = self::getPercent($score, $totalScore, 0);
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
    		'percent' => $percent, // 整体占比
    		'score' => $score, // 总得分
    		'totalScore' => $totalScore, // 总分
    		'levelConf' => $levelConf,
    	);
    }
    
    /**
     * 获取作答结果(scoreValue 包含分类)
     *
     * @return array
     */
    public static function getAnswerResultByClassify($testOrderInfo, $testPaperInfo, $classifyMap)
    {
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    	$commonConf = getStaticData($testPaperInfo['name'], 'common');
    	$totalScore = array(); // 总分
    	$score = array(); // 得分
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		if (!isset($answerList[$question['id']])) {
    			continue;
    		}
    		$selections = empty($question['selections']) ? array() : $question['selections'];
    		$optionScoreMap = empty($question['scoreValue']) ? array() : str_split($question['scoreValue']); // 每个选项的得分
    			
    		if (empty($optionScoreMap) || count($optionScoreMap) != count($selections)) {
    			continue;
    		}
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		$userAnswerClassify = $optionScoreMap[$userAnswer]; // 用户此题的分类
    
    		if (empty($scoreMap[$userAnswerClassify])) {
    			$scoreMap[$userAnswerClassify] = 1;
    		} else {
    			$scoreMap[$userAnswerClassify] += 1;
    		}
    	}
    	// 第一个分类的得分
    	$score = empty($scoreMap[reset($classifyMap)]) ? 0 : $scoreMap[reset($classifyMap)];
    	// 总分
    	$totalScore = array_sum($scoreMap);
    	$percent = self::getPercent($score, $totalScore, 0); // 第一个分类的占比
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
    		'percent' => $percent, // 整体占比
    		'score' => $score, // 总得分
    		'totalScore' => $totalScore, // 总分
    		'levelConf' => $levelConf,
    	);
    }
    
    /**
     * 获取作答结果(多维度)
     *
     * @return array
     */
    public static function getAnswerResultByDimension($testOrderInfo, $testPaperInfo)
    {
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    
    	$commonConf = getStaticData($testPaperInfo['name'], 'common');

    	$totalScoreMap = array(); // 得分
    	$scoreMap = array(); // 总分
    	$defaultOptionScoreMap = array(1, 0);
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		if (!isset($answerList[$question['id']])) {
    			continue;
    		}
    		$selections = empty($question['selections']) ? array() : $question['selections'];
    		
    		$optionScoreMap = empty($question['scoreValue']) ? $defaultOptionScoreMap : str_split($question['scoreValue']); // 选项分值
    		$testType = empty($question['testType']) ? '' : $question['testType']; // 测试维度
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		$userAnswerScore = $optionScoreMap[$userAnswer]; // 用户此题的得分
    		if (empty($totalScoreMap[$testType])) {
    			$totalScoreMap[$testType] = max($optionScoreMap);
    		} else {
    			$totalScoreMap[$testType] += max($optionScoreMap);
    		}
    		if (empty($scoreMap[$testType])) {
    			$scoreMap[$testType] = $userAnswerScore;
    		} else {
    			$scoreMap[$testType] += $userAnswerScore;
    		}
    	}

    	$score = 0; // 得分
    	$totalScore = 0; // 总分
    	$dimensionList = array();
    	foreach ($totalScoreMap as $type => $value) {
    		$dimensionList[$type] = array(
    			'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type],
    			'totalScore' => $value,
    			'typeName' => $type,
    			'percent' => self::getPercent(empty($scoreMap[$type]) ? 0 : $scoreMap[$type], $value), // 占比
    		);
    		$score += $dimensionList[$type]['score'];
    		$totalScore += $value;
    		$dimensionConf = empty($commonConf['dimensionList'][$type]) ? array() : $commonConf['dimensionList'][$type]; // 维度配置
    		$dimensionLevelList = empty($dimensionConf['levelList']) ? array() : $dimensionConf['levelList']; // 等级配置
  
    		// 根据阀值由小到大排序
    		$commonSv = \service\Common::singleton();
    		uasort($dimensionLevelList, array($commonSv, 'sortByThreshold'));
    		$dimensionLevelConf = array();
    		foreach ($dimensionLevelList as $dimensionLevelName => $row) {
    			if ($dimensionList[$type]['percent'] >= $row['threshold']) {
    				$row['levelName'] = $dimensionLevelName;
    				$dimensionLevelConf = $row;
    			} else {
    				break;
    			}
    		}
    		$dimensionList[$type]['levelConf'] = $dimensionLevelConf;
    	}

    	$totalPercent = self::getPercent($score, $totalScore);
    	$levelConf = array();
    	
    	$totalPercent = intval($totalPercent);

    	if (!empty($commonConf['levelList'])) { // 有总分等级
	    	$levelList = $commonConf['levelList']; // 等级配置
	    	// 根据阀值由小到大排序
	    	$commonSv = \service\Common::singleton();
	    	uasort($levelList, array($commonSv, 'sortByThreshold'));
	    	foreach ($levelList as $levelName => $row) {
	    		if ($totalPercent >= $row['threshold']) {
	    			$row['levelName'] = $levelName;
	    			$levelConf = $row;
	    		} else {
	    			break;
	    		}
	    	}
    	}

    	// 根据占比排序
    	uasort($dimensionList, array(self::$instance, 'sortByPercent'));
    	return array(
    		'percent' => $totalPercent, // 整体占比
    		'score' => $score, // 总得分
    		'totalScore' => $totalScore, // 总分
    		'levelConf' => $levelConf,
    		'dimensionList' => count($dimensionList) >= 2 ? $dimensionList : array(), // 维度
    	);
    }
    
    /**
     * 组件-扩展阅读
     * 对测评的介绍
     *
     * @return array
     */
    public static function componentExtendRead($title, $content)
    {
    	$commonSv = \service\Common::singleton();
    	$content = $commonSv::replaceImgSrc($content, 'report');
    	return array(
    		'setting' => array(
    			'title' => $title,
    			'content' => $content,
    		),
    	);
    }
    
    /**
     * 将数组转换成html标签
     *
     * @return array
     */
    public static function toHtmlTag($datas, $tag = 'p')
    {
    	$list = array();
    	if (is_array($datas) && is_iteratable($datas)) {
    		foreach ($datas as $row) {
    			if (is_array($row)) {
    				foreach ($row as $k => $v) {
    					$list[] = "<{$tag}>" . $v . "</{$tag}>";
    				}
    			} else {
    				$list[] = "<{$tag}>" . $row . "</{$tag}>";
    			}
    			
    		}
    	} elseif (!empty($datas)) {
    		$list[] = "<{$tag}>" . $datas . "</{$tag}>";
    	}
		return implode('', $list);
    }

    /**
     * 根据占比排序
     *
     * @return int
     */
    public function sortByPercent($row1, $row2)
    {
    	if ($row1['percent'] < $row2['percent']) {
    		return 1;
    	} elseif ($row1['percent'] > $row2['percent']) {
    		return -1;
    	} else {
    		return $row1['score'] < $row2['score'] ? -1 : 1;
    	}
    }
    
    /**
     * 获取百分比
     *
     * @return int
     */
    public static function getPercent($num, $totalNum, $decimal = 2)
    {
    	return number_format(empty($totalNum) ? 0 : $num * 100 / $totalNum, $decimal);
    }
    
    /**
     * 获取作答结果(多维度)
     *
     * @param int  $optionScoreSort  选项分值排序  1  从小到大   [0, 1, 2, 3] 2  从大到小 [4, 3, 2, 1]
     *
     * @return array
     */
    private static function getAnswerResultbak($testOrderInfo, $testPaperInfo, $optionScoreSort = 1)
    {
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    
    	$staticData = getStaticData($testPaperInfo['name'], 'common');
    	$totalScoreMap = array(); // 得分
    	$scoreMap = array(); // 总分
    	$optionScoreMap = array(); // 选项分值分布表
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		if (!isset($answerList[$question['id']])) {
    			continue;
    		}
    		$selections = empty($question['selections']) ? array() : $question['selections'];
    		if (empty($optionScoreMap) && !empty($selections)) { // 获取选项分值分布
    			for ($optionScore = 0; $optionScore < count($selections); $optionScore++) {
    				$optionScoreMap[] = $optionScore;
    			}
    			if ($optionScoreSort == 2) { // 分值由大到小
    				$optionScoreMap = array_reverse($optionScoreMap);
    			}
    		}
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		$userAnswerScore = $optionScoreMap[$userAnswer]; // 用户此题的得分
    		$scoreValue = $question['scoreValue']; // 测试的类型
    
    		if (empty($totalScoreMap[$scoreValue])) {
    			$totalScoreMap[$scoreValue] = max($optionScoreMap);
    		} else {
    			$totalScoreMap[$scoreValue] += max($optionScoreMap);
    		}
    		if (empty($scoreMap[$scoreValue])) {
    			$scoreMap[$scoreValue] = $optionScoreMap[$userAnswer];
    		} else {
    			$scoreMap[$scoreValue] += $optionScoreMap[$userAnswer];
    		}
    	}
    	$score = 0; // 得分
    	$totalScore = 0; // 总分
    	$dimensionList = array();
    	$staticData = getStaticData($testPaperInfo['name'], 'common');
    	foreach ($totalScoreMap as $type => $value) {
    		$dimensionList[$type] = array(
    			'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type],
    			'totalScore' => $value,
    			'typeName' => $type,
    			'percent' => self::getPercent(empty($scoreMap[$type]) ? 0 : $scoreMap[$type], $value), // 占比
    		);
    		$score += $dimensionList[$type]['score'];
    		$totalScore += $value;
    		$dimensionConf = empty($staticData['维度'][$type]) ? array() : $staticData['维度'][$type]; // 维度配置
    	}
    	$totalPercent = self::getPercent($score, $totalScore);
    	if (empty($staticData['等级'])) {
    		return false;
    	}
    	$levelList = $staticData['等级'];
    	ksort($levelList);
    	$levelConf = array();
    	foreach ($levelList as $threshold => $row) {
    		if ($totalPercent >= $threshold) {
    			$levelConf = $row;
    		} else {
    			break;
    		}
    	}
    	// 根据占比排序
    	uasort($dimensionList, array(self::$instance, 'sortByPercent'));
    	return array(
    		'percent' => $totalPercent, // 整体占比
    		'score' => $score, // 总得分
    		'totalScore' => $totalScore, // 总分
    		'levelConf' => $levelConf,
    		'dimensionList' => count($dimensionList) >= 2 ? $dimensionList : array(), // 维度
    	);
    }
    
}