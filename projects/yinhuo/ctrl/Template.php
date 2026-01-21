<?php
namespace ctrl;

/**
 * 剪辑模板
 * 
 * @package ctrl
 */
class Template extends CtrlBase
{
	/**
	 * 获取剪辑工程列表
	 *
	 * @return array
	 */
	public function getTemplateList()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$templateSv = \service\Template::singleton();
		$dataList = $templateSv->getTemplateList($this->userId);
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
	 * 删除剪辑工程
	 *
	 * @return array
	 */
	public function deleteTemplate()
	{
		$params = $this->params;
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$id = $this->paramFilter('id', 'intval', 0);
		if (empty($id)) {
			throw new $this->exception('请求参数错误');
		}
		$templateSv = \service\Template::singleton();
		return $templateSv->deleteTemplate($this->userId, $id);
	}
	
	/**
	 * 修改模板
	 *
	 * @return array
	 */
	public function reviseTemplate()
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
		if (isset($params['name'])) {
			$info['name'] = $this->paramFilter('name', 'string');
		}	
		$templateSv = \service\Template::singleton();
		return $templateSv->reviseTemplate($this->userId, $id, $info);
	}
	
}