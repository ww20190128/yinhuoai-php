<?php
namespace service\report;

/**
 * mbti
 * 
 * @author 
 */
class MBTI extends \service\report\ReportBase
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
     * @return MBTI
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MBTI();
        }
        return self::$instance;
    }
    
    // 量表
    private static $elementMap = array(
        'E' => 'I', // 内向 => 外向
        'S' => 'N', // 实感 => 直觉
        'T' => 'F', // 理性 => 感性
        'J' => 'P'  // 判断 => 知觉
    );
    
    /**
     * 最擅长xxx的类型
     *
     * @return string
     */
    private static function formatAdept($reportMbtiEtt)
    {
    	$title1Bg = self::titleBigBg("最擅长{$reportMbtiEtt->adeptType}", 'blue');
    	$content = self::contentBg($reportMbtiEtt->adeptDesc);
    	return "{$title1Bg}{$content}";
    }

    /**
     * 组织元素介绍
     * 
     * @return string
     */
    private static function formatElementDesc($reportMbtiEtt)
    {
    	$elementArr = str_split($reportMbtiEtt->id);
        // 元素
        $reportMbtiElementDao = \dao\ReportMbtiElement::singleton();
        $reportMbtiElementEttList = $reportMbtiElementDao->readListByPrimary($elementArr);
        $reportMbtiElementEttList = $reportMbtiElementDao->refactorListByKey($reportMbtiElementEttList);
		$titleDiv = self::titleBigBilingual('这四个字母代表', 'WHAT DO THESE FOUR LETTERS REPRESENT');
		$pList = array();
		foreach ($elementArr as $element) {
			if (empty($reportMbtiElementEttList[$element])) {
				continue;
			}
			$reportMbtiElementEtt = $reportMbtiElementEttList[$element];
			$pList[] = "<p><strong>{$reportMbtiElementEtt->id}</strong><span>{$reportMbtiElementEtt->name}</span>{$reportMbtiElementEtt->desc}</p>";
		}
    	
    	$pStr = implode('', $pList);
    	$cardSplit = self::cardSplit();
    	return 
    	<<<EOT
{$titleDiv}<div class="element-introduce">{$pStr}</div>{$cardSplit}
EOT;
    }
    
    /**
     * 人口比例
     * 
     * @return string
     */
    private static function formatProportion($reportMbtiEtt, $version)
    {
    	$titleDiv = self::titleBigBilingual('人口比例', 'PROPORTION OF POPULATION');
    	$reportMbtiDao = \dao\ReportMbti::singleton();
    	$rankDatas = $reportMbtiDao = $reportMbtiDao->readListByWhere(1, array('id', 'totalRate', 'manRate', 'womanRate'));
    	$totalRateMap = $sexRateMap = array();
    	if (is_iteratable($rankDatas)) foreach ($rankDatas as $rankData) {
    	    $totalRateMap[$rankData->id] = $rankData->totalRate;
    	    $sexRateMap[$rankData->id] = $version == 1 ? $rankData->manRate : $rankData->womanRate;
    	}
    	arsort($totalRateMap);
    	$tmp1 = array_values($totalRateMap);
    	$tmp1 = array_unique($tmp1);
    	$tmp1 = array_values($tmp1);
    	arsort($sexRateMap);
    	$tmp2 = array_values($sexRateMap);
    	$tmp2 = array_unique($tmp2);
    	$tmp2 = array_values($tmp2);
    	
    	$totalRank = array_search($rankData->totalRate, $tmp1) + 1;
    	$sexRank = array_search($version == 1 ? $reportMbtiEtt->manRate : $reportMbtiEtt->womanRate, $tmp2) + 1;
    	$map = array('一', '二', '三', '四', '五', '六', '七', '八', '九', '十');
    	$rankTitle = '';
    	if ($totalRank == 1) {
    	    $rankTitle = '人口中最常见的类型';
    	} elseif (isset($map[$totalRank - 1])) {
    	    $rankTitle = "人口中第{$map[$totalRank - 1]}常见的类型";
    	} else {
    	    $rankTitle = '人口中稀有的类型';
    	}
    	$sexName = $version == 1 ? '男' : '女';
    	if ($sexRank == 1) {
    	    $rankTitle .= " | {$sexName}性中最常见的类型";
    	} elseif (isset($map[$sexRank - 1])) {
    	    $rankTitle .= " | {$sexName}性中第{$map[$sexRank - 1]}常见的类型";
    	} else {
    	    $rankTitle .= " | {$sexName}性中稀有的类型";
    	}
    	return
    	<<<EOT
{$titleDiv}
<!--3个图形-->
<div class="progress-container">
	<div class="progress-circle">
		<div class="title">占总人口</div>
		<svg class="circle-item">
			<circle stroke="var(--inactive-color)" fill-opacity="0"></circle>
			<circle stroke="#a324f8" class="circle-progress" fill-opacity="0" style="stroke-dasharray: calc(2 * 3.1415 * var(--r) * (4 / 100)), 1000"></circle>
		</svg>
		<div class="circle-info">
			<i class="fa fa-venus-mars"></i>
			<p>{$reportMbtiEtt->totalRate}%</p>
		</div>
	</div>
	<div class="progress-circle">
		<div class="title">占男性</div>
		<svg class="circle-item ">
			<circle stroke="var(--inactive-color)" fill-opacity="0"></circle>
			<circle stroke="#0059ff" class="circle-progress" fill-opacity="0" style="stroke-dasharray: calc(2 * 3.1415 * var(--r) * (4 / 100)), 1000"></circle>
		</svg>
		<div class="circle-info">
			<i class="fa fa-mars"></i>
			<p>{$reportMbtiEtt->manRate}%</p>
		</div>
	</div>
	<div class="progress-circle">
		<div class="title">占女性</div>
		<svg class="circle-item">
			<circle stroke="var(--inactive-color)" fill-opacity="0"></circle>
			<circle stroke="#ff00dd" class="circle-progress" fill-opacity="0" style="stroke-dasharray: calc(2 * 3.1415 * var(--r) * (5 / 100)), 1000"></circle>
		</svg>
		<div class="circle-info">
			<i class="fa fa-venus"></i>
			<p>{$reportMbtiEtt->womanRate}%</p>
		</div>
	</div>
</div>
<p class="text-center">{$rankTitle}</p>
EOT;
    }
    
    /**
     * 名人
     *
     * @return string
     */
    private static function formatFamousPeople($reportMbtiEtt)
    {
    	$famousPeopleArr = empty($reportMbtiEtt->famousPeople) ? '' : explode(',', $reportMbtiEtt->famousPeople);
    	$famousPeople = self::toHtmlTag($famousPeopleArr, 'span');
    	$titleDiv = self::titleBigBilingual('代表人物', 'REPRESENTATIVE PERSONAGE');
    		
    	$famousPeopleImg = self::imgMain($reportMbtiEtt->famousPeopleImg, true, 'report' . DS . 'MBTI' . DS . 'famousPeople');
    	$famousPeopleListBox = self::listBox($famousPeopleArr);
    	return "{$titleDiv}{$famousPeopleImg}{$famousPeopleListBox}";
    }
    
    
    /**
     * 价值观和动机
     *
     * @return string
     */
    private static function formatValueDesc($reportMbtiEtt)
    {
    	$titleDiv = self::titleBigBilingual('价值观和动机', 'VALUES AND MOTIVATION');
    	$content = self::contentBg($reportMbtiEtt->valueDesc);
    	return "{$titleDiv}{$content}";
    }
    
    /**
     * 性格特点
     *
     * @return string
     */
    private static function formatCharacterDesc($reportMbtiEtt)
    {
    	$titleDiv = self::titleBigBilingual('您的性格特点', 'CHARACTERISTICS OF PERSONALITY');
    	$characterAdvantageTagArr = empty($reportMbtiEtt->characterAdvantage) ? array() : explode(',', $reportMbtiEtt->characterAdvantage);
    	$characterDisadvantageTagArr = empty($reportMbtiEtt->characterDisadvantage) ? array() : explode(',', $reportMbtiEtt->characterDisadvantage);
    	$reportMbtiCharacterDao = \dao\ReportMbtiCharacter::singleton();
    	$reportMbtiCharacterEttList = $reportMbtiCharacterDao->readListByPrimary(array_merge($characterAdvantageTagArr, $characterDisadvantageTagArr));
    	$reportMbtiCharacterEttList = $reportMbtiCharacterDao->refactorListByKey($reportMbtiCharacterEttList);
    	// 优势
    	$characterAdvantageList = array();
    	foreach ($characterAdvantageTagArr as $key => $name) {
    		//$showIndex = true, $margin = 1, $color = 'bule'$showIndex = true, $margin = 1, $color = 'bule'
    		$characterAdvantageList[] = self::combineBox($name, $reportMbtiCharacterEttList[$name]->desc, '', false, 0, 'red');
    	}
    	$advantageContent = implode('', $characterAdvantageList);
    	
    	// 劣势
    	$characterDisadvantageList = array();
    	foreach ($characterDisadvantageTagArr as $key => $name) {
    		$characterDisadvantageList[] = self::combineBox($name, $reportMbtiCharacterEttList[$name]->desc, '', false, 0, 'blue');
    	}
    	$disadvantageContent = implode('', $characterDisadvantageList);
    	$advantageTag = self::tagRounded('优势', 'red', -10);
    	$advantageTag = self::titleBigRound('性格优势', 'sm');
    	$disadvantageTag = self::tagRounded('劣势', 'green', -10);
    	$disadvantageTag = self::titleBigRound('性格劣势', 'sm');
    	return "{$titleDiv}{$advantageTag}{$advantageContent}{$disadvantageTag}{$disadvantageContent}";
    }

    /**
     * 成长建议
     *
     * @return string
     */
    private static function formatSuggest($reportMbtiEtt)
    {
        $titleDiv = self::titleBigBilingual('成长建议', 'POTENTIAL EXPLORATION GUIDE');
        $suggestArr = empty($reportMbtiEtt->suggest) ? array() : explode(',', $reportMbtiEtt->suggest);
        $reportMbtiSuggestDao = \dao\ReportMbtiSuggest::singleton();
        $reportMbtiSuggestEttList = $reportMbtiSuggestDao->readListByPrimary($suggestArr);
        $list = array();
        $index = 1;
        foreach ($reportMbtiSuggestEttList as $key => $reportMbtiSuggestEtt) {
            $title = self::titleIndex($index, $reportMbtiSuggestEtt->name);
            $content = self::content($reportMbtiSuggestEtt->desc, 20);
            $list[] = "{$title}{$content}";
            $index++;
        }
        $list = implode('', $list);
        return "{$titleDiv}{$list}";
    }

    /**
     * 荣格八维解读性格优劣势
     *
     * @return string
     */
    private static function formatRouge($reportMbtiEtt)
    {
        $title1 = self::titleBigBilingual('荣格八维解读性格优劣势', 'JUNG&#39;S EIGHT-DIMENSIONAL INTERPRETATION');
        $tagArr = empty($reportMbtiEtt->rouge) ? array() : explode(',', $reportMbtiEtt->rouge);
        $reportMbtiRougeDao = \dao\ReportMbtiRouge::singleton();
        $reportMbtiRougeEttList = $reportMbtiRougeDao->readListByPrimary($tagArr);
        $list = array();
        $index = 0;
        foreach ($reportMbtiRougeEttList as $key => $reportMbtiRougeEtt) {
        	$bgColor = ['red', 'blue', 'green', 'orange', 'pink'][$index];
        	$tag = self::tagSuspension($reportMbtiRougeEtt->name, $bgColor, -10);
        	
        	$combineBox = self::combineBox($reportMbtiRougeEtt->title, $reportMbtiRougeEtt->desc);
            $list[] = "{$tag}{$combineBox}";
			$index++;
        }
        $list = implode('', $list);
        return "{$title1}{$list}";
    }
    
    /**
     * 获取分析
     * 
     * @return array
     */
    private function getExplain($reportMbtiEtt, $version)
    {
        // 最擅长xxx的类型
        $adeptDiv = self::formatAdept($reportMbtiEtt);
        // 这四个字母代表什么
        $elementDescDiv = self::formatElementDesc($reportMbtiEtt);
    	// 人口比例
        $proportionDiv = self::formatProportion($reportMbtiEtt, $version);
    	// 名人
    	$famousPeopleDiv = self::formatFamousPeople($reportMbtiEtt);
		return $adeptDiv . $elementDescDiv . $proportionDiv . $famousPeopleDiv;
    }
    
    /**
     * 获取建议
     *
     * @return array
     */
    private function getSuggest($reportMbtiEtt)
    {
    	// 价值观和动机
    	$valueDescDiv = self::formatValueDesc($reportMbtiEtt);
    	// 你的性格特点
    	$characterDescDiv = self::formatCharacterDesc($reportMbtiEtt);
    	// 成长建议
    	$suggestDiv = self::formatSuggest($reportMbtiEtt);
    	// 荣格八维解读性格优劣势
    	$rougeDiv = self::formatRouge($reportMbtiEtt);
    	$cardSplit = self::cardSplit();
    	return $valueDescDiv . $cardSplit . $characterDescDiv . $cardSplit . $suggestDiv . $cardSplit . $rougeDiv;
    }
    
    /**
     * 恋爱部分
     *
     * @return array
     */
    public function formatLove($reportMbtiEtt, $version)
    {  
        // 恋爱中
        $lovingDesc = self::content($reportMbtiEtt->loving);

        // 单身
        $loveSingleTag = self::tagRounded('单身时期', 'purple', -10);
        $loveSingleContent = self::contentBorder($reportMbtiEtt->loveSingle);
        
        // 中期
        $lovePremetaphaseTag = self::tagRounded('恋爱中期', 'red', -10);
        $lovePremetaphaseContent = self::contentBorder($reportMbtiEtt->lovePremetaphase);
        
        // 后期
        $loveLateTag = self::tagRounded('恋爱后期', 'blue', -10);
        $loveLateContent = self::contentBorder($reportMbtiEtt->loveLate);
        
        $cardSplit = self::cardSplit();
        
        $loveTitle1 = self::titleBigRound('恋爱指南');
        
        $loveMatchingTitle = self::titleBigBilingual('最佳恋爱匹配类型', 'BEST LOVE MATCH TYPES');
        $loveMatchingImg = self::imgMain($reportMbtiEtt->loveMatchingImg);
        $loveMatchingArr = empty($reportMbtiEtt->loveMatching) ? array() : explode(',', $reportMbtiEtt->loveMatching);
        $reportMbtiDao = \dao\ReportMbti::singleton();
        $reportMbtiEttList = $reportMbtiDao->readListByPrimary($loveMatchingArr);
        $imgList = array();
        $mainImgDir = 'report' . DS . 'MBTI';
        $commonSv = \service\Common::singleton();
        if (is_iteratable($reportMbtiEttList)) foreach ($reportMbtiEttList as $reportMbtiEtt) {
        	$famousPeopleImg = $commonSv::formartImgUrl($reportMbtiEtt->famousPeopleImg, 'report');
        	if ($version != 1) { // 男
        		$mainImg = 'man' . DS . $reportMbtiEtt->id . '-01.png';
        	} else {
        		$mainImg = 'woman' . DS . $reportMbtiEtt->id . '-02.png';
        	}
        	$mainImg = $commonSv::formartImgUrl($mainImg, $mainImgDir);
        	$imgList[] = array(
        		'img' => $mainImg,
        		'top' => $reportMbtiEtt->id,	
        		'bottom' => $reportMbtiEtt->name,
        	);
        }

        $loveMatchingListBox = self::imgListBox($imgList);
		return
        <<<EOT
{$loveTitle1}
{$lovingDesc}
{$loveSingleTag}{$loveSingleContent}
{$lovePremetaphaseTag}{$lovePremetaphaseContent}
{$loveLateTag}{$loveLateContent}
{$cardSplit}
{$loveMatchingTitle}
{$loveMatchingListBox}
EOT;
    }
        
    /**
     * 工作部分
     *
     * @return array
     */
    public function formatWork($reportMbtiEtt)
    {
        $tagArr = empty($reportMbtiEtt->careerRecommend) ? array() : explode(',', $reportMbtiEtt->careerRecommend);
       
        $reportMbtiProfessionDao = \dao\ReportMbtiProfession::singleton();
        $reportMbtiRougeEttList = $reportMbtiProfessionDao->readListByPrimary($tagArr);
        $professionList = array();
        foreach ($reportMbtiRougeEttList as $key => $reportMbtiRougeEtt) {
        	$title3 = self::titleDot($reportMbtiRougeEtt->role);
        	$content = self::content($reportMbtiRougeEtt->desc);
        	$exampleListBox = self::listBox(explode(',', $reportMbtiRougeEtt->example));
        	$tag = self::tagRounded('职业推荐', 'blue', -5);
            $professionList[] = "{$title3}{$content}{$tag}{$exampleListBox}";
        }
        $professionList = implode('', $professionList);
        
        // 建议
        $suggestList = array();
        $colorMap = array('blue', 'red', 'green', 'yellow', 'blue', 'red');
        for ($index = 1; $index <= 5; $index++) {
            $titlePro = 'careerEvadeSuggestTitle' . $index;
            $contentPro = 'careerEvadeSuggestContent' . $index;
            $title = $reportMbtiEtt->$titlePro;
            $content = $reportMbtiEtt->$contentPro;
            preg_match_all('/<p>(.*?)<\/p>/s', $content, $matches);
            $contentList = array();
            if (!empty($matches['1'])) foreach ($matches['1'] as $value) {
            	$contentList[] = $value;
            }
            $title2 = self::titleIndexNum($index, $title); // 2级标题
         
            $content = self::contentBg($contentList, 'gray', 10);
            $suggestList[] = "{$title2}{$content}";
        }        

        // 工作中的
        $workIngTitle1 = self::titleBigBilingual("工作中的{$reportMbtiEtt->id}", "{$reportMbtiEtt->id} AT WORK");
        $workIngDesc = self::content($reportMbtiEtt->workIng);
        $workTeamTag = self::tagTriangle('在团队中', 'red', -10);
        $workTeamContent = self::contentBg($reportMbtiEtt->workTeam);
        $workLeadTag = self::tagTriangle('作为领导', 'blue', -10);
        $workLeadContent = self::contentBg($reportMbtiEtt->workLead);
        
        // 工作中的核心满足感
        $workSatisfyTitle1 = self::titleBigBilingual("工作中的核心满足感", "CORE SATISFACTION AT WORK");
        $workSatisfyContent = self::content($reportMbtiEtt->wrokSatisfaction);
        $workEnvironmentbestTag = self::tagRounded('最佳工作环境', 'red', -10);
        $workEnvironmentbestContent = self::contentBg($reportMbtiEtt->workEnvironmentbest);
        $workEnvironmentWorstTag = self::tagRounded('最差工作环境', 'gray', -10);
        $workEnvironmentWorstContent = self::contentBg($reportMbtiEtt->workEnvironmentWorst);
        
        // 职业参考宝典
        $professionTitle1 = self::titleBigBilingual("职业参考宝典", "CAREER REFERENCE BOOK");
        $professionContent = self::contentBorder($professionList);
        
        // 职场避雷
        $evadeTitle1 = self::titleBigBilingual("职场避雷锦囊", "WORKPLACE LIGHTNING AVOIDANCE TIPS");
        $evadeContent = implode('', $suggestList);
        
        $cardSplit = self::cardSplit();
        return
        <<<EOT
{$workIngTitle1}{$workIngDesc}
{$workTeamTag}{$workTeamContent}
{$workLeadTag}{$workLeadContent}
{$cardSplit}
{$workSatisfyTitle1}{$workSatisfyContent}
{$workEnvironmentbestTag}{$workEnvironmentbestContent}
{$workEnvironmentWorstTag}{$workEnvironmentWorstContent}
{$cardSplit}
{$professionTitle1}{$professionContent}
{$cardSplit}
{$evadeTitle1}{$evadeContent}
EOT;
    }
    
    /**
     * 获取测试结果
     *
     * @return array
     */
    public function getAnswerResult($testOrderInfo, $testPaperInfo)
    {
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    	// 统计各项得分
    	$scoreMap = array();
    	$selectionTmpArr = reset($questionList)['selections'];
    	// 获取通用配置
    	$conf = getStaticData($testPaperInfo['name'], 'common');
    	$optionScoreMap = array(); // 每个选项分数分布
    	if (!empty($conf['extend']['styleType']) && $conf['extend']['styleType'] == 1 && count($selectionTmpArr) == 5) {
    		$optionScoreMap = array(2, 1, 0, 1, 2);
    	} elseif (count($selectionTmpArr) == 2) {  // MBTI专业爱情测试     两个选项   A  B
    		$optionScoreMap = array(1, 1);
    	} elseif (count($selectionTmpArr) == 4) {  // 
    		
    	}
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		if (empty($question['scoreValue']) || !isset($answerList[$question['id']])) { // 无效的题目
    			continue;
    		}
    		$scoreValueArr = str_split($question['scoreValue']); // 类型
    		$elementType = 0; // 测试的类型
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		if ($userAnswer < 3) { // 选项 A 
    			$elementType = $scoreValueArr['0'];
    		} else if ($userAnswer > 3) { // 选项B
    			$elementType = $scoreValueArr['0'];
    		} else { // 居中
    			continue;
    		}
    		$elementType = strtoupper($elementType);
    		//  0 =>  A  1 => B (MBTI专业爱情测试)     或   A B C D E (MBTI 测试)
    		$userScore = empty($optionScoreMap[$userAnswer]) ? 0 : $optionScoreMap[$userAnswer]; // 用户的得分
    		$scoreMap[$elementType][$question['id']] = 1;
    	}
    	$mbtiTypeMap = array();
    	$percentList = array();
    	foreach (self::$elementMap as $positive => $negative) {
    		$positiveScore = empty($scoreMap[$positive]) ? 0 : array_sum($scoreMap[$positive]); // 正向得分
    		$negativeScore = empty($scoreMap[$negative]) ? 0 : array_sum($scoreMap[$negative]); // 负向得分
    		// 正向占比
    		$percentList[$positive] = intval(intval($positiveScore * 1000 / ($positiveScore + $negativeScore)) * 0.1);
    		// 负向占比
    		$percentList[$negative] = 100 - $percentList[$positive];
    		$mbtiTypeMap[] = $percentList[$positive] <= $percentList[$negative] ? $negative : $positive;
    	}
    	// 最终的类型
    	$mbtiType = implode('', $mbtiTypeMap);
    	return array(
    		'mbtiType' => $mbtiType, // 最终的类型
    		'percentList' => $percentList, // 各项元素的占比
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
    	$reportMbtiDao = \dao\ReportMbti::singleton();
    	$reportMbtiEtt = $reportMbtiDao->readByPrimary($answerResult['mbtiType']);
    	if (empty($reportMbtiEtt)) {
    	    throw new $this->exception('数据配置错误，请联系客服！');
    	}
    	$percentList = $answerResult['percentList'];
    	$reportMbtiElementDao = \dao\ReportMbtiElement::singleton();
    	$reportMbtiElementEttList = $reportMbtiElementDao->readListByPrimary(array_keys($answerResult['percentList']));
    	$reportMbtiElementEttList = $reportMbtiElementDao->refactorListByKey($reportMbtiElementEttList);
    	// 各项占比
    	$tubiaoList = array();
    	foreach (self::$elementMap as $positive => $negative) {
    	    if (empty($reportMbtiElementEttList[$positive]) || empty($reportMbtiElementEttList[$positive])) {
    	        continue;
    	    }

    	    $positiveElementEtt = $reportMbtiElementEttList[$positive];
    	    $negativeElementEtt = $reportMbtiElementEttList[$negative];
    	    $positiveModel = array(
    	        'title' => $positiveElementEtt->name . '（' . $positiveElementEtt->id . '）',
    	        'percent' => $percentList[$positiveElementEtt->id],
    	        'color' => $positiveElementEtt->color,
    	    );
    	    $negativeModel = array(
    	        'title' => $negativeElementEtt->name . '（' . $negativeElementEtt->id . '）',
    	   		'percent' => $percentList[$negativeElementEtt->id],
    	        'color' => $negativeElementEtt->color,
    	    );
    	    $tubiaoList[] = array(
    	        $positiveModel, $negativeModel
    	    );
    	}

    	// 第一部分
    	$explainDiv = $this->getExplain($reportMbtiEtt, $testOrderInfo['version']);
    	// 第二部分
    	$suggestDiv = $this->getSuggest($reportMbtiEtt);
        // 第三部分
    	$loveDiv = $this->formatLove($reportMbtiEtt, $testOrderInfo['version']);
    	// 第四部分
    	$workDiv = $this->formatWork($reportMbtiEtt);
    	$setting = array(
    	    'pl_type' => 7,
    	    'title1' => $testPaperInfo['name'],
    	    'title_icon_tag1' => 'fa-universal-access',
    	);
    	$mainImgDir = 'report' . DS . 'MBTI';
    	if ($testOrderInfo['version'] == 1) { // 男
    		$mainImg = 'man' . DS . $reportMbtiEtt->id . '-01.png';
    	} else {
    		$mainImg = 'woman' . DS . $reportMbtiEtt->id . '-02.png';
    	}
    	$commonSv = \service\Common::singleton();
    	$mainImg = $commonSv::formartImgUrl($mainImg, $mainImgDir);
    	
    	$mbti_pl = array(
    	    'weidu_name' => $reportMbtiEtt->name,
    	    'pl_type' => 7,
    	    'total_result_explain' => $explainDiv,
    	    'juti_result_explain' => $suggestDiv,
    	    'need_jisuan_value' => 'A',
    	    'extend' => array(
    	        'love'     => $loveDiv,
    	        'score'    => $reportMbtiEtt->id,
    	        'zhichang' => $workDiv,
    	    	'adeptType' => $reportMbtiEtt->adeptType,
    	    	'mainImg' => $mainImg,
    	    ),
    	);
    	$reportModel = array(
    		'mbti_pl' => array(
	    	    'setting' => $setting,
	    	    'mbti_pl' => $mbti_pl,
	    	    'tubiaoList' => $tubiaoList,
	    	    'temperament' => array(
	    	        $reportMbtiEtt->name,
	    	        $reportMbtiEtt->temperament
	    	    ),
	    	),
    	);
    	return $reportModel;
    }

}