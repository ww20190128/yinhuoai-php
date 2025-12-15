<?php
namespace entity;

/**
 * TestQuestion 实体类
 * 
 * @author 
 */
class TestQuestion extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'testQuestion';

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
     * 测卷id
     *
     * @var int
     */
    public $testPaperId = 0;

    /**
     * 次序
     *
     * @var int
     */
    public $index = 0;

    /**
     * 题干
     *
     * @var text
     */
    public $matter;

    /**
     * 题干图片
     *
     * @var text
     */
    public $matterImg;

    /**
     * 选项
     *
     * @var text
     */
    public $selections;

    /**
     * 版本
     *
     * @var int
     */
    public $version = 1;
    
    /**
     * 分值
     *
     * @var varchar
     */
    public $scoreValue = '';
    
    /**
     * 解析
     *
     * @var varchar
     */
    public $analysis = '';
    
    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end
    /**
     * 创建模型
     *
     * @return array
     */
    protected function createModel()
    {
        $selections = empty($this->selections) ? array() : json_decode($this->selections, true);   
        $commonSv = \service\Common::singleton();
        return array(
            'id'            => intval($this->id),
            'testPaperId'   => intval($this->testPaperId),
            'index'         => intval($this->index),
            'matter'        => $this->matter,
            'matterImg'     => empty($this->matterImg) ? '' : $commonSv::formartImgUrl(md5($this->matterImg) . '.png', 'question'),
            'selections'    => $selections,
            'version'       => intval($this->version),
            'createTime'    => intval($this->createTime),
        );
    }
    
}