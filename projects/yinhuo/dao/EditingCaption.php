<?php
namespace dao;

/**
 * EditingCaption 数据库类
 * 
 * @author 
 */
class EditingCaption extends DaoBase
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
     * @return EditingCaption
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new EditingCaption();
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