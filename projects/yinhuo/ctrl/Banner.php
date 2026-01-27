<?php
namespace ctrl;

/**
 * 轮播图
 * 
 * @package ctrl
 */
class Banner extends CtrlBase
{
	/**
     * 轮播图
     *
     * @return array
     */
    public function getBannerList()
    {
    	$params = $this->params;
    	$bannerSv = \service\Banner::singleton();
    	$dataList = $bannerSv->getBannerList();
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
     * 删除轮播图
     *
     * @return array
     */
    public function deleteBanner()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$ids = $this->paramFilter('ids', 'array');
    	if (empty($ids)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$bannerSv = \service\Banner::singleton();
    	return $bannerSv->deleteBanner($this->userId, $ids);
    }
    
    /**
     * 修改轮播图
     *
     * @return array
     */
    public function reviseBanner()
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
    	$name = $this->paramFilter('name', 'string');
    	if (!empty($name)) {
    		$info['name'] = $name;
    	}
    	$goto = $this->paramFilter('goto', 'string');
    	if (!empty($goto)) {
    		$info['goto'] = $goto;
    	}
    
    	$bannerSv = \service\Banner::singleton();
    	return $bannerSv->reviseBanner($this->userId, $id, $info);
    }
    
    /**
     * 创建轮播图
     *
     * @return array
     */
    public function createBanner()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$info = array();
    	$name = $this->paramFilter('name', 'string');
    	if (!empty($name)) {
    		$info['name'] = $name;
    	}
    	$goto = $this->paramFilter('goto', 'string');
    	if (!empty($goto)) {
    		$info['goto'] = $goto;
    	}
    	$files = empty($_FILES) ? array() : $_FILES; // 上传的图片信息
		$uploadFile = array();
		if (!empty($files)) {
			if (is_iteratable($files)) foreach ($files as $key => $file) {
				$fileInfo = pathinfo($file['name']);
				$uploadFile = array(
					'extension' => $fileInfo['extension'],
					'file' 		=> $file["tmp_name"],
					'name' 		=> $file["name"],
				);
			}
		}
		if (empty($uploadFile)) {
			throw new $this->exception('请选择图片文件');
		}
		if (!in_array($uploadFile['extension'], array('png', 'jpg', 'PNG', 'JPG'))) {
			throw new $this->exception('请选择正确的图片');
		}
    	$bannerSv = \service\Banner::singleton();
    	return $bannerSv->createBanner($this->userId, $info, $uploadFile);
    }
    
    /**
     * 轮播图详情
     *
     * @return array
     */
    public function bannerInfo()
    {
    	$params = $this->params;
    	$id = $this->paramFilter('id', 'intval', 0);
    	if (empty($id)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$bannerSv = \service\Banner::singleton();
    	return $bannerSv->bannerInfo($id);
    }
    
}