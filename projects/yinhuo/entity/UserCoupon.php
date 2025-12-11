<?php
namespace entity;

/**
 * UserCoupon 实体类
 * 
 * @author 
 */
class UserCoupon extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'userCoupon';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键ID
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
     * 优惠券ID
     *
     * @var int
     */
    public $couponId = 0;

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

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

// 表结构end
    /**
     * 创建模型
     *
     * @return array
     */
    protected function createModel()
    {
	    return array(
	    	'id'         	=> intval($this->id),
	    	'userId'     	=> intval($this->userId),
	    	'status'     	=> intval($this->status),
	    	'couponId'   	=> intval($this->couponId),
	    	'createTime' 	=> intval($this->createTime),
	    	'updateTime' 	=> intval($this->updateTime),
	    );
    }
    
}