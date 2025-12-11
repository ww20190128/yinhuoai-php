<?php
namespace dao;

/**
 * UserCoupon 数据库类
 * 
 * @author 
 */
class UserCoupon extends DaoBase
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
     * @return UserCoupon
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserCoupon();
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