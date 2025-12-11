<?php
namespace entity;

/**
 * UserCollect 实体类
 * 
 * @author 
 */
class UserCollect extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'userCollect';

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
     * 测评Id
     *
     * @var int
     */
    public $testPaperId = 0;

    /**
     * 课程Id
     *
     * @var int
     */
    public $mindfulnessId = 0;
    
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
}