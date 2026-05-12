<?php
namespace ctrl;

/**
 * 用户
 * 
 * @package ctrl
 */
class User extends CtrlBase
{
	/**
	 * 微信登录
	 *
	 * @return array
	 */
	public function loginByWeChat()
	{
		$params = $this->params;
		$code = $this->paramFilter('code', 'string'); // 回调码
		if (empty($code)) {
			throw new $this->exception('请求参数错误');
		}
		$userSv = \service\User::singleton();
		return $userSv->loginByWeChat($code);
	}

    /**
     * 获取用户信息
     *
     * @return array
     */
    public function userInfo()
    {
        $params = $this->params;
    	if (empty($this->userId)) {
    		$userId = $this->paramFilter('userId', 'intval');
    	} else {
    		$userId = $this->userId;
    	}
    	if (empty($userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
        $userSv = \service\User::singleton();
        return $userSv->userInfo($userId);
    }
    
    /**
     * 修改账号
     *
     * @return array
     */
    public function reviseUser()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$imageInfo = array();
		$files = empty($_FILES) ? array() : $_FILES; // 上传的图片信息
		if (!empty($files)) foreach ($files as $file) {
			$fileInfo = pathinfo($file['name']);
			$fileTmp = '/tmp/' .  $file['name'];
			move_uploaded_file($file['tmp_name'], $fileTmp);
			if (!file_exists($fileTmp)) {
				throw new $this->exception('文件上传失败');
			}
			$imageInfo = array(
				'extension' => $fileInfo['extension'],
				'file' 		=> $fileTmp,
			);
		}	
		$userSv = \service\User::singleton();
    	$userName = $this->paramFilter('userName', 'string');
    	$phone = $this->paramFilter('phone', 'intval');
    	// 检查手机号格式
    	if (!empty($phone) && !preg_match(cfg('common.regular.phone'), $phone)) {
    		throw new $this->exception('请输入正确的手机号');
    	}
    	$parentUserId = $this->paramFilter('parentUserId', 'intval');
    	$now = $this->frame->now;
    	$info = array(
    		'userName' 			=> $userName, 	// 姓名
    		'phone'  			=> $phone, 		// 手机号
    		'imageInfo'  		=> $imageInfo,
    		'parentUserId'  	=> $parentUserId,
    	);
    	return $userSv->reviseUser($this->userId, $info);
    }
    
    /**
     * 注销登录
     *
     * @return array
     */
    public function logout()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('请求参数错误');
    	}
    	$userSv = \service\User::singleton();
    	$result = $userSv->logout($this->userId);
    	return $result;
    }

    /**
     * 获取用户列表
     *
     * @return array
     */
    public function getUserList()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$searchStartTime = $this->paramFilter('searchStartTime', 'intval'); // 开始时间
    	$searchEndTime = $this->paramFilter('searchEndTime', 'intval'); // 结束时间
    	$searchStatus = $this->paramFilter('searchStatus', 'intval'); // 支付状态
    	
    	$searchUserLevel = $this->paramFilter('searchUserLevel', 'intval'); // 支付状态
    	
    	$searchParentUserId = $this->paramFilter('searchParentUserId', 'intval'); // 用户ID
    	$info = array(
    		'searchStatus' 	  => $searchStatus,
    		'searchStartTime' => empty($searchStartTime) ? 0 : strtotime($searchStartTime),
    		'searchEndTime'   => empty($searchEndTime) ? 0 : strtotime($searchEndTime) + 86399,
    		'searchUserLevel' => $searchUserLevel,
    		'searchParentUserId' => $searchParentUserId,
    	);
    	$pageNum = $this->paramFilter('pageNum', 'intval', 1); // 页码
    	$pageLimit = $this->paramFilter('pageLimit', 'intval', 20); // 每页数量限制
    	$userSv = \service\User::singleton();
    	return $userSv->getUserList($this->userId, $info, $pageNum, $pageLimit);
    }

    /**
     * 获取返利订单列表
     *
     * @return array
     */
    public function getProfitSharingList()
    {
    	$params = $this->params;
    	if (empty($this->userId)) {
    		throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
    	}
    	$userSv = \service\User::singleton();
    	return $userSv->getProfitSharingList($this->userId);
    }
    
}