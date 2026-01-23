<?php
namespace service;

/**
 * 新闻资讯 逻辑类
 * 
 * @author 
 */
class News extends ServiceBase
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
     * @return News
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new News();
        }
        return self::$instance;
    }

    /**
     * 资讯
     *
     * @return array
     */
    public function getNewsList()
    {
    	$newsDao = \dao\News::singleton();
    	$newsEttList = $newsDao->readListByIndex(array(
    		'status' => 0,
    	));
    	$backstageUserIds = array_column($newsEttList, 'userId');

    	$backstageUserSv = \service\BackstageUser::singleton();
    	$backstageUserModels = $backstageUserSv->getBackstageUserModels($backstageUserIds);
    	
    	$newsList = array();
    	if (is_iteratable($newsEttList)) foreach ($newsEttList as $newsEtt) {
    		$newsList[] = array(
    			'id' => intval($newsEtt->id),
    			'title' => $newsEtt->title,
    			'content' => $newsEtt->content,
    			'source' => $newsEtt->source,
    			'coverURL' => $newsEtt->coverURL,
    			'status' => intval($newsEtt->status),
    			'updateTime' => intval($newsEtt->updateTime),
    			'createTime' => intval($newsEtt->createTime),
    			'userInfo' => empty($backstageUserModels[$newsEtt->userId])
    				? array() : $backstageUserModels[$newsEtt->userId],
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	uasort($newsList, array($commonSv, 'sortByCreateTime'));
    	return $newsList;
    }

    /**
     * 删除资讯
     *
     * @return array
     */
    public function deleteNews($backstageUserId, $ids)
    {
    	$backstageUserDao = \dao\BackstageUser::singleton();
    	$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
    	if (empty($backstageUserEtt) || $backstageUserEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$newsDao = \dao\News::singleton();
    	$newsEttList = $newsDao->readListByPrimary($ids);
    	$removeEttList = array();
    	if (!empty($newsEttList)) foreach ($newsEttList as $newsEtt) {
    		if ($newsEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		if ($newsEtt->userId != $backstageUserEtt->userId) {
    			throw new $this->exception('轮播图已删除');
    		}
    		$removeEttList[] = $newsEtt;
    	}
    	if (!empty($removeEttList)) foreach ($removeEttList as $removeEtt) {
    		$newsDao->remove($removeEtt);
    	}
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 修改资讯
     *
     * @return array
     */
    public function reviseNews($backstageUserId, $id, $info)
    {
    	$backstageUserDao = \dao\BackstageUser::singleton();
    	$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
    	if (empty($backstageUserEtt) || $backstageUserEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$newsDao = \dao\News::singleton();
    	$newsEtt = $newsDao->readByPrimary($id);
    	if (empty($newsEtt) || $newsEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('资讯已删除');
    	}
    	if ($newsEtt->userId != $backstageUserEtt->userId) {
    		throw new $this->exception('资讯已删除');
    	}
    	if (!empty($info['title'])) {
    		$newsEtt->set('title', $info['title']);
    	}
    	if (!empty($info['content'])) {
    		$newsEtt->set('content', $info['content']);
    	}
    	if (!empty($info['source'])) {
    		$newsEtt->set('source', $info['source']);
    	}
    	if (!empty($info['coverURL'])) {
    		$newsEtt->set('coverURL', $info['coverURL']);
    	}
    	$now = $this->frame->now;
    	$newsEtt->set('updateTime', $now);
    	$newsDao->update($newsEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 创建资讯
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
    	$newsDao = \dao\News::singleton();
    	$newsEtt = $newsDao->getNewEntity();
    	$newsEtt->userId = $backstageUserId;
    	$newsEtt->title = $info['title'];
    	$newsEtt->content = $info['content'];
    	$newsEtt->source = $info['source'];
    	$newsEtt->coverURL = $info['coverURL'];
    	$newsEtt->createTime = $now;
    	$newsEtt->updateTime = $now;
    	$newsDao->create($newsEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 资讯详情
     *
     * @return array
     */
    public function newsInfo($id)
    {
    	$newsDao = \dao\News::singleton();
    	$newsEtt = $newsDao->readByPrimary($id);
    	if (empty($newsEtt) || $newsEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('资讯已删除');
    	}
    	$backstageUserSv = \service\BackstageUser::singleton();
    	$backstageUserModels = $backstageUserSv->getBackstageUserModels(array($newsEtt->userId));
    	return array(
    		'id' => intval($newsEtt->id),
    		'title' => $newsEtt->title,
    		'content' => $newsEtt->content,
    		'source' => $newsEtt->source,
    		'coverURL' => $newsEtt->coverURL,
    		'status' => intval($newsEtt->status),
    		'updateTime' => intval($newsEtt->updateTime),
    		'createTime' => intval($newsEtt->createTime),
    		'userInfo' => empty($backstageUserModels[$newsEtt->userId]) ? array() : $backstageUserModels[$newsEtt->userId],
    	);
    }
    
}