<?php
namespace service;

/**
 * 用户  逻辑类
 *
 * @author
 */
class User extends ServiceBase
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
     * @return User
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new User();
        }
        return self::$instance;
    }
    
    /**
     * 微信登陆
     * 
     * @return array
     */
    public function loginByWeChat($code)
    {
    	$weChat = empty($this->frame->conf['weChat']) ? array() : $this->frame->conf['weChat'];
    	if (empty($weChat)) {
    		throw new $this->exception('获取微信配置失败！');
    	}
    	$appId = $weChat['appId'];
    	$appSecret = $weChat['appSecret'];
    	// 第1步：通过code换取网页授权信息
    	$url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appId}&secret={$appSecret}&js_code={$code}&grant_type=authorization_code";
    	$response = httpGetContents($url);
    	$now = $this->frame->now;
    	$response = empty($response) ? array() : json_decode($response, true);
    	$userInfo = array();
    	if (empty($response['session_key'])) {
    	    return $response;
    	    throw new $this->exception('2.获取用户授权失败' . empty($response['errmsg']) ? '' : '：' . $response['errmsg'], array('status' => 2));
    	}
    	$openid = empty($response['openid']) ? '' : $response['openid']; // 用户唯一标识
    	$session_key = empty($response['session_key']) ? '' : $response['session_key']; // 会话密钥
    	$userInfo = $response;
    	
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readListByIndex(array(
    	    'openid' => $openid,
    	), true);
    	if (!empty($userEtt) && $userEtt->status == \constant\Common::DATA_DELETE) {
    	    throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	} 
    	$userName = empty($response['nickname']) ? '' : $response['nickname'];
    	$headImgUrl = empty($response['headimgurl']) ? '' : $response['headimgurl'];
    	$sex = empty($response['sex']) ? 0 : $response['sex'];
    	$language = empty($response['language']) ? '' : $response['language'];
    	$country = empty($response['country']) ? '' : $response['country'];
    	$province = empty($response['province']) ? '' : $response['province'];
    	$city = empty($response['city']) ? '' : $response['city'];
    	if (empty($userEtt)) { // 写入用户信息
    	    $userEtt = $userDao->getNewEntity();
    	    $userEtt->openid = $openid;
    	    $userEtt->session_key = $session_key;
    	    $userEtt->userName = $userName;
    	    $userEtt->headImgUrl = $headImgUrl;
    	    $userEtt->sex = $sex;
    	    $userEtt->language = $language;
    	    $userEtt->country = $country;
    	    $userEtt->province = $province;
    	    $userEtt->city = $city;
    	    $userEtt->createTime = $now;
    	    $userEtt->updateTime = $now;
    	    $userId = $userDao->create($userEtt);
    	} else { // 更新用户信息
    	    if ($userEtt->status == \constant\Common::DATA_DELETE) {
    	        throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	    }
//     	    $userEtt->set('userName', $userName);
//     	    $userEtt->set('headImgUrl', $headImgUrl);
    	    $userEtt->set('sex', $sex);
    	    if (!empty($openid)) {
    	    	$userEtt->set('openid', $openid);
    	    }
    	    if (!empty($session_key)) {
    	    	$userEtt->set('session_key', $session_key);
    	    }
    	    $userEtt->set('language', $language);
    	    $userEtt->set('country', $country);
    	    $userEtt->set('province', $province);
    	    $userEtt->set('city', $city);
    	    $userEtt->set('updateTime', $now);
    	    $userDao->update($userEtt);
    	    $userId = $userEtt->userId;
    	}
    	$userInfo['userId'] = $userId;
    	$token = encrypt(base64_encode(json_encode($userInfo)));
    	return array(
    	    'token' => $token,
    	);
    }
    
    /**
     * 获取用户信息
     *
     * @return array
     */
    public function userInfo($userEtt)
    {
    	if (is_numeric($userEtt)) {
    		$userDao = \dao\User::singleton();
    		$userEtt = $userDao->readByPrimary($userEtt);
    	}
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	// 用户购买的vip
    	$userVipDao = \dao\UserVip::singleton();
    	$userVipEttList = $userVipDao->readListByIndex(array(
    		'userId' => $userEtt->userId,
    	));
    	$vipSv = \service\Vip::singleton();
    	$vipConfigList = $vipSv->getConfigList();
    	
    	$now = $this->frame->now;
    	$userVipList = array(); // 用户购买的vip列表
    	if (is_iteratable($userVipEttList)) foreach ($userVipEttList as $userVipEtt) {
    		if (empty($vipConfigList[$userVipEtt->vipId]) || empty($userVipEtt->effectTime)) { // 未支付
    			continue;
    		}
    		$vipConfigModel = $vipConfigList[$userVipEtt->vipId];
    		// 生效的时间
    		$effectEndTime = $userVipEtt->effectTime + $vipConfigModel['effectDay'] * 86400;
    		if ($effectEndTime <= $now) { // vip已失效
    			continue;
    		}
    		$userVipList[$userVipEtt->id] = array(
    			'id' => intval($userVipEtt->id),
    			'userId' => intval($userVipEtt->userId),
    			'effectTime' => intval($userVipEtt->effectTime), // 生效时间
    			'effectEndTime' => $effectEndTime, // 效果结束时间
    			'effectDay' => ceil(($effectEndTime - $userVipEtt->effectTime) / 86400), // vip有效时长
    			'createTime' => intval($userVipEtt->createTime),
    			'updateTime' => intval($userVipEtt->updateTime),
    			'type' => intval($vipConfigModel['type']), // vip 类型
    			'name' => $vipConfigModel['name'], // vip名称
    			'price' => $vipConfigModel['price'], // 价格
    			'originalPrice' => $vipConfigModel['originalPrice'], // 原始价格
    			'outTradeNo' => '',
    		);
    	}
    	// 获取购买的订单号
    	if (!empty($userVipList)) {
    		$orderDao = \dao\Order::singleton();
    		$where = "`goodsType`=" . \constant\Order::TYPE_GOODS_VIP . ' and `goodsId` in (' . implode(',', array_keys($userVipList)) . ')';
    		$orderEttList = $orderDao->readListByWhere($where);
    		if (is_iteratable($orderEttList)) foreach ($orderEttList as $orderEtt) {
    			if (!empty($userVipList[$orderEtt->goodsId])) {
    				$userVipList[$orderEtt->goodsId]['outTradeNo'] = $orderEtt->outTradeNo;
    			}
    		}
    	}
    	$showVipModel = array(); // 优先展示的vip，展示级别最高，到期时间靠后的
    	foreach ($userVipList as $userVip) {
    		if ($userVip['effectEndTime'] <= $now) { // vip已失效
    			continue;
    		}
    		if (empty($showVipModel)) {
    			$showVipModel = $userVip;
    		} elseif ($userVip['type'] > $showVipModel['type']) { // 显示当前生效且最牛逼的
    			$showVipModel = $userVip;
    		} elseif ($userVip['type'] == $showVipModel['type'] && $userVip['effectEndTime'] > $showVipModel['effectEndTime']) {
    			$showVipModel = $userVip;
    		}
    	}
    	$vipModel = array(); // vip信息
    	
    	if (!empty($showVipModel)) {
    		$vipModel = $showVipModel;
    	}
    	$userModel = $userEtt->getModel();
    	$userModel['vipInfo'] = $vipModel;
        return array(
        	'userInfo' => $userModel,
        	'configList' => array(),
        );
    }
    
    /**
     * 修改账号信息
     *
     * @return array
     */
    public function reviseUser($userId, $info)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('账号不存在');
    	}
    	if (!empty($info['userName']) && $info['userName'] != $userEtt->userName) {
    		$userEtt->set('userName', $info['userName']);
    	}
    	if (!empty($info['phone']) && $info['phone'] != $userEtt->phone) {
    		$userEtt->set('phone', $info['phone']);
    	}

    	// 绑定分享用户
    	$level = empty($userEtt->parentUserId) ? 1 : $userEtt->level;
    	
    	if (!empty($info['parentUserId']) && $info['parentUserId'] != $userEtt->parentUserId) {
    		if (!empty($userEtt->parentUserId)) {
    			throw new $this->exception('账号已绑定过分销');
    		}
    		$parentUserEtt = $userDao->readByPrimary($userEtt->parentUserId); // 上级
    		if (!empty($parentUserEtt) && $userEtt->status != \constant\Common::DATA_DELETE) {
    			if (!empty($parentUserEtt->parentUserId)) {
    				$parentUserEtt2 = $userDao->readByPrimary($parentUserEtt->parentUserId);
    				if (empty($parentUserEtt2->parentUserId)) {
    					$level = 3;
    				} else {
    					throw new $this->exception('3级分销无法绑定');
    				}
    			} else {
    				$level = 2;
    			}
    			$userEtt->set('level', $level);
    			$userEtt->set('parentUserId', $info['parentUserId']);
    		}
    	}
    	if (!empty($info['imageInfo'])) {
    		$file = $info['imageInfo']['file']; // 文件内容
    		$fileSize = filesize($file); // 文件大小
    		$fileInfo = pathInfo($file);
    		$extension = $fileInfo['extension'];
    		$profileKey = "resources/userHeadImg/{$userId}.{$extension}"; // 上传的目录
    		$ossSv = \service\reuse\OSS::singleton();
    		$ossConf = cfg('server.oss.yinhuo'); // 阿里云配置
    		$ossSv->init($ossConf['ACCESS_KEY_ID'], $ossConf['ACCESS_KEY_SECRET']);
    		$ossResult = $ossSv::publicUploadContent($ossConf['BUCKET'], $profileKey, file_get_contents($file));
    		if (empty($ossResult)) {
    			throw new $this->exception('头像上传失败');
    		}
    		$headImgUrl = trim($ossConf['JSOSS'], 'resources/') . DS . $profileKey;
    		$userEtt->set('headImgUrl', $headImgUrl);
    	}
    	$now = $this->frame->now;
    	$userEtt->set('updateTime', $now);
    	$userDao->update($userEtt);
    	return $this->userInfo($userEtt);
    }
    
    /**
     * 注销登录
     *
     * @param  int  $userId  用户id
     *
     * @return array
     */
    public function logout($userId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('账号不存在');
    	}
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 获取用户列表
     *
     * @return array
     */
    public function getUserModels($userIds)
    {
    	$userDao = \dao\User::singleton();
    	$userEttList = $userDao->readListByPrimary($userIds);
    	$parentUserIds = array();
    	if (!empty($userEttList)) foreach ($userEttList as $userEtt) {
    		if (empty($userEtt->parentUserId)) {
    			continue;
    		}
    		$parentUserIds[$userEtt->parentUserId] = intval($userEtt->parentUserId);
    	}
    	$parentUserEttList = empty($parentUserIds) ? array() : $userDao->readListByPrimary($parentUserIds);
    	$parentUserEttList = $userDao->refactorListByKey($parentUserEttList);
    	$parentUserIds2 = array();
    	if (!empty($parentUserEttList)) foreach ($parentUserEttList as $parentUserEtt) {
    		if (empty($parentUserEtt->parentUserId)) {
    			continue;
    		}
    		$parentUserIds2[$parentUserEtt->parentUserId] = intval($parentUserEtt->parentUserId);
    	}
    	$parentUserEttList2 = empty($parentUserIds2) ? array() : $userDao->readListByPrimary($parentUserIds2);
    	$parentUserEttList2 = $userDao->refactorListByKey($parentUserEttList2);
    	$models = array();
    	if (!empty($userEttList)) foreach ($userEttList as $userEtt) {
    		$parentUser = array();
    		$topParentUser = array();
    		if (!empty($userEtt->parentUserId) && !empty($parentUserEttList[$userEtt->parentUserId])) {
    			$parentUserEtt = $parentUserEttList[$userEtt->parentUserId];
    			$parentUser = array(
	    			'userId' => intval($parentUserEtt->userId),
	    			'status' => intval($parentUserEtt->status),
    				'level' => intval($parentUserEtt->level),
	    			'sex' => intval($parentUserEtt->sex),
    				'gold' => intval($parentUserEtt->gold),
	    			'userName' => $parentUserEtt->userName,
    				'openid' => $parentUserEtt->openid,
	    			'headImgUrl' => $parentUserEtt->headImgUrl,
	    			'phone' => $parentUserEtt->phone,
	    			'updateTime' => intval($parentUserEtt->updateTime),
	    			'createTime' => intval($parentUserEtt->createTime),
    			);
    			if (!empty($parentUserEtt->parentUserId) && !empty($parentUserEttList2[$parentUserEtt->parentUserId])) {
    				$parentUserEtt2 = $parentUserEttList2[$parentUserEtt->parentUserId];
    				$topParentUser = array(
    					'userId' => intval($parentUserEtt2->userId),
    					'level' => intval($parentUserEtt2->level),
    					'status' => intval($parentUserEtt2->status),
    					'sex' => intval($parentUserEtt2->sex),
    					'gold' => intval($parentUserEtt2->gold),
    					'userName' => $parentUserEtt2->userName,
    					'openid' => $parentUserEtt2->openid,
    					'headImgUrl' => $parentUserEtt2->headImgUrl,
    					'phone' => $parentUserEtt2->phone,
    					'updateTime' => intval($parentUserEtt2->updateTime),
    					'createTime' => intval($parentUserEtt2->createTime),
    				);
    			}
    		}
    		$models[$userEtt->userId] = array(
    			'userId' => intval($userEtt->userId),
    			'status' => intval($userEtt->status),
    			'sex' => intval($userEtt->sex),
    			'gold' => intval($userEtt->gold),
    			'level' => intval($userEtt->level),
    			'openid' => $userEtt->openid,
    			'parentUserId' => intval($userEtt->parentUserId),
    			'userName' => $userEtt->userName,
    			'headImgUrl' => $userEtt->headImgUrl,
    			'phone' => $userEtt->phone,
    			'updateTime' => intval($userEtt->updateTime),
    			'createTime' => intval($userEtt->createTime),
    			'parentUser' => $parentUser, // 上级用户信息
    			'topParentUser' => $topParentUser, // 上上级用户信息
    		);
    	}
    	return $models;
    }
    
    /**
     * 获取用户列表
     *
     * @return array
     */
    public function getUserList($userId, $info, $pageNum, $pageLimit)
    {
    	$userDao = \dao\User::singleton();
    	$dataList = $userDao->getList($info, $pageNum, $pageLimit);
    	$userIds = array_column($dataList, 'userId');
    	
    	$userSv = \service\User::singleton();
    	$userModels = $userSv->getUserModels($userIds);
    	if (empty($userModels)) {
    		return array(
    			'list'     => array_values($userModels),
    			'totalNum' => 0,
    		);
    	}
    	// 剪辑工程
    	$projectDao = \dao\Project::singleton();
    	$where = "`userId` in (" . implode(',', $userIds) . ") and `status` != " . \constant\Common::DATA_DELETE;
    	$projectEttList = $projectDao->readListByWhere($where);
    	$projectMap = array();
    	$projectIds = array();
    	if (!empty($projectEttList)) foreach ($projectEttList as $projectEtt) {
    		$projectMap[$projectEtt->userId][$projectEtt->id] = $projectEtt->name;
    		$projectIds[$projectEtt->id] = $projectEtt->userId;
    	}
    	// 剪辑模板
    	$templateDao = \dao\Template::singleton();
    	$where = "`userId` in (" . implode(',', $userIds) . ") and `status` != " . \constant\Common::DATA_DELETE;
    	$templateEttList = $templateDao->readListByWhere($where);
    	$templateMap = array();
    	if (!empty($templateEttList)) foreach ($templateEttList as $templateEtt) {
    		$templateMap[$templateEtt->userId][$templateEtt->id] = $templateEtt->name;
    	}
    	// 剪辑视频
    	$projectClipDao = \dao\ProjectClip::singleton();
    	$where = "`projectId` in ('" . implode("','", array_keys($projectIds)) . "') and `status` != " . \constant\Common::DATA_DELETE;
    	$projectClipEttList = $projectClipDao->readListByWhere($where);
    	$projectClipMap = array();
    	if (!empty($projectClipEttList)) foreach ($projectClipEttList as $projectClipEtt) {
    		$tmpUserId = $projectIds[$projectClipEtt->projectId];
    		$projectClipMap[$tmpUserId][$projectClipEtt->id] = $projectClipEtt->mediaURL;
    	}
    	// 获取分销下线
    	$subUserMap = array();
    	$where = "`parentUserId` in (" . implode(',', $userIds) . ") and `status` != " . \constant\Common::DATA_DELETE;
    	$subUserEttList = $userDao->readListByWhere($where);
    	if (!empty($subUserEttList)) foreach ($subUserEttList as $subUserEtt) {
    		$subUserMap[$subUserEtt->parentUserId][$subUserEtt->userId] = $subUserEtt->userName;
    	}
    	
    	foreach ($userModels as $userId => $userModel) {
    		$userModel['projectNum'] = empty($projectMap[$userId]) ? 0 : count($projectMap[$userId]);
    		$userModel['templateNum'] = empty($templateMap[$userId]) ? 0 : count($templateMap[$userId]);
    		$userModel['projectClipNum'] = empty($projectClipMap[$userId]) ? 0 : count($projectClipMap[$userId]);
    		
    		$userModel['subUserNum'] = empty($subUserMap[$userId]) ? 0 : count($subUserMap[$userId]);
    		$userModels[$userId] = $userModel;
    	}
    
    	// 获取查询总数
    	$totalNum = $userDao->getList($info, -1);
    	return array(
    		'list'     => array_values($userModels),
    		'totalNum' => $totalNum,
    	);
    }
    
    /**
     * 获取用户的等级，返利关系
     *
     * @return array
     */
    public function getProfitSharingList($userId)
    {
    	$userDao = \dao\User::singleton();
    	$userEtt = $userDao->readByPrimary($userId);
    	if (empty($userEtt) || $userEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('账号不存在');
    	}
    	// 获取分销下线
    	$subUserMap = array();
    	$where = "`parentUserId` = {$userId} and `status` != " . \constant\Common::DATA_DELETE;
    	$subUserEttList = $userDao->readListByWhere($where);
    	$subUserIds = array_column($subUserEttList, 'userId');
    	// 分销下线线
    	if (!empty($subUserIds)) {
    		$where = "`parentUserId` in (" . implode(',', $subUserIds) . ") and `status` != " . \constant\Common::DATA_DELETE;
    		$subUserEttList2 = $userDao->readListByWhere($where);
    	}

    	$info = array(
    		'searchUserId' => $userId,
    		'searchStatus' => 1,
    	);
    	$profitSharingDao = \dao\ProfitSharing::singleton();
    	$dataList = $profitSharingDao->getList($info, 1, 9999);
    	$userIds = array_column($dataList, 'userId');
    	$parentUserIds = array_column($dataList, 'parentUserId');
    	$profitSharingUserIds = array_column($dataList, 'userId');
    	$orderIds = array_column($dataList, 'orderId');
    	$fromUserIds = array_column($dataList, 'fromUserId');
    	$orderDao = \dao\Order::singleton();
    	$orderEttList = $orderDao->readListByPrimary($orderIds);
    	$orderEttList = $orderDao->refactorListByKey($orderEttList);
    	$userSv = \service\User::singleton();
    	$userModels = $userSv->getUserModels(array_merge($fromUserIds, $parentUserIds, $profitSharingUserIds, $userIds));
    
    	$models = array();
    	foreach ($dataList as $profitSharingEtt) {
    		$orderEtt = empty($orderEttList[$profitSharingEtt->orderId]) ? array() : $orderEttList[$profitSharingEtt->orderId];
    		$orderInfo = array();
    		$topLevel = 1;
    		if (!empty($orderEtt)) {
    			$orderInfo = array(
    				'id' => intval($orderEtt->id),
    				'outTradeNo' => $orderEtt->outTradeNo,
    				'status' => intval($orderEtt->status),
    				'userId' => intval($orderEtt->userId),
    				'price' => $orderEtt->price,
    				'updateTime' => intval($orderEtt->updateTime),
    				'createTime' => intval($orderEtt->createTime),
    				'userInfo' => empty($userModels[$orderEtt->userId]) ? array() : $userModels[$orderEtt->userId],
    			);
    			if (!empty($orderInfo['userInfo']) && $orderInfo['userInfo']['parentUserId'] != $profitSharingEtt->userId) {
    				$topLevel = 2;
    			}
    		}
    		$models[] = array(
    			'id' => intval($profitSharingEtt->id),
    			'receiverAddOpenId' => $profitSharingEtt->receiverAddOpenId,
    			'currentGold' => intval($profitSharingEtt->currentGold),
    			'orderId' => intval($profitSharingEtt->orderId),
    			'outTradeNo' => empty($orderInfo['outTradeNo']) ? '' : $orderInfo['outTradeNo'],
    			'orderInfo' => $orderInfo,
    			'addGold' => intval($profitSharingEtt->addGold),
    			'status' => intval($profitSharingEtt->status),
    			'parentUserId' => intval($profitSharingEtt->parentUserId),
    			'topLevel' => $topLevel,
    			'userId' => intval($profitSharingEtt->userId),
    			'fromUserId' => intval($profitSharingEtt->fromUserId),
    			'updateTime' => intval($profitSharingEtt->updateTime),
    			'createTime' => intval($profitSharingEtt->createTime),
    			'userInfo' => empty($userModels[$profitSharingEtt->userId]) ? array() : $userModels[$profitSharingEtt->userId],
    			'fromUserInfo' => empty($userModels[$profitSharingEtt->fromUserId]) ? array() : $userModels[$profitSharingEtt->fromUserId],
    			'parentUserInfo' => empty($userModels[$profitSharingEtt->parentUserId]) ? array() : $userModels[$profitSharingEtt->parentUserId],
    		);
    	}
    	// 获取查询总数
    	$totalNum = $profitSharingDao->getList($info, -1);
    	return array(
    		'list'     => array_values($models),
    		'totalNum' => $totalNum,
    	);
    	
    }
}