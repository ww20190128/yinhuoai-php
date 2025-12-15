<?php
namespace service\reuse;

/**
 * User 通用类
 *
 * @author wangwei
 */
class User extends \service\ServiceBase
{
    /**
     * 是否在线
     *
     * @var bool
     */
    private static $logged = false;

    /**
     * 用户登录信息
     *
     * @var array
     */
    private static $userInfo = array();

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
     * 获取登录的用户信息
     *
     * @return array
     */
    public static function getUserInfo()
    {
        if (empty(self::$userInfo) && isset($_SESSION['id'])) {
        	//parse_str(decrypt($_SESSION['key']), $userInfo);
            self::$userInfo = ['userId' => $_SESSION['id']];
        }
        return self::$userInfo;
    }

    /**
     * 判断用户是否登录
     *
     * @return array | bool
     */
    public static function checkLogged()
    {
        $userInfo = self::getUserInfo();
        if (empty($userInfo)) {
            return false;
        }
        if (!$userInfo['userId']) { // COOKIE被非法篡改
            self::removeUserInfo();
            return false;
        }
        self::$logged = true;
        return $userInfo;
    }

    /**
     * 删除用户登录信息
     *
     * @return bool
     */
    public static function removeUserInfo()
    {
    	session_unset();
		session_destroy();
        unset($_SESSION['id']);
        self::$userInfo = array();
        self::$logged = false;
        return true;
    }

    /**
     * 设置用户登录信息

     * @param   array   $info   信息
     * 
     * @return string
     */
    public static function setUserlogin($info)
    {
        //$encryptStr = encrypt(str_replace('&amp;', '&', http_build_query($info)));
        $sid = self::getSessionId(str_replace('&amp;', '&', http_build_query($info)));
        session_destroy();
        session_id($sid);
        session_start();
        $_SESSION['id']  = $info['userId'];
        //$_SESSION['key'] = $encryptStr;
        return $sid;
    }

	/**
     * 获取session_id
     * 
     * @param	string	$userName	角色名
     * 
     * @return string
     */
    public static function getSessionId($userName) 
    {
        return md5($_SERVER['HTTP_USER_AGENT'] . $userName);
    }
	
    /**
     * 获取角色id
     * 
     * @param	string	$key 请求数据key
     * 
     * @return int
     */
	public static function getUserId() 
	{
  		$userInfo = self::getUserInfo();
        return $userInfo ? intval($userInfo['userId']) : 0;
    }
    
}