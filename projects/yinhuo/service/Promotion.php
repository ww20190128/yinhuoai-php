<?php
namespace service;

/**
 * 推广 逻辑类
 * 
 * @author 
 */
class Promotion extends ServiceBase
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
     * @return Promotion
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Promotion();
        }
        return self::$instance;
    }

    /**
     * 推广测评详情
     *
     * @return array
     */
    public function getPromotionInfo($promotionId, $userId = 0)
    {
        $userInfo = array();
        if (!empty($userId)) { // 获取用户信息
            $userSv = \service\User::singleton();
            $userInfo = $userSv->userInfo($userId);
            $userInfo = empty($userInfo['userInfo']) ? array() : $userInfo['userInfo'];
        }
        // 推广信息
        $promotionDao = \dao\Promotion::singleton();
        $promotionEtt = $promotionDao->readByPrimary($promotionId);
        if (empty($promotionEtt) || $promotionEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('测评已下架');
        }
        $testPaperEtt = $promotionEtt->testPaper;
        if (empty($testPaperEtt) || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
            throw new $this->exception('测评已下架');
        }
        $promotionModel = $promotionEtt->getModel();
        $testPaperModel = $testPaperEtt->getModel();
        // 获取最近一次测试订单
        $lastTestOrderInfo = array();
        if (!empty($userInfo)) {
            $testPaperSv = \service\TestPaper::singleton();
            $lastTestOrderInfo = $testPaperSv->getLastTestOrderInfo($testPaperModel, $userInfo, $promotionModel);
        }
        return array(
            'promotionInfo' => $promotionModel,
            'testPaperInfo' => $testPaperModel,
            'testOrderInfo' => $lastTestOrderInfo,
            'userInfo' => $userInfo,
        );
    }
    
    /**
     * 创建测试订单
     *
     * @return array
     */
    public function getPromotionInfo2($promotionId, $version = 1, $userId = 0)
    {
        $promotionInfo = $this->getPromotionInfo($promotionId);
   
        // 题目信息
        $testPaperSv = \service\TestPaper::singleton();
        $questionInfo = $testPaperSv->getTestOrderQuestionInfo($promotionInfo['promotionInfo']['name'], $version);
        return array_merge($questionInfo, $promotionInfo, array(
            'tipList' => array(),
        ));
    }

}