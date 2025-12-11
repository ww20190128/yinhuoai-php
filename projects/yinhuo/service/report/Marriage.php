<?php
namespace service\report;

/**
 * 婚姻质量
 * 
 * @author 
 */
class Marriage extends \service\report\ReportBase
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
     * @return Marriage
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Marriage();
        }
        return self::$instance;
    }
    
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
	public function getReport($testOrderInfo, $testPaperInfo)
    {
		$answerResult = $this->getAnswerResultByDimension($testOrderInfo, $testPaperInfo);
		$levelConf = $answerResult['levelConf'];

		$dimensionList = $answerResult['dimensionList'];
		// 分组
		$groupMap = array(
			'理想化程度' => '精神质量',
			'婚姻满意度' => '精神质量',
			'性格契合度' => '精神质量',
			'角色平等性' => '精神质量',
				
			'观念一致性' => '沟通质量',//  没有这个类型
			'夫妻交流质量' => '沟通质量',
			'解决冲突方式' => '沟通质量',
				
			'性生活' => '生活质量',
			'子女与婚姻' => '生活质量',
			'经济安排' => '生活质量',
			'业余活动' => '生活质量',
			'亲友关系' => '生活质量',
			
		);
		
		$groupList = array();
		if (is_iteratable($dimensionList)) foreach ($dimensionList as $dimensionRow) {
			$groupName = $groupMap[$dimensionRow['typeName']]; // 分组
			$groupList[$groupName][$dimensionRow['typeName']] = $dimensionRow;
		}
		
		$commonConf = getStaticData($testPaperInfo['name'], 'common');
		
		// 根据阀值由小到大排序
		$commonSv = \service\Common::singleton();

		$duoweiduParentList = array();
		$meshList = array();
		$id = 1;
		foreach ($groupList as $groupName => $list) {
			

			$child_duoweidu = array();
			$groupTotalScore = 0;
			$groupUserScore = 0;
			foreach ($list as $typeName => $dimensionRow) {
				$rangArr = empty($commonConf['dimensionList'][$typeName]['rang']) 
					? array() : explode('-', $commonConf['dimensionList'][$typeName]['rang']);
	
				$groupTotalScore += $dimensionRow['totalScore'];
				$groupUserScore += $dimensionRow['score'];
		
				$child_duoweidu[] = array(
					'id' => $id++,
					'weidu_name' => $typeName,
					'total_score' => $dimensionRow['totalScore'],
					'extend' => array(
						'duorenshu' => array(
							'min_score' => empty($rangArr['0']) ? 40 : $rangArr['0'],
                            'max_score' => empty($rangArr['1']) ? 70 : $rangArr['1'],
						),
						'isduorenshu' => 1,
						'child_weidu_result' => 1,
					),
					'jianjie' => '',
					'weidu_result' => array(
						'id' => $id++,
						'name' => $dimensionRow['levelConf']['levelName'],
						'zhuyi' => empty($dimensionRow['levelConf']['notice']) ? '' : $dimensionRow['levelConf']['notice'],
						'jianyi' => empty($dimensionRow['levelConf']['suggest']) ? '' : $dimensionRow['levelConf']['suggest'],
						'result_explain' => self::content(empty($dimensionRow['levelConf']['explain']) ? '' : $dimensionRow['levelConf']['explain']),
					),
					'user_score' => $dimensionRow['score'],
					'last_percent' => intval($dimensionRow['percent']),
				);
			}
			$groupPercent = self::getPercent($groupUserScore, $groupTotalScore, 0); // 分组占比
			$meshList[] = array(
				'id' => $id++,
				'weidu_name' => $groupName,
				'total_score' => $groupTotalScore,
				'mesh_type' => 6,
				'jifen_type' => 1,
				'last_percent' => $groupPercent,
			);
			
			// 分组等级配置
			$groupLevelConfList = $commonConf['groupLevelList'][$groupName];
			uasort($groupLevelConfList, array($commonSv, 'sortByThreshold'));
			
			$groupLevelConf = array();
			foreach ($groupLevelConfList as $levelName => $row) {
				if ($groupPercent >= $row['threshold']) {
					$row['levelName'] = $levelName;
					$groupLevelConf = $row;
				} else {
					break;
				}
			}

			$duoweiduParentList[] = array(
				'id' => $id++,	
				'weidu_name' => $groupName,
				'total_score' => $groupTotalScore,
				'extend' => array(
					'weidu_show' => 2,
					'zhizhenbiao' => 1,
					'weidu_color' => '#f6727e',
				),
				'duoweidu_type' => 1,
				'jianjie' => '',
				'weidu_result' => array(
					'id' => $id++,
					'name' => $groupLevelConf['levelName'],
					'zhuyi' => empty($groupLevelConf['notice']) ? '' : $groupLevelConf['notice'],
					'jianyi' => empty($groupLevelConf['suggest']) ? '' : $groupLevelConf['suggest'],
					'result_explain' => self::formatExplain($groupName, empty($groupLevelConf['explain']) ? '' : $groupLevelConf['explain']),
				),
				'user_score' => $groupUserScore,
				'last_percent' => $groupPercent,
				'child_duoweidu' => $child_duoweidu,
			);
		}
		
		$commonConf = getStaticData($testPaperInfo['name'], 'common');
		$reportModel = array( // 整体情况
			'total_result_scoring' => array(
				'paper_tile' => '婚姻质量综合评估',
				'jifen_guize' => 3,
				'setting' => array(
					'jifen_guize' => 3,
					'suanfa_type' => 0,
					'title' => '婚姻质量总体结果',
					'title_icon' => 1,
					'title_icon_type' => 1,
					
					
				),
				'last_percent' => intval($answerResult['percent']),
				'content' => array(
					'id' => $id++,
					'name' => $levelConf['levelName'],
					'jianyi' => empty($levelConf['suggest']) ? '' : $levelConf['suggest'],
					'result_explain' => empty($levelConf['explain']) ? '' : $levelConf['explain'],
				),
			),
			'duoweidu' => array(
				'duoweiduParentList' => $duoweiduParentList,
				'setting' => array(
					'duo_weidu_type' => 1,
					'title' => '婚姻质量水平深度解析',
					'jianjie' => '<p>以下是婚姻质量的3个大维度和12个基础维度的具体解析，您可以从中发现自己婚姻关系中所存在的欠缺和不足，并针对性的加以改善与提升。</p>',
				),
			),
			'duoweidu_mesh' => array(
				'meshList' => $meshList,
				'setting' => array(
					'mesh_type' => 6,
					'title' => '婚姻质量维度分布图',
					'jianjie' => '<p>下面是你在精神质量、沟通质量、生活质量3个综合维度上的得分网状图：</p>',
				
				),
			),
			'extend_read' => empty($commonConf['extendRead']) ? array() :
				self::componentExtendRead($commonConf['extendRead']['title'],  $commonConf['extendRead']['content'])
		);
		return $reportModel;
    }
    
}