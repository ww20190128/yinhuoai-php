<?php
namespace drive\exception;
use Exception;
use Application;

/**
 * 自定义异常类
 */
class AppException extends Exception
{
	/**
     * 抛出异常 返回
     *
     * @param   $msgStr    	string      异常消息
     * @param   $params    	array       特殊替换参数
     * @param   $code		bool  		是否以错误码格式输出
     *
     * @throws \Exception
     *
     * @return
     */
    public function __construct($msgStr = null, $params = array(), $code = true)
    {
    	if ($code) {
	    	try {
	    		$errorCode = cfg('error.' . $msgStr);
	    	} catch (Exception $e) {
	    		$errorCode = 0; //  错误码未定义
	    	}
	    	$data = array();
	    	if (!empty($params['status'])) { // 兼容登录弹窗
	    		$data = $params;
	    	}
	    	// 请求OP
			$clientOP = Application::$Dispatcher ? Application::$Dispatcher->getOp() : null;
			$output = array(
				'status' 		=> isset($params['status']) ? intval($params['status']) : 1,
				'errorCode'	 	=> (String)$msgStr,
				//'op'	 		=> intval($clientOP),
				'data'	 		=> (object)$data,
			);

    		if (Application::$View) {
        		$view = new Application::$View();
        		return $view->display($output, false);
    		} else {
        		print_r($output);
    		}
            exit;
    	} else {
    		throw new Exception(_v((empty($msgStr) || !is_string($msgStr) ? null : $msgStr), $params));
    	}
    	return ;
    }
    
}