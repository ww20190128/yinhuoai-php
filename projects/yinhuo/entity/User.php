<?php
namespace entity;

/**
 * 用户 实体类
 * 
 * @author 
 */
class User extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'user';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'userId';
    
    
    /**
     * 主键ID
     *
     * @var int
     */
    public $userId;
    
    /**
     * openid
     *
     * @var varchar
     */
    public $openid;
    
    /**
     * 头像
     *
     * @var varchar
     */
    public $headImgUrl = '';

    /**
     * 用户名
     *
     * @var varchar
     */
    public $userName = '';

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

    /**
     * 级别
     *
     * @var int
     */
    public $level = 1;
    
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
     * 更新时间
     *
     * @var int
     */
    public $sex = 0;
    
    /**
     * 语言
     *
     * @var varchar
     */
    public $language = '';
    
    /**
     * 国家
     *
     * @var varchar
     */
    public $country = '';
    
    /**
     * 省份
     *
     * @var varchar
     */
    public $province = '';
    
    /**
     * 城市
     *
     * @var varchar
     */
    public $city = '';
    
    /**
     * 用户最近编辑的剪辑Id
     *
     * @var varchar
     */
    public $editingId = 0;
    
    /**
     * 个性签名
     *
     * @var varchar
     */
    public $signature = '';
    
    /**
     * 火币
     *
     * @var varchar
     */
    public $gold = 0;
    
    /**
     * 奖励金
     *
     * @var varchar
     */
    public $award = 0;
 
    /**
     * 手机号
     *
     * @var varchar
     */
    public $phone = '';
    
    /**
     * 上线用户ID
     *
     * @var varchar
     */
    public $parentUserId = 0;
    
    /**
     * 推广收益
     *
     * @var varchar
     */
    public $shareYield = '0.00';
    
    /**
     * 已提现金额
     *
     * @var varchar
     */
    public $withdrawAmount = '0.00';
    
    /**
     * 分成比例
     *
     * @var int
     */
    public $commissionRate = 30;
    
    /**
     * 快手-授权
     *
     * @var int
     */
    public $shareKsAccess = '';
// 表结构end

    /**
     * 创建模型
     *
     * @return array
     */
    protected function createModel()
    {
    	return array(
    		'userId'        => intval($this->userId),
    		'headImgUrl'    => $this->headImgUrl,
    		'userName'      => $this->userName,
    		'status'        => intval($this->status),
    	    'updateTime'    => intval($this->updateTime),
    	    'createTime'    => intval($this->createTime),
    		'phone'    		=> intval($this->phone),
    		'signature'     => $this->signature,
    		'gold'    		=> intval($this->gold),
    		'award'    		=> intval($this->award),
    		'parentUserId'  => intval($this->parentUserId),
			'openid'        => $this->openid,
    		'level'  		=> intval($this->level),
//          'sex'           => intval($this->sex),
//          'country'       => $this->country,
//          'province'      => $this->province,
//     	    'city'          => $this->city,
//     	    'language'      => $this->language,
    	);
    }
}