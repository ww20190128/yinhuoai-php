<?php
namespace service\report;

/**
 * 九型人格
 * 
 * @author 
 */
class Enneagram extends \service\report\ReportBase
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
     * @return Enneagram
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Enneagram();
        }
        return self::$instance;
    }
   
    // 图片
    private static $imgMap = array(
    	'类型' => '九型人格-类型.png',
    	'特点' => '九型人格-特点.png',
    	'欲望与恐惧' => '九型人格-欲望与恐惧.png',
    	'原罪与美德' => '九型人格-原罪与美德.png',
    	'性格成因' => '九型人格-性格成因.png',
    	'感情方面' => '九型人格-感情方面.png',
    	'工作方面' => '霍兰德-职业列表.png',
    	'分享' => '九型人格-类型.png',
    );
    
    /**
     * 基本信息
     * 
     * @return string
     */
    private static function formatMain($data)
    {
    	$title1 = self::titleBigRound($data['名称']);
    	$title2 = self::titleUnderline($data['标签']);
    	$imgMain = self::imgMain(empty($data['主图']) ? self::$imgMap['类型'] : $data['主图']);
    	$content = self::contentBg($data['描述'], 'purple');
    	return
    	<<<EOT
{$title1}
{$title2}
{$imgMain}
<p><span class="border-left" style="color: #f6727e; font-size: 16px;">关键词：{$data['关键词']}</span></p>
{$content}
EOT;
    }
    
    /**
     * 详细解读
     *
     * @return string
     */
    private static function formatDetail($data)
    {
    	// 一. 基本信息
    	$combineArr = array();
    	$combineArr[] = self::contentBg($data['other']['内容'], 'purple');
    	
		$combineArr[] = self::combine1('代表人物', self::imgMain($data['other']['代表人物图片']));
		$combineArr[] = self::combine1('欲望特质', self::toHtmlTag($data['other']['欲望特质'], 'p'));
		$combineArr[] = self::combine1('基本困思', self::toHtmlTag($data['other']['基本困思'], 'p'));
		$combineArr[] = self::combine1('主要特征', self::toHtmlTag($data['other']['主要特征'], 'p'));
		$combineArr[] = self::combine1('生活风格', self::toHtmlTag($data['other']['生活风格'], 'p'));
		$combineArr[] = self::combine1('人际关系', self::toHtmlTag($data['other']['人际关系'], 'p'));
		$combineArr[] = self::combine1('处于顺境', reset($data['other']['顺境']), array_key_first($data['other']['顺境']));
		$combineArr[] = self::combine1('处于逆境', reset($data['other']['逆境']), array_key_first($data['other']['逆境']));
		$combineArr[] = self::combine1('代表颜色', self::toHtmlTag($data['other']['代表颜色'], 'p'));
		$combineArr[] = self::combine1('生命课题', self::toHtmlTag($data['other']['生命课题'], 'p'));
		$baseDiv = implode('', $combineArr);
		
		// 一. 欲望与恐惧
		$title1 = self::titleBigRound('欲望与恐惧');
		$imgMain = self::imgMain(self::$imgMap['欲望与恐惧']);
		
		$desireTitle = self::titleCombination('欲望', $data['欲望与恐惧']['欲望']['标题']); // 3级标题
		$desireContent = self::contentBg($data['欲望与恐惧']['欲望']['内容']);
		$fearTitle = self::titleCombination('恐惧', $data['欲望与恐惧']['恐惧']['标题']);
		$fearContent = self::contentBg($data['欲望与恐惧']['恐惧']['内容']);
		$desireAndFearDiv ="{$title1}{$imgMain}{$desireTitle}{$desireContent}{$fearTitle}{$fearContent}";

		// 二. 原罪与美德
		$title1 = self::titleBigRound('原罪与美德');
		$imgMain = self::imgMain(self::$imgMap['原罪与美德']);
		$desc = self::contentBg($data['原罪与美德']['描述'], 'purple');
		$sinTitle = self::titleCombination('原罪', $data['原罪与美德']['原罪']['标签']);

		$sinTitleDesc = self::contentBg($data['原罪与美德']['原罪']['标题']);
		$sinContent = self::contentBg($data['原罪与美德']['原罪']['内容']);
		$virtueTitle = self::titleCombination('美德', $data['原罪与美德']['美德']['标签']);
		$virtueTitleDesc = self::contentBg($data['原罪与美德']['美德']['标题']);
		$virtueContent = self::contentBg($data['原罪与美德']['美德']['内容']);
		$noticeDiv = self::tabBox('美德', '美德的意思来自拉丁语的中&ldquo;Virs&rdquo;，意为力量，泛指各种滋养生命的力量本质。');
	
		$sinAndVirtueDiv = "{$title1}{$imgMain}{$desc}{$sinTitle}{$sinTitleDesc}{$sinContent}{$virtueTitle}{$virtueTitleDesc}{$virtueContent}{$noticeDiv}";
		
		// 三. 性格成因
		$title1 = self::titleBigRound('性格成因');
		$imgMain = self::imgMain(self::$imgMap['性格成因']);
		$characterList = array();
		$index = 1;

		if (is_iteratable($data['性格成因']['内容'])) foreach ($data['性格成因']['内容'] as $title => $list) {
			$characterList[] = self::titleIndexNum($index, $title);
			$characterList[] = self::contentBg($list, 'purple');
			$index++;
		}
		$characterList = implode('', $characterList);
		$characterDiv = "{$title1}{$imgMain}{$characterList}";
		
		// 四. 工作方面
		$bigTitle = self::titleBigRound('工作方面');
		$imgMain = self::imgMain(self::$imgMap['工作方面']);
		$desc = self::contentBg($data['工作方面']['描述']);
		$workDiv = "{$title1}{$imgMain}{$desc}";
		
		$suggestList = array();
		if (is_iteratable($data['工作方面']['提升指导']['建议'])) foreach ($data['工作方面']['提升指导']['建议'] as $row) {
			$suggestList[] = '<p><i class="fa vaaeq fa-angle-double-right"></i>' . $row . '</p>';
		}
		$suggestList = implode('', $suggestList);
		
		

		$tag1 = self::tagRounded('适合的环境', 'red', -10);
		$tag2 = self::tagRounded('不适合的环境', 'gray', -10);
		$tag3 = self::tagRounded('适合的工作', 'blue', -10);
		$tag4 = self::tagRounded('理想的合作伙伴', 'purple', -10);

		$tagContent1 = self::content($data['工作方面']['适合的环境']);
		$tagContent2 = self::content($data['工作方面']['不适合的环境']);
		$tagContent3 = self::content($data['工作方面']['适合的工作']);
		$tagContent4 = self::content($data['工作方面']['理想的合作伙伴']);
		
		// 提升指导
		$suggestTag = self::tagRounded('提升指导', 'red', -10);
		$suggestDesc = self::contentBg(empty($data['工作方面']['提升指导']['描述']) ? '建议第九型的你这样做：' : $data['工作方面']['提升指导']['描述'], 'purple');
		
		$suggestContent = self::listBox($suggestList);

		$workDiv = "{$bigTitle}{$imgMain}{$desc}{$tag1}{$tagContent1}{$tag2}{$tagContent2}{$tag3}{$tagContent3}{$tag4}{$tagContent4}{$suggestTag}{$suggestDesc}{$suggestContent}";
	
		// 五. 感情方面	
		$title1 = self::titleBigRound('感情方面');
		$imgMain = self::imgMain(self::$imgMap['感情方面']);
		$desc = self::contentBg($data['感情方面']['描述']);
		$emotionDiv = "{$title1}{$imgMain}{$desc}";

		// 六. 给你的建议
		$title1 = self::titleBigRound('给你的建议'); // 二级标题
		$title2 = self::titleUnderline($data['给你的建议']['标题']); // 三级标题
		
		
		$index = 0;
		$indexMap = array('一', '二', '三', '四');
		$suggestList = array();
		if (is_iteratable($data['给你的建议']['内容'])) foreach ($data['给你的建议']['内容'] as $title => $list) {
			$arr = array();
			$subIndex = 1;
			foreach ($list as $key => $value) {
				if (is_numeric($key)) { // 内容
					$arr[] = '<p>' . $value . '</p>';
				} else { // 有标题
					$arr[] = '<p><span style="font-size: 16px; color: #3f3f3f;  font-weight: bold;">' . $subIndex . '. ' . $key . '</span></p>';
					$arr[] = '<p style="padding-left: 15px;">' . $value . '</p>';
					$subIndex++;
				}
			}
			$arr = implode('', $arr);
	
			$suggestList[] = self::combineBox($indexMap[$index] . '、' . $title, $arr);
			$index++;
		}
		$suggestList = implode('', $suggestList);
		$suggestDiv = "{$title1}{$title2}{$suggestList}";
		
		$cardSplit = self::cardSplit();
		// 组织结果
		$resultList = array(
			$baseDiv, // 基本信息
			$desireAndFearDiv, // 欲望与恐惧
			$sinAndVirtueDiv,
			$characterDiv,
			$workDiv,
			$emotionDiv,
			$suggestDiv
		);
		$result = implode($cardSplit, $resultList);
    	return $result;
    }
    
    /**
     * 获取作答答案
     *
     * @return array
     */
    private static function getAnswerResult($testOrderInfo, $testPaperInfo, &$percentList = array())
    {
    	$typeMap = array(
    		'A' => '一号完美型',
    		'B' => '二号助人型',
    		'C' => '三号成就型',
    		'D' => '四号自我型',
    		'E' => '五号理智型',
    		'F' => '六号忠诚型',
    		'G' => '七号活跃型',
    		'H' => '八号领袖型',
    		'I' => '九号和平型',
    	);
    	// 题目对应的类型
    	// https://wenku.baidu.com/view/ac65fc06dc36a32d7375a417866fb84ae55cc350?aggId=3ee314afbad528ea81c758f5f61fb7360b4c2b85&fr=catalogMain_text_ernie_recall_backup_new:wk_recommend_main2
    	// 144题对应的量表
    	$map144 = 'EB, GA, CE, HI, FE, BA, GD, EG, CI,
				EA, HG, BD, FC, EI, HD, BG, AF, CE,
    			BI, FD, GC, HA, BF, IG, ED, AI, BC,
    			HD, GF, ID, AC, HB, EG, AD, IG, CH,
    			BD, AG, CD, HI, FE, BA, GD, HF, IC,
    			DD, HI, BD, FC, EH, CH, BG, FA, CE,
    			BI, FD, GC, HA, EB, GI, DE, IA, BC,
    			EH, FG, DI, CA, GB, EG, AD, IF, CH,
    			DB, FA, DB, IH, EF, BA, FD, HF, CI,
    			EA, HG, BD, FC, EI, HI, AG, FA, CE,
    			BI, FD, GC, HA, FB, GI, ED, IA, CB,
    			EH, FG, ID, DA, HB, GE, DA, FI, CH,
    			BE, GA, CD, HI, FE, AB, GD, FH, CI,
    			EA, HG, BD, FC, DI, HD, BG, AF, EC,
    			IB, FD, GC, HA, FB, GI, ED, AI, CB,
    			HE, GF, DI, CA, BH, GE, AD, FI, HC';
    	// 36题对应的量表
    	// https://wenku.baidu.com/view/1963946acebff121dd36a32d7375a417876fc142?aggId=3ee314afbad528ea81c758f5f61fb7360b4c2b85&fr=catalogMain_text_ernie_recall_v1:wk_recommend_main2
    	$map36 = 'BE, DG, IA, CE, FH, BA, CG, HD, IF, 
    			CA, HG, BD, EI, FC, HA, DE, IB, FG,
    			AE, BH, FD, GA, BC, DI, EF, HC, IG,
    			DA, BF, CI, EH, FA, BG, CD, HI, EG';
    	
    	// 作答记录
    	$answerList = empty($testOrderInfo['answerList']) ? array() : $testOrderInfo['answerList'];
    	$testPaperSv = \service\TestPaper::singleton();
    	$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], $testOrderInfo['version'], $answerList);
    	$questionList = empty($questionInfo['questionList']) ? array() : $questionInfo['questionList'];
    
    	if (count($questionList) <= 36) {
    		$indexMap = $map36;
    	} else {
    		$indexMap = $map144;
    	}
    	$indexMap = explode(',', $string = preg_replace('/\s+/', '', $indexMap));
    	// 统计各项得分
    	$scoreMap = array();
    	$totalScoreMap = array(); // 每种类型的总分
    	$answerScoreMap = array();
    	if (is_iteratable($questionList)) foreach ($questionList as $question) {
    		// 该题测试的类型
    		$answerArr = empty($indexMap[$question['index']]) ? array() : str_split($indexMap[$question['index']]);
    		$userAnswer = $answerList[$question['id']]; // 用户的作答的答案
    		foreach ($answerArr as $key => $val) {
    			if (empty($totalScoreMap[$val])) {
    				$totalScoreMap[$val] = 1;
    			} else {
    				$totalScoreMap[$val] += 1;
    			}
    		}
    		if (!isset($answerArr[$userAnswer])) {
    			continue;
    		}
    		if (empty($scoreMap[$answerArr[$userAnswer]])) {
    			$scoreMap[$answerArr[$userAnswer]] = 1;
    		} else {
    			$scoreMap[$answerArr[$userAnswer]] += 1;
    		}
    	}
    	$percentList = array();
    	foreach ($totalScoreMap as $type => $totalScore) {
    		$percentList[$type] = array(
    			'score' => empty($scoreMap[$type]) ? 0 : $scoreMap[$type],
    			'totalScore' => $totalScore,
    			'typeName' => empty($typeMap[$type]) ? '' : $typeMap[$type],
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
    	$pl_list = array(); 
    	$id = 1;
    	if (is_iteratable($percentList)) foreach ($percentList as $type => $scoreRow) {
    		$data = getStaticData($testPaperInfo['name'], $scoreRow['typeName']);
    		if (empty($data)) {
    			continue;
    		}
    		$pl_list[] = array(
    			'id' => $id++,
    			'weidu_name' => $data['名称'],
    			'total_score' => $scoreRow['score'], // 得分
    			'jianjie' => $data['描述'],
    			'last_percent' => $scoreRow['percent'],
    			'pl_type' => 2,
    			'total_result_explain' => self::formatMain($data), // 基本信息
    			'juti_result_explain' => self::formatDetail($data),
    			'need_jisuan_value' => 'A',
    			'extend' => array(
    				'shuoming' => $data['关键词'],
    			),
    			'share_image' => self::$imgMap['分享'],
    			'share_btn_text' => '分享我的测试结果',
    		);
    	}
    	
		$title3Desc = <<<EOT
<div class ='img-main'><img src="九型人格-解析.png" /></div>
<p><span style="color: #f6727e;">以下是与你匹配度最高的三种人格类型的详细解析：</span></p>
EOT;
		$commonSv = \service\Common::singleton();
    	$setting = array(
    		'pl_type' => 2,
    		'title1' => '您的九型人格是',
    		'title_icon_tag1' => 'fa-universal-access',
    		'title2' => '你的九型人格匹配表',
    		'title_icon_tag2' => 'fa-server',	
    		'title3' => '你的人格特点解析',
    		'title_icon_tag3' => 'fa-thermometer-4',
    		'jianjie' => '<p>每个人的性格都是多面性的，以下是你与九型人格中每个类型的匹配度：</p>',
    		'title3_jieshao' => $commonSv::replaceImgSrc($title3Desc, 'report'),
    	);
    	$reportModel = array(
    		'mbti_pl' => array(
    			'pl_list' => $pl_list,
    			'setting' => $setting,
    		)
    	);
    	return $reportModel;
    }

}