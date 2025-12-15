<?php
namespace service\report;

/**
 * 霍兰德职业兴趣测评
 * 
 * @author 
 */
class HollandCareer  extends \service\report\ReportBase
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
     * @return HollandCareer
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new HollandCareer();
        }
        return self::$instance;
    }

    /**
     * 解读
     * 
     * @return string
     */
    private static function formatExplain($conf)
    {
    	$conf['推荐专业'] = self::toHtmlTag(explode(',', $conf['推荐专业']));
    	$conf['推荐职业'] = self::toHtmlTag(explode(',', $conf['推荐职业']));
    	return
    	<<<EOT
<p><i class="fa vaaicce fa-gear"></i><span style="font-size: .48rem; color: #f6727e;">性格特点</span></p>
<p>{$conf['性格特点']}</p>
<p><i class="fa vaaicce fa-tags"></i><span style="font-size: .48rem; color: #f6727e;">关键词</span></p>
<p>{$conf['关键词']}</p>
<p><i class="fa vaaicce fa-graduation-cap"></i><span style="font-size: .48rem; color: #f6727e;">推荐专业</span></p>{$conf['推荐专业']}
<p><i class="fa vaaicce fa-briefcase"></i><span style="font-size: .48rem; color: #f6727e;">推荐职业</span></p>{$conf['推荐职业']}
EOT;
    }
    
 
    
    /**
     * 解析标题
     * 
     * @return string
     */
    private static function formatTitle($type, $str)
    {
    	$commonSv = \service\Common::singleton();
    	if ($type == 2) {
    		$url = $commonSv::formartImgUrl('霍兰德-类型序列.png', 'report');
    	}
    	if ($type == 3) {
    		$url = $commonSv::formartImgUrl('霍兰德-职业兴趣.png', 'report');
    	}
    	if ($type == 4) {
    		$url = $commonSv::formartImgUrl('霍兰德-职业列表.png', 'report');
    	}
    	return <<<EOT
<div class='img-main'><img src="{$url}" title="" alt="" /></div>
<p>{$str}</p>
EOT;
	}
    
    /**
     * 获取作答结果
     * 
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo)
    { 
    	$map = array(
    		'C' => array( // C 常规型
		        '0' => '7, 19, 29, 39, 41, 51, 57', // 回答是得分的题目
		        '1' => '5, 18, 40', // 回答否得分的题目
		    ),
		    'R' => array( // R 现实型
		        '0' => '2, 13, 22, 36, 43',
		        '1' => '14, 23, 44, 47, 48',
		    ),
		    'I' => array( // I 研究型
		        '0' => '6, 8, 20, 30, 31, 42',
		        '1' => '21, 55, 56, 58',
		    ),
    		'A' => array( // A 艺术型
    			'0' => '4, 9, 10, 17, 33, 34, 49, 50, 54',
    			'1' => '32',
    		),
    		'S' => array( // S 社会型
    			'0' => '26, 37, 52, 59',
    			'1' => '1, 12, 15, 27, 45, 53',
    		),
		    'E' => array( // E 管理型
		        '0' => '11, 24, 28, 35, 38, 46, 60',
		        '1' => '3, 16, 25',
		    ),
		);
    	$indexMapA = array(); // 回答“是”的题序
    	$indexMapB = array(); // 回答“否”的题序
    	$totalScoreMap = array(); // 每种类型的总分
    	$indexMap = array();
    	foreach ($map as $type => $row) {
    		$indexList0 = array_map('intval', explode(',', $row['0']));
    		$indexList1 = array_map('intval', explode(',', $row['1']));
    		foreach ($indexList0 as $key => $index) {
    			$indexMapA[$index] = $type;
    			$indexMap[$index]['0'] = $type;
    		}
    		foreach ($indexList1 as $key => $index) {
    			$indexMapB[$index] = $type;
    			$indexMap[$index]['1'] = $type;
    		}
    		$totalScoreMap[$type] = count($indexList0) + count($indexList1);
    	}

    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];

    	// 统计各项得分
    	$scoreMap = array();
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		$userAnswer = $answerList[$question['id']]; // 作答的答案
    		if ($userAnswer == 0) { // 回答: A 是
    			if (!isset($indexMapA[$question['index']])) {
    				continue;
    			}
    			if (empty($scoreMap[$indexMapA[$question['index']]])) {
    				$scoreMap[$indexMapA[$question['index']]] = 1;
    			} else {
    				$scoreMap[$indexMapA[$question['index']]] += 1;
    			}
    		} else { // 回答 B 否
    			if (!isset($indexMapB[$question['index']])) {
    				continue;
    			}
    			if (empty($scoreMap[$indexMapB[$question['index']]])) {
    				$scoreMap[$indexMapB[$question['index']]] = 1;
    			} else {
    				$scoreMap[$indexMapB[$question['index']]] += 1;
    			}
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
    	$staticData = getStaticData($testPaperInfo['name'], '配置');
    	$answerResult = $this->getAnswerResult($testOrderInfo, $testPaperInfo);
    	$percentList = $answerResult['percentList'];
    	$list = array();
    	$model1 = array();
    	$id = 1;
		foreach ($percentList as $letter => $row) {
			$conf = empty($staticData['类型'][$letter]) ? array() : $staticData['类型'][$letter];
			if (empty($conf)) {
				continue;
			}
			$list[$letter] = array(
				'id' => $id++,
				'weidu_name' => $conf['名称'] . '（' . $letter . '）',
				'total_score' => 100, // 该类型总分
				'extend' => array(
					'pass_letter' => $letter,
				),	
				'pl_type' => 6,
				'jifen_type' => 1,
				'weidu_icon' => $conf['图标'],
				'weidu_icon_color' => $conf['图标颜色'],
				'jianjie' => $conf['简介'],
				'xiangxi' => self::formatExplain($conf),
				'last_percent' => $row['percent'], // 用户评分占比
				
			);
			$model1[$letter] = array(
				'name' => $letter . ' ' . $conf['名称'],
				'icon' => $conf['图标'],
				'active' => 0,
				'bgColor' => $conf['背景颜色'],
				'color' => $conf['图标颜色'],
			);
		}
		$top3 = array_slice($list, 0, 3);
		$typeArr = array_keys($top3);
		sort($typeArr);
		$userType = implode('', $typeArr);
		foreach ($typeArr as $val) {
			$model1[$val]['active'] = 1;
		}
		ksort($model1);
		array_unshift($model1, array(
			'name' => $userType,
			'icon' => '',
			'active' => 1,
			'bgColor' => '#f9cbcb',
			'color' => '#000',
		));

		$jobList = array();
		if (!empty($staticData['职业匹配'][$userType])) {
			$jobListArr = $staticData['职业匹配'][$userType];
			foreach ($jobListArr as $job => $row) {
				$jobList[] = array(
					'id' => $id++,
					'type' => $userType,
					'job' => $job,
					'comment' => $row['技能要求'],
					'pipei' => $row['匹配度'],
				);
			}
		}
		if (count($jobList) <= 10) {
			$userTypeArr = str_split($userType);
			$jobArrTmp = array();
			foreach ($userTypeArr as $val) {
				$jobArr = empty($staticData['类型'][$val]['推荐职业']) ? array() : explode(', ', $staticData['类型'][$val]['推荐职业']);
				if (!empty($jobArr)) {
					$jobArrTmp = array_merge($jobArrTmp, $jobArr);
				}
			}
			shuffle($jobArrTmp);
			$jobArrTmp = array_slice($jobArrTmp, 0, 10 - count($jobList));
			foreach ($jobArrTmp as $job) {
				$jobList[] =  array(
					'id' => $id++,
					'type' => $userType,
					'job' => $job,
					'comment' => '中等',
					'pipei' => rand(70, 85),
				);
			}
		}
		$reportModel = array(
			'jifen_pl' => array(
				'jifenPailieList' => array(
					'top3' => array_values($top3),
					'type' => $userType,
					'model1' => array_values($model1),
					'list' => array_values($list),
					'jobList' => array_values($jobList),
				),
				'setting' => array(
					'pl_type' => 6,
					'title1' => '你的职业兴趣密码',
					'title_icon_tag1' => 'fa-key',
					'title2' => '你的职业人格类型序列',
					'title_icon_tag2' => 'fa-unsorted',
					'title3' => '深入探索你的职业兴趣',
					'title_icon_tag3' => 'fa-binoculars',
					'title4' => '最适合你的职业列表',
					'title_icon_tag4' => 'fa-briefcase',
					'title1_zhuyi' => '<p>请特别注意每种类型在六边形中的位置。相邻的类型最为相似，而相对的类型则完全不同。例如，现实型和研究型相邻，表示这两种兴趣类型的个体较多；而现实型和社会型相对，表示同时具备这两种兴趣的人较少见。</p>',
					'title2_shuoming' => self::formatTitle(2, '以下是各职业人格类型的典型特征及你的匹配分数：'),
					'title3_shuoming' => self::formatTitle(3, '以下是与你最匹配的三种职业人格类型的详细分析：'),
					'title4_shuoming' => self::formatTitle(4, '职业兴趣与职业之间存在内在关联。以下是根据你的职业兴趣密码推荐的职业列表。'),
					'title4_zhuyi' => '<p>这些职业推荐基于世界500强的权威职业数据库，并结合国内一线城市的实际情况进行优化。</p>',
				),
			),
			// 拓展阅读
			'extend_read' => self::componentExtendRead('霍兰德测评报告指南', $staticData['拓展阅读']),
		);
		return $reportModel;
    }

}