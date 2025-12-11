<?php
namespace service;

/**
 * 后台跟踪进程
 *
 * 对外接口: send
 * <code>
 * Tracer::trace($userId, Tracer::TYPE_LOGIN, array(
 *  'ip'            => Client::getIP(),
 *  'requestMethod' => Client::requestMethod(),
 *  'browser'       => Client::getBrowser(),
 *  'os'            => Client::getOS(),
 *));
 * </code>
 *
 * @author wangwei
 */
class Tracer extends Service
{
    /**
     * 事件调度器
     *
     * @var object
     */
    private $eventDispatcher = null;

    /**
     * 数据缓冲区
     *
     * @var array
     */
    private $buffer = array();
    
    /**
     * 最大消息数
     *
     * @var int
     */
    const MAX_EVENTS = 256;

    /**
     *  日志文件
     *
     * @var resource
     */
    private $logfile = null;
    
    /**
     * 日志头文件
     * 
     * @var string
     */
    private $logfile_header_format = 'vvVVVx16';

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_size = 32;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_magic = 22352;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_tail_offset = 0x0C;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_tail_size = 4;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_head_offset = 0x08;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_head_size = 4;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_buffer_size = 33554432;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_block_size = 4000;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_sync_interval = 5.000;

    /**
     * 日志头文件
     * 
     * 
     * @var float
     */
    private $logfile_alarm_threshold = 0.500;

    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return Online
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Tracer();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     *
     *  @return Tracer
     */
    public function __construct()
    {
        parent::__construct(__CLASS__);
        // 注册回调函数,在进程退出时处理
        $this->addShutdownCallBack(array($this, 'flush'));
    }

    /**
     * 记录
     * 
     * @param   mix   	$logInfo    日志信息
     * @param   int    	$grade      日志等级
     * 
     * @return bool
     */
    public static function trace($logInfo, $grade = 1)
    {
    	$self = self::singleton();
    	$params = self::$instance->frame->params; // 请求参数
    	$paramArr = (array)$params;
    	// 将日志记录到数据库中
    	$self->recordToMysql($logInfo);
    	unset($paramArr['op']);
        $dispatcher = &\Application::$Dispatcher;
        $self = Tracer::singleton();
        $self->buffer[] = array(
            'grade'     => $grade,              					// 日志等级(1 重要,文件持久化 2 不重要,不需要持久化)
            'time'      => $self->frame->now,   					// 记录时间
        	'op' 		=> empty($params->op) ? '' : $params->op,	// 请求操作op
        	'data'		=> $logInfo,								// 日志内容
        	'params'	=> $paramArr,								// 请求参数
        );
        if ($self->frame->runType == \Bootstrap::RUN_MODE_SHELL) {
            $self->flush();
        }
        return true;
    }
    
    /**
     * 记录日志
     * 
     * @return void
     */
    public function recordToMysql($logInfo)
    {
    	if (empty($logInfo['type'])) {
    		return false;
    	}
    	$params = $this->frame->params; // 请求参数
    	$paramArr = (array)$params;
		$now = $this->frame->now;
		$refer = empty($logInfo['refer']) ? '' : $logInfo['refer'];
		$param1 = isset($logInfo['param1']) ? $logInfo['param1'] : '';
		if (is_array($param1)) {
			$param1 = json_encode($param1);
		}
		$param2 = isset($logInfo['param2']) ? $logInfo['param2'] : '';
		if (is_array($param2)) {
			$param2 = json_encode($param2);
		}
		$param3 = isset($logInfo['param3']) ? $logInfo['param3'] : '';
		if (is_array($param3)) {
			$param3 = json_encode($param3);
		}
		$param4 = isset($logInfo['param4']) ? $logInfo['param4'] : '';
		if (is_array($param4)) {
			$param4 = json_encode($param4);
		}
		$other = isset($logInfo['other']) ? $logInfo['other'] : '';
		if (is_array($other)) {
			$other = json_encode($other);
		}
		$userId = 0;
		if (!empty($logInfo['userId'])) {
			$userId = $logInfo['userId'];
		} elseif (!empty($paramArr['userId'])) {
			$userId = $paramArr['userId'];
		}
		$traceDao = \dao\Trace::singleton();
		$traceEtt = $traceDao->getNewEntity();
		$traceEtt->serverId 		= $this->frame->id;
		$traceEtt->type 			= $logInfo['type']; // 操作类型
		$traceEtt->userId 			= $userId; // 操作用户id
		$traceEtt->num 				= empty($logInfo['num']) ? 0 : $logInfo['num'];
		$traceEtt->refer 			= empty($logInfo['refer']) ? '' : $logInfo['refer'];
		$traceEtt->param1 			= empty($logInfo['param1']) ? '' : $logInfo['param1'];
		$traceEtt->param2 			= empty($logInfo['param2']) ? '' : $logInfo['param2'];
		$traceEtt->param3 			= empty($logInfo['param3']) ? '' : $logInfo['param3'];
		$traceEtt->param4 			= empty($logInfo['param4']) ? '' : $logInfo['param4'];
		$traceEtt->traceTime 		= $now;
		$traceEtt->recordTime 		= date('Y-m-d H:i:s',$now);
		$traceEtt->requestParams 	= json_encode($paramArr);
		$traceEtt->requestOp 		= $paramArr['op'];
		$traceEtt->other 			= empty($logInfo['other']) ? '' : $logInfo['other'];
		$traceDao->create($traceEtt);
    	return true;
    }

    /**
     * 刷新缓冲区
     *
     * @return void
     */
    public function flush()
    {
        if (!empty($this->buffer) && !empty($this->queue)) {
            $reporting = error_reporting(0);
            $fileBuffer = array(); // 文件缓存区, 用于向消息队列发送失败时写入文件中
            foreach ($this->buffer as $message) {
            	$ok = msg_send($this->queue, $this->server['desiredmsgtype'], $message, true, false, $errno);
                if (!$ok) { // 发送失败
                    $error = posix_strerror($errno);
                    // 将数据写入到文件缓冲区
                    $fileBuffer[] = $message;
                    syslog(LOG_ERR, "msg_send failed with error #$errno ($error) for " . json_encode($message));
                }
            }
            if (!empty($fileBuffer)) {
            	$this->flushOption($fileBuffer);
            }
       
            $this->buffer = array();
            error_reporting($reporting);
        }
        return;
    }
    
    /**
     * 写入到文件中缓冲区中
     * 
     * @param 	array 	$fileBuffer   	数据列表
     * 
     * @return bool
     */
    protected function flushOption($fileBuffer)
    {
    	$this->runtimeDir = rtrim(cfg('server.environ.runtime_dir', null, false), DS) . DS;
    	if (substr($this->runtimeDir, 0, 2) == '.' . DS) {
    		$this->runtimeDir = realpath(ROOT_PATH . ltrim($this->runtimeDir, '.')) . DS;
    	}
    	$fileName = $this->runtimeDir . $this->logDir . DS . 'sendBufferFile.txt';
		@file_put_contents($fileName, json_encode($fileBuffer) . PHP_EOL, FILE_APPEND | LOCK_EX); // 以独占追加方式写入 	 
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
        $conf = cfg('server.environ.logfile', null, false);
        $this->logfile_header_format = $conf['header_format'];
        $this->logfile_header_size = (int)$conf['header_size'];
        $block = unpack('vheader_magic', $conf['header_magic']);
        $this->logfile_header_magic = $block['header_magic'];
        $this->logfile_header_tail_offset = (int)$conf['header_tail_offset'];
        $this->logfile_header_tail_size = (int)$conf['header_tail_size'];
        $this->logfile_header_head_offset = (int)$conf['header_head_offset'];
        $this->logfile_header_head_size = (int)$conf['header_head_size'];
        $this->logfile_buffer_size = (int)$conf['buffer_size'];
        $this->logfile_block_size = (int)$conf['block_size'];
        $this->logfile_sync_interval = (float)$conf['sync_interval'];
        $this->logfile_alarm_threshold = (float)$conf['alarm_threshold'];
        unset($block);
        $fileName = $this->runtimeDir . $this->logDir . DS . 'logfile';   
        $blockSize = $this->logfile_block_size;
        $size = $this->logfile_buffer_size * $blockSize;
        if (!file_exists($fileName)) {
            $mode = 'x+b';
            $logfile = fopen($fileName, $mode);
            if ($logfile === false) {
                syslog(LOG_ERR, "fopen($fileName, $mode) failed.");
                exit(127);
            }
            $header = pack($this->logfile_header_format, $this->logfile_header_magic, 
            	$blockSize, $this->logfile_buffer_size, 0, 0);
            $num = fwrite($logfile, $header, $this->logfile_header_size);
            if ($num != $this->logfile_header_size) {
                syslog(LOG_ERR, "Can not initialize logfile header.");
                exit(127);
            }
        } else {
            $mode = 'r+b';
            $logfile = fopen($fileName, $mode);
            if ($logfile === false) {
                syslog(LOG_ERR, "fopen($fileName, $mode) failed.");
                exit(127);
            }
        }
        if (stream_set_read_buffer($logfile, 0) !== 0) {
            syslog(LOG_ERR, "stream_set_read_buffer(0) failed on logfile.");
        }
        if (stream_set_write_buffer($logfile, 0) !== 0) {
            //syslog(LOG_ERR, "stream_set_write_buffer(0) failed on logfile.");
        }
        if (filesize($fileName) != $this->logfile_header_size + $size) {
            if (ftruncate($logfile, $this->logfile_header_size + $size) === false) {
                syslog(LOG_EMERG, "ftruncate($fileName, ". ($this->logfile_header_size + $size).") failed.");
                exit(127);
            }
        }
        $this->logfile = $logfile;
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
        $logfile = $this->logfile;
        if (fseek($logfile, 0, SEEK_SET) !== 0) { // 设置文件指针stream的位置到0位置
            syslog(LOG_ERR, "fseek() failed on logfile.");
            exit(127);
        }
        $read = fread($logfile, $this->logfile_header_size);
        if ($read === false || strlen($read) != $this->logfile_header_size) {
            syslog(LOG_ERR, "fread(".($this->logfile_header_size).") failed on logfile header.");
            exit(127);
        }
        $header = unpack('vmagic/vblocksize/Vlog_buffer_size/Vhead/Vtail', $read);  // 解包头信息
        // 检查头信息
        if ($header['magic'] != $this->logfile_header_magic) {
            syslog(LOG_ERR, "logfile broken.");
            exit(127);
        }
        unset($read);
        if ($this->logfile_block_size != $header['blocksize']) {
            $error = "logfile blocksize not match: " . $this->logfile_block_size . ":" . $header['blocksize'] . ".";
            syslog(LOG_ERR, $error);
            exit(127);
        }
        if ($this->logfile_buffer_size != $header['log_buffer_size']) {
            $error = "logfile buffer size not matches: " . $this->logfile_buffer_size . ":" . $header['log_buffer_size'] . ".";
            syslog(LOG_WARNING, $error);
        }
        $head = $header['head'];
        $tail = $header['tail'];
        $logfile_header_head_offset = $this->logfile_header_head_offset;
        $logfile_header_head_size 	= $this->logfile_header_head_size;
        $logfile_header_tail_size 	= $this->logfile_header_tail_size;
        $logfile_header_size 		= $this->logfile_header_size;
        $logfile_sync_interval 		= $this->logfile_sync_interval;
        $logfile_next_sync_time 	= $this->frame->now + $logfile_sync_interval;
        $logfile_block_size 		= $this->logfile_block_size;
        $logfile_buffer_size 		= $this->logfile_buffer_size;
        $logfile_alarm_threshold 	= $this->logfile_alarm_threshold; // 磁盘警报界限百分比
        $this->log("logfile_buffer_size: $logfile_buffer_size blocksize: $logfile_block_size logfile_alarm_threshold: $logfile_alarm_threshold");
        $msgQueue       = $this->queue;                     // 消息队列
        $desiredmsgtype = $this->server['desiredmsgtype'];  // 消息操作类型
        $msgMaxSize     = 65536;                            // 消息最大大小
        $timestamp = & $this->frame->now;
        $tomorrow = & $this->tomorrow;
        do {
            $timestamp = time();
            if ($timestamp >= $tomorrow) {
                $this->scroll($timestamp);
            }
            $nevents = 0;
            try {
                $buffer = array(); // 循环数据缓冲区
                $errno = 0;
            	while (($nevents + 1 <= self::MAX_EVENTS) && ($ok = msg_receive($msgQueue, $desiredmsgtype, $msgtype, $msgMaxSize, $msg, true, MSG_IPC_NOWAIT, $errno))) {	
            		$data = json_encode($msg); // 将消息用json序列化
					$len = strlen($data); // 消息包的长度
					if ($len >= $logfile_block_size) {
						syslog(LOG_EMERG, "{$data}data too big!" . $len . "/" . $logfile_block_size);
						continue;
					}
                    $buffer[] = pack('A' . $logfile_block_size, $data);
      				// 加载历史进度
      				$progress = array('num' => 9);
      				/* $params = array(
      					'num' => $num,
      				); */
                    //$this->handleTracerEvent($serverId, $type, $userId, $params, $progress);
                    $nevents += 1;
                }
            	if ($errno > 0 && $errno != 42) {
                    $error = posix_strerror($errno);
                    syslog(LOG_ERR, "msg_receive() failed. (#$errno $error)");
                }
                // 写入日志数据到filelog文件
                if ($nevents > 0) {
                	if ((($tail + 1) % $logfile_buffer_size) == $head) {
                        syslog(LOG_EMERG, "logfile buffer overflow.");
                    } else {
                		// 剩余数据 缓冲区的可用块数量
                		$free = ($tail >= $head) ? $logfile_buffer_size - 1 - ($tail - $head) : $head - $tail - 1;
                        $pending = $nevents; // 未写入的消息数量  
                        $buffer = implode('', $buffer);
                        // 磁盘将满,只能存储部分数据
                        if ($pending > $free) {
                            syslog(LOG_WARNING, ($pending - $free) . " logs discard");
                            $pending = $free;
                        }
                        // 磁盘警报, 剩余可用块数的百分比
                        if (($free - $pending) <= $logfile_buffer_size * $logfile_alarm_threshold) {
                            syslog(LOG_WARNING, "logfile buffer free space is too low: "
                            	. (100 * ($free - $pending) / $logfile_buffer_size) . "%");
                        }
                        // 处理数据写入
                		while ($pending > 0) {
                            $offset = $logfile_header_size + $tail * $logfile_block_size; // 数据写入的起始位置
                            if (($tail >= $head) && ($tail + $pending >= $logfile_buffer_size)) {
                                $blocks = $logfile_buffer_size - $tail; // 可用的块数
                                $tail = 0; // 更新当前位置
                            } else {
                                $blocks = $pending;
                                $tail += $blocks;
                            }
                            if (fseek($logfile, $offset, SEEK_SET) === false) { // 将写入的总数据大小
                                syslog(LOG_EMERG, "fseek() failed on logfile, $pending logs discard.");
                                exit(127);
                            }
                            $num = $blocks * $logfile_block_size;
                            // 写入数据到缓冲区!!!!!
           
                            if (fwrite($logfile, $buffer, $num) !== $num) {
                                syslog(LOG_EMERG, "fwrite() failed on logfile, $blocks logs discard.");
                            }
                            // 刷出数据
                            if (fflush($logfile) === false) {
                                syslog(LOG_EMERG, "fflush() failed on logfile.");
                            }
                            // 将已经处理的数据从 buffer 中删除
                            if ($blocks < $pending) {
                                $buffer = substr($buffer, $num);
                            }
                            $pending -= $blocks;
                        }
                    }
                }
                // 更正头信息
            	if ($logfile_next_sync_time <= $timestamp) {
                    if (flock($logfile, LOCK_EX|LOCK_NB, $wouldblock) === false) {
                    	// TODO 报错
                        syslog(LOG_WARNING, "flock(LOCK_EX|LOCK_NB) failed on logfile.");
                    } else {
                        if (fseek($logfile, $logfile_header_head_offset, SEEK_SET) !== 0) {
                            syslog(LOG_ERR, "fseek() failed on logfile.");
                            exit(127);
                        }
                        $block = fread($logfile, $logfile_header_head_size);
                        if ($block === false || strlen($block) != $logfile_header_head_size) {
                            syslog(LOG_ERR, "logfile broken.");
                            exit(127);
                        }
                        $header = unpack('Vhead', $block);
                        $head = $header['head'];
                        $block = pack('V', $tail);
                        if (fwrite($logfile, $block, $logfile_header_tail_size) !== $logfile_header_tail_size) {
                            syslog(LOG_ERR, "Can not update logfile header tail: $tail");
                        } else {
                            if (fflush($logfile) === false) {
                                syslog(LOG_EMERG, "fflush() failed on logfile.");
                            }
                            $logfile_next_sync_time = $timestamp + $this->logfile_sync_interval;
                        }
                        unset($block);
                        if (flock($logfile, LOCK_UN, $wouldblock) === false) {
                            syslog(LOG_ERR, "Can not release lock on logfile.");
                        }
                    }
                }
            } catch (Exception $e) {
                $this->log('Error: %s', $e->getMessage());
            }
        	if ($nevents < self::MAX_EVENTS) {
                usleep($this->server['timeout'] * 1000000);
            }
        } while (!$this->signal());
        return;
    }

    /**
     * 处理tracer事件
     *
     * @param   int     $serverId   服务器id
     * @param   int     $type       事件类型
     * @param   int     $userId     用户id
     * @param   array   $params     其他参数
     * @param   array   $progress   用户进度
     *
     * @return void
     */
    private function handleTracerEvent($serverId, $type, $userId, $progress = array(), $params = array())
    {
        $listeners = $this->eventDispatcher->getEventListeners($serverId, $type, $userId, $progress, $params);
        if (!empty($listeners)) {
            \service\Thread::send(get_class($this->eventDispatcher) . '::dispatch', $userId,
                array(
                    'type'      => $type,   // 类型
                    'userId'    => $userId, // 用户id
                    'params'    => $params, // 其他参数
                ), $listeners);
        }
        return;
    }

}