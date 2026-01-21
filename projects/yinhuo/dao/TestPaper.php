<?php
namespace dao;

/**
 * TestPaper 数据库类
 * 
 * @author 
 */
class TestPaper extends DaoBase
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
     * @return TestPaper
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TestPaper();
        }
        return self::$instance;
    }

}