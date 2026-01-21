<?php
namespace service\report;

/**
 * 价值观测试
 * 
 * @author 
 */
class ValueSense extends \service\report\ReportBase
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
     * @return ValueSense
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ValueSense();
        }
        return self::$instance;
    }
    
    private static $groupMap = array(
    	'变化开放' => array('自主', '刺激'),
    	'自我提升' => array('享乐', '成就', '权力'),
    	'态度保守' => array('遵从', '传统', '安全'),
    	'自我超越' => array('友善', '博爱'),
    );
    
    /**
     * 解读
     * 
     * @return string
     */
    private static function formatExplain($type, $conf)
    {
    	$fitTitle = self::tagRounded('适合的环境', 'blue', -10);
    	$fit = self::content($conf['fit']);
    	$positiveTitle = self::tagRounded('积极影响', 'red', -10);
    	$positive = self::content($conf['positive']);
    	$conflictTitle = self::tagRounded('潜在冲突', 'gray', -10);
    	$conflict = self::content($conf['conflict']);
    	$advantagesTitle = self::tagRounded('发挥你的优势', 'purple', -10);
    	$advantagesArr = array();
    	foreach ($conf['advantages'] as $key => $value) {
    		$title = self::titleDot($key);
    		$content = self::content($value);
    		$advantagesArr[] = "{$title}{$content}";
    	}
    	$advantages = implode('', $advantagesArr);
    	return <<<EOT
{$fitTitle}{$fit}
{$positiveTitle}{$positive}
{$conflictTitle}{$conflict}
{$advantagesTitle}{$advantages}
EOT;
    }
    
    /**
     * 解读
     * 
     */
    private static function formatRemark($type, $conf)
    {
    	$imgMap = array();
    	$title = self::titleBigRound('你的核心价值观');
    	$subTitle = self::titleUnderline($type);
    	$imgMain = self::imgMain(empty($imgMap[$type]) ? '价值观-类型.png' : $imgMap[$type]);

    	$content = self::content($conf['remark']);
    	return "{$title}{$subTitle}{$imgMain}{$content}";
    }
    
    /**
     * 解读
     *
     */
    private static function formatGroupExplain($type, $conf)
    {
    	$imgMap = array(
    		'态度保守' => '价值观-态度保守.png',
    		'自我超越' => '价值观-自我超越.png',
    		'变化开放' => '价值观-变化开放.png',
    		'自我提升' => '价值观-自我提升.png',
    	);
    	$imgMain = self::imgMain(empty($imgMap[$type]) ? '' : $imgMap[$type], 1);
    	$content = self::content($conf['explain']);
    	return "{$imgMain}{$content}";
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
		$dimensionConf =  $commonConf['dimensionList'];
		$groupConf = $commonConf['groupList'];
	
		$groupList = array();
		$duoweiduParentList = array();
		$id = 1;
		$meshList = array();
		$jifenPailieList = array();
		
		$dimensionMap = array();
		foreach (self::$groupMap as $groupName => $list) {
			foreach ($list as $testType) {
				$dimensionMap[$testType] = $groupName;
			}
		}
		$groupArr = array();
		if (is_iteratable($dimensionList)) foreach ($dimensionList as $dimensionName => $dimensionRow) {
			$jifenPailieList[] = array(
				'id' => $id++,
				'weidu_name' => $dimensionName,
				'total_score' => $dimensionRow['totalScore'],
				'pl_type' => 2,
				'jifen_type' => 1,
				'jianjie' => $dimensionConf[$dimensionName]['desc'],
				'total_result_remark' => self::formatRemark($dimensionName, $dimensionConf[$dimensionName]), 
				'xiangxi' => self::formatExplain($dimensionName, $dimensionConf[$dimensionName]),
				'last_percent' => intval($dimensionRow['percent']),
			);
			// 所属分组
			$groupName = $dimensionMap[$dimensionName];
			$groupArr[$groupName][$dimensionName] = $dimensionRow;
		}
		$fantwoList = array();
		foreach ($groupArr as $groupName => $list ) {
			$groupTotalScore = 0;
			$groupUserScore = 0;
			foreach ($list as $dimensionRow) {
				$groupTotalScore += $dimensionRow['totalScore'];
				$groupUserScore += $dimensionRow['score'];
			}
			// 组内占比
			$groupPercent = self::getPercent($groupUserScore, $groupTotalScore, 0);
			$fantwoList[] = array(
				'id' => $id++,
				'weidu_name' => $groupName,
				'fan_type' => 4,
				'jifen_type' => 1,
				'result_explain' => self::formatGroupExplain($groupName, $groupConf[$groupName]),
				'last_percent' => $groupPercent,
			);
		}
		
		$title2_jieshao = <<<EOT
<div class='img-main'><img src="职业倦怠-02.png" /></div>	
<p>以下是与你匹配度最为相似的三项价值观在工作与生活中的表现分析以及相关建议：</p>
EOT;
		$commonSv = \service\Common::singleton();
		$reportModel = array(
			'jifen_pl' => array(
				'jifenPailieList' => $jifenPailieList,
				'setting' => array(
					'pl_type' => 2,
					'title1' => '你的所有价值观序列',
					'title2' => '价值观在工作中的应用',
					'pl_jieshao' => '<p>以下是你每一项价值观的具体度量得分。该分数的高低体现了你在进行决策时所依据的动机的相对优先级。每一项价值观的得分情况因人而异，不存在好坏之分，并且会随着新的生活经历而发生变化。</p>',
					'title2_jieshao' => $commonSv::replaceImgSrc($title2_jieshao, 'report'),
				),
			),
			'fantwo' => array(
				'fantwoList' => $fantwoList,
				'setting' => array(
					'fan_type' => 4,
					'title' => '价值观的分类维度',
					'jianjie' => '<p>十项基本价值观能够划分为两个垂直的维度，即开放与保守、自我超越与自我增强。以下是你在这四个方向上的具体呈现：</p>',
				),
			),
			
		);
		return $reportModel;
    }

}