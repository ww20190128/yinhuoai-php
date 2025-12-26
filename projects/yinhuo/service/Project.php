<?php
namespace service;

/**
 * 剪辑工程 逻辑类
 * 
 * @author 
 */
class Project extends ServiceBase
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
     * @return Project
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Project();
        }
        return self::$instance;
    }
    
    /**
     * 创建剪辑工程
     *
     * @return array
     */
    public function createProject($userId, $editingId, $info)
    {
    	$editingDao = \dao\Editing::singleton();
    	$editingEtt = $editingDao->readByPrimary($editingId);
    	if (empty($editingEtt) || $editingEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	if ($editingEtt->userId != $userId) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$editingSv = \service\Editing::singleton();
    	$editingInfo = $editingSv->editingInfo($userEtt, $editingEtt);
    	$aliEditingSv = \service\AliEditing::singleton();
    	$projectId = $aliEditingSv->createEditingProject($editingInfo); // 工程ID
//$projectId = '0d729ee22f18401d8fe71022accacf1e';
    	// 是否保存为模板
    	if (empty($projectId)) {
    		throw new $this->exception('创建剪辑工程失败');
    	}
    	// 创建剪辑工程
    	$now = $this->frame->now;
		$projectDao = \dao\Project::singleton();
		$projectEtt = $projectDao->getNewEntity();
		$projectEtt->editingId = $editingId;
		$projectEtt->name = empty($info['name']) ? $editingInfo['name'] : $info['name'];
		$projectEtt->projectId = $projectId;
		$projectEtt->createTime = $now;
		$projectEtt->updateTime = $now;
		$proProjectId = $projectDao->create($projectEtt);
		
		if (!empty($info['savaTemplate'])) { // 创建模板
			$templateDao = \dao\Template::singleton();
			$templateEtt = $templateDao->getNewEntity();
			$templateEtt->editingId = $editingId;
			$templateEtt->name = empty($info['name']) ? $editingInfo['name'] : $info['name'];
			$templateEtt->projectId = $proProjectId;
			$templateEtt->createTime = $now;
			$templateEtt->updateTime = $now;
			$templateDao->create($templateEtt);
		}
    	return array(
    		'projectId' => $proProjectId,	
    	);
    }
    
    /**
     * 剪辑工程列表
     *
     * @return array
     */
    public function getProjectList($userId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$projectDao = \dao\Project::singleton();
    	$projectEttList = $projectDao->readListByWhere("`userId`={$userId}");
    	$projectModels = array();
    	if (!empty($projectEttList)) foreach ($projectEttList as $projectEtt) {
    		if ($projectEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$projectModels[$projectEtt->id] = array(
    			'id' 			=> intval($projectEtt->id),
    			'editingId' 	=> intval($projectEtt->editingId),
    			'name'			=> $projectEtt->name,
    			'createTime' 	=> intval($projectEtt->createTime),
    			'updateTime' 	=> intval($projectEtt->updateTime),
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($projectModels, array($commonSv, 'sortByCreateTime'));
    	return $projectModels;
    	return $projectModels;
    }
    
    /**
     * 成品列表
     *
     * @return array
     */
    public function getProjectClipList($userId, $id)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$projectDao = \dao\Project::singleton();
    	$projectEtt = $projectDao->readByPrimary($id);
    	if (empty($projectEtt) || $projectEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑工程已删除');
    	}
    	if ($projectEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑工程已删除');
    	}
    	$projectClipDao = \dao\ProjectClip::singleton();
    	$projectClipEttList = array(
    		'projectId' => $id,
    	);
    	
    	$projectClipModels = array();
    	if (!empty($projectClipEttList)) foreach ($projectClipEttList as $projectClipEtt) {
    		if ($projectClipEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$projectModels[$projectEtt->id] = array(
    			'id' 			=> intval($projectClipEtt->id),
    			'url'			=> $projectClipEtt->url,
    			'createTime' 	=> intval($projectClipEtt->createTime),
    			'updateTime' 	=> intval($projectClipEtt->updateTime),
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($projectClipModels, array($commonSv, 'sortByCreateTime'));
    	return $projectClipModels;
    }
    
    /**
     * 删除剪辑工程
     *
     * @return array
     */
    public function deleteProject($userId, $id)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$projectDao = \dao\Project::singleton();
    	$projectEtt = $projectDao->readByPrimary($id);
    	if (empty($projectEtt) || $projectEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑工程已删除');
    	}
    	if ($projectEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑工程已删除');
    	}
    	$now = $this->frame->now;
    	$projectEtt->set('status', \constant\Common::DATA_DELETE);
    	$projectEtt->set('updateTime', $now);
    	$projectDao->update($projectEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 修改剪辑工程
     *
     * @return array
     */
    public function reviseProject($userId, $id, $info)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$projectDao = \dao\Project::singleton();
    	$projectEtt = $projectDao->readByPrimary($id);
    	if (empty($projectEtt) || $projectEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('剪辑工程已删除');
    	}
    	if ($projectEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑工程已删除');
    	}
    	if (!empty($info['name'])) {
    		$projectEtt->set('name', $info['name']);
    	}
    	$now = $this->frame->now;
    	$projectEtt->set('status', \constant\Common::DATA_DELETE);
    	$projectEtt->set('updateTime', $now);
    	$projectDao->update($projectEtt);
    	return array(
    		'name' => $projectEtt->name,
    	);
    }
    
    /**
     * 删除成品
     *
     * @return array
     */
    public function deleteProjectClips($userId, $ids)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$now = $this->frame->now;
    	$projectClipDao = \dao\ProjectClip::singleton();
    	$projectClipEttList = $projectClipDao->readByPrimary($ids);
    	if (!empty($projectClipEttList)) foreach ($projectClipEttList as $projectClipEtt) {
    		$projectClipEtt->set('status', \constant\Common::DATA_DELETE);
    		$projectClipEtt->set('updateTime', $now);
    		$projectClipDao->update($projectClipEtt);
    	}
    	return array(
    			'result' => 1,
    	);
    }
    
    /**
     * 成品重试
     *
     * @return array
     */
    public function resetProjectClips($userId, $ids)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$now = $this->frame->now;
    	$projectClipDao = \dao\ProjectClip::singleton();
    	$projectClipEttList = $projectClipDao->readByPrimary($ids);
    	if (!empty($projectClipEttList)) foreach ($projectClipEttList as $projectClipEtt) {
    		$projectClipEtt->set('status', \constant\Common::DATA_DELETE);
    		$projectClipEtt->set('updateTime', $now);
    		$projectClipDao->update($projectClipEtt);
    	}
    	return array(
    		'result' => 1,
    	);
    }
}