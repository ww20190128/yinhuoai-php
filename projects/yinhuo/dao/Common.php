<?php
namespace dao;

/**
 * Common 通用 数据库类
 * 
 * @author wangwei
 */
class Common extends DaoBase
{
    /**
     * 单例
     *
     * @var \dao\Common
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return \dao\Common
     */
    public static function singleton()
    {
        if (!isset(self::$instance) || self::$instance->serverId != self::$instance->frame->id) {
            self::$instance = new Common();
        }
        return self::$instance;
    }
    
	/**
     * 析构构造函数  销毁初始化的环境
     *
     * @return void
     */
    public function __destruct()
    {

    }
    
	/**
     * 获取新的实体对象
     * 
     * @return entity
     */
    public function getNewEntity($entity = 'stdClass')
    {
    	$entity = CS . 'entity' . CS . $entity;
        return new $entity;
    }
    
	/**
     * 设置数据表
     * 
     * @param	string mainTable 	数据表
     * 
     * @return void
     */
    public function setTable($mainTable)
    {
    	$this->mainTable = $mainTable;
    	$this->entity = CS . 'entity' . CS . ucfirst($mainTable);
    	$this->init($this->entity);
    	return;
    }

}