<?php
namespace entity;

/**
 * Sentence 实体类
 * 
 * @author 
 */
class Sentence extends EntityBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'sentence';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * id
     *
     * @var int
     */
    public $id;

    /**
     * 名称
     *
     * @var varchar
     */
    public $text = '';

    /**
     * 来源
     *
     * @var varchar
     */
    public $source = '';

    /**
     * 图片
     *
     * @var varchar
     */
    public $img = '';

// 表结构end
}