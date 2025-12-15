<?php
namespace dao;

/**
 * ReportCollect 数据库类
 * 
 * @author 
 */
class ReportCollect extends DaoBase
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
     * @return ReportCollect
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportCollect();
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