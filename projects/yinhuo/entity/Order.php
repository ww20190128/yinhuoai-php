<?php
namespace entity;

/**
 * Order 实体类
 * 
 * @author 
 */
class Order extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'order';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键
     *
     * @var int
     */
    public $id;

    /**
     * 用户Id
     *
     * @var int
     */
    public $userId = 0;

    /**
     * 支付状态
     *
     * @var int
     */
    public $status = 0;

    /**
     * 商品类型
     *
     * @var int
     */
    public $goodsType = 0;

    /**
     * 商品Id
     *
     * @var int
     */
    public $goodsId = 0;

    /**
     * 支付价格
     *
     * @var decimal(4,2)
     */
    public $price = 0.00;

    /**
     * 红包
     *
     * @var decimal(4,2)
     */
    public $redPacketValue = 0.00;
    /**
     * 更新时间
     *
     * @var int
     */
    public $updateTime = 0;

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

    /**
     * 消耗的优惠券ID
     *
     * @var int
     */
    public $couponId = 0;
    
    /**
     * 交易订单号
     *
     * @var string
     */
    public $outTradeNo = '';
    
    /**
     * 交易信息
     *
     * @var string
     */
    public $tradeInfo = '';
// 表结构end

    /**
     * 创建模型
     *
     * @return array
     */
    protected function createModel()
    {
        return array(
            'id'            => $this->id,
            'status'        => intval($this->status),
            'userId'        => intval($this->userId),
            'goodsType'     => intval($this->goodsType),
            'goodsId'       => intval($this->goodsId),
            'price'         => $this->price,
            'updateTime'    => intval($this->updateTime),
            'createTime'    => intval($this->createTime),
        );
    }
        
}