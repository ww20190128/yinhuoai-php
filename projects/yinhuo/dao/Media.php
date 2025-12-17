<?php
namespace dao;

/**
 * Media 数据库类
 * 
 * @author 
 */
class Media extends DaoBase
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
     * @return Media
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Media();
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