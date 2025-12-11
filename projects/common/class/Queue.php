<?php
namespace service;
use service\Queue;
declare(ticks=1);

/**
 * 队列系统
 *
 * 支持一个主进程fork多个子进程进行统一管理，
 * 支持主进程的端口绑定，防止多次重复启动，
 * 支持socket连接绑定端口查看队列状态。
 * 示例代码：<code>
 * declare(ticks=1);
 * include 'Queue.php';
 * $q = new service\Queue;
 * $q->addTask('yourTaskName', 'callbackFunc');
 * $q->execute();
 * </code>
 */
class Queue
{
    /**
     * 是否显示提示信息
     *
     * @var string
     */
    const PROMPT = true;

    /**
     * 监控间隔（单位：微秒）
     *
     * @var int
     */
    const MONITOR_INTERVAL = 1000000;

    /**
     * 日志文件路径前缀
     *
     * @var string
     */
    protected $logPathPrefix = '/tmp/queue_';

	/**
	 * 消息队列权限
	 *
	 * @var string
	 */
	const MESSAGE_QUEUE_PERMISSION = 0666;

    /**
     * 端口号
     *
     * @var int
     */
    private static $port = null;

    /**
     * 消息队列的key
     *
     * @var int
     */
    private static $msgQueueKey = null;

	/**
	 * 需要监控的信号列表
	 *
	 * @var array
	 */
	private static $monitorSignoList = array(
		SIGINT, 
		SIGTERM, 
		SIGHUP
	);

	/**
     * 任务列表
     *
     * @var array
     */
	private $taskList = array();

    /**
     * 任务队列状态列表[name => [pid, taskName, startTime, lastPingTime]]
     *
     * @var array
     */
    private $taskStatusList = array();

	/**
     * 当前时间
     *
     * @var int
     */
	private $now = null;

	/**
     * 消息队列
     *
     * @var resource
     */
	public $msgQueue = null;

	/**
     * 当前主进程id
     *
     * @var int
     */
	private static $mainQueuePid = null;

	/**
     * 当前的sock连接
     *
     * @var resource
     */
	private $sock = null;

	/**
     * 当前连接的sock列表
     *
     * @var resource
     */
	private $linkedSockList = array();

	/**
     * 当前接收到的信号
     *
     * @var int
     */
	private $signo = null;
    
	/**
     * 设置端口
     *
     * @param int $port 端口号
     *
     * @return Queue
     */
    public function setPort($port)
    {
        self::$port = $port;
        return $this;
    }
    
	/**
     * 设置日志路径前缀
     *
     * @param string $pathPrefix 日志路径前缀，最终记录到“[前缀][任务名称].log”文件中
     *
     * @return Queue
     */
    public function setLogPathPrefix($pathPrefix)
    {
        $this->logPathPrefix = $pathPrefix;
        $dirName = dirname($this->logPathPrefix);
        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        fopen($this->logPathPrefix, 'a');
        return $this;
    }
    
  	/**
     * 构造函数-初始化环境
     *
     * @return \service\Queue
     */
    public function __construct()
    {
        self::$mainQueuePid = posix_getpid();
        self::$msgQueueKey = ftok(__FILE__, 'a');
        return;
    }

    /**
     * 初始化
     *
     * @return bool
     */
    private function init() 
    {
        // 绑定端口，防止重复启动
        if (!is_null(self::$port)) {
            // 必须将返回值socket资源赋值给一个变量，否则socket将断开
            $this->sock = $this->bindPort(self::$port);
            socket_set_nonblock($this->sock);
        }
        // 初始化消息队列
        if (is_null($this->msgQueue)) {
            $this->msgQueue = $this->createMsgQueue(self::$msgQueueKey);
        }
        // 注册信号处理回调方法
        $signalCallback = array($this, 'signalHandler');
        foreach (self::$monitorSignoList as $signo) {
            pcntl_signal($signo, $signalCallback);
        }
        return true;
    }

    /**
     * 进程发送心跳包
     *
     * 子进程负责发送消息 主进程负责接收消息
     *
     * @param    int        $pid        进程ID
     * @param    string     $taskName   任务名称
     * @param    int        $pingTime   间隔时间
     * @return void
     */
    protected function ping($pid, $taskName = null, $pingTime = null)
    {
        // 发送心跳消息
        if ($pid != self::$mainQueuePid) {
            $lastPingTime = null === $pingTime ? $this->now : $pingTime;
            try {
                $messageType = 1;
                $this->sendMsg($messageType, array(
                    'pid'           => $pid,
                    'taskName'      => $taskName,
                    'lastPingTime'  => $lastPingTime,
                ));
            } catch (Exception $e) {
                $this->prompt('message queue write error!');
            }
        } else {
            // 主进程：监控进程
            while (true) {
                $msgType = null;
                $maxSize = 1024;
                $desiredMsgType = 0;
                // 收拾资源
                $message = $this->receiveMsg($msgType, $maxSize, $desiredMsgType);
                if (empty($message)) {
                    break;
                }
                if (!empty($message['taskName']) && !empty($message['lastPingTime'])) {
                    $this->taskStatusList[$message['taskName']]['lastPingTime'] = $message['lastPingTime'];
                }
            }
            // 关闭长时间未更新心跳时间的进程
            if ($this->taskStatusList) foreach ($this->taskStatusList as $name => $status) {
                // 如果心跳包超时60秒，则发送终止命令并重新启动
                if ($status['lastPingTime'] + 60 < $this->now) {
                    posix_kill($status['pid'], SIGTERM);
                    unset($this->taskStatusList[$name]);
                }
                continue;
            }
        }
        return;
    }

    /**
     * 从消息队列中接收消息
     *
     * @param   int   $messageType      消息类型
     * @param   int   $maxSize          消息大小
     * @param   int   $desiredMsgType   解密类型
     *
     * @return string
     */
    public function receiveMsg($messageType, $maxSize = 1024, $desiredMsgType = 0)
    {
        $message = null;
        msg_receive($this->msgQueue, $desiredMsgType, $messageType, $maxSize, $message, true, MSG_IPC_NOWAIT);
        return $message;
    }

    /**
     * 向消息队列发送消息
     *
     * @param   int     $messageType      消息类型
     * @param   string  $message          消息
     *
     * @return void
     */
    public function sendMsg($messageType, $message)
    {
    	msg_send($this->msgQueue, $messageType, $message);
    }

    /**
     * 子进程执行逻辑
     *
     * @param   string      $name       任务名称
     * @param   callback    $callback   任务回调
     * @param   int         $interval   时间间隔
     *
     * @return void
     */
    private function executeTask($name, $callback, $interval)
    {
        $childPid = posix_getpid();
        $this->prompt("Fork a child : %s [%s]", $name, $childPid);
        ob_start();
        while (true) {
            // 处理退出信号
            if (!empty($this->signo) && in_array($this->signo, self::$monitorSignoList)) {
                $this->prompt("Signal %s catched!", $this->signo);
                // 将心跳包时间更新为57秒钟之前，3秒后监控队列会重启此队列
                $this->ping($childPid, $name, $this->now - 57);
                break;
            }
            // 更新时间
            $this->now = time();
            // 发送心跳包
            $this->ping($childPid, $name);
            // 主要业务逻辑
            call_user_func($callback);
            // 将缓冲区内容记入日志
            $log = ob_get_contents();
            ob_clean();
            file_put_contents($this->logPathPrefix . $name . '.log', $log, FILE_APPEND);
            usleep($interval);
        }
        ob_end_clean();
        return;
    }

    /**
     * 开始执行队列
     *
     * @return void
     */
    public function execute()
    {
        $this->init();
        // 进程监控循环
        while (true) {
            // 处理信号
            if (!empty($this->signo) && in_array($this->signo, self::$monitorSignoList)) {
                $this->prompt("Signal %s catched!", $this->signo);
                break;
            }
            // 更新当前时间戳
            $this->now = time();
            // 发送心跳包
            $this->ping(self::$mainQueuePid);
            
            // 遍历任务列表，处理任务
            if (!empty($this->taskList)) foreach ($this->taskList as $name => $task) {
                // 已有PID的任务，跳过
                if (isset($this->taskStatusList[$name])) {
                    continue;
                }
                $taskPid = pcntl_fork();
                if ($taskPid == -1) {
                    // fork失败
                    $this->prompt("TaskName: %s could not fork!", $name);
                } elseif ($taskPid == 0) {  	
                    // fork出的子进程的执行逻辑
                    if (!posix_setsid()) {
                        $this->prompt("TaskName:%s could not detach from terminal!\n", $name);
                        exit;
                    }
                    // 循环执行Task
                    $this->executeTask($task['name'], $task['callback'], $task['interval']);
                    exit;
                } elseif ($taskPid > 0) {
                    // 队列主进程的执行逻辑
                    $this->taskStatusList[$name] = array(
                        'pid'           => $taskPid,
                        'taskName'      => $name,
                        'startTime'     => $this->now,
                        'lastPingTime'  => $this->now,
                    );
                }

            }
            /*
            // 接受socket连接
            try {
                if (($connection = @socket_accept($this->sock)) !== false) {
                    $linkKey = count($this->linkedSockList);
                    $this->linkedSockList[$linkKey] = $connection;
                    $this->prompt("Socket client[%d] connected!", $linkKey);
                    $this->responseSocket($connection, "help");
                }
            } catch (Exception $e) {}
            // 处理每个socket连接的请求
            foreach ($this->linkedSockList as $linkKey => $linkedSock) {
                $sockBuffer = null;
                try {
                    $receiveLen = socket_recv($linkedSock, $sockBuffer, 8096, MSG_DONTWAIT);
                    if (empty($receiveLen)) {
                        unset($this->linkedSockList[$linkKey]);
                        $this->prompt("Socket client[%d] closed!", $linkKey);
                        socket_close($linkedSock);
                    } else {
                        $this->responseSocket($linkedSock, trim($sockBuffer));
                    }
                } catch (Exception $e) {

                }
            }
            */
            // 休眠间隔
            usleep(self::MONITOR_INTERVAL);
        }
        // 收拾资源
        $this->removeMsgQueue();
    }

    /**
     * 销毁消息队列
     *
     * @return void
     */
    public function removeMsgQueue()
    {
        msg_remove_queue($this->msgQueue);
    }

    /**
     * 反馈socket请求
     *
     * @param   resource    $sock       socket连接句柄
     * @param   string      $request    socket请求信息
     *
     * @return void
     */
    protected function responseSocket($sock, $request)
    {
        switch ($request) {
            case 'help':
                $helpStr = "";
                $helpStr .= "帮助信息：\n";
                $helpStr .= "  help   查看当前帮助信息\n";
                $helpStr .= "  status 查看状态\n";
                $helpStr .= "  quit   退出\n";
                socket_write($sock, $helpStr);
                break;
            case 'status':
                $statusStr = "";
                $statusStr .= "  监控队列进程ＩＤ：" . self::$mainQueuePid . "\n";
                $statusStr .= "  监控队列启动时间：" . date("Y-m-d H:i:s", $this->now) . "\n";
                $statusStr .= "\n";
                $statusStr .= "  队列任务状态：\n";
                if (!empty($this->taskStatusList)) foreach ($this->taskStatusList as $name => $status) {
                    $statusStr .= "  任务名称：{$name}\n";
                    $statusStr .= "    进程ＩＤ：{$status['pid']}\n";
                    $statusStr .= "    启动时间：" . date("Y-m-d H:i:s", $status['startTime']) . "\n";
                    $statusStr .= "    上次ping：" . date("Y-m-d H:i:s", $status['lastPingTime']) . "\n";
                }
                socket_write($sock, $statusStr);
                break;
            case 'quit':
                socket_shutdown($sock);
                break;
            default:
                socket_write($sock, "无效的命令 {$request}，请输入\"help\"查看帮助命令。\n");
        }
        return ;
    }

    /**
     * 输出格式化后的提示信息
     *
     * @param string $format 提示信息
     *
     * @return void
     */
    private function prompt($format)
    {
        if (self::PROMPT) {
            echo "\x1B[32m" . date("[Y-m-d H:i:s]", $this->now) . ' [PID:' . posix_getpid() . '] ' . vsprintf($format, array_slice(func_get_args(), 1)) ."\x1B[0m", PHP_EOL;
        }
    }

    /**
     * 绑定端口并建立sock连接
     *
     * @param int $port 端口号
     *
     * @return resource
     */
    protected function bindPort($port)
    {
        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $ret = socket_bind($this->sock, '127.0.0.1', $port);
        if ($ret) {
            socket_listen($this->sock);
        } else {
            $errorCode = socket_last_error();
            $this->prompt("Bind port[%d] failed ! {%s:%s} Exit!", $port, $errorCode, socket_strerror($errorCode));
            socket_close($this->sock);
            exit;
        }
        return $this->sock;

    }

    /**
     * 信号处理器
     *
     * @param   int  $signo   信号常量
     *
     * @return void
     */
    private function signalHandler($signo)
    {
        $this->signo = $signo;
    	if ($signo == SIGINT || $signo == SIGTERM) { // 中断信号
    		$this->prompt("中断信号!");
    	} else if ($signo == SIGHUP) {
    		$this->prompt("重启信号!"); // 重启信号
    	} else if ($signo == SIGCHLD) {
    		$this->prompt("子进程退出信号！"); // 子进程存在
    	}
    }

    /**
     * 创建消息队列
     *
     * @param   int  $msgQueueKey    消息队列id
     *
     * @return resource
     */
    private function createMsgQueue($msgQueueKey)
    {
        return msg_get_queue($msgQueueKey, self::MESSAGE_QUEUE_PERMISSION);
    }

    /**
     * 添加任务
     *
     * @param 	string   	$name     	任务名称
     * @param 	callback 	$callback   任务回调
     * @param 	int      	$interval 	任务间隔（单位：微秒，默认为1000000，即1秒）
     *
     * @return Queue
     */
    public function addTask($name, $callback, $interval = 1000000)
    {
        $this->taskList[$name] = array(
                                    'name'     => $name,
                                    'callback' => $callback,
                                    'interval' => $interval,
                                 );
        return $this;
    }

    /**
     * 析构函数，清理环境
     *
     * @return void
     */
    public function __destruct()
    {
    	
    }
    // 创建主队列
    // 在主队列下创建子队列
    // 执行队列
    // 获取队列状态
    // 杀掉主队列
    // 杀掉子队列
}