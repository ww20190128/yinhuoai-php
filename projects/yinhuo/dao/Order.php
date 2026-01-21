<?php
namespace dao;

/**
 * Order 数据库类
 * 
 * @author 
 */
class Order extends DaoBase
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
     * @return Order
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Order();
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