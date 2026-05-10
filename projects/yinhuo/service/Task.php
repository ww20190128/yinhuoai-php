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
    	$backstageUserIds = array_column($taskEttList, 'userId');
    	$backstageUserSv = \service\BackstageUser::singleton();
    	$backstageUserModels = $backstageUserSv->getBackstageUserModels($backstageUserIds);
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
    			'userInfo' => empty($backstageUserModels[$taskEtt->userId]) 
    				? array() : $backstageUserModels[$taskEtt->userId],
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($taskList, array($commonSv, 'sortByCreateTime'));
    	return $taskList;
    }

    /**
     * 删除任务
     *
     * @return array
     */
    public function deleteTask($backstageUserId, $ids)
    {
    	$backstageUserDao = \dao\BackstageUser::singleton();
    	$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
    	if (empty($backstageUserEtt) || $backstageUserEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$taskDao = \dao\Task::singleton();
    	$taskEttList = $taskDao->readListByPrimary($ids);
    	$removeEttList = array();
    	if (!empty($taskEttList)) foreach ($taskEttList as $taskEtt) {
    		if ($taskEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		if ($taskEtt->userId != $backstageUserEtt->userId) {
    			throw new $this->exception('任务已删除');
    		}
    		$removeEttList[] = $taskEtt;
    	}
    	if (!empty($removeEttList)) foreach ($removeEttList as $removeEtt) {
    		$taskDao->remove($removeEtt);
    	}
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 修改任务
     *
     * @return array
     */
    public function reviseTask($backstageUserId, $id, $info)
    {
    	$backstageUserDao = \dao\BackstageUser::singleton();
    	$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
    	if (empty($backstageUserEtt) || $backstageUserEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$taskDao = \dao\Task::singleton();
    	$taskEtt = $taskDao->readByPrimary($id);
    	if (empty($taskEtt) || $taskEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('任务已删除');
    	}
    	if ($taskEtt->userId != $backstageUserEtt->userId) {
    		throw new $this->exception('轮播图已删除');
    	}
    	if (!empty($info['title'])) {
    		$taskEtt->set('title', $info['title']);
    	}
    	if (!empty($info['detail'])) {
    		$taskEtt->set('detail', $info['detail']);
    	}
    	if (!empty($info['goto'])) {
    		$taskEtt->set('goto', $info['goto']);
    	}
    	if (!empty($info['award'])) {
    		$taskEtt->set('award', $info['award']);
    	}
    	if (!empty($info['from'])) {
    		$taskEtt->set('from', $info['from']);
    	}
    	$now = $this->frame->now;
    	$taskEtt->set('updateTime', $now);
    	$taskDao->update($taskEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 创建任务
     *
     * @return array
     */
    public function createTask($backstageUserId, $info)
    {
    	$backstageUserDao = \dao\BackstageUser::singleton();
    	$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
    	if (empty($backstageUserEtt) || $backstageUserEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$now = $this->frame->now;
    	$taskDao = \dao\Task::singleton();
    	$taskEtt = $taskDao->getNewEntity();
    	$taskEtt->userId = $backstageUserId;
    	$taskEtt->title = empty($info['title']) ? '' : $info['title'];
    	$taskEtt->detail = empty($info['detail']) ? '' : $info['detail'];
    	$taskEtt->goto = empty($info['goto']) ? '' : $info['goto'];
    	$taskEtt->award = empty($info['award']) ? 0 : $info['award'];
    	$taskEtt->from = empty($info['from']) ? '' : $info['from'];
    	$taskEtt->createTime = $now;
    	$taskEtt->updateTime = $now;
    	$taskDao->create($taskEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 任务详情
     *
     * @return array
     */
    public function taskInfo($id)
    {
    	$taskDao = \dao\Task::singleton();
    	$taskEtt = $taskDao->readByPrimary($id);
    	if (empty($taskEtt) || $taskEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('任务已删除');
    	}
    	$backstageUserSv = \service\BackstageUser::singleton();
    	$backstageUserModels = $backstageUserSv->getBackstageUserModels(array($taskEtt->userId));
    	return array(
    		'id' => intval($taskEtt->id),
    		'title' => $taskEtt->title,
    		'detail' => $taskEtt->detail,
    		'goto' => $taskEtt->goto,
    		'award' => intval($taskEtt->award),
    		'from' => $taskEtt->from,
    		'status' => intval($taskEtt->status),
    		'updateTime' => intval($taskEtt->updateTime),
    		'createTime' => intval($taskEtt->createTime),
    		'userInfo' => empty($backstageUserModels[$taskEtt->userId]) ? array() : $backstageUserModels[$taskEtt->userId],
    	);
    }
    
}