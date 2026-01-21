<?php
namespace dao;

/**
 * News 数据库类
 * 
 * @author 
 */
class News extends DaoBase
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
     * @return News
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new News();
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