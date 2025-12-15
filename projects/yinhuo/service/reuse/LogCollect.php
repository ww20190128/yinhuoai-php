<?php
namespace service\reuse;

/**
 * 通用类
 *
 * @author
 */
class LogCollect extends \service\ServiceBase
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
     * @return LogCollect
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LogCollect();
            self::$instance->init();
        }
        return self::$instance;
    }

	private static $_logcollector;

	private static $_format = array(
		'dateline' => '',
		'serverid' => '',
		'passportid' => '',
		'op' => '',
		'ip' => '',
		'action' => '',
		'ext0' => '',
		'ext1' => '',
		'ext2' => '',
		'ext3' => '',
		'ext4' => '',
		'ext5' => '',
		'ext6' => '',
		'ext7' => '',
		'ext8' => '',
		'ext9' => '',
		'ext10' => '',
		'ext11' => '',
		'ext12' => '',
		'ext13' => '',
		'ext14' => '',

	);

	/*
	op 定义
	1001|pay	支付信息
	1002|login	登录信息
	1003|resource 资源变化记录
	*/

	private static $_opList = array(
		'pay' => '1001',
		'login' => '1002',
		'resource' => '1003',
	);

	private function init() {
		if(!class_exists("logcollector")) {
			return 0;
		}
		self::$_logcollector = new \logcollector();
		self::$_logcollector->init('rxsg');
	}

	private function _writeLog($action, $ext) {
		self::$instance = self::singleton();
		if(!isset(self::$instance) || !isset(self::$_opList[$action])) {
			return 0;
		}
		$op = self::$_opList[$action];
		
		$userInfo = \service\reuse\User::getUserInfo();
		$passportId = !isset($userInfo['passportId']) ? 0 : $userInfo['passportId'];
		$serverId = self::$instance->frame->id;    // 服务器id
		
		$logData = array(
			'dateline' => self::$instance->frame->now,
			'serverid' => $serverId,
			'passportid' => $passportId,
			'op' => \Application::$Dispatcher->getOp(),
			'ip' => \service\Client::getIP(),
			'action' => $op,
		);

		foreach($ext as $key => $value) {
			$k = 'ext'.$key;
			$logData[$k] = $value;
		}

		$data = array_merge(self::$_format, array_intersect_key((array)$logData, self::$_format));
		return self::$_logcollector->writeBaseLog($op, implode(',', $data));
	}

	public static function payLog($userId, $level, $money, $vip, $first, $paytype, $channel, $regchannel, $dateline, $orderid) {
		$data = func_get_args();
		self::_writeLog('pay', $data);
	}

	public static function loginLog($userId, $level, $vip, $dateline) {
		$data = func_get_args();
		self::_writeLog('login', $data);
	}

	public static function resourceLog($userId, $type, $num, $balance) {
		$data = func_get_args();
		self::_writeLog('resource', $data);
	}

}
