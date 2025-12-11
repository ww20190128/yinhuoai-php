<?php
namespace service\report;

/**
 * DISC
 * 
 * @author 
 */
class DISC extends \service\report\ReportBase
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
     * @return DISC
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DISC();
        }
        return self::$instance;
    }
    
    /**
     * 解读
     * 
     * @return string
     */
    private static function formatExplain($staticData)
    {
    	$result = array();
    	$result[] = self::titleBigRound("最符合您的风格特质");
    	$result[] = self::titleUnderline($staticData['类型'] . '型特质：' . $staticData['名称']);
    	$result[] = $staticData['简介'];
    	return implode('', $result);
    }
    
    /**
     * 解读
     *
     * @return string
     */
    private static function formatExplain2($staticData)
    {
    	$result = array();
    	$cardSplit = self::cardSplit();
    	// 一. 基本信息
    	$result[] = self::titleBigRound('特质分析');
    	$result[] = self::contentBg($staticData['特质分析'], 'gray');
    	$result[] = $cardSplit;
    	// 性格特点
    	$result[] = self::titleBigRound('性格特点');
    	$result[] = self::contentBg($staticData['性格特点'], 'gray');
    	$result[] = self::combineBox('优点', $staticData['优点'], '', '', 0, 'red');
    	$result[] = self::combineBox('缺点', $staticData['缺点'], '', '', 0, 'blue');
    	$result[] = self::tagRounded('在情感中', 'red', -10);
    	$result[] = self::content($staticData['在情感方面'], 10);
    	$result[] = self::tagRounded('在工作中', 'blue', -10);
    	$result[] = self::content($staticData['在工作方面'], 10);
    	$result[] = self::tagRounded('在人际关系中', 'purple', -10);
    	$result[] = self::content($staticData['在人际关系方面'], 10);
    	
    	$result[] = $cardSplit;
    	// 二. 职场表现
    	$result[] = self::titleBigRound('职场表现');
    	
    	$result[] = self::titleBg('领导风格');
    	$result[] = self::titleUnderline($staticData['职场表现']['领导风格']);
    	$result[] = self::content($staticData['职场表现']['风格描述']);
    	
    	$result[] = self::titleBg('代表人物');
    	//$result[] = self::imgMain($staticData['职场表现']['代表人物图片']);
    	$result[] = self::content($staticData['职场表现']['代表人物']);
    	$result[] = self::titleBg('对团队的贡献');
    	$result[] = self::content($staticData['职场表现']['对团队的贡献']);
    	$result[] = self::titleBg('理想环境');
    	$result[] = self::content($staticData['职场表现']['理想环境']);
    	$result[] = self::titleBg('在压力之下');
    	$result[] = self::content($staticData['职场表现']['在压力之下']);
    	$result[] = self::titleBg('可能存在的局限');
    	
    	$result[] = self::content($staticData['职场表现']['可能存在的局限']);
    	$result[] = self::titleBg('常见的情绪特征');
    	$result[] = self::titleCombination($staticData['职场表现']['常见情绪特征']['标签'], $staticData['职场表现']['常见情绪特征']['方向']);
    	
    	$result[] = self::content($staticData['职场表现']['常见情绪特征']['建议']);
    	$result[] = $cardSplit;
    	// 二. 与TA相处
    	$result[] = self::titleBigRound("与{$staticData['类型']}型人相处");
    	$result[] = self::imgMain($staticData['与TA相处']['图片']);
    	$result[] = self::titleBg("{$staticData['类型']}型人一般表现：");
    	$result[] = self::content($staticData['与TA相处']['一般表现']);
    	
    	$result[] = self::titleBg("口头禅");
    	$result[] = self::content($staticData['与TA相处']['口头禅']);
    	
    	$result[] = self::titleBg("如何与{$staticData['类型']}型人沟通");
    	$result[] = self::content($staticData['与TA相处']['与TA沟通']);
    	
    	$result[] = self::titleBg("如何面对{$staticData['类型']}型的上司");
    	$result[] = self::content($staticData['与TA相处']['如何面对该类型的上司']);
    	
    	$result[] = self::titleBg("如何激励{$staticData['类型']}型的下属");
    	$result[] = self::content($staticData['与TA相处']['如何激励该类型的下属']);
    	
    	$result[] = self::titleBg("如何养育{$staticData['类型']}型的孩子");
    	$result[] = self::content($staticData['与TA相处']['如何养育该类型的孩子']);
    	
    	$result[] = self::content('<p><i class="fa vaaii fa-exclamation-circle"></i><span style="font-size: .32rem;">所有的性格特质都没有好坏之分，每个性格都有自己的优势和弱势。</span>
</p>');
    	$result = implode('', $result);
    	return $result;
    	

EOT;
    }
    
    /**
     * 获取作答答案
     *
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo, &$percentList = array())
    { 
    	$indexMap = array(
    		1 => "DSIC",
		    2 => "CIDS",
		    3 => "SCID",
		    4 => "ICDS",
		    5 => "ICDS",
		    6 => "ISCD",
		    7 => "CSDI",
		    8 => "DISC",
		    9 => "SCID",
		    10 => "DCIS",
		    11 => "DSCI",
		    12 => "ICDS",
		    13 => "CDSI",
		    14 => "ICDS",
		    15 => "SDIC",
		    16 => "CIDS",
		    17 => "SCDI",
		    18 => "SDIC",
		    19 => "CSID",
		    20 => "IDCS",
		    21 => "SCID",
		    22 => "ISDC",
		    23 => "SCID",
		    24 => "CSID",
		    25 => "DSCI",
		    26 => "CSID",
		    27 => "DSCI",
		    28 => "SCID",
		    29 => "IDSC",
		    30 => "ICDS",
		    31 => "SDCI",
		    32 => "CDSI",
		    33 => "SDCI",
		    34 => "CIDS",
		    35 => "ISCD",
		    36 => "DSIC",
		    37 => "CDIS",
		    38 => "SDIC",
		    39 => "CIDS",
		    40 => "SDCI"
		);
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];

    	// 统计各项得分
    	$scoreMap = array();
    	$totalScoreMap = array(); // 每种类型的总分
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		$scoreValue = empty($indexMap[$question['index']]) ? '' : $indexMap[$question['index']];
    		$userAnswer = $answerList[$question['id']]; // 作答的答案
    		if (empty($scoreValue)) {
    			continue;
    		}
    		$scoreValueArr = str_split($scoreValue);
    		foreach ($scoreValueArr as $val) {
    			if (empty($totalScoreMap[$val])) {
    				$totalScoreMap[$val] = 1;
    			} else {
    				$totalScoreMap[$val] += 1;
    			}
    		}
    		if (empty($scoreMap[$scoreValueArr[$userAnswer]])) {
    			$scoreMap[$scoreValueArr[$userAnswer]] = 1;
    		} else {
    			$scoreMap[$scoreValueArr[$userAnswer]] += 1;
    		}
    	}
    	$percentList = array();
    	foreach ($totalScoreMap as $type => $totalScore) {
    		$percentList[$type] = array(
    			'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type],
    			'totalScore' => $totalScore,
    			'typeName' => $type,
    		);
    		// 占比
    		$percentList[$type]['percent'] = self::getPercent($percentList[$type]['score'], $percentList[$type]['totalScore']);
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
    	$answerResult = $this->getAnswerResult($testOrderInfo, $testPaperInfo);
    	$percentList = $answerResult['percentList'];

    	$id = 1;
    	$wz_list = array();
    	if (is_iteratable($percentList)) foreach ($percentList as $type => $row) {
    		$staticData = getStaticData($testPaperInfo['name'], $type);
    		if (empty($staticData)) {
    			continue;
    		}
    		$wz_list[] = array(
    			'id' => $id++,
    			'weidu_name' => $staticData['名称'],
    			'total_score' => 100,
    			'percent' => $row['percent'],
    			'wz_type' => 1,
    			'total_result_explain' => self::formatExplain($staticData),
    			'juti_result_explain' => self::formatExplain2($staticData),
    		);
    	}
    	
    	$jianjie = <<<EOT
<p>D、I、S、C分别是DISC测验的四个维度，也叫性格特质因子，它们分别代表：</p>
<p><i class="fa vaaii fa-caret-right "></i>支配性（D）<br /><i class="fa vaaii fa-caret-right "></i>影响性（I）<br /><i class="fa vaaii fa-caret-right "></i>稳定性（S）<br /><i class="fa vaaii fa-caret-right "></i>完美型（C）</p>
<p>以下根据你的测评结果，得出的DISC性格分布图：</p>
EOT;
   		$reportModel = array(
   			'mbti_wz' => array(
   				'wz_list' => $wz_list,
   				'setting' => array(
		    		'wz_type' => 1,
		    		'title1' => '你的DISC性格分布',
		    		'title_icon_tag1' => 'fa-sliders',
		    		'title2' => 'DISC性格深入解析',
		    		'title_icon_tag2' => 'fa-sort-amount-asc',
		    		'jianjie' => $jianjie,
		    		'zhuyi' => '如果你有某一项分数远远高于其它三项，你就是该风格的典型类型，假若你有某两项分数大大超过其它两项，你是这两种风格的综合；假若你各项分数都比较接近，那么说明你是一个面面俱到、均衡发展的人。',
		    		'title2_jieshao' => '<p>以下四种DISC性格倾向的具体解析（按照你的匹配从高到低排序）：</p>',
		    	),
   			)
   		);
   		return $reportModel;
    }

}