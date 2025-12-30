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
//     	    'openid'        => $this->openid,
//          'sex'           => intval($this->sex),
//          'country'       => $this->country,
//          'province'      => $this->province,
//     	    'city'          => $this->city,
//     	    'language'      => $this->language,
    	);
    }
}