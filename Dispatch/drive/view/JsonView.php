<?php
namespace drive\view;

/**
 * Json视图
 * 
 * @author wangwei
 */
class JsonView extends ViewBase 
{
	const TYPENAME = 'json';

    /**
     * 构造函数
     *
     * @return
     */
	public function __construct($args = array()) {}
	
	/**
     * 输出
     * 
     * @param   array   $args 		参数
     *
     * @return void
     */
	public function display($args = null, $return = true) 
	{
		if (!is_array($args)) {
			return $args;
		}
		if (isset($_REQUEST['format'])) {
    		if (empty($_REQUEST['format']) || $_REQUEST['format'] == 'json') { // json格式
    			echo json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    			exit;
    		} elseif ($_REQUEST['format'] == 'msgpack') { // 二进制包
    			echo msgpack_serialize($args);
    			exit;
    		} elseif ($_REQUEST['format'] == 'array') {
    			print_r($args);
    			exit;
    		}
    	}

		echo json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		exit;
		$opSpendTime = _e(true);
		
		// 写入文件
		// 记录op分析日志
		// SELECT op, num /10000000 AS TIME FROM  `trace_20140806` WHERE TYPE =100 and op>0 and op <= 9201 having TIME >= 0.01 order by op desc;
    	
    	// 转发成txt
    	echo msgpack_serialize($args);
	}
	
}