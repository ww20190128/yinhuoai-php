<?php
namespace service;
use Dispatch\FrameBase;

/**
 * 逻辑层抽象基类
 * 
 * @author wangwei
 */
abstract class ServiceBase extends FrameBase 
{    
    /**
     * 构造函数
     *
     * @return \service\ServiceBase
     */
	public function __construct() 
	{
		parent::__construct();
		return ;
	}

    /**
     * 单例
     *
     */
    private static $instance;

    /**
     * 单例模式
     *
     */
    public static function singleton()
    {
        $c = static::class;
        if (!isset(self::$instance[$c])) {
            self::$instance[$c] = new static();
        }
        return self::$instance[$c];
    }

    /**
     * 权限和参数的检查
     */
    public function ppcheck($info){
        // 检查参数
        $paramsCheck = \service\TaskParamsCheck::singleton();
        if (!$paramsCheck->check($info)) {
            throw new $this->exception($paramsCheck->getError());
        }

        // 检查权限
        $privilegesSv = \service\TaskPrivileges::singleton();
        $privilegesSv->setParams($info);
        $privilegesSv->check();
    }
}