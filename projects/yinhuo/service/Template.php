<?php
namespace service;

/**
 * 剪辑模板 逻辑类
 * 
 * @author 
 */
class Template extends ServiceBase
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
     * @return Template
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Template();
        }
        return self::$instance;
    }
    
    /**
     * 模板列表
     *
     * @return array
     */
    public function getTemplateList($userId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$templateDao = \dao\Template::singleton();
    	$templateEttList = $templateDao->readListByWhere("`userId`={$userId}");
    	$templateModels = array();
    	if (!empty($templateEttList)) foreach ($templateEttList as $templateEtt) {
    		if ($templateEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		$templateModels[$templateEtt->id] = array(
    			'id' 			=> intval($templateEtt->id),
    			'name'			=> $templateEtt->name,
    			'editingId'		=> intval($templateEtt->editingId),
    			'createTime' 	=> intval($templateEtt->createTime),
    			'updateTime' 	=> intval($templateEtt->updateTime),
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($templateModels, array($commonSv, 'sortByCreateTime'));
    	return $templateModels;
    }
    
    /**
     * 删除模板
     *
     * @return array
     */
    public function deleteTemplate($userId, $id)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$templateDao = \dao\Template::singleton();
    	$templateEtt = $templateDao->readByPrimary($id);
    	if (empty($templateEtt) || $templateEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('模板已删除');
    	}
    	if ($templateEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑工程已删除');
    	}
    	$templateDao->remove($templateEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 修改模板
     *
     * @return array
     */
    public function reviseTemplate($userId, $id, $info)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$templateDao = \dao\Template::singleton();
    	$templateEtt = $templateDao->readByPrimary($id);
    	if (empty($templateEtt) || $templateEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('模板已删除');
    	}
    	if ($templateEtt->userId != $userEtt->userId) {
    		throw new $this->exception('剪辑工程已删除');
    	}
    	if (!empty($info['name'])) {
    		$templateEtt->set('name', $info['name']);
    	}
    	$now = $this->frame->now;
    	$templateEtt->set('status', \constant\Common::DATA_DELETE);
    	$templateEtt->set('updateTime', $now);
    	$templateDao->update($templateEtt);
    	return array(
    		'name' => $templateEtt->name,
    	);
    }
    
}