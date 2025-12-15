<?php
namespace entity;

/**
 * Classify 实体类
 * 
 * @author 
 */
class Classify extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'classify';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键id
     *
     * @var int
     */
    public $id;

    /**
     * 名称
     *
     * @var varchar
     */
    public $name;
    
    /**
     * 图标
     *
     * @var varchar
     */
    public $icon;

    /**
     * 状态0 正常 1禁用
     *
     * @var int
     */
    public $status = 0;

    /**
     * 次序
     *
     * @var int
     */
    public $index = 0;

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
     * 是否在首页显示
     *
     * @var int
     */
    public $showHomePage = 0;

// 表结构end
    
    /**
     * 创建模型
     *
     * @return array
     */
    protected function createModel()
    {
        return array(
            'id'            => intval($this->id),
            'name'          => $this->name,
            'status'        => $this->status,
            'index'         => intval($this->index),
            'showHomePage'  => intval($this->showHomePage),
            'createTime'    => intval($this->createTime),
        );
    }
}