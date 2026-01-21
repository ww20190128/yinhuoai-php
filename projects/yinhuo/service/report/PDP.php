<?php
namespace service\report;

/**
 * PDP
 * 
 * @author 
 */
class PDP extends \service\report\ReportBase
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
     * @return PDP
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PDP();
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
    	// 行为特点
    	$characterList = self::toHtmlTag($staticData['行为特点'],  'li');

    	// 工作风格
    	$workStyleList = array();
    	if (is_iteratable($staticData['工作风格'])) foreach ($staticData['工作风格'] as $value) {
    		$workStyleList[] = self::contentBg($value, 'purple');
    	}
    	$workStyleList = implode('', $workStyleList);
    	$result = array();
    	$result[] = self::imgMain($staticData['形象图片']);
    	
    	$desc = <<<EOT
    	<span style="color: #f6727e;">{$staticData['类型']}（{$staticData['别名']}）</span>的人{$staticData['描述']}
EOT;
    	$result[] = self::contentBg($desc);
    	$result[] = self::titleBigRound('代表名人');
    	$result[] = self::imgMain($staticData['代表名人图片']);
    	$result[]  = self::titleUnderline($staticData['代表名人']); // 标题
    	$result[] = self::content($staticData['代表名人特质']);
    	$result[] = self::combine1('处世格言', self::toHtmlTag(array($staticData['处世格言']['文本'], $staticData['处世格言']['来源']), 'p'));
    	$result[] = self::combine1('关键词', self::toHtmlTag($staticData['关键词'], 'p'));
    	$result[] = self::combine1('优点', self::toHtmlTag($staticData['优点'], 'p'));
    	$result[] = self::combine1('缺点', self::toHtmlTag($staticData['缺点'], 'p'));
    	$result[] = self::combine1('行为特点', self::toHtmlTag($characterList, 'p'));
    	$result[] = self::combine1('工作风格', self::toHtmlTag($workStyleList, 'p'));
    	return implode('', $result);
    }
    
    /**
     * 获取作答的结果
     *
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo, &$percentList = array())
    { 
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    	
    	// 统计各项得分
    	$scoreMap = array();
    	$totalScoreMap = array(); // 每种类型的总分
    	$answerScoreMap = array(4, 3, 2, 1, 0); // 非常符合  比较符合 不确定  不太符合   完全不符合
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		if (empty($question['scoreValue'])) {
    			continue;
    		}
    		$scoreValue = $question['scoreValue'];
    		$userAnswer = $answerList[$question['id']]; // 作答的答案
    		$userScore = $answerScoreMap[$userAnswer];
    		$maxScore = max($answerScoreMap);
    		
    		if (empty($totalScoreMap[$scoreValue])) {
    			$totalScoreMap[$scoreValue] = $maxScore;
    		} else {
    			$totalScoreMap[$scoreValue] += $maxScore;
    		}
    		if (empty($scoreMap[$scoreValue])) {
    			$scoreMap[$scoreValue] = $userScore;
    		} else {
    			$scoreMap[$scoreValue] += $userScore;
    		}
    	}
    	$percentList = array();
    	foreach ($totalScoreMap as $type => $totalScore) {
    		$percentList[$type] = array(
    			'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type],
    			'totalScore' => $totalScore,
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
    	$meshList = array();
    	$fantwoList = array();
    	$sort = 1;
    	if (is_iteratable($percentList)) foreach ($percentList as $type => $row) {
    		$staticData = getStaticData($testPaperInfo['name'], $type);
    		if (empty($staticData)) {
    			continue;
    		}
    		$meshList[] = array(
    			'id' => $id,
    			'weidu_name' => $staticData['类型'],
    			'total_score' => 24,
    			'mesh_type' => 5,
    			'jifen_type' => 0,
    			'suanfa_type' => 4,
    			'sort' => $sort,
    			'last_percent' => $row['percent'],
    			'result' => array(
    				'id' => $id,
    				'name' => $staticData['类型'],
    				'min_score' => 0,
    				'max_score' =>  24,
    				'result_explain' => $staticData['简介'],
    				'mesh_id' => $id,
    			),	
    	    );
    		
    		$fantwoList[] = array(
    			'id' => $id++,
    			'weidu_name' => $staticData['类型'],
    			'fan_type' => 4,
    			'jifen_type' => 1,
    			'last_percent' => $row['percent'],
    			'result_explain' => self::formatExplain($staticData),
    			'sort' => $sort,
    			'last_percent' => $row['percent'],
    		);
    		$id ++;
    		$sort++;
    	}
    	$conf = getStaticData($testPaperInfo['name'], 'common');
    	$reportModel = array(
    		'duoweidu_mesh' => array(
    			'meshList' => $meshList,
    			'setting' => array(
    				'mesh_type' => 5,
    				'title' => '您的PDP类型',
    				'title_icon_tag' => 'fa-ioxhost',
    			),
    		),
    		'extend_read' => self::componentExtendRead('PDP五类型形象化讲解',  $conf['PDP五类型形象化讲解'], 'fa-github-alt'),
    		'fantwo' => array(
	    		'fantwoList' => $fantwoList,
    			'setting' => array(
    				'fan_type' => 4,
    				'title' => '你的各类型指标分布图',
    				'title_icon_tag' => 'fa-pie-chart',
    				'tubiao_touming_color' => 'rgba(245,122,134,0.5)',
    				'tubiao_yangshi' => 1, // 图标样式
    				'jianjie' => '<p>"PDP行为风格"是指一个人天赋中最擅长的做事风格，根据不同的人风格特性的不同，分别用了5种动物来代表，分别是：老虎型、孔雀型、考拉型、猫头鹰型和变色龙型。每个人身上都带有一种或多种不同的处事风格，以下是你的专属行为风格示意图：</p>',
    				'xiangxijieda' => '<p>假若你有某一项分远远高于其它四项，你就是典型的这种风格，假若你有某两项分大大超过其它三项，你是这两种风格的综合；假若你各项分数都比较接近，那么说明你是一个面面俱到、均衡发展的人。</p>',
    			),
    		),
    	);
    	return $reportModel;
    }

}