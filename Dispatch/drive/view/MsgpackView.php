<?php
namespace drive\view;

/**
 * msgpack视图
 * 
 * @author wangwei
 */
class MsgpackView extends ViewBase 
{
	const TYPENAME = 'msgpack';

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
		if (isset($_REQUEST['debug'])) {
			if ($_REQUEST['debug'] == 'json') {
				echo json_encode($args);
				exit;
			} else {
				print_r($args);exit;
			}
		}

		echo msgpack_serialize($args);
		exit;
		$opSpendTime = _e(true);
		// 记录op分析日志
		// SELECT op, num /10000000 AS TIME FROM  `trace_20140806` WHERE TYPE =100 and op>0 and op <= 9201 having TIME >= 0.01 order by op desc;
    	
    	exit;
	}
	
}