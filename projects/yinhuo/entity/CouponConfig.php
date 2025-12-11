<?php
namespace entity;

use constant\Coupon;
/**
 * CouponConfig 实体类
 * 
 * @author 
 */
class CouponConfig extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'couponConfig';

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
     * 类型 1 现金券  2 测评赠送券 3 测评折扣券  4 VIP折扣券
     *
     * @var int
     */
    public $type = 0;
    
    /**
     * 名称
     *
     * @var string
     */
    public $name = '';
    
    /**
     * 描述
     *
     * @var string
     */
    public $desc = '';

    /**
     * 作用对象ID
     *
     * @var string
     */
    public $targetIds = 0;

    /**
     * 数量
     *
     * @var int
     */
    public $value = 0;

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

    /**
     * 生效天数
     *
     * @var int
     */
    public $effectiveDay = 0;

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
     * 数量上限
     *
     * @var int
     */
    public $limitNum = 0;
    
// 表结构end
    /**
     * 创建模型
     *
     * @return array
     */
    protected function createModel()
    {
    	$forVip = 0; // 是否用于VIP
    	$forTestPaper = 0; // 是否用于测评
    	if ($this->type == \constant\Coupon::TYPE_CASH_DEDUCTION) { // 现金抵扣券
    		$forVip = 1;
    		$forTestPaper = 1;
    	} elseif ($this->type == \constant\Coupon::TYPE_TEST_PAPER_GIVE) { // 测评赠送券
    		$forTestPaper = 1;
    	} elseif ($this->type == \constant\Coupon::TYPE_TEST_PAPER_DISCOUNT) { // 测评折扣券
    		$forTestPaper = 1;
    	} elseif ($this->type == \constant\Coupon::TYPE_VIP_DISCOUNT) { // vip 抵扣券
    		$forVip = 1;
    	}
    	return array(
    		'id'         	=> intval($this->id),
    		'type'       	=> intval($this->type),	
    		'name'       	=> $this->name,
    		'desc'       	=> $this->desc,
    		'targetIds'     => empty($this->targetIds) 
    			? array() : array_map('intval', explode(',', $this->targetIds)),
    		'status'     	=> intval($this->status),
    		'effectiveDay'  => max(0, intval($this->effectiveDay)),
    		'value'  		=> max(0, $this->value),
    		'forVip' 		=> intval($forVip),
    		'forTestPaper'  => intval($forTestPaper),
    		'createTime' 	=> intval($this->createTime),
    		'updateTime' 	=> intval($this->updateTime),
    		'targetInfos'   => array(),
    	);
    }
    
}