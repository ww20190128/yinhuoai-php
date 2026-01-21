<?php
namespace service\report;

/**
 * mbti 专业爱情测试
 * 
 * @author 
 */
class MBTILove extends \service\report\ReportBase
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
     * @return MBTILove
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MBTILove();
        }
        return self::$instance;
    }

    /**
     * 获取文章
     *
     * @return array
     */
    public static function formatArticle($reportMbtiLoveEtt)
    {
    	
    	$titleBig = self::titleBigCombination($reportMbtiLoveEtt->name, $reportMbtiLoveEtt->type);
    	$subTitle = self::titleUnderline( $reportMbtiLoveEtt->tags);
		// 图片路径
    	$subDir = 'report' . DS . 'MBTI' . DS . 'love' . DS . ($reportMbtiLoveEtt->version == 1 ? 'man' : 'woman');
    	$imgMain = self::imgMain($reportMbtiLoveEtt->type . '-' . $reportMbtiLoveEtt->version . '.png', true, $subDir);

    	$titleDot1 = self::titleDot('能量方向');
    	$content1 = self::content($reportMbtiLoveEtt->energyDirection, 20);
    	$titleDot2 = self::titleDot('体验倾向');
    	$content2 = self::content($reportMbtiLoveEtt->experienceTend, 20);
    	$titleDot3 = self::titleDot('决定倾向');
    	$content3 = self::content($reportMbtiLoveEtt->determiningTend, 20);
    	$titleDot4 = self::titleDot('组织倾向');
    	$content4 = self::content($reportMbtiLoveEtt->organizationalTend, 20);
		$peculiarityTag = self::tagRounded('TA的个性特点', 'blue', -10);
		$peculiarity = self::content($reportMbtiLoveEtt->peculiarity);
		
		$meetWithPlaceTag = self::tagRounded('与TA偶遇', 'red', -10);
		$meetWithPlace = self::content($reportMbtiLoveEtt->meetWithPlace);
		
		$firstLoveDescTag = self::tagRounded('爱之初体验', 'purple', -10);
		$firstLoveDesc = self::content($reportMbtiLoveEtt->firstLoveDesc);
		$firstLoveBox = self::combineBox('约会锦囊：' . $reportMbtiLoveEtt->firstLoveTitle, $reportMbtiLoveEtt->firstLoveContent);
		
		$actionDescTag = self::tagRounded('捕心行动', 'gray', -10);
		$actionDesc = self::content($reportMbtiLoveEtt->actionDesc);
		$actionDescBox = self::combineBox('约会锦囊：' . $reportMbtiLoveEtt->actionTitle, $reportMbtiLoveEtt->actionContent);
		
		$perfectSexTag = self::tagRounded('拥有完美的性爱', 'red', -10);
		$perfectSex = self::content($reportMbtiLoveEtt->perfectSex);
		$longerTag = self::tagRounded('让爱天长地久', 'blue', -10);
		$longer = self::content($reportMbtiLoveEtt->longer);
		

return <<<EOT
{$titleBig}
{$subTitle}
{$imgMain}
{$titleDot1}{$content1}
{$titleDot2}{$content2}
{$titleDot3}{$content3}
{$titleDot4}{$content4}

{$peculiarityTag}{$peculiarity}
{$meetWithPlaceTag}
<p>要找寻你的教导者，你可以在以下地方找到TA：</p>{$meetWithPlace}
{$firstLoveDescTag}{$firstLoveDesc}{$firstLoveBox}

{$actionDescTag}{$actionDesc}{$actionDescBox}

{$perfectSexTag}{$perfectSex}
{$longerTag}{$longer}

EOT;
    }
    
    /**
     * 获取分析
     * 
     * @return array
     */
    private static function formatExplain($reportMbtiLoveEtt, $version = 1)
    {
    	$map = array(
    		'相遇' => array('meet', '相遇.png'),
    		'相知' => array('know', '相知.png'),
    		'相爱' => array('love', '相爱.png'),
    		'相惜' => array('cherish', '相惜.png'),
    		'相守' => array('together', '相守.png'),
    	);
    	$divList = array();
    	foreach ($map as $title => $row) {
    		$descPro = $row['0'] . 'Desc';
    		$desc = $reportMbtiLoveEtt->$descPro;
    		$title = self::titleBigRound($title);
    		$imgMain = self::imgMain($row['1']);
    		$divList[] = "{$title}{$imgMain}{$desc}";
    	}
    	$cardSplit = self::cardSplit();
    	$divList = implode($cardSplit, $divList);
    	
    	
		// 获取匹配部分
		$matching = empty($reportMbtiLoveEtt->matching) ? array() : explode(',', $reportMbtiLoveEtt->matching);
		$reportMbtiLoveTypeDao = \dao\ReportMbtiLoveType::singleton();
		$matchVersion = $version ? 2 : 1;
		$where = "`version` = {$matchVersion} and `name` in ('" . implode("','", $matching) . "')";
		$MBTILoveSv = \service\report\MBTILove::singleton();
		$reportMbtiLoveTypeEttList = $reportMbtiLoveTypeDao->readListByWhere($where);
		$matchDivList = array();
		$matchTitleList = array();
		if (is_iteratable($reportMbtiLoveTypeEttList)) foreach ($reportMbtiLoveTypeEttList as $key => $reportMbtiLoveTypeEtt) {

			$content = self::formatArticle($reportMbtiLoveTypeEtt); // 文章内容
			$divClass = $key == 0 ? 'xl-b15box on' : 'xl-b15box'; // 将第一个展开
			$titleClass = $key == 0 ? ' class="on"' : ''; // 将第一个展开
			$matchTitleList[] = "<span{$titleClass}>{$reportMbtiLoveTypeEtt->name}</span>";
			if (empty($reportMbtiLoveTypeEtt->matchingReason)) {
				$matchDivList[] =  <<<EOT
<div class="{$divClass}">
$content
</div>
EOT;
			} else {
				$matchDivList[] =  <<<EOT
<div class="{$divClass}">
<div class="pipei-yuanyin"><h5><i class="fa fa-venus-mars"></i>匹配原因</h5><p>{$reportMbtiLoveTypeEtt->matchingReason}</p></div>
$content
</div>
EOT;
			}
			
		}
		$matchTitleList = implode('', $matchTitleList);
		$matchDivList = implode('', $matchDivList);
		$fascinationArr = empty($reportMbtiLoveEtt->fascination) ? array() : explode('、', $reportMbtiLoveEtt->fascination);
		$complementaryArr = empty($reportMbtiLoveEtt->complementary) ? array() : explode('、', $reportMbtiLoveEtt->complementary);
		$temperamentArr = array_merge($fascinationArr, $complementaryArr);
		$reportMbtiLoveTemperamentDao = \dao\ReportMbtiLoveTemperament::singleton();
		$reportMbtiLoveTemperamentEttList = empty($temperamentArr) ? array() : $reportMbtiLoveTemperamentDao->readListByPrimary($temperamentArr);
		$reportMbtiLoveTemperamentEttList = $reportMbtiLoveTemperamentDao->refactorListByKey($reportMbtiLoveTemperamentEttList);
		$fascinationDiv = array();
		foreach ($fascinationArr as $row) {
			if (empty($reportMbtiLoveTemperamentEttList[$row])) {
				continue;
			}
			$reportMbtiLoveTemperamentEtt = $reportMbtiLoveTemperamentEttList[$row];
			$titleDot = self::titleDot($reportMbtiLoveTemperamentEtt->name);
			$desc = self::content($reportMbtiLoveTemperamentEtt->desc);
			$fascinationDiv[] = "{$titleDot}{$desc}";
		}
		$complementaryDiv = array();
		foreach ($complementaryArr as $row) {
			if (empty($reportMbtiLoveTemperamentEttList[$row])) {
				continue;
			}
			$reportMbtiLoveTemperamentEtt = $reportMbtiLoveTemperamentEttList[$row];
			$titleDot = self::titleDot($reportMbtiLoveTemperamentEtt->name);
			$desc = self::content($reportMbtiLoveTemperamentEtt->desc);
			$complementaryDiv[] = "{$titleDot}{$desc}";
		}
		$fascinationDiv = implode('', $fascinationDiv);
		$complementaryDiv = implode('', $complementaryDiv);
    
    	
    	$mainImg = self::imgMain("MBTI" . DS . ($version == 1 ? 'man' : 'woman') . DS . $reportMbtiLoveEtt->type . ($version == 1 ? '-01' : '-02') . '.png', 1 , 'report');
    	
  
    	
    	$imgMain = self::imgMain($mainImg);
    	
    	$fascinationTitle = self::titleCombination('让你着迷的特质', $reportMbtiLoveEtt->fascination);
    	$complementaryTitle = self::titleCombination('和你互补的特质', $reportMbtiLoveEtt->complementary);
  
    	$titleBigRound1 = self::titleBigRound('你的MBTI恋爱类型');
    	$titleBigRound2 = self::titleBigRound('你的天生情人');
    	$typeDiv = self::titleUnderline($reportMbtiLoveEtt->name . '（' . $reportMbtiLoveEtt->type . '）');
    	return <<<EOT
{$titleBigRound1}{$typeDiv}

{$cardSplit}
{$fascinationTitle}{$fascinationDiv}
{$cardSplit}
{$complementaryTitle}{$complementaryDiv}

{$divList}
{$titleBigRound2}

<p>以下人格类型者作为伴侣与你的契合度最高</p>
<div class="xl-b15" style="margin: .5rem 0;">{$matchTitleList}</div>
<div class="xl-b15m"><!--标签内容-1-->
{$matchDivList}
</div>
EOT;
    }
    
    /**
     * 获取扩展
     *
     * @return array
     */
    private static function formatExtend($reportMbtiLoveEtt, $version)
    {
    	$reportMbtiLoveTypeDao = \dao\ReportMbtiLoveType::singleton();
    	$matchVersion = $version == 1 ? 2 : 1;
    	$where = "`version` = {$matchVersion}";
    	$MBTILoveSv = \service\report\MBTILove::singleton();
    	$reportMbtiLoveTypeEttList = $reportMbtiLoveTypeDao->readListByWhere($where);
    	$reportMbtiLoveTypeEttList = $reportMbtiLoveTypeDao->refactorListByKey($reportMbtiLoveTypeEttList);
    	$divList = array();
    	//<a href="/article?id=33" target="_self" _href="/article?id=33" textvalue="劝告者">劝告者</a> 
    	if (is_iteratable($reportMbtiLoveTypeEttList)) foreach ($reportMbtiLoveTypeEttList as $key => $reportMbtiLoveTypeEtt) {
    		$divList[] =  $key == 1 ? 
<<<EOT
<a href="/article?id={$reportMbtiLoveTypeEtt->id}" class="lxlr">{$reportMbtiLoveTypeEtt->name}</a> 
EOT
:
<<<EOT
<a href="/article?id={$reportMbtiLoveTypeEtt->id}" target="_self" _href="/article?id=$reportMbtiLoveTypeEtt->id" textvalue="{$reportMbtiLoveTypeEtt->name}">{$reportMbtiLoveTypeEtt->name}</a>
EOT;
    	}
    	$divList = implode('', $divList);
    	return <<<EOT
<p>先天的契合并不代表全部，充分了解自己的伴侣，培养同理心并充分沟通，任何人格类型者之间都能成为现实中的完美情人！你可以点击以下的人格类型查看与TA的相处之道。</p>
<p>如果您想了解TA的个人类型，可以邀请TA来参与测评，然后点击以下人格类型查看与TA的相处之道。</p>
<style>
    .lxlr {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        text-align: center;
    }
    .lxlr a {
        width: 23.6%;
        background: #ecececc2;
        color: #ff5d7d;
        margin: 0.7%;
        display: inline-block;
        line-height: 1.1rem;
    }
</style>
<div class="lxlr">{$divList}</div>
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

    	// 获取通用配置
    	$conf = getStaticData($testPaperInfo['name'], 'common');
    	$scoreMap = array(); // 每个选项分数分布
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
	    	if (empty($question['scoreValue']) || !isset($answerList[$question['id']])) { // 无效的题目
	    		continue;
	    	}
    		$scoreValueArr = str_split($question['scoreValue']); // 类型
    		$elementType = 0; // 测试的类型
    		$userAnswer = $answerList[$question['id']]; // 用户作答的答案
    	
    		if ($userAnswer == 0) { // 选项 A
    			$elementType = $scoreValueArr['0'];
    		} else { // 选项B
    			$elementType = $scoreValueArr['1'];
    		}
    		$elementType = strtoupper($elementType);
    		$scoreMap[$elementType][$question['id']] = 1;
    	}
    	// IE 
    	$I = empty($scoreMap['I']) ? 0 : count($scoreMap['I']);
    	$E = empty($scoreMap['E']) ? 0 : count($scoreMap['E']);
    	
    	$N = empty($scoreMap['N']) ? 0 : count($scoreMap['N']);
    	$S = empty($scoreMap['S']) ? 0 : count($scoreMap['S']);
    	
    	$T = empty($scoreMap['T']) ? 0 : count($scoreMap['T']);
    	$F = empty($scoreMap['F']) ? 0 : count($scoreMap['F']);
    	
    	$P = empty($scoreMap['P']) ? 0 : count($scoreMap['P']);
    	$J = empty($scoreMap['J']) ? 0 : count($scoreMap['J']);
    	$mbtiTypeMap = array();
    	$mbtiTypeMap[] = $I >= $E ? 'I' : 'E';
    	$mbtiTypeMap[] = $N >= $S ? 'N' : 'S';
    	$mbtiTypeMap[] = $T >= $F ? 'T' : 'F';
    	$mbtiTypeMap[] = $P >= $J ? 'P' : 'J';
    	
    	// 最终的类型
    	$mbtiType = implode('', $mbtiTypeMap);
    	return array(
    		'mbtiType' => $mbtiType, // 最终的类型
    	);
    }
    
    
    /**
     * 获取报告
     *
     * @return array
     */
    public function getReport($testOrderInfo, $testPaperInfo)
    {
    	$answerResult = self::getAnswerResult($testOrderInfo, $testPaperInfo);
    	$mbtiType = $answerResult['mbtiType'];
    	
    	$reportMbtiLoveDao = \dao\ReportMbtiLove::singleton();
    	$reportMbtiLoveEtt = $reportMbtiLoveDao->readByPrimary($mbtiType);

    	if (empty($reportMbtiLoveEtt)) {
    	    throw new $this->exception('数据配置错误，请联系客服！');
    	}
    	$report = array(
    		'mbti_pl' => array(
	    		'setting' => array(
	    	    	'pl_type' => 3,
	    		),
	    		'pl_list' => array(
	    			'id' => $reportMbtiLoveEtt->type,
	    			'weidu_name' => $reportMbtiLoveEtt->name,
	    			'total_result_explain' => self::formatExplain($reportMbtiLoveEtt, $testOrderInfo['version']),
	    			'extend' => array(
	    				'score' => $mbtiType,
	    			)
	    		),
	    	),
    	    'extend_read' => self::componentExtendRead('与TA相处的艺术', self::formatExtend($reportMbtiLoveEtt, $testOrderInfo['version'])),
    	);
    	return $report;
    }

}