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
    	$pageLimit = $this->paramFilter('pageLimit', 'intval', 200); // 每页数量限制
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
     * 修改轮播图
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
    	if (isset($params['title'])) {
    		$info['title'] = $this->paramFilter('title', 'string');
    	}
    	if (isset($params['source'])) {
    		$info['source'] = $this->paramFilter('source', 'string');
    	}
    	if (isset($params['content'])) {
    		$info['content'] = $this->paramFilter('content', 'string');
    	}
    	$newsSv = \service\News::singleton();
    	return $newsSv->reviseNews($this->userId, $id, $info);
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
    	if (!empty($params['name'])) {
    		$info['name'] = $this->paramFilter('name', 'string');
    	}
    	if (!empty($params['numLimit'])) {
    		$info['numLimit'] = $this->paramFilter('numLimit', 'intval');
    		$info['numLimit'] = min($info['numLimit'], 2000);
    	}
    	$newsSv = \service\News::singleton();
    	return $newsSv->createNews($this->userId, $info);
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