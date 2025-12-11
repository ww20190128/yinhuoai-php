<?php
namespace ctrl;

/**
 * 管理后台
 * 
 * @author 
 */
class Backstage extends CtrlBase
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
     * @return Backstage
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Backstage();
        }
        return self::$instance;
    }
    
	/**
	 * 获取选择项
	 *
	 * @return array
	 */
	public function getSelectItems()
	{
		$params = $this->params;
		$backstageSv = \service\Backstage::singleton();
		$staticSelectItems = $backstageSv->getSelectItems();
		foreach ($staticSelectItems as $key => $list) {
			$staticSelectItems[$key] = array_values($list);
		}
		return $staticSelectItems;
	}
	
	/**
	 * 权限
	 *
	 * @return array
	 */
	public function getPrivileges()
	{
		$params = $this->params;
		$backstageSv = \service\Backstage::singleton();
		$privileges = $backstageSv->getPrivileges();
		return array(
			'list' => array_values($privileges),
		);
	}
	
	/**
	 * 静态信息
	 *
	 * @return array
	 */
	public function staticInfo()
	{
		// 获取用户的权限信息
		$backstageSv = \service\Backstage::singleton();
		$privilegeTree = $backstageSv->privilegeTree();
		return array(
			'privilegeTree' => $privilegeTree,  // 权限树
		);
	}
	
	/**
	 * 获取分销订单
	 *
	 * @return array
	 */
	public function distributeOrderList()
	{
		$params = $this->params;
		if (empty($params->userId)) {
			throw new $this->exception('请求参数错误');
		}
		$searchStartTime = $this->paramFilter('searchStartTime', 'intval'); // 开始时间
		$searchEndTime = $this->paramFilter('searchEndTime', 'intval'); // 结束时间
		$searchTestPaperId = $this->paramFilter('searchTestPaperId', 'intval'); // 搜索的测评
		$searchShareUserId = $this->paramFilter('searchShareUserId', 'intval'); // 搜索的推荐者
		$info = array(
			'searchStatus' => $this->paramFilter('searchStatus', 'intval'),
		);
		if (!empty($searchStartTime)) {
			$info['searchStartTime'] = empty($searchStartTime) ? 0 : strtotime($searchStartTime);
		}
		if (!empty($searchEndTime)) {
			$info['searchEndTime'] = empty($searchEndTime) ? 0 : strtotime($searchEndTime) + 86399;
		}
		if (!empty($searchTestPaperId)) {
			$info['searchTestPaperIds'] = array($searchTestPaperId);
		}
		if (!empty($searchShareUserId)) {
			$info['searchShareUserIds'] = array($searchShareUserId);
		}
		$pageNum = $this->paramFilter('pageNum', 'intval');  	// 页码
		$pageLimit = $this->paramFilter('pageLimit', 'intval'); // 每页数量限制
		$backstageSv = \service\Backstage::singleton();
		$result = $backstageSv->distributeOrderList($params->userId, $info, $pageNum, $pageLimit);
		return $result;
	}
	
	/**
	 * 分销订单导出
	 *
	 * @return array
	 */
	public function distributeOrderExport()
	{
		$params = $this->params;
		if (empty($params->userId)) {
			throw new $this->exception('请求参数错误');
		}
		$searchStartTime = $this->paramFilter('searchStartTime', 'intval'); // 开始时间
		$searchEndTime = $this->paramFilter('searchEndTime', 'intval'); // 结束时间
		$searchTestPaperId = $this->paramFilter('searchTestPaperId', 'intval'); // 搜索的测评
		$searchShareUserId = $this->paramFilter('searchShareUserId', 'intval'); // 搜索的推荐者
		$info = array(
			'searchStatus' => $this->paramFilter('searchStatus', 'intval'),
		);
		if (!empty($searchStartTime)) {
			$info['searchStartTime'] = empty($searchStartTime) ? 0 : strtotime($searchStartTime);
		}
		if (!empty($searchEndTime)) {
			$info['searchEndTime'] = empty($searchEndTime) ? 0 : strtotime($searchEndTime) + 86399;
		}
		if (!empty($searchTestPaperId)) {
			$info['searchTestPaperIds'] = array($searchTestPaperId);
		}
		if (!empty($searchShareUserId)) {
			$info['searchShareUserIds'] = array($searchShareUserId);
		}
		$backstageSv = \service\Backstage::singleton();
		$result = $backstageSv->distributeOrderExport($params->userId, $info);
		return $result;
	}
	
	/**
	 * 创建分享码
	 *
	 * @return array
	 */
	public function getShareCodeList()
	{
		$params = $this->params;
		if (empty($params->userId)) {
			throw new $this->exception('请求参数错误');
		}
		$searchTestPaperId = $this->paramFilter('searchTestPaperId', 'intval'); // 搜索的测评
		$searchShareUserId = $this->paramFilter('searchShareUserId', 'intval'); // 搜索的推荐者

		$backstageSv = \service\Backstage::singleton();
    	return $backstageSv->getShareCodeList($params->userId, $searchShareUserId, $searchTestPaperId);
	}
	
	/**
	 * 申请合作
	 *
	 * @return array
	 */
	public function businessApply()
	{
		$params = $this->params;
		$info = array(
			'phone' => $this->paramFilter('phone', 'string', 0),
			'nickname' => $this->paramFilter('nickname', 'string', 0),
			'weChat' => $this->paramFilter('weChat', 'string', 0),
			'accounts' => $this->paramFilter('accounts', 'string', 0),
		);
		$backstageSv = \service\Backstage::singleton();
		return $backstageSv->businessApply($info);
	}
	
	/**
	 * 获取推广测评
	 *
	 * @return array
	 */
	public function promotionList()
	{
		$params = $this->params;
		$backstageSv = \service\Backstage::singleton();
		return $backstageSv->promotionList($params->userId);
	}
	
	/**
	 * 获取优惠券配置
	 *
	 * @return array
	 */
	public function couponList()
	{
		$params = $this->params;
		$backstageSv = \service\Backstage::singleton();
		return $backstageSv->couponList($params->userId);
	}
}