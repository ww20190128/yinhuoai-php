<?php
namespace service;

/**
 * 抓取报告数据
 * 
 * @author 
 */
class GrabReport extends ServiceBase
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
     * @return GrabReport
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GrabReport();
        }
        return self::$instance;
    }
    
    /**
     * 同步报告数据
     * 暗黑人格魅力测试
城府类型测评
城府类型测评
冲动特质测评


简单 

	[易怒程度专业鉴定] => 易怒程度专业鉴定
    [自我效能评估] => 自我效能评估
    [孤独症特质测试] => 孤独症特质测试
    [创伤后应激障碍测评] => 创伤后应激障碍测评
    [情绪管控评估] => 情绪管控评估
    [控制信念评测] => 控制信念评测
    [自信心水平测试] => 自信心水平测试
    [职场战斗力评估] => 职场战斗力评估
    
    
    双相情感障碍筛查
    职业倦怠度评估
    潜意识投射测试
    母亲依恋关系评测
    心理弹性专业测评
   易怒程度专业鉴定
  NLP感官系统测评
 内外向性格专业评定
情绪化程度测评
责任心专业评估
开放性指数评测
自我观平衡测试
自我觉察能力综合测评
    
     * @return
     */
    public function main($name = '')
    {
    	$commonDao = \dao\Common::singleton();

$where = "`goods_name` like '%ABO%'";
//$where = 1;
    	$sql = "SELECT * FROM `xz_report` WHERE {$where};";
    	$reportList = $commonDao->readDataBySql($sql);
    
    	$now = $this->frame->now;
    	$reportArr = array();
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperSv = \service\TestPaper::singleton();
    	$testPaperEttList = $testPaperDao->readListByWhere();
    	$testPaperEttList = array_column($testPaperEttList, null, 'name');
    	$reportMbtiDao = \dao\ReportMbti::singleton();
    
    	$onlineMap  = \constant\TestPaper::onlineMap();
    

    	$map = array();
    	$goods_name_list = array();
    	
    	$dimensionList = array();
    	
    	
    	
    	

    	$groupMap = array();
    	
    	$a = array();
    	if (is_iteratable($reportList)) foreach ($reportList as $key => $data) {
    		$goods_name = $data->goods_name;
    		if (in_array($goods_name, $onlineMap)) {
//     			continue;
    		}
    		$report = empty($data->report) ? array() : json_decode(base64_decode($data->report), true);

    		
    		
    		$pay = empty($data->pay) ? array() : json_decode(base64_decode($data->pay), true);
    		$order = empty($data->order) ? array() : json_decode(base64_decode($data->order), true);
    		$create_report = empty($pay['create_report']) ? array() : $pay['create_report'];
    		$goods_version_select = empty($order['goodsOrder']['goods_version_select']) ? 1 : $order['goodsOrder']['goods_version_select'];
    		$paperOrderResult = empty($report['paperOrderResult']) ? array() : $report['paperOrderResult'];
    		$paper_order_sn = $data->paper_order_sn;
    		if (empty($report['paperOrderResult']) || empty($report['paperOrderResult']['total_result_scoring'])) {
    			continue;
    		}
			$paperOrderResult = $report['paperOrderResult']['total_result_scoring'];
    		
    		
			echo $paperOrderResult['paper_tile'] . "\n";
			
			$weiduList = $report['paperOrderResult']['danweidu']['weiduList'];
			foreach ($weiduList as $row) {
				
				$a[$row['weidu_name']][$row['weidu_result']['name']][$row['last_percent']] = $row['weidu_result']['result_explain'];
			}

    		continue;
    		exit;
    		
    		
    		// 
    		$jifenPailieList = $paperOrderResult['jifen_pl']['jifenPailieList'];
    		foreach ($jifenPailieList as $row) {
    			$dimensionList[$row['weidu_name']] = array(
    				'desc' => $row['jianjie'],
    					'remark' => $row['total_result_remark'],
    				'explain' => $row['xiangxi'],	
    			);
    		}
    		continue;
    		
    		
    		
    		
    		
    		
		
    		$duoweidu = empty($paperOrderResult['duoweidu']) ? array() : $paperOrderResult['duoweidu']; // 多维度
    		
    		print_r($paperOrderResult);exit;

    		$weiduList = empty($paperOrderResult['danweidu']['weiduList']) ? array() : $paperOrderResult['danweidu']['weiduList']; // 多维度
    		
    		foreach ($weiduList as $row) {
    	
    			$dimensionList[$row['weidu_name']][$row['weidu_result']['name']] = array(
    				'suggest' => $row['weidu_result']['jianyi'],
    				'explain' => $row['weidu_result']['result_explain'],
    				'notice' => $row['weidu_result']['zhuyi'],
    			);
    		}
    		continue;
    		

   print_r($weiduList);exit;
    		 
    		$duoweiduParentList = $duoweidu['duoweiduParentList'];
    		
    		foreach ($duoweiduParentList as $groupRow) {
    			$groupMap[$groupRow['weidu_name']][$groupRow['weidu_result']['name']] = array(
    				'suggest' => $groupRow['weidu_result']['jianyi'],
    				'explain' => $groupRow['weidu_result']['result_explain'],
    				'notice' => $groupRow['weidu_result']['zhuyi'],
    			);
    			
    			$child_duoweidu = $groupRow['child_duoweidu'];
    			foreach ($child_duoweidu as $child) {
    				$dimensionList[$child['weidu_name']][$child['weidu_result']['name']] = array(
    					'suggest' => $child['weidu_result']['jianyi'],
    					'explain' => $child['weidu_result']['result_explain'],
    				);
    			}
    		
    		}
   

    		$jifenPailieList = $paperOrderResult['jifen_pl']['jifenPailieList'];
    		if (is_iteratable($jifenPailieList)) foreach ($jifenPailieList as $row) {

    			$dimensionList[$row['weidu_name']]['icon'] = $row['weidu_icon'];
    			$dimensionList[$row['weidu_name']]['iconColor'] = $row['weidu_icon_color'];
    			if (!empty($row['jianjie'])) {
    				$dimensionList[$row['weidu_name']]['desc'] = $row['jianjie'];
    			}
    			if (!empty($row['total_result_remark'])) {
    				$dimensionList[$row['weidu_name']]['totalRemark'] = $row['total_result_remark'];
    			}
    			if (!empty($row['xiangxi'])) {
    				$dimensionList[$row['weidu_name']]['explain'] = $row['xiangxi'];
    			}
    		}
    		var_export($dimensionList);exit;

    		if (empty($paperOrderResult['total_result_scoring'])) {
    			continue;
    		}
    		if (isset($paperOrderResult['total_result_scoring']['jifen_guize']) && $paperOrderResult['total_result_scoring']['jifen_guize'] != 2) {
    			//continue;
    		}
    		if (!isset($paperOrderResult['extend_read'])) {
    			//continue;
    		}
    		foreach ($paperOrderResult as $k => $val) {
    			if (empty($val)) {
    				unset($paperOrderResult[$k]);
    			}
    		}
    		unset($paperOrderResult['create_time']);
    		
    		
    		
    		$duoweidu_mesh = empty($paperOrderResult['duoweidu_mesh']) ? array() : $paperOrderResult['duoweidu_mesh'];
    		$duoweidu = empty($paperOrderResult['duoweidu']) ? array() : $paperOrderResult['duoweidu']; // 多维度
    		$danweidu = empty($paperOrderResult['danweidu']) ? array() : $paperOrderResult['danweidu']; // 单维度
    		$jifen_pl = empty($paperOrderResult['jifen_pl']) ? array() : $paperOrderResult['jifen_pl'];
    		$fanone = empty($paperOrderResult['fanone']) ? array() : $paperOrderResult['fanone'];
    
    		$weiduList = empty($danweidu['weiduList']) ? array() : $danweidu['weiduList'];
    		if (is_iteratable($weiduList))foreach ($weiduList as $row) {
    			$dimensionList[$row['weidu_name']][$row['weidu_result']['name']]['threshold'] = 0;
    			$dimensionList[$row['weidu_name']][$row['weidu_result']['name']]['explain'] = $row['weidu_result']['result_explain'];
    			if (!empty($row['weidu_result']['jianyi'])) {
    				$dimensionList[$row['weidu_name']][$row['weidu_result']['name']]['suggest'] = $row['weidu_result']['jianyi'];
    			}
    			if (!empty($row['weidu_result']['zhuyi'])) {
    				$dimensionList[$row['weidu_name']][$row['weidu_result']['name']]['notice'] = $row['weidu_result']['zhuyi'];
    			}
    		}
    		if (!empty($duoweidu_mesh) || !empty($danweidu) || !empty($duoweidu) || !empty($jifen_pl) || !empty($fanone)) {
    			continue;
    		}
  
$goods_name_list[$goods_name] = $goods_name;continue;	
    		
    		if (empty($duoweidu_mesh) && empty($danweidu) && empty($duoweidu) && empty($jifen_pl) && empty($fanone)) {
    			$map[$goods_name][$paper_order_sn] = $data;
    			
    			$goods_name_list[$goods_name] = $goods_name;
    		}
    		if (!empty($fanone)) {
    			//$goods_name_list[$goods_name] = $goods_name;
    		}
    	}
    	
    	print_r($a);exit;
var_export($dimensionList);exit;
    	foreach ($map as $goods_name => $list) {
    		$commonConf = $this->setCommon($goods_name, $list);
    		echo $goods_name . "\n";
    	print_r($commonConf);exit;
    	}
    	exit;
    }
    
    /**
     * 写入配置
     *
     * @return array
     */
    public function setCommon($goods_name, $reportList)
    {
    	// common 配置文件
    	$commonConf = getStaticData($goods_name, 'common');
    	$commonConf = empty($commonConf) ? array() : $commonConf;
//$commonConf = array();

    	foreach ($reportList as $row) {
    		$report = empty($row->report) ? array() : json_decode(base64_decode($row->report), true);
    		$paperOrderResult = empty($report['paperOrderResult']) ? array() : $report['paperOrderResult'];
    		if (empty($paperOrderResult)) {
    			continue;
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
    		// 扩展阅读
    		if (isset($paperOrderResult['extend_read']['setting'])) {
    			$extend_read = $paperOrderResult['extend_read']['setting']; // 扩展阅读
    			unset($extend_read['title_icon']);
    			if (!empty($extend_read)) {
    				$commonConf['extendRead'] = $extend_read;
    			}
    		}
    	}

    	$commonConf = setStaticData($goods_name, 'common', $commonConf);
    	

    	var_export($commonConf);
    	return $commonConf;
    }
}