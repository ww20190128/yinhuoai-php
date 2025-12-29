<?php
namespace dao;

/**
 * MusicClassify 数据库类
 * 
 * @author 
 */
class MusicClassify extends DaoBase
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
     * @return MusicClassify
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MusicClassify();
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