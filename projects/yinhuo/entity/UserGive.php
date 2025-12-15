<?php
namespace entity;

/**
 * UserGive 实体类
 * 
 * @author 
 */
class UserGive extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'userGive';

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
     * 赠送用户Id
     *
     * @var int
     */
    public $userId = 0;

    /**
     * 领取用户Id
     *
     * @var int
     */
    public $drawUserId = 0;

    /**
     * 测评Id
     *
     * @var int
     */
    public $testPaperId = 0;

    /**
     * 领取时间
     *
     * @var int
     */
    public $drawTime = 0;

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
            'userId'       	=> intval($this->userId),
            'status'       	=> intval($this->status),
            'userId'       	=> intval($this->userId),
            'testPaperId'   => intval($this->testPaperId),
            'drawUserId'    => intval($this->drawUserId),
            'drawTime'      => intval($this->drawTime),
            'createTime' 	=> intval($this->createTime),
            'updateTime' 	=> intval($this->updateTime),
        );
    }
    
}