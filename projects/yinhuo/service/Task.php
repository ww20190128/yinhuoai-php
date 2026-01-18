<?php
namespace service;

/**
 * Task 逻辑类
 * 
 * @author 
 */
class Task extends ServiceBase
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return Task
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Task();
        }
        return self::$instance;
    }

    /**
     * 任务
     * 
     * @return array
     */
    public function getTaskList()
    {
    	$taskDao = \dao\Task::singleton();
    	$taskEttList = $taskDao->readListByIndex(array(
    		'status' => 0,
    	));
    	$commonSv = \service\Common::singleton();
    	$taskList = array();
    	if (is_iteratable($taskEttList)) foreach ($taskEttList as $taskEtt) {
    		$taskList[] = array(
    			'id' => intval($taskEtt->id),
    			'title' => $taskEtt->title,
    			'detail' => $taskEtt->detail,
    			'goto' => $taskEtt->goto,
    			'award' => intval($taskEtt->award),
    			'from' => $taskEtt->from,
    			'status' => intval($taskEtt->status),
    			'updateTime' => intval($taskEtt->updateTime),
    			'createTime' => intval($taskEtt->createTime),
    		);
    	}
    	return $taskList;
    }

}