<?php
namespace dao;

/**
 * MindfulnessClassifyRelation 数据库类
 * 
 * @author 
 */
class MindfulnessClassifyRelation extends DaoBase
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
     * @return MindfulnessClassifyRelation
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MindfulnessClassifyRelation();
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