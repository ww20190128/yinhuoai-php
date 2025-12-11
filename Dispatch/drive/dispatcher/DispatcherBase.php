<?php
namespace drive\dispatcher;
use Application;
/**
 * 请求调度的基类
 * 
 * @author wangwei
 */
class DispatcherBase
{	
	/**
     * 控制器类名
     *
     * @var string
     */
    protected static $ctrlName;

    /**
     * 控制器方法名
     *
     * @var string
     */
    protected static $methodName;
        
    /**
     * 请求 (act: 请求的方法   params： 请求的参数 op： 请求的操作码)
     *
     * @var string
     */
    static protected $request = array('act' => null, 'params' => null, 'op' => null);

    /**
     * 构造函数
     *
     * @return DispatcherBase
     */
    public function __construct()
    {
       	if (preg_match('/^([a-z_\\\\]+)\.([a-z_0-9]+)$/i', self::$request->act, $items)) {
        	self::$methodName = array_pop($items);    	
            self::$ctrlName  = array_pop($items);         
        } else {
        	if (self::$request->act == '\"Attendance.signIn\"') {
        		self::$methodName = "signIn";
        		self::$ctrlName = "Attendance";
        		return ;
        	}
        	if (self::$request->act == '\"Attendance.signIn\"') {
        		self::$methodName = "signIn";
        		self::$ctrlName = "Attendance";
        		return ;
        	} else if (self::$request->act == '\"Message.reply\"') {
        		self::$methodName = "reply";
        		self::$ctrlName = "Message";
        		return ;
        	}
           	throw new Application::$Exception('非法请求', array(), false);
        }
        return ;
    }
	
	/**
     * 获取请求分发参数 请求调度
     *
     * @return obj
     */
    public function getParams()
    {
        return self::$request->params;
    }

    /**
     * 请求调度
     *
     * @return mixed
     *
     * @throws
     */
    public function distribute()
    {  	 
		if (Application::$Locator) {
			$ctrl = Application::$Locator->getCtrl(self::$ctrlName);
		} else {
			// 控制器Application::Locator没找到
			throw new Application::$Exception('系统错误', array(), true);
		}
        if (is_null($ctrl)) {
        	// 控制器对象[CLASS] 没找到
        	throw new Application::$Exception("控制类 '[CLASS]' 不存在", array('CLASS' => self::$ctrlName));
        }
        $method = self::$methodName;
        if (!is_callable(array($ctrl, $method))) {
         	// 控制器方法[CLASS].[METHOD] 没找到
           throw new Application::$Exception("控制方法 '[CLASS].[METHOD]' 不存在", array('CLASS' => self::$ctrlName, 'METHOD' => $method));
        }

        if (is_callable(array($ctrl, 'beforeFilter'))) {
            $rs = $ctrl->beforeFilter();
            if ( $rs !== NULL && $rs !== true ) return $rs;
        }
        return $ctrl->$method();
    }
    
   /**
     * 获取操作op
     *
     * @return string
     */
	public function getOp()
    {
		return self::$request->op;
	}

}