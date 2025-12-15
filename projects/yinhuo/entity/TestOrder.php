<?php
namespace entity;

/**
 * TestOrder 实体类
 * 
 * @author 
 */
class TestOrder extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'testOrder';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 测试Id
     *
     * @var int
     */
    public $id = 0;

    /**
     * 状态
     *
     * @var tinyint
     */
    public $status = 0;

    /**
     * 用户ID
     *
     * @var int
     */
    public $userId = 0;
    
    /**
     * 推广Id
     *
     * @var int
     */
    public $promotionId = 0;

    /**
     * 测评Id
     *
     * @var int
     */
    public $testPaperId = 0;
    
    /**
     * 订单Id
     *
     * @var int
     */
    public $testOrderId = 0;
    
    /**
     * 选择的版本
     *
     * @var int
     */
    public $version = 0;
    
    /**
     * 支付价格
     *
     * @var int
     */
    public $price = 0;

    /**
     * 支付订单
     *
     * @var int
     */
    public $orderId = 0;
    
    /**
     * 设备信息
     *
     * @var varchar
     */
    public $deviceInfo = '';
    
    /**
     * 折扣类型
     *
     * @var int
     */
    public $discountType = 0;
    
    /**
     * 折扣Id
     *
     * @var int
     */
    public $discountId = 0;
    
    /**
     * 折扣值
     *
     * @var string
     */
    public $discountValue = 0;
    
    
    /**
     * 答案
     *
     * @var string
     */
    public $answerList = 0;

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;
    
    /**
     * 更新时间
     *
     * @var int
     */
    public $updateTime = 0;
    
    /**
     * 测试完成时间
     *
     * @var int
     */
    public $testCompleteTime = 0;
    
    /**
     * 年龄
     *
     * @var int
     */
    public $age = 0;
    
    /**
     * 性别
     *
     * @var int
     */
    public $gender = 0;
    
    /**
     * 红包类型
     *
     * @var int
     */
    public $redPacketType = 0;
    
    /**
     * 红包领取状态
     *
     * @var int
     */
    public $redPacketStatus = 0;
    
    /**
     * 分享码
     *
     * @var string
     */
    public $shareCode = '';
    
    /**
     * 是否显示过动画
     *
     * @var int
     */
    public $showReportProcess = 0;
// 表结构end
    /**
     * 支付订单
     *
     * @var Object
     */
    protected $order = null;
    
    /**
     * 获取支付订单
     *
     * @return int
     */
    protected function getOrder()
    {
        if (is_null($this->order) && $this->orderId) {
            $orderDao = \dao\Order::singleton();
            $orderEtt = $orderDao->readByPrimary($this->orderId);
            $this->order = $orderEtt;
        }
        return $this->order;
    }

    /**
     * 创建模型
     *
     * @return array
     */
    protected function createModel()
    {
        $testUseTime = 0; // 测试使用时长
        if (!empty($this->testCompleteTime)) {
            $testUseTime = $this->testCompleteTime - $this->createTime;
        }
        $answerList = empty($this->answerList) ? array() : json_decode($this->answerList, true);
        $answerNum = count($answerList);
        return array(
            'id'                => intval($this->id),
            'status'            => intval($this->status),
            'userId'            => intval($this->userId),
            'testPaperId'       => intval($this->testPaperId),
            'promotionId'       => intval($this->promotionId),
            'version'           => intval($this->version),
            'price'             => $this->price,
        	'discountType'      => intval($this->discountType),
        	'discountId'       	=> intval($this->discountId),
        	'discountValue'     => $this->discountValue,
            'deviceInfo'        => empty($this->deviceInfo) ? array() : json_encode($this->deviceInfo, true),
            'answerList'        => $answerList,
            'answerNum'         => $answerNum,
            'createTime'        => intval($this->createTime),
            'updateTime'        => intval($this->updateTime),
            'testCompleteTime'  => intval($this->testCompleteTime), // 测试完成时间
            'age'               => intval($this->age),
            'gender'            => intval($this->gender),
            'redPacketType'     => intval($this->redPacketType), // 红包类型
            'redPacketStatus'   => intval($this->redPacketStatus), // 红包领取状态 2 残忍拒绝  1 解锁报告 3 领取报告 8 关闭 4 退出 6 取消支付
            'testUseTime'       => $testUseTime,
        	'shareCode'         => $this->shareCode,
        	'showReportProcess' => intval($this->showReportProcess),
        );
    }
    
}