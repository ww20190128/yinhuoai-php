<?php
namespace ctrl;

/**
 * 管理后台-账号
 * 
 * @author 
 */
class BackstageUser extends CtrlBase
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
	 * 登录
	 *
	 * @return array
	 */
	public function login()
	{			
		$params = $this->params;
		$userName = $this->paramFilter('userName'); // 姓名
		$password = $this->paramFilter('password'); // 密码
		if (empty($userName) || empty($password)) {
			throw new $this->exception('请求参数错误');
		}
		$backstageUserSv = \service\BackstageUser::singleton();
		$result = $backstageUserSv->login($userName, md5($password));
		return $result;
	}
	
	/**
	 * 获取账号列表
	 *
	 * @return array
	 */
	public function getUsers()
	{
		$params = $this->params;
		if (empty($params->userId)) {
			throw new $this->exception('请求参数错误');
		}
		$info =  array(
			'searchUserName' 		=> $this->paramFilter('searchUserName', 'string'),
			'searchPhone' 			=> $this->paramFilter('searchPhone', 'string'),
			'searchShareUserId' 	=> $this->paramFilter('searchShareUserId', 'intval'),
			'searchPrivilege' 		=> $this->paramFilter('searchPrivilege', 'string'),
			'searchUserIds' 		=> $this->paramFilter('searchUserIds', 'array'),
		);
		$pageNum = $this->paramFilter('pageNum', 'intval');  	// 页码
		$pageLimit = $this->paramFilter('pageLimit', 'intval'); // 每页数量限制
		$backstageUserSv = \service\BackstageUser::singleton();
		return $backstageUserSv->getUsers($params->userId, $info, $pageNum, $pageLimit);
	}

	/**
	 * 获取登录token
	 *
	 * @return array
	 */
	public function refreshToken()
	{
		$params = $this->params;
		if (empty($params->loginRefreshKey)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 3));
		}
		$backstageUserSv = \service\BackstageUser::singleton();
		return $backstageUserSv->refreshToken($params->loginRefreshKey);
	}
	
	/**
	 * 获取用户信息
	 *
	 * @return array
	 */
	public function userInfo()
	{
		$params = $this->params;
		if (empty($params->userId)) {
			throw new $this->exception('请求参数错误');
		}
		$backstageUserSv = \service\BackstageUser::singleton();
		$result = $backstageUserSv->userInfo($params->userId);
		// TODO 屏蔽手机号
		if (!empty($result['user']['phone'])) {
			$result['user']['phone'] = substr_replace($result['user']['phone'], '****', 3, 4);
		}
		return $result;
	}
	
	/**
	 * 删除后台账号
	 *
	 * @return array
	 */
	public function deleteUser()
	{
		$params = $this->params;
		$id = empty($params->id)? 0 : intval($params->id); // 被删除者账号id
		if (empty($params->id)) {
			throw new $this->exception('请求参数错误');
		}
		$backstageUserSv = \service\BackstageUser::singleton();
		// 检查删除者是否有权限
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		} else {
			if ($this->userId == $id) {
				throw new $this->exception('不可删除自己');
			}
		}
		return $backstageUserSv->deleteUser($id, $this->userId);
	}

	/**
	 * 创建账号
	 *
	 * @return array
	 */
	public function createUser()
	{
		$params = $this->params;
		$backstageUserSv = \service\BackstageUser::singleton();
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}

		$base64Image = empty($params->base64Image) ? '' : $params->base64Image;
		$imageInfo = array();
		if (!empty($base64Image) && preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64Image, $matches)) {
			$imageInfo = array(
				'suffix' => $matches['2'],
				'data'	=> 	base64_decode(str_replace($matches[1],'', $base64Image)),
			);
		}
		// 根据账号类型分配权限
	
		// 权限
		$map = empty($params->map) ?  array() : json_decode($params->map, true);
		$privilegeMap = array();
		$showPrivilege = empty($map['show']) ? array() : $map['show']; // 显示的权限
		if (is_iteratable($showPrivilege)) foreach ($showPrivilege as $row) {
			$privilegeMap['show'][$row['id']] = $row;
		}
		$op = empty($map['op']) ? array() : $map['op']; // 操作的权限
		if (is_iteratable($op)) foreach ($op as $row) {
			$privilegeMap['op'][$row['id']] = $row;
		}
	
		$startTime = $this->paramFilter('startTime', 'intval', 0); // 有效开始时间
		$endTime = $this->paramFilter('endTime', 'intval', 0); // 有效结束时间

		$userName = $this->paramFilter('userName', 'string');
		$password = $this->paramFilter('password', 'string');
		$phone = $this->paramFilter('phone', 'intval');
		$shareUserIds = $this->paramFilter('shareUserIds', 'string'); // 绑定的分享账号
		$shareUserIds = empty($shareUserIds) ? array(): explode(',', $shareUserIds);
		if (empty($userName)) {
			throw new $this->exception('请设置姓名');
		}
		if (empty($password)) {
			throw new $this->exception('请设置密码');
		}

		if (empty($shareUserIds)) {
			throw new $this->exception('请绑定分享的账号');
		}
		// 检查手机号格式
		if (empty($phone) || !preg_match(cfg('common.regular.phone'), $phone)) {
			throw new $this->exception('请输入正确的手机号');
		}
		$now = $this->frame->now;
		if (empty($startTime) || empty($endTime) || $endTime <= $startTime) {
			throw new $this->exception('有效时间配置错误');
		}
		$type = $this->paramFilter('type', 'intval', 0); // 账号类型  0 特邀推广员 1 管理员
		$info = array(
			'userName' 			=> $userName, 		// 姓名
			'password'  		=> $password, 		// 密码
			'privilegeMap'  	=> $privilegeMap, 	// 权限列表
			'imageInfo'  		=> $imageInfo,
			'phone'  			=> $phone, 			// 手机号
			'startTime'			=> $startTime,
			'endTime'			=> $endTime,
			'shareUserIds'		=> $shareUserIds,
			'type'				=> $type,
		);
		return $backstageUserSv->createUser($params->userId, $info);
	}
	
	/**
	 * 修改账号
	 *
	 * @return array
	 */
	public function reviseUser()
	{
		$params = $this->params;
		$id = $this->paramFilter('id', 'intval', 0); // 修改的账号id
		if (empty($params->id)) {
			throw new $this->exception('请求参数错误');
		}
		$backstageUserSv = \service\BackstageUser::singleton();
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		$base64Image = empty($params->base64Image) ? '' : $params->base64Image;
		$imageInfo = array();
		if (!empty($base64Image) && preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64Image, $matches)) {
			$imageInfo = array(
				'suffix' => $matches['2'],
				'data'	=> 	base64_decode(str_replace($matches[1],'', $base64Image)),
			);
		}
		// 权限
		$map = empty($params->map) ?  array() : json_decode($params->map, true);
		$privilegeMap = array();
		$showPrivilege = empty($map['show']) ? array() : $map['show']; // 显示的权限
		if (is_iteratable($showPrivilege)) foreach ($showPrivilege as $row) {
			$privilegeMap['show'][$row['id']] = $row;
		}
		$op = empty($map['op']) ? array() : $map['op']; // 操作的权限
		if (is_iteratable($op)) foreach ($op as $row) {
			$privilegeMap['op'][$row['id']] = $row;
		}
		
		$startTime = $this->paramFilter('startTime', 'intval', 0); // 有效开始时间
		$endTime = $this->paramFilter('endTime', 'intval', 0); // 有效结束时间
		
		$userName = $this->paramFilter('userName', 'string');
		$password = $this->paramFilter('password', 'string');
		$phone = $this->paramFilter('phone', 'intval');
		if (empty($userName)) {
			//throw new $this->exception('请设置姓名');
		}
		if (empty($password)) {
			//throw new $this->exception('请设置密码');
		}
		// 检查手机号格式
		// 		if (empty($phone) || !preg_match(cfg('common.regular.phone'), $phone)) {
		// 			throw new $this->exception('请输入正确的手机号');
		// 		}
		$now = $this->frame->now;
		$shareUserIds = $this->paramFilter('shareUserIds', 'string'); // 绑定的分享账号
		$shareUserIds = empty($shareUserIds) ? array(): explode(',', $shareUserIds);
		
		$info = array(
			'userName' 			=> $userName, 	// 姓名
			'password'  		=> $password == '******' ? '' : md5($password), 	// 密码
			//'phone'  			=> $phone, 			// 手机号
			'privilegeMap'  	=> $privilegeMap, 	// 权限列表
			'status'  			=> $this->paramFilter('status', 'intval'), // 状态,
			'imageInfo'  		=> $imageInfo,
			'startTime'			=> $startTime,
			'endTime'			=> $endTime,
			'shareUserIds'		=> $shareUserIds,
		);
		return $backstageUserSv->reviseUser($this->userId, $id, $info);
	}
	
	/**
	 * 修改分享用户信息（调整收益占比）
	 *
	 * @return array
	 */
	public function reviseShareUser()
	{
		$params = $this->params;
		$shareUserId = $this->paramFilter('shareUserId', 'intval', 0); // 修改的账号id
		$commissionRate = $this->paramFilter('commissionRate', 'intval', 0);
		if (empty($shareUserId) || empty($commissionRate)) {
			throw new $this->exception('请求参数错误');
		}
		
		$backstageUserSv = \service\BackstageUser::singleton();
		if (empty($this->userId)) {
			throw new $this->exception('登录已过期，请重新登录', array('status' => 2));
		}
		
		$info = array(
			'commissionRate' => $commissionRate, 
		);
		return $backstageUserSv->reviseShareUser($this->userId, $shareUserId, $info);
	}
	
	/**
	 * 消息列表
	 */
	public function getMessageList()
	{
		return array(
			'totalNum' => 0,
			'list' => array(),
		);
	}

}