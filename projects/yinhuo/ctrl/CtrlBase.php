<?php
namespace ctrl;
use Dispatch\FrameBase;

/**
 * 控制器层抽象基类
 * 
 * @author wangwei
 */
abstract class CtrlBase extends FrameBase
{
    // 不需要登录检查
    protected $exceptLogin = [
        'User.login',
        'User.dingdingLogin',
        'User.loginParams',
        'User.smsLogin',
        'User.sendSmsCode',
        'User.getList',
    	'App.publish'	
    ];

	/**
	 * 在执行控制器方法之前执行的过滤方法，该方法返回布尔类型值，当返回结果为true时，继续执行请求的方法，当返回结果为false时，终端请求的执行。
	 * 该方法可用于用户认证或者请求加锁等。
	 * 
	 * @return boolean 
	 */
    public function beforeFilter()
    {
        // 命令行
        if(\Bootstrap::$runType != \Bootstrap::RUN_MODE_WEB){
            return true;
        }
       
        $params = $this->frame->params;
        if (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            return false;
        }
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) { 
        	$authorization = substr($_SERVER['HTTP_AUTHORIZATION'], strlen('Bearer '));
        	$authorizationArr = explode('.', $authorization);
        	$authorizationInfo = empty($authorizationArr['1']) ? array() : json_decode(base64_decode($authorizationArr['1']), true);

        	if (!empty($authorizationInfo['uid'])) {
        		$this->userId = intval($authorizationInfo['uid']);
        	}
        	if (empty($this->userId)) {
        	    $authorization = decrypt($authorization); // 解密
        	    $authorizationInfo = empty($authorization) ? array() : json_decode(base64_decode($authorization), true);
        	    if (!empty($authorizationInfo['userId'])) {
        	        $this->userId = intval($authorizationInfo['userId']);
        	    }
        	}
        }
        if (empty($this->userId) && !empty($params->userId)) {
            $this->userId = intval($params->userId);
        }
        return true;
        
        if (in_array($params->op, ['Token.getToken', 'Token.refreshToken', 'User.checkLogged', 'App.publish'])) {
            // 排除不需要token验证的
            return true;
        }

        // 服务端调用
        // 直接使用appKey、appSecret、opUserId 及可调用接口
        if (!empty($params->appKey) && !empty($params->appSecret)) {
            // 验证服务端appKey
            \service\App::singleton()->check(['appKey' => $params->appKey, 'appSecret' => $params->appSecret]);
            if (!empty($params->opUserId)) {
                // 检查操作的userId
                $userEtt = \dao\User::singleton()->readByPrimary($params->opUserId);
                if (empty($userEtt)) {
                    throw new $this->exception('帐号不存在', ['status' => 3]);
                }
                \service\User::singleton()->checkStatus($userEtt);
                $this->userId = $userEtt->userId;
                return true;
            } elseif (in_array($params->op, $this->exceptLogin) || in_array($params->op, ['User.userInfo', 'User.getUsers'])) {
                // 兼容之前的，可以不提供opUserId参数访问有限的接口
                return true;
            }
        }

        // 检查token
        if (!isset($params->token)) {
            throw new $this->exception('缺少token参数!');
        }

        $info = [];
        if (in_array($params->op, $this->exceptLogin)) {
            // 不需要验证登录的，只检查下token
            $info = \service\Token::singleton()->check($params->token);
            return true;
        }

        // 检查登录情况
        $info = \service\User::singleton()->checkLogged($params->token);
        $this->userId = $info['userId'];

        return true;
    }
	
	/**
	 * 在执行控制器方法之后执行，无论beforeFilter是否返回为true，该方法都会得到执行。
	 * 该方法可用于日志记录或者请求解锁等。
	 * 
	 * @see beforeFilter();
	 */
	public function afterFilter(){}
	
	/**
	 * 请求参数
	 * 
	 * @var object
	 */
	protected $params;
	
	/**
	 * 角色id
	 * 
	 * @var int
	 */
	protected $userId = 0;
	
    /**
     * 进程锁数组
     *
     * @var array
     */
    private $locks = array();
	
    /**
     * 构造函数
     *
     * @return \ctrl\CtrlBase
     */
	public function __construct() 
	{
		parent::__construct(__NAMESPACE__);
		$this->params = $this->dispatcher->getParams();
		// 用户id赋值		
		/* // 检查是否外网用户请求内网服务器
		$testWhiteList = cfg('server.test_white_list');	
		if (!empty($this->frame->mark) && isset($testWhiteList[$this->frame->mark])) { // 测试服务器
    		// 检查ip是否在
    		$whiteIps = ipList($testWhiteList[$this->frame->mark]);	// 服务器白名单ip列表
			$selfIp = \service\Client::getIP();
			if ($selfIp != 'unknown' && !in_array($selfIp, $whiteIps)) { // 玩家ip不在白名单中
				throw new $this->exception('服务器正在维护中');
			}
    	}
    	$maintain = $this->checkMaintain();
    	if (is_array($maintain)) { // 停服维护中
			// IP白名单, 配置在服务器配置文件中
			$whiteList = empty($this->frame->conf['white_list']) 
				? array() : $this->frame->conf['white_list'];
			$whiteIps = ipList($whiteList); // 服务器白名单ip列表
        	if (!in_array(\service\Client::getIP(), $whiteIps)) {
        		throw new $this->exception('服务器正在维护中');
        	}
     	} 
     	$this->initIndex();
     	*/
		$this->addShutdownCallBack(array($this, 'shutdownUnlock'));
		if (!empty($this->userId) && !empty($this->params->op)) {
			$this->lock($this->params->op, $this->userId);
		}
		$this->frame->params = $this->params;
		return;
	}
	
	/**
     * 检查是否停服维护中
     * 
     * @return bool|array
     */
    public function checkMaintain()
    {
        $now = $this->frame->now;
        $serverConf = $this->frame->conf;
        // 维护开始时间
        $maintainStartTime  = empty($serverConf['maintain_start_time']) 
        	? 0 : strtotime($serverConf['maintain_start_time']);
        // 维护结束时间
        $maintainEndTime  = empty($serverConf['maintain_end_time']) 
        	? 0 : strtotime($serverConf['maintain_end_time']);  	
        if ($now > $maintainStartTime && $now < $maintainEndTime) {
        	return array(
        		'startTime' => $maintainStartTime,
        		'endTime' 	=> $maintainEndTime,
        	);
        } 
        return false;
    }
	
	/**
     * 前台请求初始化操作
     * 
     * @return void
     */
    protected function initIndex()
    {
        if (empty($this->userId) && (!empty($this->params->op) 
        	&& is_numeric($this->params->op) && !in_array($this->params->op, array(1001, 1002)))
        ) {
            throw new $this->exception('尚未登录无法访问');        
       	}
        return;
    }
    
	/**
     * 请求加锁
     *
     * @param   string  	$key        锁key
     * @param   string  	$value      锁值
     * @param   int     	$ttl        生效时间
     * @param   float   	$timeout    超时时间
     * 
     * @return bool
     */
    public function lock($key, $value, $ttl = 0, $timeout = 0.100)
    {
        $key = 't://run.lock.' . crc32($key . "[$value]");
        $cache = $this->cache;
        if (empty($cache)) {
        	return false;
        }
        $timeout += microtime(1);
        if ($ttl == 0) {
            $ttl = 5;
            if (isset($this->locks[$key])) {
                return true;
            }
            $this->locks[$key] = $ttl; 
        }
        do {
            $ok = $cache->add($key, 1, $ttl);
        } while (!$ok && microtime(1) < $timeout && usleep(20000) === null);
        return $ok;
    }

    /**
     * 解锁
     *
     * @param   string  $key        锁key
     * @param   string  $value      锁值
     *
     * @return bool
     */
    public function unlock($key, $value)
    {
    	if (empty($this->cache)) {
        	return true;
        }
        $key = 't://run.lock. ' . crc32($key . "[$value]");
        $ok = $this->cache->delete($key);
        if ($ok) {
            unset($this->locks[$key]);
        }
        return $ok;
    }
    
	/**
     * 进程结束时回调
     *
     * @return void;
     */
	public function shutdownUnlock()
    {
    	if (empty($this->cache)) {
        	return true;
        }
        if (!empty($this->locks)) {
            foreach ($this->locks as $lock => $ttl) {
               $this->cache->delete($lock); 
            }
        }
        return;
    }
	
    /**
     * 将以字符串分隔请求参数转化成数组
     *
     * @return array;
     */
    protected function paramFilter($pro, $type = '', $defaultVaule = '')
    {
    	if ($type == 'intval') {
    		$defaultVaule = empty($defaultVaule) ? 0 : intval($defaultVaule);
    		return empty($this->params->$pro) ? $defaultVaule : intval($this->params->$pro);
    	} else if ($type == 'array') {
    		$defaultVaule = empty($defaultVaule) ? array() : (array)$defaultVaule;
    		return empty($this->params->$pro) ? array()
    			: array_map('intval', explode(',', str_replace('，', ',', $this->params->$pro)));
    	}
    	return empty($this->params->$pro) ? $defaultVaule : trim($this->params->$pro);
    }
}