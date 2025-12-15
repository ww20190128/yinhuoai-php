<?php
namespace dao;

/**
 * CouponConfig 数据库类
 * 
 * @author 
 */
class CouponConfig extends DaoBase
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
     * @return CouponConfig
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new CouponConfig();
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