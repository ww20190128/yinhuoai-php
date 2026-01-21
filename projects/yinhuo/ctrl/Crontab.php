<?php
namespace ctrl;

/**
 * Crontab 计划任务  控制器类
 * 后台时间表任务类，用于封装定时执行的任务
 * 
 * @author 
 */
class Crontab extends CtrlBase
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
     * @return Crontab
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Crontab();
        }
        return self::$instance;
    }
    
    /**
     * 每天00:00执行
     *
     * format: Y-m-d H:00:00
     * interval:60
     *
     * @return bool
     */
    public function executePre_00_00()
    {
        $s = time();
        // 同步钉钉通讯录数据
        $res = \service\Dingding::singleton()->organizational();
        if($res !== true) {
            echo date('Y-m-d H:i:s') . $res ."\n\n";
            exit;
        }

        echo date('Y-m-d H:i:s') . "同步钉钉组织架构完成，耗时：". (time() - $s) ."s\n\n";
    }
}