<?php
namespace service;
require_once('vendor/autoload.php');
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * 管理后台	 逻辑类
 *
 * @author
*/
class Backstage extends ServiceBase
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
	 * 获取可选项(知识点，标签，模板有修改时，清除缓存)
	 *
	 * @return array
	 */
	public function getSelectItems()
	{
		// 获取账号
		$userDao = \dao\User::singleton();
		$userEttList = $userDao->readListByWhere("`type` = 1");
		$userModelList = array();
		foreach ($userEttList as $userEtt) {
			$userModelList[] = $userEtt->getModel();
		}
		
		$classifySv = \service\Classify::singleton();
		// 可分享的测评
		$testPaperList = $classifySv->getListByClassify(888, array(), 1, 200);
		$testPaperList = empty($testPaperList['list']) ? array() : $testPaperList['list'];
		// 佣金状态
    	$brokerageStatusArr = array(
    		\constant\Order::BROKERAGE_STATUS_NOT_APPLY => array(
    			'id'	=> \constant\Order::BROKERAGE_STATUS_NOT_APPLY,
    			'name'	=> '未申请',
    		),
    		\constant\Order::BROKERAGE_STATUS_IN_REVIEW => array(
    			'id'	=> \constant\Order::BROKERAGE_STATUS_IN_REVIEW,
    			'name'	=> '审核中',
    		),
    		\constant\Order::BROKERAGE_STATUS_APPROVE => array(
    			'id'	=> \constant\Order::BROKERAGE_STATUS_APPROVE,
    			'name'	=> '已审核', // 审核通过
    		),
    		\constant\Order::BROKERAGE_STATUS_FAIL_AUDIT => array(
    			'id'	=> \constant\Order::BROKERAGE_STATUS_FAIL_AUDIT,
    			'name'	=> '审核未通过',
    		),
    		\constant\Order::BROKERAGE_STATUS_RECEIVED => array(
    			'id'	=> \constant\Order::BROKERAGE_STATUS_RECEIVED,
    			'name'	=> '已到账',
    		),
    	);
		return array(
			'brokerageStatusList' => array_values($brokerageStatusArr),
			'userList' => array_values($userModelList),
			'testPaperList' => array_values($testPaperList),
		);
	}
	
	/**
	 * 权限列表
	 *
	 * @return array
	 */
	public function getPrivileges()
	{
		$backstagePrivilegeDao = \dao\BackstagePrivilege::singleton();
		$privilegeEttList = $backstagePrivilegeDao->readListByWhere();
		$privilegeModels = array();
		if (is_iteratable($privilegeEttList)) foreach ($privilegeEttList as $privilegeEtt) {
			$privilegeModels[$privilegeEtt->id] = array(
				'id' 		=> intval($privilegeEtt->id),
				'name' 		=> $privilegeEtt->name,
				'type'		=> intval($privilegeEtt->type),
			);
		}
		return $privilegeModels;
	}
	
	/**
	 * 获取分销订单
	 *
	 * @return array
	 */
	public function distributeOrderList($backstageUserId, $info, $pageNum = 1, $pageLimit = 999)
	{
		$backstageUserSv = \service\BackstageUser::singleton();
		$backstageUserInfo = $backstageUserSv->userInfo($backstageUserId);
		$searchShareUserIds = empty($info['searchShareUserIds']) ? array() : $info['searchShareUserIds'];
		$shareUsers = empty($backstageUserInfo['user']['shareUsers']) ? array() : $backstageUserInfo['user']['shareUsers'];
		$shareUsers = array_column($shareUsers, null, 'userId');

		if (empty($shareUsers)) { // 管理员
			return array(
				'totalNum' 	=> 0,
				'list'	=> array(),
			);
		}
		if (!empty($searchShareUserIds)) {
			$tmp = array();
			foreach ($searchShareUserIds as $searchShareUserId) {
				if (empty($shareUsers[$searchShareUserId])) {
					throw new $this->exception('请求参数错误');
				}
				$tmp[] = $shareUsers[$searchShareUserId];
			}
			$shareUsers = $tmp;
		}
		$info['searchShareUserIds'] = array_column($shareUsers, 'userId');

		$brokerageSv = \service\Brokerage::singleton();
		$yieldList = $brokerageSv->yieldList($info, $pageNum, $pageLimit, true);
		$yieldList = empty($yieldList['list']) ? array() : $yieldList['list'];
		
		$dataList = array();
		foreach ($yieldList as $row) {
			$payStatus = empty($row['orderInfo']['status']) ? '' : $row['orderInfo']['status'];
			$dataList[] = array(
				'outTradeNo' => empty($row['orderInfo']['outTradeNo']) ? '' : $row['orderInfo']['outTradeNo'], // 订单号
				'testPaperId' => empty($row['testPaperInfo']['id']) ? '' : $row['testPaperInfo']['id'], // 测评Id
				'testPaperName' => empty($row['testPaperInfo']['name']) ? '' : $row['testPaperInfo']['name'], // 测评名称
				'testPaperSubhead' => empty($row['testPaperInfo']['subhead']) ? '' : $row['testPaperInfo']['subhead'], // 测评副标题
				'amount' => empty($row['amount']) ? '' : $row['amount'], // 收益金额
				'payPrice' => empty($row['orderInfo']['price']) ? '' : $row['orderInfo']['price'], // 支付金额
				'createTime' => empty($row['orderInfo']['updateTime']) ? '' : $row['orderInfo']['updateTime'], // 支付时间
				'shareUserName' => empty($row['shareUserInfo']['userName']) ? '' : $row['shareUserInfo']['userName'], // 分享用户
				'testUserName' => empty($row['testUserInfo']['userName']) ? '' : $row['testUserInfo']['userName'], // 测试用户
				'payStatus' => $payStatus, // 支付状态
				'payChannel' => '微信', // 支付渠道
				'status' => empty($row['status']) ? 0 : $row['status'], // 提现状态
			);
		}
		$yieldRecordDao = \dao\YieldRecord::singleton();
    	$totalNum = $yieldRecordDao->getList($info, -1);
		return array(
			'totalNum' 	=> $totalNum,
			'list'	=> $dataList,
		);
	}
	
	/**
	 * 导出
	 *
	 * @return
	 */
	public function distributeOrderExport($backstageUserId, $info)
	{
		$backstageSv = \service\Backstage::singleton();
		$result = $this->distributeOrderList($backstageUserId, $info, 1, 1000);
		$list = empty($result['list']) ? array() : $result['list'];
		
		// 字段
		$columns  = array(
			'outTradeNo'        => '订单号',
			'shareUserName'    	=> '分享账号',
			'testPaperName'    	=> '测评名称',
			'testPaperSubhead'	=> '测评副标题',
			'payPrice'      	=> '支付金额',
			'amount'      		=> '收益金额',
			'createTime'      	=> '支付时间',
			'testUserName'    	=> '测试用户',
			'payStatus'        	=> '答案',
			'payChannel'       	=> '支付渠道',
			'status'       		=> '提现状态',
		);
		$dataList = array();
		if (is_iteratable($list)) foreach ($list as $key => $row) {
			$row['createTime'] = date('Y-m-d H:i:s', $row['createTime']);
			if ($row['status'] == 2) {
				$status = '审核中';
			} elseif ($row['status'] == 3) {
				$status = '已审核';
			} elseif ($row['status'] == 4) {
				$status = '审核未通过';
			} elseif ($row['status'] == 5) {
				$status = '已到账';
			} else {
				$status = '未申请';
			}
			$row['status'] = $status;
			$dataList[] = $row;
		}

		$filename = '订单' . date('Ymd');
		$officeSv = \service\reuse\Office::singleton();
		
		$file = $officeSv->exportExcel($filename, $columns, $dataList, true, false);
		$filename = substr($file, (strripos($file, '/') + 1));
		$fileUrl = $this->frame->conf['urls']['files'] . $filename;
		return array(
			'localFile' => $file,
			'fileUrl'   => $fileUrl,
		);
	}
	
	/**
	 * 获取权限树
	 *
	 * @return array
	 */
	public function privilegeTree()
	{
		$privilegeDao = \dao\BackstagePrivilege::singleton();
		$privilegeEttList = $privilegeDao->readListByWhere("`status`!=-1");
		$privilegeModels = array();
		$treeSv = \service\reuse\Tree::singleton();
		if (is_iteratable($privilegeEttList)) foreach ($privilegeEttList as $privilegeEtt) {
			$privilegeModels[] = array(
				'id' 			=> intval($privilegeEtt->id), 			// 知识点id
				'name' 			=> $privilegeEtt->name, 				// 知识点名称
				'parentId' 		=> intval($privilegeEtt->parentId), 	// 父Id
				'index' 		=> intval($privilegeEtt->index), 		// 次序
				'treeId' 		=> intval($privilegeEtt->treeId),		// 树id
				'defaultOpen' 	=> intval($privilegeEtt->defaultOpen),	// 是否默认开启
				'type' 			=> intval($privilegeEtt->type),			// 类型  1 控制显示 2 控制操作
				'level' 		=> intval($privilegeEtt->level),		// 层级
				'open'			=> false,	// 默认不可写
			);
		}
		// 获取树状结构
		$treeStructure = $this->getTreeStructure($privilegeModels);
		return $treeStructure;
	}
	
	/**
	 * 获取树装结构
	 *
	 * @return array
	 */
	public function getTreeStructure($data)
	{
		$dataBase = $data;
		$map = array();
		foreach ($data as $row) {
			$map[$row['parentId']][$row['index']] = $row;
		}
		// 获取第一个元素
		$root = $map['0'];
		unset($map['0']);
		// 将子节点挂着在根节点上
		do {
			$mapBase = $map;
			foreach ($map as $parentId => $list) {
				// 将元素添加到根节点
				$this->addToRoot($root, $parentId, $list, $map);
			}
			if (count($map) != 0 && count($map) == count($mapBase)) {
				throw new $this->exception('树结构已破坏');
			}
		} while (!empty($map));
		$tree = array_values($root);
		// 重新排序
		$treeS = \service\reuse\Tree::singleton();
		$indexMap = $treeS->treeToArray($tree, true);
		if (!empty($indexMap)) {
			$privilegeDao = \dao\BackstagePrivilege::singleton();
			$privilegeEttList = $privilegeDao->readListByPrimary(array_keys($indexMap));
			if (is_iteratable($privilegeEttList)) foreach ($privilegeEttList as $privilegeEtt) {
				$privilegeEtt->set('index', $indexMap[$privilegeEtt->id]['index']);
				$privilegeEtt->set('parentId', $indexMap[$privilegeEtt->id]['parentId']);
				$privilegeDao->update($privilegeEtt);
			}
		}
		return $tree;
	}
	
	private function addToRoot(&$root, $parentId, $list, &$map)
	{
		if (empty($map)) {
			return false;
		}
		foreach ($root as &$row) {
			if (!empty($row['sublevels'])) { // 有子节点，就只能挂着在子节点上
				$this->addToRoot($row['sublevels'], $parentId, $list, $map);
			} else {
				if ($row['id'] == $parentId) {
					$tmp = empty($row['parentNames']) ? array() : $row['parentNames'];
					array_push($tmp, $row['name']);
					// 继承父类的名称
					foreach ($list as &$one) {
						$one['parentNames'] = $tmp;
					}
					$row['sublevels'] = array_values($list);
					unset($map[$parentId]);
				}
			}
		}
	}
	
	/**
	 * 生成分享二维码
	 *
	 * @return array
	 */
	public function getShareCodeList($backstageUserId, $searchShareUserId, $searchTestPaperId)
	{
		$backstageUserSv = \service\BackstageUser::singleton();
		$backstageUserInfo = $backstageUserSv->userInfo($backstageUserId);
		$shareUsers = empty($backstageUserInfo['shareUsers']) ? array() : $backstageUserInfo['shareUsers'];
		if (empty($shareUsers)) { // 管理员
			$selectItems = $this->getSelectItems();
			$shareUsers = empty($selectItems['userList']) ? array() : array_column($selectItems['userList'], null, 'userId');
		}
		if (!empty($shareUsers[$searchShareUserId])) {
			$shareUser = $shareUsers[$searchShareUserId];
		} else {
			$shareUser = reset($shareUsers);
		}
		if (empty($shareUsers)) {
			return array(
				'totalNum' => 0,
				'list'	=> array(),
				'shareUserId' => 0,
			);
		}
		
		// 获取分享的测评
		$classifySv = \service\Classify::singleton();
		$dataList = $classifySv->getListByClassify(888, array('testPaperId' => $searchTestPaperId));
		$dataList = empty($dataList['list']) ? array() : $dataList['list'];
		$brokerageSv = \service\Brokerage::singleton();
		foreach ($dataList as $key => $data) {
			$data['shareInfo'] = $brokerageSv->createShareInfo($data['id'], $shareUser['userId']);
			$dataList[$key] = $data;
		}
		return array(
			'totalNum' => count($dataList),
			'list'	=> array_values($dataList),
			'shareUserId' => $shareUser['userId'],
		);
	}
	
	/**
	 * 合作登记
	 *
	 * @return array
	 */
	public function businessApply($info)
	{
		$now = $this->frame->now;
		$backstageBusinessApplyDao = \dao\BackstageBusinessApply::singleton();
		$backstageBusinessApplyEtt = $backstageBusinessApplyDao->getNewEntity();
		$backstageBusinessApplyEtt->phone = $info['phone'];
		$backstageBusinessApplyEtt->nickname = $info['nickname'];
		$backstageBusinessApplyEtt->weChat = $info['weChat'];
		$backstageBusinessApplyEtt->accounts = $info['accounts'];
		$backstageBusinessApplyEtt->status = 0;
		$backstageBusinessApplyEtt->createTime = $now;
		$backstageBusinessApplyDao->create($backstageBusinessApplyEtt);
		return true;
	}
	
	/**
	 * 获取推广测评
	 *
	 * @return array
	 */
	public function promotionList($backstageUserId)
	{
		$backstageUserSv = \service\BackstageUser::singleton();
		$backstageUserInfo = $backstageUserSv->userInfo($backstageUserId);
		if (empty($backstageUserInfo)) {
			throw new $this->exception('请求参数错误');
		}
		// 推广信息
        $promotionDao = \dao\Promotion::singleton();
        $promotionEttList = $promotionDao->readListByWhere();
        $testPaperIds = array();
        foreach ($promotionEttList as $promotionEtt) {
        	$testPaperIds[] = intval($promotionEtt->testPaperId);
        }
        $testPaperDao = \dao\TestPaper::singleton();
        $testPaperEttList = $testPaperDao->readListByPrimary($testPaperIds);
        $testPaperEttList = $testPaperDao->refactorListByKey($testPaperEttList);
        $list = array();
        
      
        $webUrlBase = $this->frame->conf['web_url'];
        
        
        foreach ($promotionEttList as $promotionEtt) {
        	$testPaperEtt = $testPaperEttList[$promotionEtt->testPaperId];
        	$promotionModel = $promotionEtt->getModel();
        	$promotionModel['testPaper'] = $testPaperEtt->getModel();
     
        	if ($promotionEtt->id == 1) { // 版本选择
        		$promotionModel['url1'] = $webUrlBase . '/chooseGender2?promotionId=' . $promotionEtt->id;
        	}
        	$promotionModel['url'] = $webUrlBase . '/detail?promotionId=' . $promotionEtt->id;
        	$list[] = $promotionModel;
        }

		return array(
			'totalNum' => count($list),
			'list'	=> array_values($list),
		);
	}
	
	/**
	 * 获取优惠券配置
	 *
	 * @return array
	 */
	public function couponList($backstageUserId)
	{
		$backstageUserSv = \service\BackstageUser::singleton();
		$backstageUserInfo = $backstageUserSv->userInfo($backstageUserId);
		if (empty($backstageUserInfo)) {
			throw new $this->exception('请求参数错误');
		}
		$ids = array(1,2,3,100);
		if ($backstageUserInfo['user']['type'] == 0) { // 特邀推广员
			$ids = array(1);
		}
		$couponConfigDao = \dao\CouponConfig::singleton();
		$couponConfigEttList = $couponConfigDao->readListByPrimary($ids);
	
		$testPaperIds = array();
		
		$list = array();
		$now = $this->frame->now;
		$privateKey = md5(SHARE_PRIVATE_KEY);
		$host = empty($this->frame->conf['web_url']) ? '' : $this->frame->conf['web_url'];
		foreach ($couponConfigEttList as $couponConfigEtt) {
			$couponConfigModel = $couponConfigEtt->getModel();
			$encryptInfo = encrypt(json_encode(array(
				$now, // 当前时间
				$couponConfigEtt->id,
			)), $privateKey); // 加密后的信息
			$couponConfigModel['url'] = $host . "/coupon?couponCode=" . $encryptInfo;
			$list[] = $couponConfigModel;
		}
		return array(
			'totalNum' => count($list),
			'list'	=> array_values($list),
		);
	}
}