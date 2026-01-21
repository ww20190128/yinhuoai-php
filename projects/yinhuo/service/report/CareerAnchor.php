<?php
namespace service\report;

/**
 * 职业锚
 *
 * @author
 */
class CareerAnchor extends \service\report\ReportBase
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
     * @return CareerAnchor
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new CareerAnchor();
        }
        return self::$instance;
    }
    
    /**
     * 解析
     * 
     * @return string
     */
    private static function formatFeature($dimensionConf)
    {
    	$cardSplit = self::cardSplit();
    	$title = self::titleBigRound('你的主职业锚类型');
    	$subTitle = self::titleUnderline($dimensionConf['name']);
//     	$manImg = self::imgMain(self::$imgMap[$reportABOEtt->name]);
    	$feature = self::content($dimensionConf['feature']);
    	$featureDesc = self::contentBg($dimensionConf['featureDesc'], 'purple');
    	return "{$title}{$subTitle}{$feature}{$featureDesc}{$cardSplit}";
    }
    
    /**
     * 解析
     *
     * @return string
     */
    private static function formatExplain($dimensionConf)
    {
    	//     	$manImg = self::imgMain(self::$imgMap[$reportABOEtt->name]);
    	$explainDesc = self::content($dimensionConf['explainDesc']);
    	$workTag = self::tagRounded('工作类型', 'blue', -10);
    	$paymentTag = self::tagRounded('薪酬补贴', 'purple', -10);
    	$promotionTag = self::tagRounded('工作晋升', 'gray', -10);
    	$approvalTag = self::tagRounded('认可方式', 'red', -10);
    	$work = self::content($dimensionConf['work'], 20);
    	$payment = self::content($dimensionConf['payment'], 20);
    	$promotion = self::content($dimensionConf['promotion'], 20);
    	$approval = self::content($dimensionConf['approval'], 20);

    	return "{$explainDesc}{$workTag}{$work}{$paymentTag}{$payment}{$promotionTag}{$promotion}{$approvalTag}{$approval}";
    }

    /**
     * 获取作答答案
     *
     * @return array
     */
    public static function getAnswerResult($testOrderInfo, $testPaperInfo, &$percentList = array())
    {
    	$map = array(
    		'TF' => array(1,  9, 17, 25, 33), // 技术型（TF）
    		'GM' => array(2, 10, 18, 26, 34), // 管理型（GM)
    		'AU' => array(3, 11, 19, 27, 35), // 自主型（AU）
    		'SE' => array(4, 12, 20, 28, 36), // 安全型（SE）
    		'EC' => array(5, 13, 21, 29, 37), // 创造型（EC）
    		'SV' => array(6, 14, 22, 30, 38), // 服务型（SV）
    		'CH' => array(7, 15, 23, 31, 39), // 挑战型（CH）
    		'LS' => array(8, 16, 24, 32, 40), // 生活型（LS）
    	);
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    	$indexMap = array();
		foreach ($map as $type => $indexList) {
			foreach ($indexList as $index) {
				$indexMap[$index] = $type;
			}
		}
    	// 统计各项得分
    	$questionScoreMap = array();
    	$totalScoreMap = array(); // 每种类型的总分
    	$optionScoreMap = array(0, 1, 2 ,3, 4, 5); // 从不   偶尔   有时     经常   频繁  总是
    	
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		if (empty($indexMap[$question['index']])) {
    			continue;
    		}
    		$testType = $indexMap[$question['index']]; // 测试的类型
    		$userAnswer = $answerList[$question['id']]; // 用户的作答的答案
    		$userAnswerScore = empty($optionScoreMap[$userAnswer]) ? 0 : $optionScoreMap[$userAnswer]; // 该题的得分
    		if (empty($totalScoreMap[$testType])) {
    			$totalScoreMap[$testType] = max($optionScoreMap);
    		} else {
    			$totalScoreMap[$testType] += max($optionScoreMap);
    		}

    		$questionScoreMap[$question['index']] = $userAnswerScore;
    	}
    
    	arsort($questionScoreMap);
    	$top3 = array_slice($questionScoreMap, 0, 3, true); // 前3的加分项
    	foreach ($top3 as $index => $score) {
    		$questionScoreMap[$index] += (max($optionScoreMap) - 1);
    	}
    	$scoreMap = array();
    	foreach ($questionScoreMap as $index => $score) {
    		$testType = $indexMap[$index];
    		if (empty($scoreMap[$testType])) {
    			$scoreMap[$testType] = $score;
    		} else {
    			$scoreMap[$testType] += $score;
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
    	}
    	// 根据占比排序
    	uasort($dimensionList, array(self::$instance, 'sortByPercent'));
    	return array(
    		'dimensionList' => count($dimensionList) >= 2 ? $dimensionList : array(), // 维度
    	);
    }
    
    /**
     * 获取报告
   
     * @return array
     */
    public function getReport($testOrderInfo, $testPaperInfo)
    {
    	$commonConf = getStaticData($testPaperInfo['name'], 'common');    	
    	// 获取每种类型的平均分
    	$answerResult = $this->getAnswerResult($testOrderInfo, $testPaperInfo);
    	$dimensionList = $answerResult['dimensionList'];
    	$jifenPailieList = array(); // 积分排列
    	$id = 1;
    	if (is_iteratable($dimensionList)) foreach ($dimensionList as $type => $dimensionRow) {
    		$dimensionConf = empty($commonConf['dimensionList'][$type]) ? array() : $commonConf['dimensionList'][$type];
    		if (empty($dimensionConf)) {
    			continue;
    		}
    		$jifenPailieList[] = array(
    			'id' => $id++,
    			'weidu_name' => $dimensionConf['name'],
    			'total_score' => 100, // 总分
    			'pl_type' => 2,
    			'jifen_type' => 1,
    			'weidu_icon' => $dimensionConf['icon'],
    			'weidu_icon_color' => $dimensionConf['iconColor'],
    			'jianjie' => $dimensionConf['desc'],
    			'total_result_remark' => self::formatFeature($dimensionConf),
    			'xiangxi' => self::formatExplain($dimensionConf),
    			'last_percent' => $dimensionRow['percent'], // 得分
    		);
    	}
    	

    	$img = self::imgMain('615ac5b8903fb.jpg', false, 'report');
    	$title2_jieshao = '<p><img src="' . $img . '" style="display: block; margin-left: auto; margin-right: auto; border-radius: 15px;" /></p>';
    	$title2_jieshao .= '<p>以下是与你匹配度最相近的三项职业锚的实际应用和详细解析，这三个最主要的职业锚决定了你的工作方向和职业满意度。</p>';
    	$reportModel = array(
    		'jifen_pl' => array(
    			'jifenPailieList' => $jifenPailieList,
    			'setting' => array(
		    		'pl_type' => 2,
		    		'title1' => '你的职业锚序列',
		    		'title_icon_tag1' => 'fa fa-list-ol',
		    		'title2' => '职业锚解读',
		    		'title_icon_tag2' => 'fa fa-file-text-o',
		    		'pl_jieshao' => '<p>每个人的职业锚都不是完全单一的，而是一个整合的体系，根据你的回答，下面是你每一项职业锚的具体匹配情况：</p>',
		    		'zhuyi' => '<p><i class="fa vaaii fa-exclamation-circle"></i>分数的高低表示你相应职业锚的偏好程度和优先级，每一项职业锚的得分高低因人而异，没有好坏之分，而且也会随着新的工作和生活经历而发生改变。</p>',
		    		'title2_jieshao' => $title2_jieshao,
		    	),
    		),
    		'extend_read' => empty($commonConf['extendRead']) ? array() : 
    			self::componentExtendRead($commonConf['extendRead']['title'],  $commonConf['extendRead']['content'], $commonConf['extendRead']['title_icon_tag']));
    	return $reportModel;
    }
    
}