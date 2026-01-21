<?php
namespace service\report;

/**
 * 心理防御
 * 
 * @author 
 */
class MentalDefense extends \service\report\ReportBase
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
     * @return MentalDefense
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MentalDefense();
        }
        return self::$instance;
    }

    private static $groupMap = array(
    	'不成熟型' => array(
    		'投射'=> array(4,12,25,36,55,60,66,72,87),
    		'被动攻击'=> array(2,22,39,45,54),
    		'潜意显现'=> array(7,21,27,33,46),
    		'抱怨'=> array(69,75,82),
    		'幻想'=> array(40),
    		'分裂'=> array(43,53,64),
    		'退缩'=> array(9,67),
    		'躯体化'=> array(28,62),
    	),
    	'成熟型' => array(
    		'升华'=> array(5,74,84),
    		'压抑'=> array(3,59),
    		'幽默'=> array(8,61,34),
    	),
    	'中间型' => array(
    		'反向形成'=> array(13,47,56,63,65),
    		'回避'=> array(32,35,49),
    		'理想化'=> array(51,58),
    		'假性利他'=> array(1),
    		'逞强'=> array(11,18,23,24,30,37), // 伴无能之全能
    		'仪式抵消'=> array(71,78,88), // 解除
    		'克制'=> array(10,17,29,41,50), // 制止
    		'认同'=> array(19), // 同一化
    		'心理预演'=> array(68,81), // 期望
    		'交往补偿'=> array(80,86), // 交往倾向
    		'口欲补偿'=> array(73,79,85), // 消耗倾向
    		'隔离'=> array(70,76,77,83),
    		'否认'=> array(16,42,52),
    	),
    );
    
    /**
     * 解析
     *
     * @return string
     */
    private static function formatExplain($groupName, $text)
    {
    	$imgMap = array(
    		'生活质量' => '婚姻-生活质量.png',
    		'沟通质量' => '婚姻-沟通质量.png',
    		'精神质量' => '婚姻-精神质量.png',
    	);
    	$img = self::imgMain($imgMap[$groupName]);
    	return self::content($text . $img);
    }
    
    /**
     * 获取报告
     *
     * @return array
     */
    public function initQuestion()
    {
    	$imgMap = array(
    			'不成熟防御机制' => array( //8
    					'投射'=> array(4,12,25,36,55,60,66,72,87),
    					'被动攻击'=> array(2,22,39,45,54),
    					'潜意显现'=> array(7,21,27,33,46),
    					'抱怨'=> array(69,75,82),
    					'幻想'=> array(40),
    					'分裂'=> array(43,53,64),
    					'退缩'=> array(9,67),
    					'躯体化'=> array(28,62),
    			),
    			'成熟防御机制' => array( //3
    					'升华'=> array(5,74,84),
    					'压抑'=> array(3,59),
    					'幽默'=> array(8,61,34),
    			),
    			'中间型防御机制' => array( //13
    					'反向形成'=> array(13,47,56,63,65),
    					'回避'=> array(32,35,49),
    					'理想化'=> array(51,58),
    					'假性利他'=> array(1),
    					'逞强'=> array(11,18,23,24,30,37), // 伴无能之全能
    					'仪式抵消'=> array(71,78,88), // 解除
    					'克制'=> array(10,17,29,41,50), // 制止
    					'认同'=> array(19), // 同一化
    					'心理预演'=> array(68,81), // 期望
    					'交往补偿'=> array(80,86), // 交往倾向
    					'口欲补偿'=> array(73,79,85), // 消耗倾向
    					'隔离'=> array(70,76,77,83),
    					'否认'=> array(16,42,52),
    			),
    			'其他' => array('' => array(6,14,15,20,26,31,38,44,48,57)),
    	);
    	$questionDatas = <<<BLOCK

BLOCK;
    	$questionDatas = explode("\n", $questionDatas);
    	$questionList = array();
    	foreach ($questionDatas as $questionData) {
    		$questionDataArr = explode(".", $questionData);
    		$questionList[$questionDataArr['0']] = $questionDataArr['1'];
    	}
    	$questionStable = array();
    
    	foreach ($imgMap as $group => $groupList) {
    		foreach ($groupList as $testType => $indexs) {
    			foreach ($indexs as $index) {
    				$matter = $questionList[$index];
    				$questionStable[$index] = array (
    					'groupName' => '',
    					'matter' => $matter,
    					'testType' => $testType,
    					'scoreValue' => '54321',
    					'selections' => array (
    						'A' => array ('name' => '完全符合'),
    						'B' => array ('name' => '比较符合'),
    						'C' => array ('name' => '中立或不确定'),
    						'D' => array ('name' => '不太符合'),
    						'E' => array ('name' => '完全不符合')
    					),
    				);
    	
    			}
    			
    		}
    		
    	}

    	shuffle($questionStable);
    	$index = 1;
    	$newQuestionList = array();
    	foreach ($questionStable as $row) {
    		$newQuestionList[$index++] = $row;
    	}
    	$newQuestionList = array('1' => $newQuestionList);
    	var_export($newQuestionList);exit;
    	
    }
    
    /**
     * 获取报告
     *  
     * @return array
     */
	public function getReport($testOrderInfo, $testPaperInfo)
    {
		$answerResult = $this->getAnswerResultByDimension($testOrderInfo, $testPaperInfo);
		$dimensionList = $answerResult['dimensionList'];
		
		$commonConf = getStaticData($testPaperInfo['name'], 'common');
		
		$groupConf = $commonConf['groupList'];
		$dimensionConf = $commonConf['dimensionList'];
		
		
		$groupList = array();
		$duoweiduParentList = array();
		$id = 1;
		$meshList = array();
		$jifenPailieList = array();
		if (is_iteratable($groupConf)) foreach ($groupConf as $groupName => $row) {
			$child_duoweidu = array();
			$dimensionNames = array_keys(self::$groupMap[$groupName]);
			$groupTotalScore = 0;
			$groupUserScore = 0;
			foreach ($dimensionNames as $dimensionName) {
				$dimensionRow = $dimensionList[$dimensionName];
				$child_duoweidu[] = array(
					'id' => $id++,
					'weidu_name' => $dimensionName,
					'total_score' => $dimensionRow['totalScore'],
					'extend' => array(
						'duorenshu' => array(
							'min_score' => '',
                            'max_score' => '',
						),
					),
					'duoweidu_type' => 1,
					'weidu_result' => array(),
					'user_score' => $dimensionRow['score'],
					'last_percent' => intval($dimensionRow['percent']),
				);

				$jifenPailieList[] = array(
					'id' => $id++,
					'weidu_name' => $dimensionName,
					'total_score' => $dimensionRow['totalScore'],
					'pl_type' => 1,
					'jifen_type' => 1,
					'jianjie' => $dimensionConf[$dimensionName]['desc'],
					'xiangxi' => $dimensionConf[$dimensionName]['explain'],
					'last_percent' => intval($dimensionRow['percent']),
				);
				$groupTotalScore += $dimensionRow['totalScore'];
				$groupUserScore += $dimensionRow['score'];
			}
			// 组内占比
			$groupPercent = self::getPercent($groupUserScore, $groupTotalScore, 0);
			$duoweiduParentList[] = array(
				'id' => $id++,
				'weidu_name' => $groupName,
				'total_score' => 0,
				'extend' => array(
					'weidu_show' => 1,
					'tubiao_yangshi' => 1,
				),
				'duoweidu_type' => 1,
				'jianjie' => $row['desc'],
				'xiangxijieda' => $row['explain'],
				'child_duoweidu' => $child_duoweidu,
				
			);
			$meshList[] = array(
				'id' => $id++,
				'weidu_name' => $groupName,
				'mesh_type' => 4,
				'last_percent' => $groupPercent,
			);
		}
		$jianjie = <<<EOT
<div class='img-main'><img src="大五人格-测试.png" /></div>	
<p>以下是你在防御方式上各维度上的得分网状分布图：</p>
EOT;
		$title2_jieshao = <<<EOT
<div class='img-center'><img src="瑞文-理论.png" /></div>	
<p>以下是你最常使用的三种防御方式以及详细的分析和指导建议。</p>
EOT;
		$commonSv = \service\Common::singleton();
		$reportModel = array(
			'duoweidu' => array(
				'duoweiduParentList' => $duoweiduParentList,	
				'setting' => array(
					'duo_weidu_type' => 1,
					'title' => '你的防御方式子维度分布',
					'jianjie' => $commonSv::replaceImgSrc($jianjie, 'report'),
					'show_type' => 1,
				),
			),
			'duoweidu_mesh' => array(
				'meshList' => $meshList,
				'setting' => array(
					'mesh_type' => 4,
					'title' => '你的防御方式类型分布',
					'jianjie' => '<p>心理防御是我们抵御内心不愉快想法或情感的心理手段，所有的防御方式都是为了保护我们免受心理刺激。</p>
<p>但就其效果和影响可分为成熟的防御方式、不成熟的防御方式、中间型防御方式。以下是你在这3个维度上的具体情况：</p>',
					'xiangxijieda' => '<p><i class="fa vaaii fa-exclamation-circle"></i>注意事项：上方图表可以点击任意维度后会显示详细信息，<span style="color: #f6727e;">如果百分比占比太小而不显示文字</span>，请点击图表看详情</p>',
				
				),
			),
			'jifen_pl' => array(
				'jifenPailieList' => $jifenPailieList,
				'setting' => array(
					'pl_type' => 1,
					'title1' => '你的心理防御方式序列',
					'title2' => '你的常用防御方式解析',
					'pl_jieshao' => '<p>由于每个人的成长经历不同，观念不同，所以人们常用的心理防御方式也有所不同，以下是你的心理防御方式具体得分情况：</p>',
					'title2_jieshao' => $commonSv::replaceImgSrc($title2_jieshao, 'report'),
						),
				),
			'extend_read' => empty($commonConf['extendRead']) ? array() :
				self::componentExtendRead($commonConf['extendRead']['title'],  $commonConf['extendRead']['content'])
		);
		return $reportModel;
    }
    
}