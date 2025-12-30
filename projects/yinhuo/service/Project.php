<?php
namespace service;

/**
 * 剪辑工程 逻辑类
 * 
 * 1. 镜头素材 a个  		随机取1个
 * 1. 镜头数量N个
 * 2. 标题b个     		随机1个
 * 3. 镜头字幕 c个 		随机1个
 * 4. 演员配音d个 	          随机1个
 * 5. 背景音乐e个 		随机1个
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
     * 获取预览
     *
     * @return array
     */
    public function getPreview($userId, $editingId)
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
    	if ($editingEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑已删除');
    	}
    	$editingSv = \service\Editing::singleton();
    	$preview = empty($editingEtt->preview) ? array() : json_decode($editingEtt->preview, true);
    	$now = $this->frame->now;
    	$aliEditingSv = \service\AliEditing::singleton();
    	if (empty($preview['jobId'])) { // 没有生成预览任务
    		$editingInfo = $editingSv->editingInfo($userEtt, $editingEtt);
    		$chipParam = $editingSv->randomChipParam($editingInfo);
    		if (empty($chipParam)) {
    			throw new $this->exception('预览生成失败');
    		}
    		$tries = 3;
    		do {
    			$jobId = $aliEditingSv->submitMediaProducingJob($chipParam);
    		} while (empty($jobId) && --$tries > 0);
    		if (empty($jobId)) {
    			throw new $this->exception('预览生成失败');
    		}
    		$preview['createTime'] = $now;
    		$preview['jobId'] = $jobId;
    		$editingEtt->set('preview', json_encode($preview, JSON_UNESCAPED_UNICODE));
    		$editingEtt->set('updateTime', $now);
    		$editingDao->update($editingEtt);
    	} elseif (empty($preview['mediaURL'])) { // 没有生成视频
    		$tries = 3;
    		do {
    			$mediaProducingJob = $aliEditingSv->getMediaProducingJob($preview['jobId']);
    		} while (empty($mediaProducingJob) && --$tries > 0);
			if (empty($mediaProducingJob) || (!empty($mediaProducingJob['jobStatus']) && in_array($mediaProducingJob['jobStatus'], array('Failed')))) {
				$preview['jobId'] = '';
				$editingEtt->set('preview', json_encode($preview, JSON_UNESCAPED_UNICODE));
				$editingDao->update($editingEtt);
				throw new $this->exception('预览生成失败');
			}
			$preview['jobStatus'] = $mediaProducingJob['status'];
			$preview['mediaURL'] = empty($mediaProducingJob['mediaURL']) ? '' : $mediaProducingJob['mediaURL'];
			$preview['duration'] = empty($mediaProducingJob['duration']) ? '' : $mediaProducingJob['duration'];
			$editingEtt->set('preview', json_encode($preview, JSON_UNESCAPED_UNICODE));
			$editingEtt->set('updateTime', $now);
			$editingDao->update($editingEtt);
		}
    	return array(
    		'mediaURL' => empty($preview['mediaURL']) ? '' : $preview['mediaURL'],
    		'jobStatus' => empty($preview['jobStatus']) ? '' : $preview['jobStatus'],
    	);
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
    	
    	$templateDao = \dao\Template::singleton();
    	$now = $this->frame->now;
    	$projectId = '';
    	if (!empty($info['type']) && in_array($info['type'], array(1, 3))) { // 创建剪辑工程
    		$aliEditingSv = \service\AliEditing::singleton();
    		$tries = 3;
    		do {
    			$projectId = $aliEditingSv->createEditingProject($editingInfo); // 工程ID
    		} while (empty($projectId) && --$tries > 0);
    		// 是否保存为模板
    		if (empty($projectId)) {
    			throw new $this->exception('创建剪辑工程失败');
    		}
    		if (empty($info['numLimit']) || $info['numLimit'] <= 0) {
    			throw new $this->exception('请输入生成数量');
    		}
    		$projectDao = \dao\Project::singleton();
    		$projectEtt = $projectDao->getNewEntity();
    		$projectEtt->id = $projectId;
    		$projectEtt->editingId = $editingId;
    		$projectEtt->userId = $editingEtt->userId;
    		$projectEtt->numLimit = $info['numLimit']; // 成品数量
    		$projectEtt->name = empty($info['name']) ? $editingInfo['name'] : $info['name'];
    		$projectEtt->createTime = $now;
    		$projectEtt->updateTime = $now;
    		$projectDao->create($projectEtt);
    	}
    	$templateId = 0;
		if (!empty($info['type']) && in_array($info['type'], array(2,3))) { // 创建模板
			$templateEtt = $templateDao->getNewEntity();
			$templateEtt->editingId = $editingId;
			$templateEtt->name = empty($info['name']) ? $editingInfo['name'] : $info['name'];
			$templateEtt->projectId = $projectId;
			$templateEtt->userId = $editingEtt->userId;
			$templateEtt->createTime = $now;
			$templateEtt->updateTime = $now;
			$templateId = $templateDao->create($templateEtt);
		}
    	return array(
    		'projectId' => $projectId,
    		'templateId' => intval($templateId),
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
    			'id' 			=> $projectEtt->id,
    			'editingId' 	=> intval($projectEtt->editingId),
    			'name'			=> $projectEtt->name,
    			'createTime' 	=> intval($projectEtt->createTime),
    			'updateTime' 	=> intval($projectEtt->updateTime),
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($projectModels, array($commonSv, 'sortByCreateTime'));
    	return $projectModels;
    }
    
    /**
     * 预览列表
     *
     * @return array
     */
    public function getProjectPreviewList($userId, $id, $pageNum, $pageLimit)
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
    	$projectClipEttList = $projectClipDao->readListByIndex(array(
    		'projectId' => $id,
    	));
    	$numLimit = intval($projectEtt->numLimit);
    	// 可创建的总数量
    	$canCreateTotalNum = $numLimit - count($projectClipEttList);
    	$previewModels = array(); // 预览列表
    	$clipModels = array(); // 成品列表
    	if (!empty($projectClipEttList)) foreach ($projectClipEttList as $projectClipEtt) {
    		if ($projectClipEtt->status == \constant\Common::DATA_DELETE) { // 已删除
    			continue;
    		}
    		$projectClipModel = array(
    			'id' 			=> intval($projectClipEtt->id),
    			'mediaURL' 		=> $projectClipEtt->mediaURL,
    			'jobStatus' 	=> $projectClipEtt->jobStatus,
    			'previewUrl' 	=> $projectClipEtt->previewUrl,
    		);
    		if (!empty($projectClipEtt->mediaURL)) { // 已生成成片
    			$clipModels[$projectClipEtt->id] = $projectClipModel;
    		} else {
    			$previewModels[$projectClipEtt->id] = $projectClipModel;
    		}
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($previewModels, array($commonSv, 'sortByCreateTime'));
    	
    	$previewTotalNum = count($previewModels); // 预览总数
    	$editingSv = \service\Editing::singleton();
    	
    	// 分页显示
    	$dataList = array_slice($previewModels, ($pageNum - 1) * $pageLimit, $pageLimit);
    	$needCreateNum = 0;
    	if (count($dataList) < $pageLimit) {
    		$needCreateNum = $pageLimit - count($dataList);
    	}
    	$now = $this->frame->now;
    	$needCreateNum = min($needCreateNum, $canCreateTotalNum); // 需要创建的数量
    	if ($needCreateNum > 0) { // 创建
    		$editingInfo = $editingSv->editingInfo($userEtt, $projectEtt->editingId);
    		$chipParamList = array();
    		for ($index = 1; $index <= $needCreateNum; $index++) {
    			$chipParam = $editingSv->randomChipParam($editingInfo);
    			$chipParamList[$index] = $chipParam;
    		}
    		foreach ($chipParamList as $chipParam) {
    			$projectClipEtt = $projectClipDao->getNewEntity();
    			$projectClipEtt->projectId = $projectEtt->id;
    			$projectClipEtt->chipParam = json_encode($chipParam, true);
    			$projectClipEtt->previewUrl = $chipParam['previewUrl'];
    			$projectClipEtt->createTime = $now;
    			$projectClipEtt->updateTime = $now;
    			$projectClipId = $projectClipDao->create($projectClipEtt);
    			$projectClipModel = array(
    				'id' 			=> intval($projectClipId),
    				'mediaURL' 		=> $projectClipEtt->mediaURL,
    				'jobStatus' 	=> $projectClipEtt->jobStatus,
    				'previewUrl' 	=> $projectClipEtt->previewUrl,
    			);
    			$dataList[] = $projectClipModel;
    			$previewTotalNum++;
    			$canCreateTotalNum--;
    		}
    	}
    	return array(
    		'canCreateTotalNum' => $canCreateTotalNum,
    		'totalNum' => $previewTotalNum + $canCreateTotalNum,
    		'list' => array_values($dataList),
    	);
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
    	$projectClipEttList = $projectClipDao->readListByIndex(array(
    		'projectId' => $id,
    	));
    	$projectClipModels = array();
    	if (!empty($projectClipEttList)) foreach ($projectClipEttList as $projectClipEtt) {
    		if ($projectClipEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		if (empty($projectClipEtt->mediaURL)) { // 已生成成片
    			continue;
    		}
    		$projectClipModels[$projectEtt->id] = array(
    			'id' 			=> intval($projectClipEtt->id),
    			'mediaURL' 		=> $projectClipEtt->mediaURL,
    			'jobStatus' 	=> $projectClipEtt->jobStatus,
    			'previewUrl' 	=> $projectClipEtt->previewUrl,
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
    	// 删除云剪辑工程
    	$aliEditingSv = \service\AliEditing::singleton();
    	$tries = 3;
    	do {
    		$delete = $aliEditingSv->deleteEditingProjects($projectEtt->id);
    	} while (empty($delete) && --$tries > 0);
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
    	$projectIds = array();
    	if (!empty($projectClipEttList)) foreach ($projectClipEttList as $projectClipEtt) {
    		$projectIds[] = $projectClipEtt->projectId;
    	}
    	$projectDao = \dao\Project::singleton();
    	$projectEttList = $projectClipDao->readByPrimary($projectIds);
    	if (!empty($projectEttList)) foreach ($projectEttList as $projectEtt) {
	    	if ($projectEtt->status == \constant\Common::DATA_DELETE) {
	    		throw new $this->exception('剪辑工程已删除');
	    	}
	    	if ($projectEtt->userId != $userEtt->userId) {
	    		throw new $this->exception('剪辑工程已删除');
	    	}
    	}
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
     * 生成成品
     *
     * @return array
     */
    public function createProjectClips($userId, $ids)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$projectClipDao = \dao\ProjectClip::singleton();
    	$projectClipEttList = $projectClipDao->readByPrimary($ids);
   	 	$projectIds = array();
    	if (!empty($projectClipEttList)) foreach ($projectClipEttList as $projectClipEtt) {
    		$projectIds[] = $projectClipEtt->projectId;
    	}
    	$projectDao = \dao\Project::singleton();
    	$projectEttList = $projectClipDao->readByPrimary($projectIds);
    	if (!empty($projectEttList)) foreach ($projectEttList as $projectEtt) {
	    	if ($projectEtt->status == \constant\Common::DATA_DELETE) {
	    		throw new $this->exception('剪辑工程已删除');
	    	}
	    	if ($projectEtt->userId != $userEtt->userId) {
	    		throw new $this->exception('剪辑工程已删除');
	    	}
    	}
    	$now = $this->frame->now;
    	$aliEditingSv = \service\AliEditing::singleton();
    	$clipNum = 0;
    	if (!empty($projectClipEttList)) foreach ($projectClipEttList as $projectClipEtt) {
    		$chipParam = json_decode($projectClipEtt->chipParam, true);
			if (empty($chipParam) || empty($projectClipEtt->projectId)) {
				continue;
			}
			if (!empty($projectClipEtt->mediaURL)) { // 有生成
				continue;
			}
			$tries = 3;
			do {
				$jobId = $aliEditingSv->submitMediaProducingJob($chipParam);
			} while (empty($jobId) && --$tries > 0);
			if (empty($jobId)) {
				continue;
			}
			$projectClipEtt->set('jobId', $jobId);
			$projectClipEtt->set('updateTime', $now);
			$projectClipDao->update($projectClipEtt);
			$clipNum++;
    	}
    	return array(
    		'clipNum' => $clipNum,
    	);
    }
    
}