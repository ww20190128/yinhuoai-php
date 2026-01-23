<?php
namespace service;

/**
 * 轮播图 逻辑类
 * 
 * @author 
 */
class Banner extends ServiceBase
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
     * @return Banner
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Banner();
        }
        return self::$instance;
    }

    /**
     * 轮播图
     *
     * @return array
     */
    public function getBannerList()
    {
    	// 轮播图
    	$bannerDao = \dao\Banner::singleton();
    	$bannerEttList = $bannerDao->readListByIndex(array(
    		'status' => 0,
    	));
    	$bannerList = array();
    	$backstageUserIds = array_column($bannerEttList, 'userId');
    	$backstageUserSv = \service\BackstageUser::singleton();
    	$backstageUserModels = $backstageUserSv->getBackstageUserModels($backstageUserIds);
    	if (is_iteratable($bannerEttList)) foreach ($bannerEttList as $bannerEtt) {
    		$bannerList[] = array(
    			'id' => intval($bannerEtt->id),
    			'url' => $bannerEtt->url,
    			'goto' => $bannerEtt->goto,
    			'userId' => intval($bannerEtt->userId),
    			'userInfo' => empty($backstageUserModels[$bannerEtt->userId]) 
    				? array() : $backstageUserModels[$bannerEtt->userId],
    			'createTime' => intval($bannerEtt->createTime),
    			'updateTime' => intval($bannerEtt->updateTime),
    		);
    	}

    	$commonSv = \service\Common::singleton();
    	uasort($bannerList, array($commonSv, 'sortByCreateTime'));
    	return $bannerList;
    }
    
    /**
     * 删除轮播图
     *
     * @return array
     */
    public function deleteBanner($backstageUserId, $ids)
    {
    	$backstageUserDao = \dao\BackstageUser::singleton();
    	$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
    	if (empty($backstageUserEtt) || $backstageUserEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$bannerDao = \dao\Banner::singleton();
    	$bannerEttList = $bannerDao->readListByPrimary($ids);
    	$removeEttList = array();
    	if (!empty($bannerEttList)) foreach ($bannerEttList as $bannerEtt) {
    		if ($bannerEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		if ($bannerEtt->userId != $backstageUserEtt->userId) {
    			throw new $this->exception('轮播图已删除');
    		}
    		$removeEttList[] = $bannerEtt;
    	}
    	if (!empty($removeEttList)) foreach ($removeEttList as $removeEtt) {
    		$bannerDao->remove($removeEtt);
    	}
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 修改轮播图
     *
     * @return array
     */
    public function reviseBanner($backstageUserId, $id, $info)
    {
    	$backstageUserDao = \dao\BackstageUser::singleton();
    	$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
    	if (empty($backstageUserEtt) || $backstageUserEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$bannerDao = \dao\Banner::singleton();
    	$bannerEtt = $bannerDao->readByPrimary($id);
    	if (empty($bannerEtt) || $bannerEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('轮播图已删除');
    	}
    	if ($bannerEtt->userId != $backstageUserEtt->userId) {
    		throw new $this->exception('轮播图已删除');
    	}
    	if (!empty($info['name'])) {
    		$bannerEtt->set('name', $info['name']);
    	}
    	if (!empty($info['goto'])) {
    		$bannerEtt->set('goto', $info['goto']);
    	}
    	if (!empty($info['urlData'])) { // 上传图片
    		$folderSv = \service\Folder::singleton();
    		$info['url'] = $folderSv->getUrlByConten(base64_decode($info['urlData']), time(), 'png');
    	}
    	if (!empty($info['url'])) {
    		$bannerEtt->set('url', $info['url']);
    	}
    	$now = $this->frame->now;
    	$bannerEtt->set('updateTime', $now);
    	$bannerDao->update($bannerEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 创建轮播图
     *
     * @return array
     */
    public function createBanner($backstageUserId, $info)
    {
    	$backstageUserDao = \dao\BackstageUser::singleton();
    	$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
    	if (empty($backstageUserEtt) || $backstageUserEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('用户不存在');
    	}
    	$url = '';
    	if (!empty($info['urlData'])) { // 上传图片
    		$folderSv = \service\Folder::singleton();
    		$url = $folderSv->getUrlByConten(base64_decode($info['urlData']), time(), 'png');
    	}
    	$now = $this->frame->now;
    	$bannerDao = \dao\Banner::singleton();
    	$bannerEtt = $bannerDao->getNewEntity();
    	$bannerEtt->userId = $backstageUserId;
    	$bannerEtt->name = $info['name'];
    	$bannerEtt->url = $url;
    	$bannerEtt->goto = $info['goto'];
    	$bannerEtt->createTime = $now;
    	$bannerEtt->updateTime = $now;
    	$bannerDao->create($bannerEtt);
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 轮播图详情
     *
     * @return array
     */
    public function bannerInfo($id)
    {
    	$bannerDao = \dao\Banner::singleton();
    	$bannerEtt = $bannerDao->readByPrimary($id);
    	if (empty($bannerEtt) || $bannerEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('轮播图已删除');
    	}
    	$backstageUserSv = \service\BackstageUser::singleton();
    	$backstageUserModels = $backstageUserSv->getBackstageUserModels(array($bannerEtt->userId));
    	return array(
    		'id' =>  intval($bannerEtt->id),
    		'name' => $bannerEtt->name,
    		'url' => $bannerEtt->url,
    		'goto' => $bannerEtt->goto,
    		'userId' => intval($bannerEtt->userId),
    		'createTime' => intval($bannerEtt->createTime),
    		'updateTime' => intval($bannerEtt->updateTime),
    		'userInfo' => empty($backstageUserModels[$bannerEtt->userId]) ? array() : $backstageUserModels[$bannerEtt->userId],
    	);
    }
    
}