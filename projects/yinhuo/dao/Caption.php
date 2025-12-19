<?php
namespace dao;

/**
 * Caption 数据库类
 * 
 * @author 
 */
class Caption extends DaoBase
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
     * @return Caption
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Caption();
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