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
    	$commonSv = \service\Common::singleton();
    	$bannerList = array();
    	if (is_iteratable($bannerEttList)) foreach ($bannerEttList as $bannerEtt) {
    		$bannerList[] = array(
    			'id' => intval($bannerEtt->id),
    			'url' => $bannerEtt->url,
    			'goto' => $bannerEtt->goto,
    			'userId' => intval($bannerEtt->userId),
    			'createTime' => intval($bannerEtt->createTime),
    			'updateTime' => intval($bannerEtt->updateTime),
    		);
    	}
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
    	foreach ($bannerEttList as $bannerEtt) {
    		if ($bannerEtt->status == \constant\Common::DATA_DELETE) {
    			continue;
    		}
    		if ($bannerEtt->userId != $backstageUserEtt->userId) {
    			throw new $this->exception('轮播图已删除');
    		}
    		$bannerDao->remove($bannerEtt);
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
    	$now = $this->frame->now;
    	$bannerDao = \dao\Banner::singleton();
    	$bannerEtt = $bannerDao->getNewEntity();
    	$bannerEtt->editingId = $editingId;
    	$bannerEtt->userId = $backstageUserEtt->userId;
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
    	return array(
    		'id' =>  intval($bannerEtt->id),
    		'url' => $bannerEtt->url,
    		'goto' => $bannerEtt->goto,
    		'createTime' => intval($bannerEtt->createTime),
    		'updateTime' => intval($bannerEtt->updateTime),
    	);
    }
}