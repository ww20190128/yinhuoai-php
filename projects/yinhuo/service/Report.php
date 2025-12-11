<?php
namespace service;

/**
 * 报告 逻辑类
 * 
 * @author 
 */
class Report extends ServiceBase
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
     * @return Report
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Report();
        }
        return self::$instance;
    }
    
    /**
     * 获取mbti的文章
     *
     * @return array
     */
    public function articleInfo($articleId)
    {
    	$reportMbtiLoveTypeDao = \dao\ReportMbtiLoveType::singleton();
    	$reportMbtiLoveTypeEtt = $reportMbtiLoveTypeDao->readByPrimary($articleId);
    	if (empty($reportMbtiLoveTypeEtt)) {
    		throw new $this->exception("系统数据配置错误，请联系客服！");
    	}
    	$content = \service\report\MBTILove::formatArticle($reportMbtiLoveTypeEtt);
    	return array(
    		'title' => '与TA相处的艺术',
    		'content' => $content,
    	);
    }
    
    /**
     * 生成报告
     *
     * @return array
     */
    public function create()
    {
    	
    	$testOrderId = 14;
    	// 获取订单信息
    	$testPaperSv = \service\TestPaper::singleton();
    	$testOrderInfo = $testPaperSv->testOrderInfo($testOrderId);
    	// 获取测评信息
    	$testPaperInfo = empty($testOrderInfo['testPaperInfo']) ? array() : $testOrderInfo['testPaperInfo'];
    	// 支付页面
    	$payInfo = empty($testOrderInfo['payInfo']) ? array() : $testOrderInfo['payInfo'];
    	
    	if (empty($testPaperInfo) || empty($payInfo)) {
    		throw new $this->exception('订单已失效，请重新下单！');
    	}
    	
 $testPaperInfo['name'] = 'MBTI专业爱情测试';
    	// TODO 根据报告类型替换
    	$where = "`goods_name` = '{$testPaperInfo['name']}'";
    	$sql = "select * from `xz_report` where {$where} limit 1;";
    	$commonDao = \dao\Common::singleton();
    	$datas = $commonDao->readDataBySql($sql);
    	$data = reset($datas);
    	$report = empty($data->report) ? array() : json_decode(base64_decode($data->report), true);
    	$paperOrderResult = $report['paperOrderResult'];
    	
    	$MBTILoveSv = \service\report\MBTILove::singleton();
    	$reportInfo = $MBTILoveSv->getReport($testOrderInfo['testOrderInfo'], $testPaperInfo, $paperOrderResult);
    	$reportInfo = array();
    	if (!empty($paperOrderResult['mbti_pl']['mbti_pl'])) { // mbti
    		
    	}
    }
    
    /**
     * 报告收藏
     *
     * @return array
     */
    public function reportCollect($userId, $testOrderId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
    	if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已删除');
    	}
    	$reportCollectDao = \dao\ReportCollect::singleton();
    	$reportCollectEtt = $reportCollectDao->getNewEntity();
    	$now = $this->frame->now;
    	$reportCollectEtt->testOrderId = $testOrderId;
    	$reportCollectEtt->userId = $userId;
    	$reportCollectEtt->createTime = $now;
    	$reportCollectDao->commit($reportCollectEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 报告评论
     *
     * @return array
     */
    public function reportComment($userId, $testOrderId, $info)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
    	if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已删除');
    	}
    	$reportCommentDao = \dao\ReportComment::singleton();
    	$reportCommentEtt = $reportCommentDao->getNewEntity();
    	$now = $this->frame->now;
    	$reportCommentEtt->testOrderId = $testOrderId;
    	$reportCommentEtt->userId = $userId;
    	$reportCommentEtt->experience = $info['experience'];
    	$reportCommentEtt->accuracy = $info['accuracy'];
    	$reportCommentEtt->satisfaction = $info['satisfaction'];
    	$reportCommentEtt->content = $info['content'];
    	$reportCommentEtt->createTime = $now;
    	$reportCommentDao->commit($reportCommentEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 报告信息
     *
     * @return array
     */
    private function get_xz_reportInfo($goods_name)
    {
    	$where = "`goods_name` = '{$goods_name}'";
    	$sql = "select * from `xz_report` where {$where} order by createTime desc limit 1;";
    	$commonDao = \dao\Common::singleton();
    	$datas = $commonDao->readDataBySql($sql);
    	$data = reset($datas);
    	$report = empty($data->report) ? array() : json_decode(base64_decode($data->report), true);
    	$paperOrderResult = $report['paperOrderResult'];
    	return $paperOrderResult;
    }

    /**
     * 报告信息
     *
     * @return array
     */
    public function reportInfo($userId, $testOrderId)
    {
    	if (!empty($userId)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userId);
    		if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    		}
    		$userSv = \service\User::singleton();
    		$userInfo = $userSv->userInfo($userEtt);
    		$userInfo = $userInfo['userInfo'];
    	}
    	
    	// 检查订单状态
    	$orderSv = \service\Order::singleton();
    	$checkResult = $orderSv->checkTestOrderPay($testOrderId, $userId, true);
    	if (empty($checkResult['testComplete'])) {
    		throw new $this->exception('测试未完成，请继续答题');
    	}
    	if (!empty($checkResult['needPay'])) {
    		throw new $this->exception('订单未支付，请尽快支付');
    	}
        $promotionInfo = empty($checkResult['testOrderInfo']['promotionInfo']) ? array() : $checkResult['testOrderInfo']['promotionInfo'];
        $testPaperInfo = empty($checkResult['testOrderInfo']['testPaperInfo']) ? array() : $checkResult['testOrderInfo']['testPaperInfo'];
        $payInfo = empty($checkResult['testOrderInfo']['payInfo']) ? array() : $checkResult['testOrderInfo']['payInfo'];
        $testOrderInfo = empty($checkResult['testOrderInfo']['testOrderInfo']) ? array() : $checkResult['testOrderInfo']['testOrderInfo'];
        $simpleSv = \service\report\Simple::singleton();
        $reportInfo = array(); // 报告信息
//         $reportInfo = $this->get_xz_reportInfo($testPaperInfo['name']);
//print_r($testPaperInfo['name']);exit;

        if ($testPaperInfo['name'] == \constant\TestPaper::NAME_MBTI) { // MBTI性格测试2025最新版  done
        	$MBTISv = \service\report\MBTI::singleton();
        	$reportInfo = $MBTISv->getReport($testOrderInfo, $testPaperInfo);
        } elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_MBTI_LOVE) { // MBTI专业爱情测试 done
       		$MBTILoveSv = \service\report\MBTILove::singleton();
       		$reportInfo = $MBTILoveSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_RAVEN) { // 瑞文国际标准智商测试
       		$ravenSv = \service\report\Raven::singleton();
       		$reportInfo = $ravenSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_ABO) { // ABO性别角色评估
       		$ABOSv = \service\report\ABO::singleton();
       		$reportInfo = $ABOSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_JUNG) { // 荣格古典心理原型测评  (未找到量表)
       		$jungSv = \service\report\Jung::singleton();
       		$jifen_pl = $jungSv->getReport($testOrderInfo, $testPaperInfo);
       		if (!empty($jifen_pl)) {
       			$reportInfo['jifen_pl'] = $jifen_pl;
       		}
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_GALLUP) { // 盖洛普优势识别测试
       		$gallupSv = \service\report\Gallup::singleton();
       		$reportInfo = $gallupSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_CAREER_ANCHOR) { // 职业锚类型专业评测
       		$careerAnchorSv = \service\report\CareerAnchor::singleton();
       		$reportInfo = $careerAnchorSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_ENNEAGRAM) { // 九型人格测试
       		$enneagramSv = \service\report\Enneagram::singleton();
       		$reportInfo = $enneagramSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_PDP) { // PDP性格测试
       		$PDPSv = \service\report\PDP::singleton();
       		$reportInfo = $PDPSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_DISC) { // DISC个性测试
       		$DISCSv = \service\report\DISC::singleton();
       		$reportInfo = $DISCSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_A_TENDENCY) { // A型人格倾向评估
       		$ATendencySv = \service\report\ATendency::singleton();
       		$reportInfo = $ATendencySv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_HOLLAND_CAREER) { // 霍兰德职业兴趣测评
       		$hollandCareerSv = \service\report\HollandCareer::singleton();
       		$reportInfo = $hollandCareerSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_PERSONALITY_DISORDER) { // 人格障碍类型专业评估
       		$personalityDisorderSv = \service\report\PersonalityDisorder::singleton();
       		$reportInfo = $personalityDisorderSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_BIG_FIVE_PERSONALITY) { // 大五人格专业测式
       		$bigFivePersonalitySv = \service\report\BigFivePersonality::singleton();
       		$reportInfo = $bigFivePersonalitySv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_MINDFULNESS) { // 正念指数测试
       		$mindfulnessSv = \service\report\Mindfulness::singleton();
       		$reportInfo = $mindfulnessSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_DEPI) { // 抑郁指数评估「医用版」
       		$DEPISv = \service\report\DEPI::singleton();
       		$reportInfo = $DEPISv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_MULTIPLE_IQ) { // 多元智力
       		$reportInfo = $simpleSv->getMultipleIQReport($testOrderInfo, $testPaperInfo);
       	} elseif (in_array($testPaperInfo['name'], array(
       		\constant\TestPaper::NAME_HYPERACTIVITY, // 儿童多动症初步筛查
       		\constant\TestPaper::NAME_HYPOCHONDRIAC, // 疑病心理倾向评估
       		\constant\TestPaper::NAME_SLEEP_QUALITY, // 睡眠质量专业评估
       		
       		\constant\TestPaper::NAME_JOB_BURNOUT, // 职业倦怠度评估
       	))) {
       		$reportInfo = $simpleSv->getDimensionReport($testOrderInfo, $testPaperInfo);
       	} elseif (in_array($testPaperInfo['name'], array( // 最简单的样式10个【检查，完整】
       		\constant\TestPaper::NAME_IRRITABILITY_DEGREE, // 易怒程度专业鉴定
			\constant\TestPaper::NAME_SELF_EFFICACY, // 自我效能评估
			\constant\TestPaper::NAME_AUTISM, // 孤独症特质测试
			\constant\TestPaper::NAME_PTSD, // 创伤后应激障碍测评
			\constant\TestPaper::NAME_EMOTIONAL_CONTROL, // 情绪管控评估
			\constant\TestPaper::NAME_CONTROL_BELIEF, // 控制信念评测
			\constant\TestPaper::NAME_CONFIDENCE_LEVEL, // 自信心水平测试
			\constant\TestPaper::NAME_WORKPLACE_COMBAT, // 职场战斗力评估
			\constant\TestPaper::NAME_SELF_ESTEEM, // 自尊类型测试
       		\constant\TestPaper::NAME_DELAY_BEHAVIOR, // 拖延行为风格评估
       	))) {
       		$reportInfo = $simpleSv->getSimpleReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_MARRIAGE) { // 婚姻质量综合评估
       		$marriageSv = \service\report\Marriage::singleton();
       		$reportInfo = $marriageSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_MENTAL_DEFENSE) { // 心理防御测评
       		$mentalDefenseSv = \service\report\MentalDefense::singleton();
       		$reportInfo = $mentalDefenseSv->getReport($testOrderInfo, $testPaperInfo);
       	} elseif ($testPaperInfo['name'] == \constant\TestPaper::NAME_VALUE_SENSE) { // 价值观测评
       		$valueSenseSv = \service\report\ValueSense::singleton();
       		$reportInfo = $valueSenseSv->getReport($testOrderInfo, $testPaperInfo);
       	}
//==============================================================       	
  
       	if (empty($reportInfo)) {
       		throw new $this->exception('报告生成失败！请联系客服退款');
       	}
        // 报告评论
        $reportCommentDao = \dao\ReportComment::singleton();
        $reportCommentEttList = $reportCommentDao->readListByIndex(array(
        	'userId' => $userId,
        	'testOrderId' => $testOrderId,
        ));
        
        return array(
        	'vipInfo' => empty($userInfo['vipInfo']) ? array() : $userInfo['vipInfo'],
            'promotionInfo' => $promotionInfo,
            'testPaperInfo' => $testPaperInfo,
            'reportInfo' => $reportInfo,
            'testOrderInfo' => $testOrderInfo,
            'sendCouponList' => array(),
            'payStyleType3SendGoods' => array(),
            'customerServiceLink' => '', // 客服地址
            'goods_sale_price_type' => 0,
            'hasComment' => empty($reportCommentEttList) ? 0 : 1, // 是否有评论  hasPaperOrderResultComment
        );
    }
    
    /**
     * 获取重测订单
     *
     * @return array
     */
    public function resetTestOrderList($userId, $testOrderId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	return array(
    		'list' => array()
    	);
    }
    
    /**
     * 获取答题情况
     *
     * @return array
     */
    public function answerQuestionInfo($userId, $testOrderId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$testOrderDao = \dao\TestOrder::singleton();
    	$testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
    	if (empty($testOrderEtt) || $testOrderEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('订单已删除');
    	}
    	$reportCollectDao = \dao\ReportCollect::singleton();
    	$reportCollectEtt = $reportCollectDao->getNewEntity();
    	$now = $this->frame->now;
    	$reportCollectEtt->testOrderId = $testOrderId;
    	$reportCollectEtt->userId = $userId;
    	$reportCollectEtt->createTime = $now;
    	$reportCollectDao->commit($reportCollectEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
}