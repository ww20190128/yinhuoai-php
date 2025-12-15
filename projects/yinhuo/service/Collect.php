<?php
namespace service;

/**
 * 
 * 收藏
 * 
 * @author 
 */
class Collect extends ServiceBase
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
     * @return Collect
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Collect();
        }
        return self::$instance;
    }

    /**
     * 加入收藏或者取消收藏
     *
     * @return array
     */
    public function doCollect($userId, $testPaperId, $mindfulnessId)
    {
    	$userSv = \service\User::singleton();
    	$userInfo = $userSv->userInfo($userId);
    	$collectResult = $this->collectList($userId);
    	$collectId = 0;
    	if (!empty($testPaperId)) {
    		$testPaperDao = \dao\TestPaper::singleton();
    		$testPaperEtt = $testPaperDao->readByPrimary($testPaperId);
    		if (empty($testPaperEtt) || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('测评已删除');
    		}	
    		$collectList = empty($collectResult['testPapers']) ? array() : array_column($collectResult['testPapers'], null, 'id');
    		$collectId = empty($collectList[$testPaperId]) ? 0 : $collectList[$testPaperId]['collectId'];
    	} elseif (!empty($mindfulnessId)) {
    		$mindfulnessDao = \dao\Mindfulness::singleton();
    		$testPaperEtt = $mindfulnessDao->readByPrimary($mindfulnessId);
    		if (empty($testPaperEtt) || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
    			throw new $this->exception('课程已删除');
    		}
    		$collectList = empty($collectResult['mindfulnesss']) ? array() : array_column($collectResult['mindfulnesss'], null, 'id');
    		$collectId = empty($collectList[$mindfulnessId]) ? 0 : $collectList[$mindfulnessId]['collectId'];
    	} else {
    		return false;
    	}

        $collectStatus = 0;
        if (!empty($collectId)) { // 已收藏，给取消
        	$userCollectDao = \dao\UserCollect::singleton();
        	$userCollectEtt = $userCollectDao->readByPrimary($collectId);
        	$userCollectDao->remove($userCollectEtt);
        	$collectStatus = 0;
        } else {
        	$now = $this->frame->now;
        	$userCollectDao = \dao\UserCollect::singleton();
        	$userCollectEtt = $userCollectDao->getNewEntity();
        	$userCollectEtt->userId = $userId;
        	$userCollectEtt->testPaperId = $testPaperId;
        	$userCollectEtt->mindfulnessId = $mindfulnessId;
        	$userCollectEtt->createTime = $now;
        	$userCollectEtt->updateTime = $now;
        	$userCollectDao->create($userCollectEtt);
        	$collectStatus = 1;
        }
    	return array(
    		'collectStatus' => $collectStatus,
    	);
    }
    
    /**
     * 获取收藏记录
     *
     * @return array
     */
    public function collectList($userId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		return array();
    	}
    	$userCollectDao = \dao\UserCollect::singleton();
    	$userCollectEttList = $userCollectDao->readListByIndex(array(
    		'userId' => $userId,
    	));
    	$testPaperIds = array();
    	$mindfulnessIds = array();
    	if (is_iteratable($userCollectEttList)) foreach ($userCollectEttList as $key => $userCollectEtt) {
    		if (!empty($userCollectEtt->testPaperId)) {
    			$testPaperIds[$userCollectEtt->id] = intval($userCollectEtt->testPaperId);
    		}
    		if (!empty($userCollectEtt->mindfulnessId)) {
    			$mindfulnessIds[$userCollectEtt->id] = intval($userCollectEtt->mindfulnessId);
    		}
    	}
    	$testPaperDao = \dao\TestPaper::singleton();
    	$mindfulnessDao = \dao\Mindfulness::singleton();
    	$testPaperModels = array();
    	if (!empty($testPaperIds)) {
    		$testPaperEttList = $testPaperDao->readListByPrimary($testPaperIds);
    		if (is_iteratable($testPaperEttList)) foreach ($testPaperEttList as $testPaperEtt) {
    			$testPaperModel = $testPaperEtt->getModel();
    			$testPaperModel['collectId'] = array_search($testPaperModel['id'], $testPaperIds);
    			$testPaperModel['collectStatus'] = 1;
    			$testPaperModels[$testPaperModel['id']] = $testPaperModel;
    		}
    	}
    	$mindfulnessModels = array();
    	if (!empty($mindfulnessIds)) {
    		$mindfulnessSv = \service\Mindfulness::singleton();
    		$mindfulnessModels = $mindfulnessSv->getListByClassify($userId, $mindfulnessIds);
    		if (is_iteratable($mindfulnessModels)) foreach ($mindfulnessModels as $key => $mindfulnessModel) {
    			$mindfulnessModel['collectId'] = array_search($mindfulnessModel['id'], $mindfulnessIds);
    			$mindfulnessModel['collectStatus'] = 1;
    			$mindfulnessModels[$key] = $mindfulnessModel;
    		}
    	}

    	return array(
    		'testPapers' => array_values($testPaperModels),
    		'mindfulnesss' => array_values($mindfulnessModels),
    	);
    }
    
}