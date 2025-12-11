<?php
namespace dao;

/**
 * ClassifyRelation 数据库类
 * 
 * @author 
 */
class ClassifyRelation extends DaoBase
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return ClassifyRelation
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ClassifyRelation();
        }
        return self::$instance;
    }

    /**
     * 主方法
     *
     * @return void
     */
    public function main()
    {
        return ;
    }

}