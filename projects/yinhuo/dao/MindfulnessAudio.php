<?php
namespace dao;

/**
 * MindfulnessAudio 数据库类
 * 
 * @author 
 */
class MindfulnessAudio extends DaoBase
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
     * @return MindfulnessAudio
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MindfulnessAudio();
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