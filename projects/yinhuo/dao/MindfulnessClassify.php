<?php
namespace dao;

/**
 * MindfulnessClassify 数据库类
 * 
 * @author 
 */
class MindfulnessClassify extends DaoBase
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
     * @return MindfulnessClassify
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MindfulnessClassify();
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