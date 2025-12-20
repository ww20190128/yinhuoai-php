<?php
namespace dao;

/**
 * EditingDecal 数据库类
 * 
 * @author 
 */
class EditingDecal extends DaoBase
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
     * @return EditingDecal
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new EditingDecal();
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