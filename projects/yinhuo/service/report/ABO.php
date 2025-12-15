<?php
namespace service\report;

/**
 * ABO
 * 
 * @author 
 */
class ABO extends \service\report\ReportBase
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
     * @return ABO
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ABO();
        }
        return self::$instance;
    }
    
    // 图片
    private static $imgMap = array(
    	'中性化Beta' => '中性化Beta.png',
    	'双性化Beta' => '双性化Beta.png',
    	'女性Alpha' => '女性Alpha.png',
    	'女性Omega' => '女性Omega.png',
    	'男性Alpha' => '男性Alpha.png',
    	'男性Omega' => '男性Omega.png',
    );
    
    /**
     * 解读
     * 
     * @return string
     */
    private static function formatExplain($reportABOEtt, $sex)
    {
    	$manImg = self::imgMain(self::$imgMap[$reportABOEtt->name], 0, 'report' . DS . 'ABO');
    	$pheromoneDesc = self::contentBg($reportABOEtt->pheromoneDesc, 'purple');
    	$cardSplit = self::cardSplit();
    	$title1 = self::titleBigRound('代表人物');
    	$personageImg = self::imgMain(self::$imgMap[$reportABOEtt->name]);
    	$personageTitle2 = self::titleUnderline($reportABOEtt->personageTitle);
    	$personageDesc = self::content($reportABOEtt->personageDesc);
    	
    	$workTag = self::tagRounded('工作方面', 'blue', -10);
    	$interpersonalTag = self::tagRounded('人际关系', 'gray', -10);
    	$emotionTag = self::tagRounded('情感方面', 'red', -10);
    	
    	$workContent = self::content($reportABOEtt->work);
    	$interpersonalContent = self::content($reportABOEtt->interpersonal);
    	$emotionContent = self::content($reportABOEtt->emotion);
    	return
    	<<<EOT
{$manImg}
<p><b>第一性别：</b>{$sex}</p>
<p><span style="color:#e03997;"><b>ABO性别：</b></span>{$reportABOEtt->name}</p>
<p><b>性别特征：</b>{$reportABOEtt->sexCharacter}</p>
<p><b>信息素：</b>{$reportABOEtt->pheromoneTag}</p> 
{$pheromoneDesc}
{$cardSplit}
{$title1}
{$personageTitle2}
{$personageDesc}
{$workTag}{$workContent}
{$interpersonalTag}{$interpersonalContent}
{$emotionTag}{$emotionContent}
</p>
EOT;
    }
    
    /**
     * 解读
     *
     * @return string
     */
    private static function formatExplain2($reportABOEtt)
    {
    	$img = 'ABO-提升.jpg';
    	$personageImg = self::imgMain($img);
    	$workContent = self::content($reportABOEtt->suggest);
    	return "{$personageImg}{$workContent}";
    }
    
    /**
     * 获取作答答案
     *
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo, &$percentList = array())
    { 
    	// 第一题为引导题，次序应该往后加1   男生，女生 量表相同 
    	$scoreTable = array(
    		'男性Alpha' => array(1, 6, 11, 16, 21, 26, 31),
    		'女性Alpha' => array(2, 7, 12, 17, 22, 27, 32),
    		'双性化Beta' => array(3, 8, 13, 18, 23, 28, 33),
    		'男性Omega' => array(4, 9, 14, 19, 24, 29, 34),
    		'女性Omega' => array(5, 10, 15, 20, 25, 30, 35)
    	);
    	$indexMap = array();
    	foreach ($scoreTable as $type => $indexList) {
    		foreach ($indexList as $index) {
    			$indexMap[$index] = $type;
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
    	$answerScoreMap = array(0, 1, 2 ,3, 4); // [{"name":"完全不符合","img":""},{"name":"不太符合","img":""},{"name":"一般","img":""},{"name":"基本符合","img":""},{"name":"完全符合","img":""}]
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
//     		if (empty($testQuestionEtt->scoreValue) && isset($indexMap[$testQuestionEtt->index - 1])) {
//     			$testQuestionEtt->set('scoreValue', $indexMap[$testQuestionEtt->index - 1]);
//     			$testQuestionDao->update($testQuestionEtt);
//     		}
    		
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    		if (empty($indexMap[$question['index'] - 1])) {
    			continue;
    		}
    		if (!isset($answerScoreMap[$userAnswer])) { // 最后一题，选择年龄
    			continue;
    		}
    		$scoreValue = $indexMap[$question['index'] - 1]; // 该题测试的类型
    		if (empty($totalScoreMap[$scoreValue])) {
    			$totalScoreMap[$scoreValue] = max($answerScoreMap);
    		} else {
    			$totalScoreMap[$scoreValue] += max($answerScoreMap);
    		}
    		
    		if (empty($scoreMap[$scoreValue])) {
    			$scoreMap[$scoreValue] = $answerScoreMap[$userAnswer];
    		} else {
    			
    			$scoreMap[$scoreValue] += $answerScoreMap[$userAnswer];
    		}
    	}
  
    	$percentList = array();
    	foreach ($totalScoreMap as $type => $totalScore) {
    		$percentList[$type] = array(
    			'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type],
    			'totalScore' => $totalScore,
    			'typeName' => $type,
    			'percent' => self::getPercent(empty($scoreMap[$type]) ? 0 : $scoreMap[$type], $totalScore),
    		);
    	}

    	// 根据占比排序
    	uasort($percentList, array(self::$instance, 'sortByPercent'));
    	$userType = reset($percentList)['typeName']; // 用户的类型，取第一个
    	// 计算男性化，女性化 占比
    	
    	$manScore = (empty($percentList['男性Alpha']['score']) ? 0 : $percentList['男性Alpha']['score']) 
    		+ (empty($percentList['男性Omega']['score']) ? 0 : $percentList['男性Omega']['score']); // 男性化得分
    	$manTotalScore = (empty($percentList['男性Alpha']['totalScore']) ? 0 : $percentList['男性Alpha']['totalScore']) 
    		+ (empty($percentList['男性Omega']['totalScore']) ? 0 : $percentList['男性Omega']['totalScore']); // 男性化总分
    	
    	$manPercent = self::getPercent($manScore, $manTotalScore, 0); // 男性化占比
    	
    	$womanScore = (empty($percentList['女性Alpha']['score']) ? 0 : $percentList['女性Alpha']['score']) 
    		+ (empty($percentList['女性Omega']['score']) ? 0 : $percentList['女性Omega']['score']); // 女性化得分
    	$womanTotalScore = (empty($percentList['女性Alpha']['totalScore']) ? 0 : $percentList['女性Alpha']['totalScore']) 
    		+ (empty($percentList['女性Omega']['totalScore']) ? 0 : $percentList['女性Omega']['totalScore']); // 女性化总分
    	$womanPercent = self::getPercent($womanScore, $womanTotalScore, 0); // 女性化占比

    	// 根据阀值由小到大排序
    	$conf = getStaticData($testPaperInfo['name'], 'common');
 
    	$commonSv = \service\Common::singleton();
    	// 男性气质
    	$levelList1 = $conf['levelList1'];
    	uasort($levelList1, array($commonSv, 'sortByThreshold'));
    	$manLevelConf = array();
    	foreach ($levelList1 as $levelName => $row) {
    		if ($manPercent >= $row['threshold']) {
    			$row['levelName'] = $levelName;
    			$manLevelConf = $row;
    		} else {
    			break;
    		}
    	}
    	// 女性气质
    	$levelList2 = $conf['levelList2'];
    	uasort($levelList2, array($commonSv, 'sortByThreshold'));
    	$womanLevelConf = array();
    	foreach ($levelList2 as $levelName => $row) {
    		if ($womanPercent >= $row['threshold']) {
    			$row['levelName'] = $levelName;
    			$womanLevelConf = $row;
    		} else {
    			break;
    		}
    	}
   
    	return array(
    		'percentList' => $percentList,
    		'userType' => $userType, // 用户的类型
    		'manPercent' => $manPercent,
    		'womanPercent' => $womanPercent,		
    		'manScore' => $manScore,
    		'manTotalScore' => $manTotalScore,
    		'womanScore' => $womanScore,
    		'womanTotalScore' => $womanTotalScore,
    		'manLevelConf' => $manLevelConf,
    		'womanLevelConf' => $womanLevelConf,
    	);
    }
    
    /**
     * 获取扩展阅读
     * <div class="card-bg-green"><p>充满领导力的Alpha？</p></div>
<div class="card-bg-purple"><p>温柔细腻的Omega？</p></div>
<div class="card-bg-orange"><p>务实理性的Beta？</p></div>
     * @return array
     */
    private function getExtendRead()
    {
    	$imgMain = self::imgMain('介绍.png');
    	
    	$aboImg = self::imgMain('abo.png');
    	$content = <<<BLOCK
{$imgMain}
<p>ABO 是Alpha、Beta、Omega 三个单词的缩写。它是欧美同人圈中常见的三大设定之一。</p>
<p>当你在社交网站上冲浪时，是否常看到这样的评论：</p>
<p>“好 A 啊！”</p>
<p>为什么 A 会被用来形容酷炫霸气？如果你了解 ABO 性别设定，你就会明白其中的原因。</p>
<p>ABO 由三个单词的首字母构成：</p>
<div class="card-bg-green"><p><span style="color:#f37b1d;"><b>Alpha</b></span>&nbsp;霸道、理智、行动力强</p></div>
<div class="card-bg-purple"><p><span style="color:#f37b1d;"><b>Beta</b></span>&nbsp;平和、踏实</p></div>
<div class="card-bg-orange"><p><span style="color:#f37b1d;"><b>Omega</b></span>&nbsp;温柔、柔弱</p></div>	
<p>在 ABO 性别设定中，人类被分为五种性别：</p>
{$aboImg}  	
<div class="card-bg-green"><p><span style="color:#f37b1d;">Alpha 性格鲜明、能量强大，</span>经常作为领袖角色出现，拥有强大的责任感与勇气。</p></div>
<div class="card-bg-orange"><p><span style="color:#f37b1d;">Beta</span>是团队中的关键执行者，他们<span style="color:#f37b1d;">团结、友善、脚踏实地</span>，给人一种稳定可靠的感觉。</p></div>
<div class="card-bg-purple"><p><span style="color:#f37b1d;">Omega</span>相对更加<span style="color:#f37b1d;">安静、温柔</span>，比起 A 和 B 更加敏感和害羞，特别惹人怜爱。</p></div>
    	
<p>在娱乐圈中，有很多典型代表人物，比如：</p>
<p>气场强大的<b>胡军</b>，被誉为娱乐圈的“大总攻”。无论是拍戏还是日常生活，他都散发出浓烈的 Alpha 气息。</p>
<p><br /></p>
<p>另一位典型代表是<b>宁静</b>。她敢说敢做，侵略性的美貌让人觉得具有攻击性，是美艳的女王 Alpha。</p>
    	
<p>与宁静一同参加《浪姐3》的<b>王心凌</b>，则是明显的<b>乖甜可爱的 Omega</b>。一首《爱你》不知击中了多少人的心灵。</p>
<p><br /></p>
<p>不过，不是每个人的外表都与他们的 ABO 性别相符。有些<span style="color:#f37b1d;">男性具有女性化特质</span>，比如温柔、善于体察他人情绪，而<span style="color:#f37b1d;">女性也可以拥有好强勇猛的男性特质</span>。</p>
<p><br /></p>
<p>说唱歌手姜云升，就曾在微博上自曝，<b>他的 ABO 性别是“弱女性”</b>。</p>
    	
<p>在 ABO 性别设定中，<span style="white-space: normal;">不同性别类型拥有不同的信息素气味。</span></p>
<p><span style="white-space: normal;">通过了解 ABO 性别，</span>你或许可以<span style="color:#f37b1d;">发现自己内心深藏的另一面</span>，<b>甚至连陈坤都忍不住好奇发问：什么是信息素？</b>想弄清楚自己的 ABO 性别。</p>
    	
<p>我们对性别的认知，常常被外在生理性别限制，认为男人应该强悍、克制，女人应当温柔体贴、善解人意，甚至<b>无意识地压抑那些与性别认知不符的性格特质</b>，<span style="color:#f37b1d;">导致内心冲突和困惑</span>。</p>
<p>实际上，<span style="color:#f37b1d;">一个人的性别特质并不是单一的</span>。<b>美国心理学家桑德拉·贝姆</b>在上世纪七十年代提出了<span style="color:#f37b1d;">双性化模型</span>，认为一个人可以同时具有强烈的男性化和女性化特质。</p>
<p><br /></p>
<p>目前心理学研究表明：双性化特质的个体<span style="color:#f37b1d;">自尊水平更高</span>，<span style="color:#f37b1d;">社会适应能力更强</span>，而且他们比其他类型的人<span style="color:#f37b1d;">更受欢迎</span>。</p>
<p>了解自己的 ABO 性别类型和特征，通过心理学技巧可以帮助你发挥内在性别优势，弥补性别弱势。</p>
BLOCK;
    	return array(
    		'setting' => array(
    			'title' => 'ABO是什么？',
    			'content' => $content,
    		),
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
    	$reportABODao = \dao\ReportABO::singleton();
    	$reportABOEtt = $reportABODao->readByPrimary($answerResult['userType']);
    	if (empty($reportABOEtt)) {
    		throw new $this->exception('数据配置错误，请联系客服！');
    	}
   
    	$sex = $testOrderInfo['version'] == 1 ? '男' : '女';
		$title = 'ABO性别角色评估（' . $sex . "）";
    	$manTabBox = self::tabBox('注意', '男性化气质定义是社会上普遍认为符合男性的描述，归类为工具性特质，并不是只在男性身上出现的特征，同样也能出现在女性身上');
    	$womanTabBox = self::tabBox('注意', '女性化气质定义是社会上普遍认为符合女性的描述，归类为表达性特质，并不是只在女性身上出现的特征，同样也能出现在男性身上');
    	$marginBottom = self::marginBottom(60);
   
    	$weiduList = array(
    		array(
    			'weidu_name' => '男性化气质',
    			'last_percent' => $answerResult['manPercent'],
    			'extend' => array(
    				'tubiao_color' => '#9c26b0',
    			),
    			'danweidu_type' => 3,
    			
    			'weidu_result' => array(
    				'name' => $answerResult['manLevelConf']['levelName'], // 较高，较低
    				'result_explain' => $answerResult['manLevelConf']['explain']  . $manTabBox . $marginBottom,
    			),	
    		),
    		array(
    			'weidu_name' => '女性化气质',
    			'last_percent' => $answerResult['womanPercent'],
    			'extend' => array(
    				'tubiao_color' => '#e03997',
    			),
    			'danweidu_type' => 3,
    			'weidu_result' => array(
    				'name' => $answerResult['womanLevelConf']['levelName'],
    				'result_explain' => $answerResult['womanLevelConf']['explain'] . $womanTabBox,
    			),
    		),
    	);
    	$fanone = array(
    		'paper_tile' => $title,
    		'fan_type' => 2,
    		'setting' => array(
    			'fan_type' => 2,
    			'title' => '气质提升',
    		),
    		'content' => array(
    			'result_name' => $reportABOEtt->name,
    			'result_explain' => self::formatExplain2($reportABOEtt),
    			'fan_type' => 2,
    		),
    	);
    	$conf = getStaticData($testPaperInfo['name'], 'common');
    	$report = array(
    		'total_result_scoring' =>  array(
	    		'paper_tile' => $title,
	    		'jifen_guize' => 4,
	    		'setting' => array(
	    			'jifen_guize' => 4,
	    			'title' => $title,
	    			'title_icon_image' => 'fa-transgender-alt',
	    		),
	    		'content' => array(
	    			'name' => $reportABOEtt->name,
	    			'result_explain' => self::formatExplain($reportABOEtt, $sex),
	    		),
	    	),
    		'danweidu' => array(
    			'weiduList' => $weiduList,
    			'setting' => array(
    				'dan_weidu_type' => 3,
    				'title' => '气质维度分析',
    			),
    		),
    		'extend_read' => self::getExtendRead(),
    		'wuguize' => array(
	    		'setting' => array(
	    			'rule_type' => 3,
	    			'title' => '你的ABO性别气质指数',
	    			'title_icon_tag' => 'fa-intersex',
	    			'left_text' => '男性化气质',
	    			'left_total_score' => $answerResult['manTotalScore'], // 60
	    			'right_text' => '女性化气质',
	    			'right_total_score' => $answerResult['womanTotalScore'], // 60
	    			'l1_percent' => $answerResult['manPercent'],
	    			'l2_percent' => $answerResult['womanPercent'],
	    			'l1' => $answerResult['manScore'], // 160
	    			'l2' => $answerResult['womanScore'], // 205
	    		),	
    		),
    		'fanone' => $fanone,
    	);
    	return $report;
    }

}