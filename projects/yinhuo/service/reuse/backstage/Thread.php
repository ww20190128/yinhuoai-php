<?php
namespace service;

/**
 * 后台执行线程
 *
 * 对外接口: send
 *
 *<code>
 *Thread::send($callBack, $priorityFlag)
 *</code>
 *
 *用于将任务推送到后台执行
 *
 * @author wangwei
 */
class Thread extends Service
{    
    /**
     * 中等优先级
     *
     * @var int
     */
    const PRIORITY_NORMAL = 0x00000000;

    /**
     * 低优先级
     *
     * @var int
     */
    const PRIORITY_LOW = 0x00010000;

    /**
     * 任务缓冲区
     *
     * @var array
     */
    private $buffer = array();

    /**
     * 本地回调域名/ip
     *
     * @var string
     */
    private static $localhost = '127.0.0.1';

    /**
     * 单例
     *
     * @var Thread
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return Thread
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Thread();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     * 
     * @return this
     */
    public function __construct()
    {
        parent::__construct(__CLASS__);
      	$this->addShutdownCallback(array($this, 'flush'));
    }

    /**
     * 添加一项后台任务
     *
     * @param   string  $callBack           回调函数
     * @param   int     $priorityFlag       优先级标记字段
     *
     * @return bool
     */
    public static function send($callBack, $priorityFlag)
    {
        $self = Thread::singleton();
        if (empty($self->server['switch']) || !is_callable($callBack)) { // 无效的回调函数
            return false;
        } 
        $priority = self::PRIORITY_NORMAL | (1 << ($priorityFlag % ($self->server['queue_num'] - 1)));
        $argv = func_get_args();
        if (!is_numeric($argv['0'])) { // 第一个参数为数字则为服务器ID
        	array_unshift($argv, $self->frame->id);
        }
        $argv['2'] = $priority;
        $self->buffer[] = $argv;
        if ($self->frame->runType == \Bootstrap::RUN_MODE_SHELL) {
            $self->flush();
        }
        return true;
    }
   
    /**
     * 刷新所有的任务
     *
     * @return bool
     */
    public function flush()
    {     	
        $ok = false;
        if (!empty($this->buffer)) {
            $reporting = error_reporting(0);
            foreach ($this->buffer as $args) {
                list($serverId, $callBack, $priority) = $args;
                if ($priority & self::PRIORITY_LOW) {
                    $msgtype = $this->server['desiredmsgtype'] + ($this->server['queue_num'] - 1) * 2;
                } else {
                    $msgtype = $this->server['desiredmsgtype'];
                    if ($priority & 0xffff) {
                        for ($index = 0, $num = ($this->server['queue_num'] - 1); $index < $num; $index++) {
                            if ($priority & (1 << $index)) {
                            	$msgtype += $index * 2;
                                break;
                            }
                        }
                    }
                }   
                $task = array(
                    'serverId' => $serverId,
                    'callBack' => $callBack,
                    'args'     => array_slice($args, 3),
                );
				$ok = msg_send($this->queue, $msgtype, json_encode($task), true, false, $errno);
            }
            $this->buffer = array();
            error_reporting($reporting);
        }
        return $ok;
    }

    /**
     * 使用php-fpm执行给定函数
     *
     * @param   int     $num    执行数量
     *
     * @return int
     */
    public function execute($num)
    { 
        $taskNum = 0;
        $desiredmsgtype = $this->server['desiredmsgtype'] + $num * 2;    
        $maxTaskNum = ($num < ($this->server['queue_num'] - 1)) ? 32 : 4;
        while (msg_receive($this->queue, $desiredmsgtype, $msgtype, 65536, $task, true, MSG_IPC_NOWAIT, $errno)) {
        	$taskNum += 1;
            $task = json_decode($task, true);
            $serverId 	= $task['serverId'];
            $callBack 	= $task['callBack'];
            $args 		= $task['args'];  
            try {
                if (is_string($callBack) && strpos($callBack, '::') !== false) {
                	$callBack = explode('::', $callBack, 2);
                }
   				if (\Application::reload($serverId) === false) {
                    syslog(LOG_ERR, "Application::reload($serverId) failure!");         
                    continue;
                };
				ob_start();
                call_user_func_array($callBack, $args);
                ob_clean();
            } catch (Exception $reason) {
                syslog(LOG_WARNING, 'execute callback failure on function ' . $callBack . ':' . json_encode($args));
                syslog(LOG_WARNING, 'reason: ' . $reason->getMessage());
            }       
            if ($taskNum >= $maxTaskNum) {
                break;
            }
        }
        if ($errno > 0 && $errno != 42) {
            $error = posix_strerror($errno);
            syslog(LOG_WARNING, "msg_receive failure with error #$errno($error) on Thread->execute");
        }
        return $taskNum;
    }

    /**
     * 初始化
     *
     * @return bool
     */
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 启动服务
     *
     * @return bool
     */
    public function start()
    {
        $this->initialize();
       	$desiredmsgtype = ($this->server['desiredmsgtype'] + $this->num * 2);
        do {
            try {            	
                $ok = msg_receive($this->queue, $desiredmsgtype, $msgtype, 1, $task, true, 0, $errno);  // 心跳 向 http发生一个请求
                if ($ok !== false) {
                    syslog(LOG_WARNING, 'Unexpect success on Thread->start, received: ' . json_encode($task));
                    continue;
                }
                if ($errno != 7) { // 参数太长或者消息队列被清理
                    $error = posix_strerror($errno);
                    syslog(LOG_WARNING, "msg_receive failed on Thread->start with error: #$errno ($error)");
                    continue;
                }
                $this->frame->now = time();
                if ($this->frame->now >= $this->tomorrow) {
                    $this->scroll($this->frame->now);
                }
                $callBack = 'http://' . $this->frame->conf['inner_ip'] . '/index.php?op=GREENCHANNEL&THREAD=1&num=' . $this->num; 
                $output = httpGetContents($callBack, '', 30); 
                syslog(LOG_WARNING, 'Unexpect success on Thread->start, received: ' . json_encode($task));
                if (empty($output)) {
                	continue;
                }
                $data = json_decode($output, true);
                if (!isset($data) || !isset($data['status'])/* || ($data['status'] != 0)*/) {
                    // 回调函数执行失败或者返回异常
                	syslog(LOG_ERR, "execute callback failure with output: '$output' on $callBack");
                }
                //$this->log("#%d jobs processed.", $data['data']);
            } catch (Exception $e) {
                $this->log('ERROR: %s', $e->getMessage());
            }
        } while (!$this->signal());
        return true;
    }
    
}