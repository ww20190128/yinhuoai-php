<?php
namespace dao;

/**
 * ReportComment 数据库类
 * 
 * @author 
 */
class ReportComment extends DaoBase
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
     * @return ReportComment
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportComment();
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