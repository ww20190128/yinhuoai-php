<?php
namespace dao;

/**
 * ReportABO 数据库类
 * 
 * @author 
 */
class ReportABO extends DaoBase
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
     * @return ReportABO
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportABO();
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