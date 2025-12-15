<?php
namespace service;

/**
 * 提示词工程
 * 
 * @author 
 */
class Prompt extends ServiceBase
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
     * @return Prompt
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Prompt();
        }
        return self::$instance;
    }

    /**
     * 题目
     *
     * @return array
     */
    private function printQuestion($testPaperInfo)
    {
    	$testPaperSv = \service\TestPaper::singleton();
    	$selections = array();
    	if (!empty($testPaperInfo['versionConfig'])) { // 多个版本
    		$questionInfo1 = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], 1);
    		$questionInfo2 = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], 2);
    		$questionList1 = $questionInfo1['questionList'];
    		$questionList2 = $questionInfo2['questionList'];
    		$models1 = array();
    		foreach ($questionList1 as $row) {
    			$models1[] = array(
    				'题目ID' => $row['id'],
    				'题序' => $row['index'],
    				'题干' => $row['matter'],
    				'选项' => $row['selections'],
    			);
    		}
    		$models1 = json_encode($models1, JSON_UNESCAPED_UNICODE);
    		$models2 = array();
    		foreach ($questionList1 as $row) {
    			$models2[] = array(
    				'题目ID' => $row['id'],
    				'题序' => $row['index'],
    				'题干' => $row['matter'],
    				'选项' => $row['selections'],
    			);
    		}
    		$models2 = json_encode($models2, JSON_UNESCAPED_UNICODE);
    		$printStr = <<<EOT
该测评针对男生，女生不同性别设计了2套题: 
针对男生测试的版本：{$models1}
针对女生测试的版本：{$models1}
EOT;
    	} else {
    		$questionInfo = $testPaperSv->getTestOrderQuestionInfo($testPaperInfo['name'], 1);
    		$questionList = $questionInfo['questionList'];
    		$models = array();
    		foreach ($questionList as $row) {
    			$models[] = array(
    				'题目ID' => $row['id'],
    				'题序' => $row['index'],
    				'题干' => $row['matter'],
    				'选项' => $row['selections'],
    			);
    		}
    		$models = json_encode($models, JSON_UNESCAPED_UNICODE);
    		$printStr = <<<EOT
题目：{$models}
EOT;
    	}
    	return $printStr;
    }
    
    /**
     * 写入配置
     *
     * @return array
     */
    public function setCommon($testPaperEtt, $reportList)
    {
    	// common 配置文件
    	$commonConf = getStaticData($testPaperEtt->name, 'common');
    	$commonConf = empty($commonConf) ? array() : $commonConf;
    	foreach ($reportList as $row) {
    		$report = empty($row->report) ? array() : json_decode(base64_decode($row->report), true);
    		$paperOrderResult = empty($report['paperOrderResult']) ? array() : $report['paperOrderResult'];
    		if (empty($paperOrderResult)) {
    			continue;
    		}
    		
    print_r($paperOrderResult['total_result_scoring']);
    continue;
    		$extend_read = $paperOrderResult['extend_read']['setting']; // 扩展阅读
    		unset($extend_read['title_icon']);
    		if (!empty($extend_read)) {
    			$commonConf['extendRead'] = $extend_read;
    		}
    		$total_result_scoring = $paperOrderResult['total_result_scoring'];
    		if (empty($total_result_scoring)) {
    			continue;
    		}
    		if (isset($total_result_scoring['paper_tile'])) {
    			$commonConf['title'] = $total_result_scoring['paper_tile']; // 报告标题
    		}
    		if (isset($total_result_scoring['setting']['title'])) {
    			$commonConf['totalTitle'] = $total_result_scoring['setting']['title']; // 总体结果-标题
    		}
    		if (isset($total_result_scoring['setting']['title_icon_image'])) {
    			$commonConf['totalIcon'] = $total_result_scoring['setting']['title_icon_image']; // 总体结果-图标
    		}
    		if (isset($total_result_scoring['setting']['title_icon_color'])) {
    			$commonConf['totalIconColor'] = $total_result_scoring['setting']['title_icon_color']; // 总体结果-图标-颜色
    		}
    		$levelList = empty($commonConf['levelList']) ? array() : $commonConf['levelList']; // 等级配置
    		if ($total_result_scoring['jifen_guize'] == 1) { // 计分规则
    			// 等级
    			$level = $total_result_scoring['content']['name']; // 等级名称
    			$explain = $total_result_scoring['content']['result_explain']; // 解说
    			if (empty($explain)) {
    				continue;
    			}
    			// 设置阀值
    			if (!isset($levelList[$level]['threshold'])) {
    				$levelList[$level]['threshold'] = 0;
    			}
    			$levelList[$level]['explain'] = $explain;
    			// 注意
    			$notice = $total_result_scoring['content']['zhuyi'];
    			if (!empty($notice)) {
    				$levelList[$level]['notice'] = $notice;
    			}
    			// 建议
    			$suggest = $total_result_scoring['content']['jianyi'];
    			if (!empty($suggest)) {
    				$levelList[$level]['suggest'] = $suggest;
    			}
    		}
    		if (!empty($levelList)) {
    			$commonConf['levelList'] = $levelList;
    		}
    	}
    	//$commonConf = setStaticData($testPaperEtt->name, 'common', $commonConf);
    	print_r($commonConf);exit;
    }
    
    /**
     * 生成提示词
     *
     * @return array
     */
    public function main($reportList)
    {
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperSv = \service\TestPaper::singleton();
    	$testPaperEttList = $testPaperDao->readListByWhere();
    	$testPaperEttList = array_column($testPaperEttList, null, 'name');
    	if (empty($reportList)) {
    		throw new $this->exception('没有找到报告数据');
    	}
    	$testPaperEtt = null;
    	if (is_iteratable($reportList)) foreach ($reportList as $data) {
    		
    	print_r($data);exit;
    		$goods_name = $data->goods_name;
    		if (empty($testPaperEttList[$goods_name])) {
    			throw new $this->exception('没有找到测评');
    		}
    		$testPaperEtt = $testPaperEttList[$goods_name];
    	}
    	if (empty($testPaperEtt)) {
    		throw new $this->exception('没有找到报告数据');
    	}
    	$testPaperInfo = $testPaperSv->testPaperInfo($testPaperEtt->id);
    	
    	// 第一步： 拿到修改后的题目及评分规则
    	$testPaperSv = \service\TestPaper::singleton();
    	$testPaperInfo = $testPaperSv->testPaperInfo($testPaperEtt->id);
    	$questionStr = $this->printQuestion($testPaperInfo);

    	
    	//=====================
    	$this->setCommon($testPaperEtt, $reportList);exit;
    	///==========================
    	
    	
    	
    	
    	
    	
    	
    	
    	
    	
    	
    	
    	
    	
      	echo <<<EOT
你是一个资深的心理测评师，需要设计一套关于《{$testPaperInfo['name']}》的测评试卷，以帮学生{$testPaperInfo['subhead']}。
要求：
1. 你通过公开已有的权威知识学习《{$testPaperInfo['name']}》有关的理论知识，了解该测评的原理，测评结果的分类，深度学习关于该测试的理论，以设计出高质量的测评试卷。
2. 不要自己随意编造测评类型，要参考已有的理论参考。
3. 通过参考下面样例提供的"参考数据"设计出本测评试卷的题目， 注意：（a. 不要修改题目ID，题序的内容。b. 只需要修改题干的表述，并且不改变题目原本测试的意图，例如题目1为了测试类型A，修改后仍然测试类型A）
	参考数据：
	{$questionStr}
4. 生成的题目要有趣味性，不要以固定格式输出，例如不要都是以"你希望xxx"， "我觉得xxx", "你总是...", "我总是.. "等固定格式，可以变换表述方式，让题目看起来不是那么机械。
5. 注意： 上面列举的"参考数据"的题目没有指出每道题目是为了测试那种类型，你需要根据理解题干并结合选项，结合本测评的理论知识，准确的判断出这道题的测试意图，即为了测试某一种类型或者多种类型，或者选择不同的选项给予不同的分值。
6. 将生成的题目以sql语句的方式输出，以分别直接录入到数据库，表名为: testQuestion， 输出的字段(`version`, `testPaperId`, `source`, `index`, `matter`, `selections`, `scoreValue`)，字段说明
	index：题目次序，整型，从1开始;
	matter：题干;
	selections：选项，json字符串例如: '[{"name":"xxx"}...]'
	scoreValue：对应的测试类型 或者分值, 字符串类型;
	testPaperId: {$testPaperInfo['id']}, 固定，整型;
	source: gpt，固定，字符串类型;
	version：题目版本，整型  1 男生 2 女生， 如果没有多个版本 默认为 1
	例如: REPLACE INTO `testQuestion` (`version`, `testPaperId`, `source`, `index`, `matter`, `selections`, `scoreValue`) VALUES...;
7. 结合题目及测评的理论，设计出评分量表。尽量以PHP数组的方式输出，例如题目（题序）1, 4, 6 是测试类型A 可以表示为 A => array(1, 4, 6)，注意测试类型要跟本测评测试的类型对应上。

EOT;

		// 创建目录
		$testPaperName = $testPaperEtt->name;
		$dir = CODE_PATH . 'static' . DIRECTORY_SEPARATOR . $testPaperName;
		if (!is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}
		$file = $dir . DIRECTORY_SEPARATOR . 'reportData.txt';
		$content = "样本数据如下：\n\n";
		file_put_contents($file, $content);

		$index = 1;
		$sampleArr = array();
		
		$a = array();
		if (is_iteratable($reportList)) foreach ($reportList as $data) {
			$goods_name = $data->goods_name;
			if (empty($testPaperEttList[$goods_name])) {
				throw new $this->exception('没有找到测评');
			}
			$report = empty($data->report) ? array() : json_decode(base64_decode($data->report), true);
			$pay = empty($data->pay) ? array() : json_decode(base64_decode($data->pay), true);
			$order = empty($data->order) ? array() : json_decode(base64_decode($data->order), true);
			$create_report = empty($pay['create_report']) ? array() : $pay['create_report'];
			$goods_version_select = empty($order['goodsOrder']['goods_version_select']) ? 1 : $order['goodsOrder']['goods_version_select'];
			$paperOrderResult = empty($report['paperOrderResult']) ? array() : $report['paperOrderResult'];
			$paper_order_sn = $data = $data->paper_order_sn;

print_r($report['paperOrderResult']);continue;
			
			$list = $report['paperOrderResult']['danweidu']['weiduList'];
			foreach ($list as $row) {
				
			}
			
			print_r($report['paperOrderResult']['danweidu']['weiduList']);exit;
			
			
			
			
			
			
			
			
			
			$ruiwen_ord = empty($report['paperOrderResult']['ruiwen_ord']) ? array() : $report['paperOrderResult']['ruiwen_ord'];
			if (empty($ruiwen_ord)) {
				continue;
			}
			print_r($ruiwen_ord);
			continue;
$fanthreeList = $paperOrderResult['fanthree']['fanthreeList'];	
// echo "样本数据：\n";
// echo json_encode($a, JSON_UNESCAPED_UNICODE) . "\n";
// continue;

foreach ($fanthreeList as $fanthree) {
print_r($fanthree);exit;
	$a[$fanthree['weidu_name']][$fanthree['weidu_result']['name']] = $fanthree['weidu_result']['result_explain'];
	
	//$a[$fanthree['weidu_name']][$fanthree['weidu_result']['name']] = $fanthree;
}
//echo $a['weidu_name'] . "\t" . $a['weidu_result']['name'] . "\t" . $a['weidu_result']['result_explain'] . "\n";
continue;
$report = $paperOrderResult['jifen_pl']['jifenPailieList']['jobList'];
			$reportStr = "样本{$index}：" . json_encode($report, JSON_UNESCAPED_UNICODE) . "\n";
			// 以追加的方式将数组写入文件
			file_put_contents($file, $reportStr, FILE_APPEND);
			$index++;
			$sampleArr[] = $reportStr;
		}
		print_r($a);exit;
		
		// 写入输出模版
		$model = getStaticData($testPaperName, 'model');
		
		// 以追加的方式将数组写入文件
		$modelStr = "\n\n样本输出格式模版：\n根据样本整理的数据：\n" . var_export($model, true) . "\n";
		file_put_contents($file, $modelStr, FILE_APPEND);
		$sampleArr = implode("\n", $sampleArr);
		echo <<<EOT
有如下样本数据:
----------------------
{$sampleArr}
----------------------
通过学习"样本数据"规则，帮我整理出每种类型的数据，并按照"样本输出格式模版"输出。
注意： 
1. 样本数据来自于同一个接口协议，所以格式，样式都是固定的。
2. 要求：请将每个类型以php数组的方式输出，方便直接复制到php文件中。
3. 要求：大多数情况，"样本输出格式模版"是根据第一个样本中的数据进行的整理，由于样本数量有限，"样本输出格式模版"中的数据可能存在错误。例如字段A的内容可能是一个p标签或者多个p标签，"样本输出格式模版"可能将其整理成了字符串类型，需要你根据实际情况进行转换，如果所有样本数据中该字段都只1个，以字符串类型保存，如果存在多个，那么该字段应设置为数组类型。
4. 要求整理出"样本数据"中所覆盖到的所有类型。
5. 如果数据中找不到"样本输出格式模版"，请试图帮我组织一份PHP模版，尽量做到所有类型通用
EOT;
		exit;
    }
    
    
   
}