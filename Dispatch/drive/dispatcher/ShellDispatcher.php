<?php
namespace drive\dispatcher;

/**
 * shell请求调度接口
 * 
 * @author wangwei
 */
class ShellDispatcher extends DispatcherBase
{
    /**
     * 构造函数
     *
     * @return
     */
    public function __construct() 
    {
    	$request = $_SERVER['argv'];
    	$act = array_shift($request);
    	self::$request = (object)self::$request;
    	if ($act) {
    		if (isset($GLOBALS['ACTION_MAP'][$act])) {
    			self::$request->op = $act;
    			$act = $GLOBALS['ACTION_MAP'][$act];
    		}
    	} else {
    		$act = 'Shell';
    	}
    	strrchr($act, '.') or $act .= '.main';
        self::$request->act = $act;
        $params = array();
        foreach($request as $index => $val) {
            if (preg_match('/^-([a-z_0-9]+)$/', $val, $items)) {
            	if (!empty($items)) {
            		$params[end($items)] = isset($request[$index + 1]) ? $request[$index + 1] : null;
            	}
            }
        }
        self::$request->params = (object)$params;
    	parent::__construct();
        return;
    }

}