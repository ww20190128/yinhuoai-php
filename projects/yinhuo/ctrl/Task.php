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
    	$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
    	// 符合条件的总条数
    	$totalNum = count($dataList);
    	// 分页显示
    	$dataList = array_slice($dataList, ($pageNum - 1) * $pageLimit, $pageLimit);
    	return array(
    		'totalNum' => $totalNum,
    		'list' => array_values($dataList),
    	);
    }
    
    /**
     * 删除任务
     *
     * @return array
     */
    public function deleteTask()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$ids = $this->paramFilter('ids', 'array');
    	if (empty($ids)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$taskSv = \service\Task::singleton();
    	return $taskSv->deleteTask($this->userId, $ids);
    }
    
    /**
     * 修改任务
     *
     * @return array
     */
    public function reviseTask()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$id = $this->paramFilter('id', 'intval', 0);
    	if (empty($id)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$info = array();
    	$title = $this->paramFilter('title', 'string');
    	if (!empty($title)) {
    		$info['title'] = $title;
    	}
    	$detail = $this->paramFilter('detail', 'string');
    	if (!empty($detail)) {
    		$info['detail'] = $detail;
    	}
    	$goto = $this->paramFilter('goto', 'string');
    	if (!empty($goto)) {
    		$info['goto'] = $goto;
    	}
    	$award = $this->paramFilter('award', 'string');
    	if (!empty($award)) {
    		$info['award'] = $award;
    	}
    	$from = $this->paramFilter('from', 'string');
    	if (!empty($from)) {
    		$info['from'] = $from;
    	}
    	$taskSv = \service\Task::singleton();
    	return $taskSv->reviseTask($this->userId, $id, $info);
    }
    
    /**
     * 创建任务
     *
     * @return array
     */
    public function createTask()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$info = array();
    	$title = $this->paramFilter('title', 'string');
    	if (!empty($title)) {
    		$info['title'] = $title;
    	}
    	$detail = $this->paramFilter('detail', 'string');
    	if (!empty($detail)) {
    		$info['detail'] = $detail;
    	}
    	$goto = $this->paramFilter('goto', 'string');
    	if (!empty($goto)) {
    		$info['goto'] = $goto;
    	}
    	$award = $this->paramFilter('award', 'string');
    	if (!empty($award)) {
    		$info['award'] = $award;
    	}
    	$from = $this->paramFilter('from', 'string');
    	if (!empty($from)) {
    		$info['from'] = $from;
    	}
    	$taskSv = \service\Task::singleton();
    	return $taskSv->createTask($this->userId, $info);
    }
    
    /**
     * 任务详情
     *
     * @return array
     */
    public function taskInfo()
    {
    	$params = $this->params;
    	$id = $this->paramFilter('id', 'intval', 0);
    	if (empty($id)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$taskSv = \service\Task::singleton();
    	return $taskSv->taskInfo($id);
    }
    
}