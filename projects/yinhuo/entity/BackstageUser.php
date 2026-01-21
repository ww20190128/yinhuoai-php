<?php
namespace entity;

/**
 * BackstageUser 实体类
 * 
 * @author 
 */
class BackstageUser extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'backstageUser';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'userId';

    /**
     * 用户id
     *
     * @var int
     */
    public $userId;

    /**
     * 用户名
     *
     * @var varchar
     */
    public $userName = '';

    /**
     * 有效开始时间
     *
     * @var int unsigned
     */
    public $startTime = 0;

    /**
     * 有效期结束时间
     *
     * @var int unsigned
     */
    public $endTime = 0;

    /**
     * 状态 -1､删除 0､正常 1､禁用
     *
     * @var tinyint
     */
    public $status = 0;

    /**
     *
     * @var tinyint
     */
    public $type = 0;
    
    /**
     * 头像
     *
     * @var varchar
     */
    public $icon = '';

    /**
     * 手机号
     *
     * @var varchar
     */
    public $phone = '';

    /**
     * 密码
     *
     * @var varchar
     */
    public $password = '';

    /**
     * 创建者用户id
     *
     * @var int
     */
    public $createUserId = 0;

    /**
     * 权限-可显示模块
     *
     * @var varchar
     */
    public $showPrivileges = '';

    /**
     * 权限-可操作内容
     *
     * @var varchar
     */
    public $opPrivileges = '';

    /**
     * 最近一次登录的key
     *
     * @var varchar
     */
    public $loginKey = '';

    /**
     * 最近一次登录时间
     *
     * @var int
     */
    public $lastLoginTime = 0;

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

    /**
     * 上次更新的时间
     *
     * @var int unsigned
     */
    public $updateTime = 0;

    /**
     * 绑定的分享账号
     *
     * @var
     */
    public $shareUserIds = 0;
// 表结构end
    /**
     * 创建模型
     *
     * @return array
     */
    protected function createModel()
    {
    	// 显示的模块
    	$showPrivileges = empty($this->showPrivileges) ? array() : explode(',', $this->showPrivileges);
    	// 可操作的内容
    	$opPrivileges = empty($this->opPrivileges) ? array() : explode(',', $this->opPrivileges);
    	$item = array(
    		'userId'        => (int)$this->userId,                    // 用户id
    		'userName'      => $this->userName,                       // 用户名
    		'phone'         => (int)$this->phone,                     // 手机号
    		'password'      => $this->password,                       // 密码
    		'status'        => (int)$this->status,                    // 状态
    		'type'          => (int)$this->type,                      // 类型
    		'createTime'    => (int)$this->createTime,                // 创建时间
    		'icon'          => $this->icon,                           // 头像
    		'lastLoginTime' => (int)$this->lastLoginTime,             // 最近一次登录的时间
    		'createUserId'  => (int)$this->createUserId,              // 创建者id
    		'startTime'     => (int)$this->startTime,                 // 有效开始时间
    		'endTime'       => (int)$this->endTime,                   // 有效结束时间
    		'opControl' 	=> $opPrivileges,
    		'showControl' 	=> $showPrivileges,
    		'shareUserIds'	=> empty($this->shareUserIds) ? array() : array_map('intval', explode(',', $this->shareUserIds))
    	);
    	return $item;
    }
    
}