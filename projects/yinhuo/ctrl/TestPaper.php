<?php
namespace ctrl;

/**
 * 测评
 * 
 * @author 
 */
class TestPaper extends CtrlBase
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
     * @return TestPaper
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TestPaper();
        }
        return self::$instance;
    }
    
    /**
     * 是否显示历史订单按钮
     *
     * @return array
     */
    public function showHistoryOrderButton()
    {
    	$params = $this->params;
    	$testPaperId = $this->paramFilter('testPaperId', 'intval', 0); // 测评id
    	$promotionId = $this->paramFilter('promotionId', 'intval', 0); // 推广订单
    	if (empty($testPaperId) || empty($promotionId)) { // 推广订单不显示 历史订单按钮
    		return array(
    			'showButton' => 0,
    		);
    	}
    	if (empty($this->userId)) {
    		return array(
    			'showButton' => 0,
    		);
    	}
    	$userSv = \service\User::singleton();
    	$result = $userSv->testOrderList($this->userId, 1, 10, $testPaperId);
    	return array(
    		'showButton' => empty($result['list']) ? 0 : 0,
    	);
    }

    /**
     * 获取测卷详情
     * 
     * @return array
     */
    public function getTestPaperInfo()
    {
        $params = $this->params;
        $testPaperId = $this->paramFilter('testPaperId', 'intval', 0); // 测评id
        $couponId = $this->paramFilter('couponId', 'intval', 0); // 优惠券id
        if (empty($testPaperId)) {
            throw new $this->exception('请求参数错误');
        }
        $testPaperSv = \service\TestPaper::singleton();
        $testPaperInfo = $testPaperSv->testPaperInfo($testPaperId, $couponId);
        // 推荐列表
        $classifySv = \service\Classify::singleton();
        $choicenessList = $classifySv->getListByClassify(103, array(), 1, 100); // 精选推荐
        // 获取用户信息
        $userInfo = array();
        $collectList = array();
        if (!empty($this->userId)) {
        	$userSv = \service\User::singleton();
        	$userInfo = $userSv->userInfo($this->userId);
        	$userInfo = empty($userInfo['userInfo']) ? array() : $userInfo['userInfo'];
        	
        	// 是否已收藏
        	$collectSv = \service\Collect::singleton();
        	$collectResult = $collectSv->collectList($this->userId);
        	$collectList = empty($collectResult['testPapers']) ? array() : array_column($collectResult['testPapers'], null, 'id');
        }
        // 获取最近一次测试订单
        $lastTestOrderInfo = $testPaperSv->getLastTestOrderInfo($testPaperInfo, $userInfo);
        
        return array(
            'testPaperInfo' => $testPaperInfo, // 详情
            'choicenessList' => $choicenessList, // 推荐列表
            'userInfo' => $userInfo, // 用户信息
            'couponInfo' => empty($testPaperInfo['couponInfo']) ? array() : $testPaperInfo['couponInfo'], // 用户优惠价信息
            'lastTestOrderInfo' => $lastTestOrderInfo, // 测评订单信息
            'commentList' => array(), // 评价列表
            'collectStatus' => empty($collectList[$testPaperId]) ? 0 : $collectList[$testPaperId],
        );
    }
    
    /**
     * 创建测试订单
     * 
     * @return array
     */
    public function createTestOrder()
    {
        $params = $this->params;
        $testPaperId = $this->paramFilter('testPaperId', 'intval'); // 测评id
        $promotionId = $this->paramFilter('promotionId', 'intval'); // 推广ID
       
        if (empty($promotionId) && empty($testPaperId)) {
            throw new $this->exception('请求参数错误');
        }
        $answerList = empty($params->answerList) ? array() : $params->answerList; // 答案
        $userId = empty($this->userId) ? 0 : intval($this->userId); // 用户ID
        $deviceInfo = array( // 设备信息
            'phoneModel'        => $this->paramFilter('phoneModel', 'string'),
            'browserVersion'    => $this->paramFilter('browserVersion', 'string'),
            'network'           => $this->paramFilter('network', 'string'),
            'screenResolution'  => $this->paramFilter('screenResolution', 'string'),
            'hasParams'         => $this->paramFilter('hasParams', 'string'),
            'useEnv'            => $this->paramFilter('useEnv', 'intval'),
        );
        // 其它信息 source  
        $info = array(
        	'promotionId'   => $promotionId,
            'testPaperId'   => $testPaperId,
            'version'       => $this->paramFilter('version', 'intval', 1), // 选择的版本
            'giveId'        => $this->paramFilter('giveId', 'intval', 0), // 赠送的ID
            'couponId'      => $this->paramFilter('couponId', 'intval', 0), // 优惠券的ID
            'shareCode'		=>  $this->paramFilter('shareCode', 'string'), // 分享推广码
        );
        
        $testPaperSv = \service\TestPaper::singleton();
        return $testPaperSv->createTestOrder($info, $deviceInfo, $userId, $answerList);
    }
    
    /**
     * 创建重测测试订单
     *
     * @return array
     */
    public function createResetTestOrder()
    {
    	$params = $this->params;
    	$testOrderId = $this->paramFilter('testOrderId', 'intval'); // 测评id
    	if (empty($testOrderId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	
    	$testPaperSv = \service\TestPaper::singleton();
    	return $testPaperSv->createResetTestOrder($testOrderId, $this->userId);
    }
    
    /**
     * 获取重测订单
     *
     * @return array
     */
    public function resetTestOrderList()
    {
    	$params = $this->params;
    	$testOrderId = $this->paramFilter('testOrderId', 'intval'); // 订单Id
    	if (empty($testOrderId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$testPaperSv = \service\TestPaper::singleton();
    	$list = $testPaperSv->resetTestOrderList($testOrderId, $this->userId);

    	return array(
    		'list' => array_values($list),
    	);
    }
    
    /**
     * 修改测试订单
     *
     * @return array
     */
    public function updateTestOrder()
    {
        $params = $this->params;
        $testOrderId = $this->paramFilter('testOrderId', 'intval'); // 测试ID
        if (empty($testOrderId)) {
            throw new $this->exception('请求参数错误');
        }
        $age = $this->paramFilter('age', 'intval'); // 年龄
        $redPacketType = $this->paramFilter('redPacketType', 'intval');
        $info = array();
        if (!empty($age)) { // 设置年龄
            $info['age'] = $age;
        }
        if (isset($params->redPacketType)) { // 红包类型
            $info['redPacketType'] = $this->paramFilter('redPacketType', 'intval');
        }
        $testPaperSv = \service\TestPaper::singleton();
        return $testPaperSv->updateTestOrder($testOrderId, $info);
    }
    
    /**
     * 重新测试
     *
     * @return array
     */
    public function resetTestOrder()
    {
        $params = $this->params;
        $testOrderId = $this->paramFilter('testOrderId', 'intval'); // 测试ID
        if (empty($testOrderId)) {
            throw new $this->exception('请求参数错误');
        }
        $promotionId = $this->paramFilter('promotionId', 'intval'); // 推广ID
        $version = $this->paramFilter('version', 'intval', 1); // 选择的版本
        $userId = empty($this->userId) ? 0 : intval($this->userId); // 用户ID
        $testPaperId = $this->paramFilter('testPaperId', 'intval');
        $info = array(
            'testPaperId'   => $testPaperId,
            'version'       => $version,
            'promotionId'   => $promotionId,
        );
        $testPaperSv = \service\TestPaper::singleton();
        return $testPaperSv->resetTestOrder($testOrderId, $info, $userId);
    }
    
    /**
     * 测试订单详情
     *
     * @return array
     */
    public function getTestOrderInfo()
    {
        $params = $this->params;
        $testOrderId = $this->paramFilter('testOrderId', 'intval'); // 测试ID
        if (empty($testOrderId)) {
            throw new $this->exception('请求参数错误');
        }
        $couponId = $this->paramFilter('couponId', 'intval', 0); // 优惠券id
        $testPaperSv = \service\TestPaper::singleton();
        $testOrderInfo = $testPaperSv->testOrderInfo($testOrderId);
        // 题目信息
        $questionInfo = $testPaperSv->getTestOrderQuestionInfo($testOrderInfo['testPaperInfo']['name'], $testOrderInfo['testOrderInfo']['version']);
      
        // 赋值版本名称
        $versionOptions = empty($testOrderInfo['testPaperInfo']['versionConfig']['options']) 
        	? array() : $testOrderInfo['testPaperInfo']['versionConfig']['options'];
        if (!empty($versionOptions)) foreach ($versionOptions as $versionOption) {
        	if ($versionOption['id'] == $testOrderInfo['testOrderInfo']['version']) {
        		$testOrderInfo['testOrderInfo']['versionName'] = $versionOption['name'];
        	}
        }
        $reportProcessList = array();
       	if (empty($testOrderInfo['testOrderInfo']['showReportProcess'])) {
       		$reportProcessList = $testPaperSv->getReportProcess($testOrderInfo['testOrderInfo']);
       		$testOrderDao = \dao\TestOrder::singleton();
       		$testOrderEtt = $testOrderDao->readByPrimary($testOrderId);
       		$testOrderEtt->set('showReportProcess', 1);
       		$testOrderDao->update($testOrderEtt);
       	}

        $result = array_merge($testOrderInfo, $questionInfo);
        $result['tipList'] = array(); // paper_tips
        $result['reportProcessList'] = $reportProcessList; // 输出报告create_report
        $result['couponList'] = array();
        $result['couponInfo'] = array();
        if (!empty($couponId)) {
        	$couponSv = \service\Coupon::singleton();
        	$result['couponInfo'] = $couponSv->couponInfo($couponId);
        }
        return $result;
    }
    
    /**
     * 根据订单号获取答题记录
     *
     * @return array
     */
    public function getAnswerRecord()
    {
        $params = $this->params;
        $testOrderId = $this->paramFilter('testOrderId', 'intval'); // 测试ID
        if (empty($testOrderId)) {
            throw new $this->exception('请求参数错误');
        }
        $testPaperSv = \service\TestPaper::singleton();
        return $testPaperSv->getAnswerRecord($testOrderId);
    }
    
    /**
     * 提交答案
     *
     * @return array
     */
    public function submitAnswers()
    {
        $params = $this->params;
      	$testOrderId = $this->paramFilter('testOrderId', 'intval'); // 测试ID
      	$answerList = empty($params->answerList) ? array() : $params->answerList;
        if (empty($testOrderId)) {
            throw new $this->exception('请求参数错误');
        }
        $testPaperSv = \service\TestPaper::singleton();
        return $testPaperSv->submitAnswers($testOrderId, $answerList);
    }
    
    /**
     * 提交试卷
     *
     * @return array
     */
    public function submitTest()
    {
        $params = $this->params;
        $testOrderId = $this->paramFilter('testOrderId', 'intval'); // 测试ID
        if (empty($testOrderId)) {
            throw new $this->exception('请求参数错误');
        }
        $answerList = empty($params->answerList) ? array() : $params->answerList;
        $testPaperSv = \service\TestPaper::singleton();
        return $testPaperSv->submitTest($testOrderId, $answerList);
    }
    
}