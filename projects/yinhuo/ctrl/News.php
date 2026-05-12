<?php
namespace ctrl;

/**
 * 资讯
 * 
 * @package ctrl
 */
class News extends CtrlBase
{

	/**
     * 新闻列表
     *
     * @return array
     */
    public function getNewsList()
    {
    	$params = $this->params;
    	$newsSv = \service\News::singleton();
    	$dataList = $newsSv->getNewsList();
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
     * 删除资讯
     *
     * @return array
     */
    public function deleteNews()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$ids = $this->paramFilter('ids', 'array');
    	if (empty($ids)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$newsSv = \service\News::singleton();
    	return $newsSv->deleteNews($this->userId, $ids);
    }
    
    /**
     * 修改资讯
     *
     * @return array
     */
    public function reviseNews()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$params = (array)$params;
    	$id = $this->paramFilter('id', 'intval', 0);
    	if (empty($id)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$info = array();
    	if (isset($params['title'])) { // 标题
    		$info['title'] = $this->paramFilter('title', 'string');
    	}
    	if (isset($params['content'])) { // 内容
    		$info['content'] = $this->paramFilter('contentHtml', 'string');
    	}
    	if (isset($params['source'])) { // 来源
    		$info['source'] = $this->paramFilter('source', 'string');
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
    
    	if (!empty($uploadFile) && !in_array($uploadFile['extension'], array('png', 'jpg', 'PNG', 'JPG'))) {
    		throw new $this->exception('请选择正确的图片');
    	}
    	$newsSv = \service\News::singleton();
    	return $newsSv->reviseNews($this->userId, $id, $info, $uploadFile);
    }
    
    /**
     * 创建资讯
     *
     * @return array
     */
    public function createNews()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$params = (array)$params;
    	$info = array();
    	if (!empty($params['title'])) {
    		$info['title'] = $this->paramFilter('title', 'string');
    	} else {
    		throw new $this->exception('请编辑标题');
    	}
    	if (!empty($params['source'])) {
    		$info['source'] = $this->paramFilter('source', 'string');
    	}
    	if (!empty($params['content'])) {
    		$info['content'] = $this->paramFilter('contentHtml', 'string');
    	} else {
    		throw new $this->exception('请编辑内容');
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
    		throw new $this->exception('请选择封面文件');
    	}
    	if (!in_array($uploadFile['extension'], array('png', 'jpg', 'PNG', 'JPG'))) {
    		throw new $this->exception('请选择正确的图片');
    	}
    	
    	$newsSv = \service\News::singleton();
    	return $newsSv->createNews($this->userId, $info, $uploadFile);
    }
    
    /**
     * 资讯详情
     *
     * @return array
     */
    public function newsInfo()
    {
    	$params = $this->params;
    	$id = $this->paramFilter('id', 'intval', 0);
    	if (empty($id)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$newsSv = \service\News::singleton();
    	return $newsSv->newsInfo($id);
    }
    
}