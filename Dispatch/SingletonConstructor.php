<?php
/**
 * 单例对象构造器
 * 
 * @author wangwei
 */
class SingletonConstructor
{
    /**
     * 单例对象列表
     *
     * @var array
     */
    private static $instances;

    /**
     * 获取单例对象的逻辑实现
     *
     * @param  string 	$className  类名
     * @param  array 	$args       参数数组
     *
     * @throws
     * @return \$classname  obj
     */
    public static function _get($className, $args = null)
    {
        if (!isset(self::$instances[$className])) {
            if (!class_exists($className)) { 
               throw new \Application::$Exception("您调用的类 {$className} 不存在！");
            }
            self::$instances[$className] = new $className($args);
        }
        return self::$instances[$className];
    }

    /**
     * 获取一个单例对象
     * 
     * @param  string	$className  类名
     * @param  array	$args       参数数组
     * 
     * @return \$classname  obj
     *
     *
     */
    public function get($className, $args = array())
    {
        $instance = self::_get($className, $args);
        if (is_array($args) && $args) {
            $instance->__construct($args);
        }
        return $instance;
    }
    
    /**
     * 魔法函数 - 方法自动加载
     * 
     * @param   string	$func	方法名
     * @param   array	$args   参数列表
     * 
     * @return void
     */
    public function __call($func, $args)
    {
    	if (in_array($func, array('getCtrl', 'getService', 'getDao'))) {  
    		return $this->get(lcfirst(ltrim($func, 'get')) . CS . array_shift($args), array_shift($args));
    	}
    	return;
    }
    
}