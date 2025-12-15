<?php
namespace dao;

/**
 * ReportGallupElement 数据库类
 * 
 * @author 
 */
class ReportGallupElement extends DaoBase
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
     * @return ReportGallupElement
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ReportGallupElement();
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