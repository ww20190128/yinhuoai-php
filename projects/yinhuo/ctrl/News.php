<?php
namespace ctrl;

/**
 * 新闻资讯
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
    
    
}
	