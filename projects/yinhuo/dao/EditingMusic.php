<?php
namespace dao;

/**
 * EditingMusic 数据库类
 * 
 * @author 
 */
class EditingMusic extends DaoBase
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
     * @return EditingMusic
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new EditingMusic();
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