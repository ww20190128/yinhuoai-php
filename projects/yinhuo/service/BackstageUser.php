<?php
namespace service;

/**
 * 管理后台-账号	 逻辑类
 *
 * @author
*/
class BackstageUser extends ServiceBase
{
	/**
	 * 单例
	 *
	 * @var object
	 */
	private static $instance;

	private static $appKey = 'xxx';
	private static $appSecret = 'xxsadfsd';
	
	/**
	 * 单例模式
	 *
	 * @return BackstageUser
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new BackstageUser();
		}
		return self::$instance;
	}
	
	/**
	 * 获取部门列表
	 *
	 * @return array
	 */
	public function staticInfo()
	{
		// 获取用户的权限信息
		$privilegeSv = \service\Privilege::singleton();
		$privilegeTree = $privilegeSv->privilegeTree();
		return array(
			'privilegeTree' => $privilegeTree,  // 权限树
		);
	}
	
	
	
	/**
	 * 获取用户列表
	 *
	 * @return array
	 */
	public function getUsers($backstageUserId, $info, $pageNum, $pageLimit)
	{
		$backstageUserDao = \dao\BackstageUser::singleton();
		$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
		$backstageUserModel = $backstageUserEtt->getModel();
		$backstageSv = \service\Backstage::singleton();
		$privilegeModels = $backstageSv->getPrivileges();
	
		if (!empty($info['searchPrivilege']) && !empty($privilegeModels[$info['searchPrivilege']])) {
			$privilegeModel = $privilegeModels[$info['searchPrivilege']];
			if ($privilegeModel['type'] == 1) { // 显示
				$info['searchShowPrivilegeId'] = "FIND_IN_SET({$privilegeModel['id']}, `showPrivileges`)";
			} else { // 操作
				$info['searchOpPrivilegeId'] = "FIND_IN_SET({$privilegeModel['id']}, `opPrivileges`)";
			}
		}
		$backstageSv = \service\Backstage::singleton();
		$selectItems = $backstageSv->getSelectItems();
		$userList = empty($selectItems['userList']) ? array() : array_column($selectItems['userList'], null, 'userId');
		
		if (empty($backstageUserModel['type'])) { // 特邀推广员
			$info['searchUserId'] = $backstageUserId;
		}
		$backstageUserDao = \dao\BackstageUser::singleton();
		$backstageUserEttList = $backstageUserDao->getList($info);
		
		$createUserIds = array();
		if (is_iteratable($backstageUserEttList)) foreach ($backstageUserEttList as $key => $backstageUserEtt) {
			$createUserIds[] = $backstageUserEtt->createUserId;
		}
		$createBackstageUserEttList = empty($createUserIds) ? array() : $backstageUserDao->readListByPrimary($createUserIds);
		$createBackstageUserEttList = $backstageUserDao->refactorListByKey($createBackstageUserEttList);
		$users = array();
		if (is_iteratable($backstageUserEttList)) foreach ($backstageUserEttList as $key => $backstageUserEtt) {
			$userModel = $backstageUserEtt->getModel();
			$showControls = array();
			foreach ($userModel['showControl'] as $opId) {
				if (empty($privilegeModels[$opId])) {
					continue;
				}
				$showControls[] = $privilegeModels[$opId]['name'];
			}
			$opControls = array();
			foreach ($userModel['opControl'] as $opId) {
				if (empty($privilegeModels[$opId])) {
					continue;
				}
				$opControls[] = $privilegeModels[$opId]['name'];
			}
			$shareUsers = array();
			foreach ($userModel['shareUserIds'] as $shareUserId) {
				if (empty($userList[$shareUserId])) {
					continue;
				}
				$shareUsers[] = $userList[$shareUserId];
			}
			$userModel['showControls'] = $showControls;
			$userModel['opControls'] = $opControls;
			$userModel['shareUsers'] = $shareUsers;
			if (!empty($createBackstageUserEttList[$backstageUserEtt->createUserId])) {
				$userModel['createUserInfo'] = $createBackstageUserEttList[$backstageUserEtt->createUserId]->getModel();
			}
			$users[] = $userModel;
		}
		return array(
			'totalNum' 	=> intval($users),
			'list'		=> array_values($users),
		);
	}
	
	/**
	 * 开启关联权限
	 *
	 * @return array
	 */
	private function parentPrivilege($privilegeId, $privilegeArr)
	{
		$parentPrivilegeIds = array(); // 需要开启的父id
		// 开启上级及上上级
		if (!empty($privilegeArr[$privilegeId]) && !empty($privilegeArr[$privilegeId]['parentId'])) {
			$parent1 = $privilegeArr[$privilegeArr[$privilegeId]['parentId']];
			if (!empty($privilegeArr[$parent1['id']]) && empty($privilegeArr[$parent1['id']]['open'])) {
				$parentPrivilegeIds[] = $parent1['id'];
			}
			if (!empty($privilegeArr[$parent1['id']]) && !empty($privilegeArr[$parent1['id']]['parentId'])) {
				$parent2 = $privilegeArr[$privilegeArr[$parent1['id']]['parentId']];
				if (!empty($privilegeArr[$parent2['id']]) && empty($privilegeArr[$parent2['id']]['open'])) {
					$parentPrivilegeIds[] = $parent2['id'];
				}
			}
		}
		return $parentPrivilegeIds;
	}
	
	/**
	 * 设置权限
	 *
	 * @return array
	 */
	private function setPrivilege($privilegeMap, $backstageUserModel = array())
	{
		// 权限
		$showControl = empty($backstageUserModel) ? array() : $backstageUserModel['showControl'];
		$opControl = empty($backstageUserModel) ? array() : $backstageUserModel['opControl'];
	
		$backstageSv = \service\Backstage::singleton();
		$privilegeTree = $backstageSv->privilegeTree();
		$treeSv = \service\reuse\Tree::singleton();
		$privilegeArr = $treeSv->treeToArray($privilegeTree);
		$openPrivilegeIds = array();
		// 显示
		if (!empty($privilegeMap['show'])) foreach ($privilegeMap['show'] as $key => $row) {
			if (!empty($row['open'])) { // 开启
				$showControl[] = $row['id'];
				$parentPrivilegeIds = $this->parentPrivilege($row['id'], $privilegeArr);
				if (!empty($parentPrivilegeIds)) {
					$openPrivilegeIds = array_merge($openPrivilegeIds, $parentPrivilegeIds);
				}
			} elseif (isset($row['open'])) { // 关闭
				$key = array_search($row['id'], $showControl);
				if ($key !== false) {
					unset($showControl[$key]);
				}
			}
		}
		// 操作
		if (!empty($privilegeMap['op'])) foreach ($privilegeMap['op'] as $key => $row) {
			if (!empty($row['open'])) { // 开启
				$opControl[] = $row['id'];
				$parentPrivilegeIds = $this->parentPrivilege($row['id'], $privilegeArr);
				if (!empty($parentPrivilegeIds)) {
					$openPrivilegeIds = array_merge($openPrivilegeIds, $parentPrivilegeIds);
				}
			} elseif (isset($row['open'])) { // 关闭
				$key = array_search($row['id'], $opControl);
				if ($key !== false) {
					unset($opControl[$key]);
				}
			}
		}
		$showControl = array_merge($showControl, $openPrivilegeIds);
		$showControl = array_unique($showControl);
		$opControl = array_unique($opControl);
		return array(
			'showControl' => $showControl,
			'opControl' => $opControl,
		);
	}
	
	/**
	 * 创建账号
	 *
	 * @return array
	 */
	public function createUser($backstageUserId, $info)
	{
		$backstageUserDao = \dao\BackstageUser::singleton();
		$opBackstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
		if (empty($opBackstageUserEtt) || empty($opBackstageUserEtt->type)) {
			throw new $this->exception('没有创建账号的权限');
		}
		// 权限
		$showControl = array();
		$opControl = array();
		if (!empty($info['privilegeMap'])) {
			$privilegeArr = $this->setPrivilege($info['privilegeMap']);
			$showControl = $privilegeArr['showControl'];
			$opControl = $privilegeArr['opControl'];
		}
		
		$where = "`userName`='{$info['userName']}'";
		$backstageUserDao = \dao\BackstageUser::singleton();
		$backstageUserEtt = $backstageUserDao->readListByWhere($where);
		
		// 检查姓名是否可用
		if (!empty($backstageUserEtt)) {
			throw new $this->exception('有重名用户');
		}
		// 检查手机号是否可用
		$where = "`phone`='{$info['phone']}'";
		$backstageUserEtt = $backstageUserDao->readListByWhere($where);
		if (!empty($backstageUserEtt)) {
			throw new $this->exception('手机号已使用');
		}
		$userDao = \dao\User::singleton();
		$shareUserEttList = $userDao->readListByPrimary($info['shareUserIds']);

		foreach ($shareUserEttList as $shareUserEtt) {
			if (empty($shareUserEtt->type) || $shareUserEtt->type == \constant\Common::DATA_DELETE) {
				throw new $this->exception('请绑定正确的分享账号');
			}
		}
		if (count($info['shareUserIds']) != count($shareUserEttList)) {
			throw new $this->exception('请绑定正确的分享账号');
		}
	
		$now = $this->frame->now;
		// 创建
		$backstageUserEtt = $backstageUserDao->getNewEntity();
		$backstageUserEtt->userName = $info['userName'];
		$backstageUserEtt->phone = $info['phone'];
		$backstageUserEtt->type = $info['type'];
		$backstageUserEtt->password = md5($info['password']);
		$backstageUserEtt->startTime = $info['startTime'];
		$backstageUserEtt->endTime = $info['endTime'];
		$backstageUserEtt->shareUserIds = implode(',', $info['shareUserIds']);
		$backstageUserEtt->showPrivileges = empty($showControl) ? '' : implode(',', $showControl);
		$backstageUserEtt->opPrivileges = empty($opControl) ? '' : implode(',', $opControl);
		$backstageUserEtt->createTime = $now;
		$backstageUserEtt->createUserId = $opBackstageUserEtt->userId; // 创建者
		$backstageUserId = $backstageUserDao->create($backstageUserEtt);
		$backstageUserEtt->userId = $backstageUserId;

		// 初始化用户权限
		$this->initUserPrivilege($backstageUserEtt);
		return $this->userInfo($backstageUserId);
	}
	
	/**
	 * 修改分享用户信息（调整收益占比）
	 *
	 * @return array
	 */
	public function reviseShareUser($opBackstageUserId, $shareUserId, $info)
	{
		// 操作者账号
		$backstageUserDao = \dao\BackstageUser::singleton();
		$opBackstageUserEtt = $backstageUserDao->readByPrimary($opBackstageUserId);
		if (empty($opBackstageUserEtt) || empty($opBackstageUserEtt->type)) {
			throw new $this->exception('账号权限不够');
		}
		$backstageUserModel = $opBackstageUserEtt->getModel();
		$shareUserIds = $backstageUserModel['shareUserIds']; // 绑定的分享账号
		if (!in_array($shareUserId, $shareUserIds)) {
			throw new $this->exception('非法请求');
		}
	
		$userDao = \dao\User::singleton();
		$userEtt = $userDao->readByPrimary($shareUserId);
		if (empty($userEtt)) {
			throw new $this->exception('账号不存在');
		}
		$now = $this->frame->now;
		if (!empty($info['commissionRate']) && $info['commissionRate'] != $userEtt->commissionRate) {
			$userEtt->set('commissionRate', $info['commissionRate']);
			$userEtt->set('updateTime', $now);
			$userDao->update($userEtt);
		}
		return array(
			'result' => 1,
		);
	}
	
	/**
	 * 修改后台账号
	 *
	 * @return array
	 */
	public function reviseUser($opBackstageUserId, $id, $info)
	{
		$backstageUserDao = \dao\BackstageUser::singleton();
		$backstageUserEtt = $backstageUserDao->readByPrimary($id);
		if (empty($backstageUserEtt)) {
			throw new $this->exception('账号不存在');
		}
		// 操作者账号
		
		$opBackstageUserEtt = $backstageUserDao->readByPrimary($opBackstageUserId);
		if ($opBackstageUserId != $id) { // 修改他人账号
			if (empty($opBackstageUserEtt) || empty($opBackstageUserEtt->type)) {
				throw new $this->exception('账号权限不够');
			}
			if (!empty($backstageUserEtt) && $backstageUserEtt->type == 666) {
				throw new $this->exception('账号权限不够');
			}
		}

		// 修改密码
		if (!empty($info['password']) && $info['password'] != $backstageUserEtt->password) {
			if (empty($backstageUserEtt->password)) {
				// 企业微信兼职帐号不能修改密码，只能扫码登录
				throw new $this->exception('企业微信兼职帐号不能修改密码，只能扫码登录');
			}
			$backstageUserEtt->set('password', $info['password']);
		}
		// 封禁与解禁
		if (isset($info['status']) && $info['status'] != $backstageUserEtt->status) {
			$backstageUserEtt->set('status', $info['status']);
		}
		// 修改姓名
		if (!empty($info['userName']) && $info['userName'] != $backstageUserEtt->userName) {
			// 检查姓名是否可用
			$haveBackstageUserEtt = $backstageUserDao->readListByWhere("`userName`='{$info['userName']}'");
			if (!empty($haveBackstageUserEtt)) {
				throw new $this->exception('有重名的用户');
			}
			$backstageUserEtt->set('userName', $info['userName']);
		}
		// 修改手机号
		if (!empty($info['phone']) && $info['phone'] != $backstageUserEtt->phone) {
			// 检查手机号是否可用
			$haveBackstageUserEtt = $backstageUserDao->readListByWhere("`phone`='{$info['phone']}'");
			if (!empty($haveBackstageUserEtt)) {
				throw new $this->exception('手机号已使用');
			}
			$backstageUserEtt->set('phone', $info['phone']);
		}
		// 设置有效时间
		if (!empty($info['startTime']) && $info['startTime'] != $backstageUserEtt->startTime) {
			$backstageUserEtt->set('startTime', $info['startTime']);
		}
		if (!empty($info['endTime']) && $info['endTime'] != $backstageUserEtt->endTime) {
			$backstageUserEtt->set('endTime', $info['endTime']);
		}
		$now = $this->frame->now;
		// 修改权限
		if (!empty($info['privilegeMap']) && $opBackstageUserId != $id) {
			$backstageUserModel = $backstageUserEtt->getModel();
			$privilegeArr = $this->setPrivilege($info['privilegeMap'], $backstageUserModel);
			$showControl = $privilegeArr['showControl'];
			$opControl = $privilegeArr['opControl'];
			$backstageUserEtt->set('showPrivileges', empty($showControl) ? '' : implode(',', $showControl));
			$backstageUserEtt->set('opPrivileges', empty($opControl) ? '' : implode(',', $opControl));
		}
		if (!empty($info['shareUserIds'])) {
			$userDao = \dao\User::singleton();
			$shareUserEttList = $userDao->readListByPrimary($info['shareUserIds']);
			foreach ($shareUserEttList as $shareUserEtt) {
				if (empty($shareUserEtt->type) || $shareUserEtt->type == \constant\Common::DATA_DELETE) {
					throw new $this->exception('请绑定正确的分享账号');
				}
			}
			if (count($info['shareUserIds']) != count($shareUserEttList)) {
				throw new $this->exception('请绑定正确的分享账号');
			}
			$backstageUserEtt->set('shareUserIds', implode(',', $info['shareUserIds']));
		}

		$backstageUserEtt->set('updateTime', $now);
		$backstageUserDao->update($backstageUserEtt);
		return $this->userInfo($backstageUserEtt->userId);
	}
	
	/**
	 * 删除后台账号
	 *
	 * @return array
	 */
	public function deleteUser($backstageUserId, $opUserId)
	{
		// 操作者账号
		$backstageUserDao = \dao\BackstageUser::singleton();
		$opBackstageUserEtt = $backstageUserDao->readByPrimary($opUserId);
		if (empty($opBackstageUserEtt->type)) {
			throw new $this->exception('账号权限不够');
		}
		// 操作者账号
		$backstageUserDao = \dao\BackstageUser::singleton();
		$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
		if (!empty($backstageUserEtt) && $backstageUserEtt->type == 666) {
			throw new $this->exception('账号权限不够');
		}
		if (!empty($backstageUserEtt)) {
			$backstageUserDao->remove($backstageUserEtt);
		}
		return array(
			'result' => 1,
		);
	}
	
	/**
	 * 初始化用户权限
	 *
	 * @return array
	 */
	private function initUserPrivilege($backstageUserEtt)
	{
		$backstageSv = \service\Backstage::singleton();
		$privilegeTree = $backstageSv->privilegeTree();
		$treeSv = \service\reuse\Tree::singleton();
		$privilegeArr = $treeSv->treeToArray($privilegeTree);
		$userOpPrivileges = array(); // 用户操作权限
		$userShowPrivileges = array(); // 用户显示权限
		
		$backstageUserModel = $backstageUserEtt->getModel();
	
		$userShowPrivileges = array_merge($backstageUserModel['showControl'], $userShowPrivileges);
		$userOpPrivileges = array_merge($backstageUserModel['opControl'], $userOpPrivileges);
		$userShowPrivileges = array_unique($userShowPrivileges);
		$userOpPrivileges = array_unique($userOpPrivileges);
		sort($userShowPrivileges);
		sort($userOpPrivileges);
		
		$update = false;
		if ($userShowPrivileges != $backstageUserModel['showControl']) {
			$backstageUserEtt->set('showPrivileges', empty($userShowPrivileges) ? '' : implode(',', $userShowPrivileges));
			$update = true;
		}
		if ($userOpPrivileges != $backstageUserModel['opControl']) {
			$backstageUserEtt->set('opPrivileges', empty($userOpPrivileges) ? '' : implode(',', $userOpPrivileges));
			$update = true;
		}
		if (!empty($update)) {
			$backstageUserDao = \dao\BackstageUser::singleton();
			$backstageUserDao->update($backstageUserEtt);
		}
		return $backstageUserEtt;
	}
	
	/**
	 * 判断是否有权限
	 *
	 * @return bool
	 */
	public function checkPrivilege($name, $opBackstageUserEtt, $throw = false, $targetIds = array())
	{
		// 操作者账号id
		if (is_numeric($opBackstageUserEtt)) {
			$backstageUserDao = \dao\BackstageUser::singleton();
			$opBackstageUserEtt = $backstageUserDao->readByPrimary($opBackstageUserEtt);
			if (empty($opBackstageUserEtt)) {
				if ($throw) {
					throw new $this->exception('账号权限不够');
				}
				return false;
			}
		}
		$opBackstageUserModel = $opBackstageUserEtt->getModel();
		$opControl = $opBackstageUserModel['opControl']; // 操作权限
		$privilegeId = '';
		
		$backstagePrivilegeDao = \dao\BackstagePrivilege::singleton();
		$backstagePrivilegeEttList = $backstagePrivilegeDao->readListByWhere("`name`='{$name}' && `type` = 2");
		if (empty($backstagePrivilegeEttList)) {
			return  false;
		}
		$backstagePrivilegeEtt = reset($backstagePrivilegeEttList);
		$privilegeId = intval($backstagePrivilegeEtt->id);
		$havePrivilege = in_array($privilegeId, $opControl) ? true : false;
		if (empty($havePrivilege) && $throw) {
			throw new $this->exception('账号权限不够');
		}
		return $havePrivilege;
	}
	
	/**
	 * 检查用户的有效期、状态等
	 * 
	 * @param $userEtt
	 */
	public function checkStatus($backstageUserEtt)
	{
		// 检查用户有效期
		if (!empty($backstageUserEtt->startTime) && $backstageUserEtt->startTime > $this->frame->now) {
			throw new $this->exception('该帐号不在有效期内', ['status' => 3, 'userId' => (int)$backstageUserEtt->userId]);
		}
		if (!empty($backstageUserEtt->endTime) && $backstageUserEtt->endTime < $this->frame->now) {
			throw new $this->exception('该帐号不在有效期内！', ['status' => 3, 'userId' => (int)$backstageUserEtt->userId]);
		}
		// 用户状态检查
		if ($backstageUserEtt->status == \constant\Common::DATA_DELETE) {
			throw new $this->exception('帐号已禁用或已删除！请联系管理员', ['status' => 3, 'userId' => (int)$backstageUserEtt->userId]);
		}
	}
	
	/**
	 * 生成签名
	 * @param $userId
	 * @param $appKey
	 * @param $appSecret
	 * @param $timeStamp
	 * @param $randNum
	 * @return string
	 */
	private function generateSignature($userId, $appKey, $appSecret, $timeStamp, $randNum)
	{
		$plainStr = $userId . '-' . $appKey . '-' . $timeStamp . '-' . $randNum;
		//$encryptedStr = bin2hex(mhash(MHASH_SHA1, $plainStr, $appSecret));
		$encryptedStr = substr(md5($plainStr . $appSecret), 8, 16);
		return $encryptedStr . $timeStamp . $randNum;
	}
	
	/**
	 * 生成Token
	 * @param $appKey
	 * @param $appSecret
	 * @param int $userId
	 * @param int $limitTime
	 * @param bool $refreshToken
	 * @return string
	 */
	public function generateToken($appKey, $appSecret, $userId = 0, $refreshToken = false, $limitTime = 12)
	{
		$header = $userId . '-' . $appKey;
		$timeStamp = time() + ($limitTime * 3600); // 有效期
		$randNum = dechex($timeStamp);
		if ($refreshToken) {
			// 用来更新Token 的refresh_token
			$header    .= '-refresh';
		}
		$sign  = $this->generateSignature($userId, $appKey, $appSecret, $timeStamp, $randNum);
		$token = base64_encode($header) . "." . $sign;
	
		return $token;
	}

	/**
	 * 登录成功后生成token
	 * 
	 * @param $userEtt
	 */
	public function setToken($backstageUserEtt)
	{
		// 检查用户有效期、状态等
		$this->checkStatus($backstageUserEtt);
	
		$backstageUserDao = \dao\BackstageUser::singleton();
		$key = $this->generateToken(self::$appKey, self::$appSecret, $backstageUserEtt->userId);
		// 更新登录的信息
		$backstageUserEtt->set('loginKey', $key);
		$backstageUserEtt->set('lastLoginTime', $this->frame->now);
		$backstageUserDao->update($backstageUserEtt);
		$userInfo = $this->userInfo($backstageUserEtt->userId);
		$userInfo['loginKey'] = $key;
		$userInfo['loginRefreshKey'] = $this->generateToken(self::$appKey, self::$appSecret, $backstageUserEtt->userId, true, 72);
		return $userInfo;
	}
	
	/**
	 * 获取用户信息
	 *
	 * @return array
	 */
	public function userInfo($backstageUserId)
	{
		$backstageUserDao = \dao\BackstageUser::singleton();
		$backstageUserEtt = $backstageUserDao->readByPrimary($backstageUserId);
		$backstageUserModel = $backstageUserEtt->getModel();

    	$userOpPrivileges = $backstageUserModel['opControl'];
    	$userShowPrivileges = $backstageUserModel['showControl'];
    	$shareUserIds = $backstageUserModel['shareUserIds']; // 绑定的分享账号
    	$backstageSv = \service\Backstage::singleton();
    	$selectItems = $backstageSv->getSelectItems();

    	if ($backstageUserEtt->type == 666) { // 超级管理员
    		$shareUsers = empty($selectItems['userList']) ? array() : array_column($selectItems['userList'], null, 'userId');
    		$addShareUserIds = array_keys($shareUsers);
    		$shareUserIds = array_merge($addShareUserIds, $shareUserIds);
    		$shareUserIds = array_unique($shareUserIds);
    	} elseif ($backstageUserEtt->type == 1) { // 管理员
    		// 获取创建的账户
    		$where = "`createUserId` = {$backstageUserEtt->userId}";
    		$backstageUserEttList = $backstageUserDao->readListByWhere($where);
    		$shareUsers = empty($backstageUserEttList) ? array() : array_column($backstageUserEttList, null, 'userId');
    		$addShareUserIds = array_keys($shareUsers);
    		$shareUserIds = array_merge($addShareUserIds, $shareUserIds);
    		$shareUserIds = array_unique($shareUserIds);
    	}
    	$shareUsers = array();
    	if (!empty($shareUserIds)) {
    		$userDao = \dao\User::singleton();
    		$shareUserEttList = $userDao->readListByPrimary($shareUserIds);
    		if (is_iteratable($shareUserEttList)) foreach ($shareUserEttList as $shareUserEtt) {
    			$shareUserModel = $shareUserEtt->getModel();
    			$commissionRate = 30;
    			if (!empty($shareUserEtt->type)) {
    				$commissionRate = number_format($shareUserEtt->commissionRate, 0);
    			}
    			$shareYield = $shareUserEtt->shareYield; // 累积分享收益
    			$withdrawAmount = $shareUserEtt->withdrawAmount; // 累积提现金额
    			$residueAmount = max(0, $shareYield - $withdrawAmount); // 可提现金额
    			$shareInfo = array(
    				'type' => intval($shareUserEtt->type),
    				'commissionRate' => $commissionRate,
    				'shareYield' => number_format($shareYield, 2), // 累积分享收益
    				'withdrawAmount' => number_format($withdrawAmount, 2), // 累积提现金额
    				'residueAmount' => number_format($residueAmount, 2), // 可提现金额
    			);
    			$shareUserModel = array_merge($shareUserModel, $shareInfo);

    			$shareUsers[] = $shareUserModel;
    		}
    	}
    	$backstageUserModel['shareUsers'] = $shareUsers;
    
    	// 获取权限信息
    	$backstageSv = \service\Backstage::singleton();
    	$privilegeTree = $backstageSv->privilegeTree();

    	// 获取用户的权限树
    	$treeSv = \service\reuse\Tree::singleton();
    	$privilegeArr = $treeSv->treeToArray($privilegeTree);
    	$openPrivilegeIds = array(); // 需要开启的父级模块
    	// 开启特殊权限
    	if (is_iteratable($privilegeArr)) foreach ($privilegeArr as $key => $row) {
    		$open = false; // 是否开启
    		$content = array(); // 控制的内容
    		if ($row['type'] == 2) { // 控制操作
    			if (in_array($row['id'], $userOpPrivileges)) {
    				$open = true;
    			}
    		} else { // 控制显示
    			if (in_array($row['id'], $userShowPrivileges)) {
    				$open = true;
    			}
    		}
    		// 默认开启
    		if (!empty($row['defaultOpen'])) {
    			$open = true;
    		}
    		if (!empty($open) && $row['type'] == 2) {
    			$parentPrivilegeIds = $this->parentPrivilege($row['id'], $privilegeArr);
    			if (!empty($parentPrivilegeIds)) {
    				$openPrivilegeIds = array_merge($openPrivilegeIds, $parentPrivilegeIds);
    				foreach ($parentPrivilegeIds as $parentPrivilegeId) {
    					$privilegeArr[$parentPrivilegeId]['open'] = true;
    				}
    			}
    		}
    		$privilegeArr[$key]['open'] = $open;
    	}
    	if (!empty($openPrivilegeIds)) {
    		$openPrivilegeIds = array_unique($openPrivilegeIds);
    		$userShowPrivileges = array_merge($userShowPrivileges, $openPrivilegeIds);
    		$userShowPrivileges = array_unique($userShowPrivileges);
    	}
    	
    	$privilegeTree = $backstageSv->getTreeStructure($privilegeArr);
    	$userPrivileges = array(
    		'showControl'	=> $userShowPrivileges, // 用户权限-显示
    		'opControl'		=> $userOpPrivileges, // 用户权限-控制
    	);
    	return array(
    		'user' 					=> $backstageUserModel, // 用户信息
    		'userPrivileges' 		=> $userPrivileges, // 用户的权限
    		'privilegeTree'			=> $privilegeTree,
			'staticPrivileges'		=> array_values($privilegeArr),
			'loginLogList'			=> array(), // 最近登录日志
    	);
	}
	
	/**
     * 登录
     *
     * @return array
     */
    public function login($userName, $password)
    {
    	$backstageUserDao = \dao\BackstageUser::singleton();
    	$backstageUserEtt = $backstageUserDao->readByPrimary(array(
    		'userName' => $userName,
    		'status'   => '!' . \constant\Common::DATA_DELETE
    	));
    	if (empty($backstageUserEtt)) {
    		$backstageUserEtt = $backstageUserDao->readByPrimary(array(
    			'phone'  => $userName,
    			'status' => '!' . \constant\Common::DATA_DELETE
    		));
    	}
    	if (empty($backstageUserEtt)) {
    		throw new $this->exception('用户名错误');
    	}
    	if($backstageUserEtt->password == '') {
    		throw new $this->exception('请输入密码');
    	}
    	if ($backstageUserEtt->password != $password) {
    		throw new $this->exception('密码错误');
    	}
		$userInfo = $this->setToken($backstageUserEtt);
    	return $userInfo;
    }
    
    /**
     * Token 验证
     */
    public function check($token, $refreshToken = false)
    {
    	$tokenArr = explode('.', $token);
    	if (count($tokenArr) != 2) {
    		throw new $this->exception('Token错误!', ['status' => 3]);
    	}
    	$header = explode('-', base64_decode($tokenArr[0]));
    	if ((!$refreshToken && count($header) != 2) || ($refreshToken && count($header) != 3)) {
    		throw new $this->exception('Token错误!', ['status' => 3]);
    	}
    	$header = ['userId' => $header[0], 'appKey' => $header[1]];
    	$appEtt = \dao\App::singleton()->readListByIndex(['appKey' => $header['appKey']], true);
    	if (empty($appEtt)) {
    		throw new $this->exception('Token错误!!', ['status' => 3]);
    	}
    
    	$randNum   = substr($tokenArr[1], -8);
    	$timeStamp = substr($tokenArr[1], 16, 10);
    	if ($timeStamp - $this->frame->now <= 0) {
    		throw new $this->exception('Token已过期!', ['status' => 2]);
    	}
    
    	$checkSign = $this->generateSignature($header['userId'], $header['appKey'], $appEtt->appSecret, $timeStamp, $randNum);
    	if ($checkSign != $tokenArr[1]) {
    		throw new $this->exception('Token错误!!!', ['status' => 3]);
    	}
    	// 添加到全局对象中
    	$this->frame->appInfo = $appEtt;
    	$header['appId']      = $appEtt->id;
    	$header['timeStamp']  = $timeStamp;
    	return $header;
    }
    
    /**
     * 刷新token
     *
     * @return array
     */
    public function refreshToken($loginRefreshKey)
    {
    	$header = $this->check($loginRefreshKey, true);
    	$backstageUserDao     = \dao\BackstageUser::singleton();
    	$backstageUserEtt = $backstageUserDao->readByPrimary($header['userId']);
    	/*
    	 if ($userEtt->loginKey != $token) {
    	 throw new $this->exception('请重新登录', ['status' => 3]);
    	}*/
    	$token = $this->generateToken(self::$appKey, self::$appSecret, $header['userId']);
    	// 更新Token
    	$backstageUserEtt->set('loginKey', $token);
    	$$backstageUserDao->update($backstageUserEtt);
    	// 不影响用户连续操作，当刷新token时效不足2小时也需要更新
    	if ($header['timeStamp'] < $this->frame->now + 12 * 3600) {
    		$refreshToken = $this->generateToken(self::$appKey, self::$appSecret, $backstageUserEtt->userId, true, 72);
    	}
 
    	return array(
    		'loginKey' =>  $token,
    		'loginRefreshKey' =>  $refreshToken,
    	);
    }
    
    /**
     * 刷新Token
     */
    public function refreshToken1($token, $refreshToken)
    {
    	$header  = $this->check($refreshToken, true);
    	$dao     = \dao\User::singleton();
    	$userEtt = $dao->readByPrimary($header['userId']);
    	/*
    	 if ($userEtt->loginKey != $token) {
    	 throw new $this->exception('请重新登录', ['status' => 3]);
    	}*/
    	$ett   = \dao\App::singleton()->readByPrimary($header['appId']);
    	$token = $this->generateToken($ett->appKey, $ett->appSecret, $header['userId']);
    	// 更新Token
    	$userEtt->set('loginKey', $token);
    	$dao->update($userEtt);
    	// 不影响用户连续操作，当刷新token时效不足2小时也需要更新
    	if ($header['timeStamp'] < $this->frame->now + 12 * 3600) {
    		$refreshToken = $this->generateToken($this->frame->appInfo->appKey, $this->frame->appInfo->appSecret,
    				$userEtt->userId, true, 72);
    		return ['access_token' => $token, 'refresh_token' => $refreshToken];
    	}
    	return ['access_token' => $token];
    }
}