<?php
namespace dao;

/**
 * BackstageBusinessApply 数据库类
 * 
 * @author 
 */
class BackstageBusinessApply extends DaoBase
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
     * @return BackstageBusinessApply
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BackstageBusinessApply();
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