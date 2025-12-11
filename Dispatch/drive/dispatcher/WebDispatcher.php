<?php
namespace drive\dispatcher;
use Application;

/**
 * web请求调度接口
 *
 * @author wangwei
 */
class WebDispatcher extends DispatcherBase 
{
    /**
     * 构造函数
     *
     * @return void
     */
    public function __construct() 
    {
    	// 设置请求头信息
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        self::$request = (object)self::$request; 
        if (isset($_REQUEST['op']) && isset($GLOBALS['ACTION_MAP'][$_REQUEST['op']])) {
            $act = $GLOBALS['ACTION_MAP'][$_REQUEST['op']];
            self::$request->op = $_REQUEST['op'];
        } else {
        	$act = empty($_REQUEST['op']) ? 'Index' : $_REQUEST['op'];
        }
        strrchr($act, '.') or $act .= '.main';
        self::$request->act = $act;
        unset($_REQUEST['act']);
        self::$request->params = (object)$_REQUEST;
        parent::__construct();
        return;
    }
    
    /**
     * 解析请求url
     * 
     * @return string
     */
    private function parseUrlInfo()
    {
    	$scriptName = $_SERVER['SCRIPT_NAME'];
    	if (!empty($_SERVER['PATH_INFO'])) {
    		$pathInfo = $_SERVER['PATH_INFO'];
    		if (0 === strpos($pathInfo, $scriptName)){
    			$pathInfo = substr($pathInfo, strlen($_SERVER['SCRIPT_NAME']) - 1);
    		}
    	} else {
    		$pathInfo = $_SERVER['REQUEST_URI']; // 请求的ur
    		if (0 === strpos($pathInfo, $scriptName)) {
    			$pathInfo = substr($pathInfo, strlen($_SERVER['SCRIPT_NAME']));
    		}
    	}
    	return urldecode($pathInfo);
    }
    
    /**
     * 请求调度
     *
     * @return array
     * 
     * 'errorCode' => $errorCode, 'errorMsg' => $msgStr
     */
    public function distribute() {
        $result = array(
            'status' 	=> 0,
       		'errorCode' => '',
        //    'op' 		=> intval(self::$request->op),
            'data' 		=> parent::distribute(),
     	);
        // 将请求结果缓存
        $cache = Application::$Cache;
        if(!empty($cache) && !empty(self::$request->params->key) && !empty(self::$request->
            params->requestFlag)) {
            $result['requestFlag'] = self::$request->params->requestFlag;
            $cache->set('requestResult:' . self::$request->params->key, $result, 10);
        }
        return $result;
    }

}