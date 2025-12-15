<?php
namespace service\report;

/**
 * 简单的
 * 
 * @author 
 */
class Simple extends \service\report\ReportBase
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
     * @return Simple
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Simple();
        }
        return self::$instance;
    }

    /**
     * 获取报告(多个维度)
     * 
     * @return array
     */
    public function getDimensionReport($testOrderInfo, $testPaperInfo)
    {
    	// 获取作答结果
    	$answerResult = $this->getAnswerResultByDimension($testOrderInfo, $testPaperInfo);
		$dimensionList = empty($answerResult['dimensionList']) ? array() : $answerResult['dimensionList'];
    	$weiduList = array();
    	$id = 1;
    	$meshList = array();
    	$commonConf = getStaticData($testPaperInfo['name'], 'common');
    	if (!empty($commonConf['dimensionList'])) {
    		foreach ($commonConf['dimensionList'] as $dimensionName => $dimensionConf) {
    			
    			$dimensionRow = empty($answerResult['dimensionList'][$dimensionName]) ? array() : $answerResult['dimensionList'][$dimensionName];
    	
    			if (empty($dimensionRow)) {
    				continue;
    			}
    			$weiduList[] = array(
    				'id' => $id++,
    				'weidu_name' => $dimensionName, // 维度名称
    				'total_score' => $dimensionRow['totalScore'], // 总分
    				'extend' => array('tubiao_color' => '#f37b1d'),
    				'danweidu_type' => 3,
    				'weidu_result' => array(
    					'name' => empty($dimensionRow['levelConf']['levelName']) ? '' : $dimensionRow['levelConf']['levelName'],
    					'jianyi' => empty($dimensionRow['levelConf']['suggest']) ? '' : $dimensionRow['levelConf']['suggest'],
    					'result_explain' => empty($dimensionRow['levelConf']['explain']) ? '' : $dimensionRow['levelConf']['explain'],
    				),
    				'last_percent' => $dimensionRow['percent'],
    				'user_score' => $dimensionRow['score'], // 用户分数
    			);
    			$meshList[] = array(
    				'id' => $id++,
    				'jifen_type' => 1,
    				'weidu_name' => $dimensionName, // 维度名称
    				'total_score' => $dimensionRow['totalScore'], // 总分
    				'last_percent' => $dimensionRow['percent'],				
    			);
    		}
    	}

    	if ($testPaperInfo['name'] == \constant\TestPaper::NAME_HYPERACTIVITY) {  // 儿童多动症初步筛查
    		$jifen_guize = 2;
    	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_HYPOCHONDRIAC) {  // 疑病心理倾向评估
    		$jifen_guize = 1;
    	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_JOB_BURNOUT) {  // 职业倦怠度评估
    		$jifen_guize = 1;
    	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_SLEEP_QUALITY) {  // 睡眠质量专业评估
    		$dimensionList = array_values($dimensionList);
    		$result = array();
    		$charMap = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K');
    		if (is_iteratable($dimensionList)) foreach ($dimensionList as $key => $row) {
    			$result[$charMap[$key]] = array(
    				'text' => $row['typeName'],
    				'score' => $row['score'],
    			);
    		}
    		
    		$commonSv = \service\Common::singleton();
    		$reportModel = array(
    			'fanone' => array(    		
    				'result' => $result,
    				'psqi_level' => $answerResult['levelConf']['levelName'],
    				'fanone' => array(
    					'fan_type' => 3,
    					'result_name' => $answerResult['levelConf']['levelName'],
    					'result_explain' => empty($answerResult['levelConf']['explain']) ? '' : $commonSv::replaceImgSrc($answerResult['levelConf']['explain'], 'report'),
    				),
    				'setting' => array(
    					'fan_type' => 3,
    					'title1' => '您的睡眠质量',
    					'title_icon1' => 1,
    					'title_icon_tag1' => 'fa-hotel',
    					'title2' => '影响您睡眠的各维度分值',
    					'zhuyi' => '<p>从上面六个维度评估你的睡眠质量，分值越高，代表该维度对您的睡眠质量影响越大。</p>',
    				),		 
    			),
    			'extend_read' => self::componentExtendRead($commonConf['extendRead']['title'],  $commonConf['extendRead']['content']),
    		);
    		return $reportModel;
    	}
    	$explain = $answerResult['levelConf']['explain'];
    	$commonSv = \service\Common::singleton();
    	$explain = $commonSv::replaceImgSrc($explain, 'report');
    	
    	$suggest = empty($answerResult['levelConf']['suggest']) ? '' : $answerResult['levelConf']['suggest'];
    	$suggest = $commonSv::replaceImgSrc($suggest, 'report');
    	
    	$notice = empty($answerResult['levelConf']['notice']) ? '' : $answerResult['levelConf']['notice'];
    	$notice = $commonSv::replaceImgSrc($notice, 'report');
    	
    	$reportModel = array(
    		'total_result_scoring' => array(
    			'jifen_guize' => $jifen_guize,
    			'paper_tile' => $commonConf['title'],
    			'last_percent' => $answerResult['percent'],
    			'content' => array(
    				'id' => 1,
    				'name' => $answerResult['levelConf']['levelName'],
    				'result_explain' => $explain,
    				'jianyi' => $suggest,
    				'zhuyi' => $notice,
    			),
    			'setting' => array(
    				'jifen_guize' => $jifen_guize,
    				'title' => $commonConf['totalTitle'],
    				'title_icon_type' => 1,
    				'title_icon_image' => empty($commonConf['totalIcon']) ? '' : $commonConf['totalIcon'],
    				'zongjieguo_image' => empty($commonConf['totalImage']) ? '' : $commonConf['totalImage'],
    			),
    		),
    		'danweidu' => array(
    			'weiduList' => $weiduList,
    			'setting' => array(
    				'dan_weidu_type' => 3,
    				'title' => $commonConf['dimensionSet']['title'],
    				//'title_icon_tag' => $commonConf['dimensionSet']['icon'],
    				'jianjie' => $commonSv::replaceImgSrc($commonConf['dimensionSet']['desc']),
    			),
    		),
    		'extend_read' => self::componentExtendRead($commonConf['extendRead']['title'],  $commonConf['extendRead']['content']),
    	);
    	if ($testPaperInfo['name'] == \constant\TestPaper::NAME_JOB_BURNOUT) {
    		$reportModel['duoweidu_mesh'] = array(
    			'meshList' => $meshList,
    			'setting' => array(
    				'mesh_type' => 6,
    				'title' => '职业倦怠维度分布',
    			),
    		);
    	}
    	return $reportModel;
    }
    
    
    /**
     * 获取报告（最简单类型）
     *  易怒程度专业鉴定
		自尊类型测试
		自我效能评估
		孤独症特质测试
		创伤后应激障碍测评
		情绪管控评估
		控制信念评测
		自信心水平测试
		职场战斗力评估
		拖延行为风格评估
     * @return array
     */
    public function getSimpleReport($testOrderInfo, $testPaperInfo)
    {
    	// 获取作答结果
    	if ($testPaperInfo['name'] == \constant\TestPaper::NAME_CONTROL_BELIEF) { // 控制信念评测
    		$answerResult = $this->getAnswerResultByClassify($testOrderInfo, $testPaperInfo, array('内向' => 'I', '外向' => 'O'));
    	} else {
    		$answerResult = $this->getAnswerResultByScoreValue($testOrderInfo, $testPaperInfo);
    	}    	
    	$commonConf = getStaticData($testPaperInfo['name'], 'common');

    	$jifen_guize = 1;
    	$setting = array(
    		'jifen_guize' => $jifen_guize,
    		'title' => $commonConf['totalTitle'],
    		'title_icon_image' => $commonConf['totalIcon'],
    		'title_icon_color' => empty($commonConf['totalIconColor']) ? '' : $commonConf['totalIconColor'],
    		'icon2_touming' => '#FEDDDD',
    		'title_icon_type' => 1,
    	);
    	if ($testPaperInfo['name'] == \constant\TestPaper::NAME_DELAY_BEHAVIOR) {  // 拖延行为风格评估
    		$jifen_guize = 2;
    		$setting = array(
    			'jifen_guize' => $jifen_guize,
    			'title' => $commonConf['totalTitle'],
    			'title_icon_image' => $commonConf['totalIcon'],
    			'title_icon' => 1,
    			'zongjieguo_image' => empty($commonConf['totalImage']) ? '' : $commonConf['totalImage'],
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	$explain = empty($answerResult['levelConf']['explain']) ? '' : $answerResult['levelConf']['explain'];
    	$explain = $commonSv::replaceImgSrc($explain, 'report');
    	
    	$notice = empty($answerResult['levelConf']['notice']) ? '' : $answerResult['levelConf']['notice'];
    	$notice = $commonSv::replaceImgSrc($notice, 'report');
    	
    	$suggest = empty($answerResult['levelConf']['suggest']) ? '' : $answerResult['levelConf']['suggest'];
    	$suggest = $commonSv::replaceImgSrc($suggest, 'report');
    	$reportModel = array(
    		'total_result_scoring' => array(
	    		'jifen_guize' => $jifen_guize,
	    		'paper_tile' => $commonConf['title'],
	    		'last_percent' => $answerResult['percent'],
	    		'content' => array(
	    			'id' => 1,
	    			'name' => $answerResult['levelConf']['levelName'],
	    			'result_explain' => $explain,
	    			'zhuyi' => $notice,
	    			'jianyi' => $suggest,
	    		),
	    		'setting' => $setting,		 
    		),
    		'extend_read' => empty($commonConf['extendRead']) ? array() : 
    			self::componentExtendRead($commonConf['extendRead']['title'],  $commonConf['extendRead']['content']),
    	);
    	return $reportModel;
    }
    
    /**
     * 获取报告(多元智力)
     *
     * @return array
     */
    public function getMultipleIQReport($testOrderInfo, $testPaperInfo)
    {
    	// 获取作答结果
    	$answerResult = self::getAnswerResultByDimension($testOrderInfo, $testPaperInfo);
    	$dimensionList = empty($answerResult['dimensionList']) ? array() : $answerResult['dimensionList'];
    	$commonConf = getStaticData($testPaperInfo['name'], 'common');
    
    	$weiduList = array();
    	$meshList = array();
    	$id = 1;
    	$commonConf = getStaticData($testPaperInfo['name'], 'common');
    	$alias = array(
    		'言语' => '语言智能',
    		'逻辑' => '逻辑数学智能',	
    		'视觉' => '空间智能',
    		'身体' => '身体动觉智能',
    		'音乐' => '音乐智能',
    		'人际交往' => '人际智能',
    		'自知自省' => '内省智能',
    		'自然观察' => '自然观察者智能',	
    	);
    	if (!empty($commonConf['dimensionList'])) {
    		foreach ($commonConf['dimensionList'] as $dimensionName => $dimensionConf) {
    			$dimensionRow = empty($dimensionList[$dimensionName]) ? array() :$dimensionList[$dimensionName];
    			if (empty($dimensionRow)) {
    				$dimensionRow = empty($dimensionList[$alias[$dimensionName]]) ? array() :$dimensionList[$alias[$dimensionName]];
	    			if (empty($dimensionRow)) {
	    				continue;
	    			}
    			}
    			// 范围
    			$rangeArr = empty($commonConf['dimensionRangeList'][$dimensionName]) ? array() : explode('-', $commonConf['dimensionRangeList'][$dimensionName]);
    			$meshList[] = array(
    				'id' => $id++,
    				'weidu_name' => $dimensionName,
    				'total_score' => $dimensionRow['totalScore'], // 总分
    				'mesh_type' => 6,
    				'last_percent' => $dimensionRow['percent'],
    			);
    			$weiduList[] = array(
    				'id' => $id++,
    				'weidu_name' => $dimensionName, // 维度名称
    				'total_score' => $dimensionRow['totalScore'], // 总分
    				'extend' => array(
    					'duorenshu' => array(
    						'max_score' => max($rangeArr),
    						'min_score' => min($rangeArr),
    					),
    					'tubiao_color' => '#f6727e',
    				),	
    				'danweidu_type' => 4,
    				'weidu_result' => array(
    					'name' => empty($dimensionRow['levelConf']['levelName']) ? '' : $dimensionRow['levelConf']['levelName'],
    					'jianyi' => empty($dimensionRow['levelConf']['suggest']) ? '' : $dimensionRow['levelConf']['suggest'],
    					'result_explain' => empty($dimensionRow['levelConf']['explain']) ? '' : $dimensionRow['levelConf']['explain'],
    				),
    				'last_percent' => $dimensionRow['percent'],
    				'user_score' => $dimensionRow['score'], // 用户分数
    			);
    		}
    	}
    	$dimensionDesc = <<<EOT
		<div class ='img-main'><img src="多元智力-维度.jpg" /></div>
<p>以下是多元智能理论中每种智力的详细解读，以及根据你的表现为你提供的个性化指导建议：</p>
EOT;
    	$dimensionMeshDesc = <<<EOT
<div class ='img-center'><img src="多元智力-结构.png" /></div>
<p>美国心理学家加德纳提出了多元智能理论，该理论对智力的定义和认识不同于传统观念。他将人类智能划分为八种类型：语言智能、音乐智能、逻辑-数学智能、空间智能、肢体运动智能、自然智能、自省智能和人际智能。加德纳认为这八种能力彼此独立，反映了人类处理信息的不同方式。</p>
<p>每个人都有独特的智力组合体系，识别出自己的优势智能，有助于在生活和工作中扬长避短，激发潜在能力，充分发展个性。</p>
<p>下面展示的是你专属的多元智力结构图：</p>
EOT;
    	$reportModel = array(
    		'danweidu' => array(
    			'weiduList' => $weiduList,
	    		'setting' => array(
	    			'dan_weidu_type' => 4,
	    			'title' => '多元智力维度解析',
	    			'title_icon_tag' => 'fa-sliders',
	    			'jianjie' => $commonSv::replaceImgSrc($dimensionDesc, 'report'),
	    		),
    		),
    		'duoweidu_mesh' => array(
    			'meshList' => $meshList,
    			'setting' => array(
    				'mesh_type' => 6,
    				'title' => '你的多元智力结构',
    				'title_icon_tag' => 'fa-cloud',
    				'tubiao_yangshi' => 1,
    				'jianjie' => $commonSv::replaceImgSrc($dimensionMeshDesc, 'report'),
    				'title_icon_color' => '#f6727e',
    				'icon_touming_color' => 'rgba(245,122,134,0.5)',
    			),
    		),
    		'extend_read' => empty($commonConf['extendRead']) ? array() : 
    			self::componentExtendRead($commonConf['extendRead']['title'],  $commonConf['extendRead']['content']),
    	);
    	return $reportModel;
    }
}