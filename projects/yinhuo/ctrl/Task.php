<?php
namespace ctrl;

/**
 * 任务
 * 
 * @package ctrl
 */
class Task extends CtrlBase
{
	/**
     * 任务列表
     *
     * @return array
     */
    public function getTaskList()
    {
    	$params = $this->params;
    	$taskSv = \service\Task::singleton();
    	$dataList = $taskSv->getTaskList();
    	$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
    	$pageLimit = $this->paramFilter('pageLimit', 'intval', 200); // 每页数量限制
    	// 符合条件的总条数
    	$totalNum = count($dataList);
    	// 分页显示
    	$dataList = array_slice($dataList, ($pageNum - 1) * $pageLimit, $pageLimit);
    	return array(
    		'totalNum' => $totalNum,
    		'list' => array_values($dataList),
    	);
    }
    
}