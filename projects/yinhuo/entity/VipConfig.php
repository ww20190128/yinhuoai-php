<?php
namespace entity;

/**
 * VipConfig 实体类
 * 
 * @author 
 */
class VipConfig extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'vipConfig';

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
    public $name = '';

    /**
     * 描述
     *
     * @var varchar
     */
    public $desc = '';

    /**
     * 简介
     *
     * @var varchar
     */
    public $intro = '';
    
    /**
     * 内容描述
     *
     * @var text
     */
    public $content;

    /**
     * 类型
     *
     * @var tinyint
     */
    public $type = 0;

    /**
     * 原始价格
     *
     * @var decimal(6,2)
     */
    public $originalPrice = 0.00;

    /**
     * 价格
     *
     * @var decimal(6,2)
     */
    public $price = 0.00;

    /**
     * 状态0 正常 1禁用
     *
     * @var tinyint
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
     * 有效时长
     *
     * @var int
     */
    public $effectDay = 0;
    
    /**
     * 赠送次数
     *
     * @var int
     */
    public $giveNum = 0;
    
    /**
     * 测评数
     *
     * @var int
     */
    public $testPaperNum = 0;

// 表结构end

    /**
     * 创建模型
     *
     * @return array
     */
    protected function createModel()
    {
    	$content = empty($this->content) ? array() : json_decode($this->content, true);
    	$contentArr = array();
    	foreach ($content as $title => $value) {
    		$contentArr[] = array(
    			'title' => $title,
    			'value' => $value,
    		);
    	}
 
        return array(
            'id'                => intval($this->id), // vip id 
            'name'              => $this->name, // 名称
            'desc'              => $this->desc, // 简单描述
        	'intro'             => $this->intro, // 简介
            'content'           => $contentArr, // 详细描述
            'status'            => $this->status,
            'type'              => intval($this->type), // 类型
            'originalPrice'     => $this->originalPrice, // 原始价格
            'price'             => $this->price, // 价格
            'effectDay'         => intval($this->effectDay), // 
            'giveNum'           => intval($this->giveNum), // 可赠送次数
            'testPaperNum'      => intval($this->testPaperNum), // 可测试数量
            'createTime'        => intval($this->createTime),
        );
    }
    
}