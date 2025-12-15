<?php
namespace service;

/**
 * 聊天服务器监控进程
 * 
 * @author wangwei
 * 
 * @package service
 */
class EpollMonitor extends Service
{    
    /**
	 * 所有的聊天服务器
	 * 
	 * id => {n => 报错信息数量, t => 统计的时间}
	 * 
	 * @var array
	 */
	public static $epollServers = array();

    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return EpollMonitor
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new EpollMonitor();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct(__CLASS__);
    }
    
	/**
     * 日志文件监控
     * 
     * @param 	array	$conf	服务器配置
     * 
     * @return bool
     * 
     * $this->restartEpollServer($conf);
     */
    private function logFileMonitor($conf)
    {  	
   	 	$runtimeDir = rtrim(cfg('server.environ.runtime_dir'), DS) . DS . 'epoll' . DS;
   	 	//$logFiles = glob($runtimeDir . "\*_{$conf['mark']}.log");
   	 	$list = array(
			'Gateway' 		=> 1,		// 网关
			'Chat' 			=> 1,		// 聊天
			'QueueRead' 	=> 1,		// 队列
		//	'Crontab' 		=> 1,		// 计划任务
		);
    	$errorNum = 0;
		foreach ($list as $name => $num) {
			if (is_readable($runtimeDir . $name . "_{$conf['mark']}.log")) {
				$logFile = $runtimeDir . $name . "_{$conf['mark']}.log";
				$result = @shell_exec("cat {$logFile} | egrep 'php call' | wc -l");
				$errorNum += intval($result);
			}
		}
 		if (!empty($errorNum)) { // 存在报错信息							
 			$serverId = $conf['id']; // 服务器id
 			$nowTime = time(); // 当前时间
 			if (!empty(self::$epollServers[$serverId])) { // 之前有记录过	
 				ksort(self::$epollServers[$serverId]); // 将历史记录按照时间有小到大排序
 				$lastNum = end(self::$epollServers[$serverId]); // 获取最近一条的 记录
 				if ($errorNum > $lastNum) { // 报错信息在增加
 					// 增长率
 					$growthRate = ($errorNum - $lastNum) 
 						/ ($nowTime - array_search($lastNum, self::$epollServers[$serverId]));
 					if ($growthRate > 0) { // 增长率过大 立即重启
$errorInfo = array(
	'callable_addTimerLoop' => is_callable('addTimerLoop'),
	'callable_sendToFds' 	=> is_callable('sendToFds'),
	'callable_sendTo' 		=> is_callable('sendTo'),
	'class_funcs' 			=> get_extension_funcs('EpollServer'),
);					
 						$this->restartEpollServer($conf);
$this->log("[%s] %s epollserver restarted, errorNum: %d (%s), errorInfo: %s", 
	date('Y-m-d H:i:s', $nowTime), $conf['mark'], $errorNum, $growthRate, var_export($errorInfo, true));
 					}
 				} elseif ($errorNum < $lastNum) {
 					unset(self::$epollServers[$serverId]);
 				} else {
 					return true;
 				}
 			}	
 			self::$epollServers[$serverId][$nowTime] = $errorNum;		
 		}
 		return true;
    }
    
	/**
     * 重启聊天服务器
     * 
     * @param 	array	$conf	服务器配置
     *
     * @return bool
     */
    private function restartEpollServer($conf)
    { 	 	
    	$list = array(
			'Gateway' 		=> 1,		// 网关
			'Chat' 			=> 1,		// 聊天
			'QueueRead' 	=> 1,		// 队列
		//	'Crontab' 		=> 1,		// 计划任务
		);
		$mark = $conf['mark'];
    	$host = $conf['host'];
    	// 关闭 
    	for ($index = 0; $index < 3; $index++) {
      		exec('kill -9 `ps aux | grep "main.php" | grep ' . $host . ' | grep -v grep | awk \'{print $2}\'`');
      		usleep(500000);
    	}
    	foreach ($list as $className => $num) {
			exec('kill -9 `ps aux | grep "main.php" | grep ' . $className . ' | grep ' . $host . ' | grep -v grep | awk \'{print $2}\'`');
        }
        usleep(500000);
        // 启动  并且 清理日志
        $path = ROOT_PATH . 'Shell' . DS . 'main.php';
   	 	$runtimeDir = rtrim(cfg('server.environ.runtime_dir'), DS) . DS;
        if (substr($runtimeDir, 0, 2) == '.' . DS) {
        	$runtimeDir = realpath(ROOT_PATH . ltrim($runtimeDir, '.')) . DS;
        }    	
    	$logs = $runtimeDir . 'epoll' . DS;
    	//exec("nohup /bin/php {$path} -h {$host} Shell.epoll -s {$mark} -a all"); 
        foreach($list as $className => $num) {
			for($index = 1; $index <= $num; $index++) {
				exec('su -s /bin/bash www -c "nohup php ' . $path . ' -h ' . $host . ' Shell.epollStart -a ' . $className
					. ' >> ' . $logs . $className . '_' . $mark . '.log 2>&1 &"', $retval); 		
				usleep(500000);
			}
		}    
        // 清理统计信息
        unset(self::$epollServers[$conf['id']]);
        return true;
    }
    
    /**
     * 初始化
     *
     * @return bool
     */
    protected function initialize()
    { 	
        parent::initialize();
    	if (posix_getuid() != 0) {
    		$user = cfg('server.environ.user');
    		$pw = posix_getpwnam($user);
    		if ($pw == false) {
        		echo("没有用户 $user.");
        		exit(0);
    		}
    		if ($pw['uid'] != posix_getuid()) {
        		echo("必须用root权限!!!\n");
        		exit(0);
    		}
		}      
        if ($this->serverConfs) foreach ($this->serverConfs as $serverId => $conf) {
            if (empty($conf['communicate']['socket'])) {
                unset($this->serverConfs[$serverId]);
                continue;
            }
            self::$epollServers = array(); 
        }
        return true;
    }

    /**
     * 启动服务
     *
     * @return bool
     */
    public function start()
    {
        $this->initialize();
        $timeout = $this->server['timeout'] * 1000000; 
        do {
            $this->frame->now = time();
            if ($this->frame->now >= $this->tomorrow) {
                $this->scroll($this->frame->now);
            }
            // 检查每个服务器的日志
            if (is_iteratable($this->serverConfs)) foreach ($this->serverConfs as $serverConf) {
				$this->logFileMonitor($serverConf);
            }
            usleep($timeout); // 每秒执行一次
        } while (!$this->signal());
        return true;
    }

}