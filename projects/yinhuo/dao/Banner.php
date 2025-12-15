<?php
namespace dao;

/**
 * Banner 数据库类
 * 
 * @author 
 */
class Banner extends DaoBase
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
     * @return Banner
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Banner();
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