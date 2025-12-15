<?php
namespace service;

/**
 * 日志记录进程
 *
 * 用于将flielog的数据写入到日志中
 *
 * @author wangwei
 */
class Logging extends Service
{
    /**
     * 日志文件
     *
     * @var resource|null
     */
    private $logfile = null;
    
    /**
     * 系统配置文件
     *
     * @var object
     */
    private $conf = null;
    

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_size;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_magic;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_tail_offset;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_tail_size;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_head_offset;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_header_head_size;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_buffer_size;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_block_size;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_sync_interval;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $logfile_max_blocks;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $head;

    /**
     * 日志头文件
     * 
     * @var int
     */
    private $tail;

    /**
     * 数据库操作实例
     *
     * @var object
     */
    private $db;

    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return Logging
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Logging();
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
     * 初始化
     *
     * @return bool|void
     */
    protected function initialize()
    {
        parent::initialize();
        $conf = cfg('server.environ.logfile', null, false);
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
        $this->logfile_max_blocks = (int)$conf['max_blocks'];
        unset($block);
    	$fileName = $this->runtimeDir . $this->logDir . DS . 'logfile'; 
        if (!file_exists($fileName)) {
            syslog(LOG_WARNING, "logfile not exists!");
            exit(127);
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
        $this->logfile = $logfile;
        // 服务器配置
        $this->conf = (object)\Bootstrap::getConfigures(\Bootstrap::$hostName);
        if (empty($this->conf)) {
        	syslog(LOG_WARNING, "127.conf not exists!");
        	exit(127);
        }
        // 初始化数据的连接
        $daoHelper = clone \Application::$DaoHelper;
        $this->db = $daoHelper;
        // 检测数据表
    	if (empty($tables)) {
    		$createSq = 
<<<BLOCK
				CREATE TABLE IF NOT EXISTS `trace` (
				  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志id',
				  `serverId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '服务器id',
				  `type` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '日志类型',
				  `userId` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
				  `num` int(11) NOT NULL DEFAULT '0' COMMENT '变化数量',
				  `refer` varchar(256) NOT NULL DEFAULT '' COMMENT '关联类型(题目id等)',
				  `param1` varchar(256) NOT NULL DEFAULT '' COMMENT '其他扩展参数1',
				  `param2` varchar(256) NOT NULL DEFAULT '' COMMENT '其他扩展参数2',
				  `param3` varchar(256) NOT NULL DEFAULT '' COMMENT '其他扩展参数3',
				  `param4` varchar(256) NOT NULL DEFAULT '' COMMENT '其他扩展参数4',
				  `traceTime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '跟踪时间',
				  `recordTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录时间',
				  `requestOp` varchar(64) NOT NULL DEFAULT '' COMMENT '请求接口',
				  `requestParams` text NOT NULL COMMENT '请求参数',
				  `other` text NOT NULL COMMENT '其他信息',
				  PRIMARY KEY (`id`),
				  KEY `type` (`type`),
				  KEY `requestOp` (`requestOp`),
				  KEY `userId` (`userId`),
				  KEY `serverId` (`serverId`),
				  KEY `serverId_2` (`serverId`,`type`),
				  KEY `serverId_3` (`serverId`,`requestOp`),
				  KEY `serverId_4` (`serverId`,`userId`),
				  KEY `serverId_5` (`serverId`,`type`,`requestOp`),
				  KEY `serverId_6` (`serverId`,`type`,`requestOp`,`userId`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED COMMENT='用户跟踪日志表' ;
BLOCK;
    		$this->db->execBySql($createSq);
    	}
        $todayTable = 'trace_' . date('Ymd', $this->frame->now);
    	$tables = $this->db->fetchBySql("show tables like '{$todayTable}';", null, array(), $daoHelper::FETCH_MODE_ARR_ALL);

    	if (empty($tables)) {
    		$this->db->execBySql("CREATE TABLE `$todayTable` LIKE `trace`");
    	}
        return true;
    }

    /**
     * 启动
     *
     * @return bool
     */
    public function start()
    { 	
        $this->initialize(); 
        $logfile = $this->logfile;
        if (fseek($logfile, 0, SEEK_SET) !== 0) {
            syslog(LOG_ERR, "fseek() failed on logfile.");
            exit(127);
        }
        $block = fread($logfile, $this->logfile_header_size);
        if ($block === false || strlen($block) != $this->logfile_header_size) {
            syslog(LOG_ERR, "fread(".($this->logfile_header_size).") failed on logfile header.");
            exit(127);
        }
        $header = unpack('vmagic/vblocksize/Vlog_buffer_size/Vhead/Vtail', $block);
        if ($header['magic'] != $this->logfile_header_magic) {
            syslog(LOG_ERR, "logfile broken.");
            exit(127);
        }
        unset($block);
        $this->head = $head = $header['head'];
        $this->tail = $tail = $header['tail'];
        $this->logfile_block_size = $logfile_block_size = $header['blocksize'];
        $this->logfile_buffer_size = $logfile_buffer_size = $header['log_buffer_size'];
        $logfile_header_tail_offset = $this->logfile_header_tail_offset;
        $logfile_header_tail_size = $this->logfile_header_tail_size;
        $logfile_header_size = $this->logfile_header_size;
        $logfile_max_blocks = $this->logfile_max_blocks;
        $this->log("logfile_buffer_size: $logfile_buffer_size head: $head tail: $tail");
        $timestamp = & $this->frame->now;
        $tomorrow = & $this->tomorrow;
        do {
        	if (flock($logfile, LOCK_SH, $wouldblock) === false) {
                syslog(LOG_WARNING, "flock(LOCK_SH) failed on logfile.");
            } else {
            	if (fseek($logfile, $logfile_header_tail_offset, SEEK_SET) !== 0) {
                    syslog(LOG_ERR, "fseek() failed on logfile.");
                    exit(127);
                }
                $block = fread($logfile, $logfile_header_tail_size);
                if ($block === false || strlen($block) != $logfile_header_tail_size) {
                    syslog(LOG_ERR, "logfile broken.");
                    exit(127);
                }
                if (flock($logfile, LOCK_UN, $wouldblock) === false) {
                    syslog(LOG_ERR, "Can not release lock on logfile.");
                }
                $header = unpack('Vtail', $block);
                $this->tail = $tail = $header['tail'];
            }
           	$blocks = 0;
        	while ($head != $tail) {
        		$timestamp = time();
                if ($timestamp >= $tomorrow) {
                    $this->scroll($timestamp);
                    // 创建日志表
                    $table = 'trace_' . date('Ymd', $timestamp);
                    $this->db->execBySql("CREATE TABLE `$table` LIKE `trace`");
                }
                $offset = $logfile_header_size + $head * $logfile_block_size;
                if ($tail < $head) {
                    if ($head + $logfile_max_blocks < $logfile_buffer_size) {
                        $blocks = $logfile_max_blocks;
                        $head += $blocks;
                    } else {
                        $blocks = $logfile_buffer_size - $head;
                        $head = 0;
                    }
                } else {
                    if ($tail - $head > $logfile_max_blocks) {
                        $blocks = $logfile_max_blocks;
                        $head += $blocks;
                    }
                    else {
                        $blocks = $tail - $head;
                        $head += $blocks;
                    }
                }
                $this->head = $head;
                if (fseek($logfile, $offset, SEEK_SET) === false) {
                    syslog(LOG_EMERG, "fseek() failed on logfile, logging exited.");
                    exit(127);
                }
                $num = $blocks * $logfile_block_size;
                $data = fread($logfile, $num);
                if ($data === false || strlen($data) != $num) {
                    syslog(LOG_EMERG, "fread() failed on logfile, logging exited.");
                    exit(127);
                }
                $leader = $deadline = null;
                $groups = array();
                $last = $blocks - 1;
                for ($index = 0; $index < $blocks; $index++) {
                	$block = unpack('A' . $logfile_block_size . 'data', substr($data, $index * $logfile_block_size, $logfile_block_size));
					if (empty($block['data'])) {
                        continue;
                    }
                   	$logData = $this->assembleLog($block); // 组织日志数据
                   	if (empty($logData)) {
                   		continue;
                   	}
                    $groups[] = $logData;
                }
                $this->flush($groups);
            }
        	if ($blocks < $logfile_max_blocks) {
                usleep($this->server['timeout'] * 1000000);
            }
        } while (!$this->signal());
        return ture;
    }
    
    /**
     * 组织日志
     * 
     * @return array
     */
    private function assembleLog($block)
    {
    	$blockData = json_decode($block['data'], true);
    	$now = time();
    	$logInfoData = $blockData['data'];
    	$params = $blockData['params'];
    	return array(
    		'serverId'		=> 0,
    		'traceTime'		=> empty($blockData['time']) ? 0 : $blockData['time'], // 跟踪时间
    		'requestOp' 	=> empty($blockData['op']) ? '' : $blockData['op'], // 请求op
    		'requestParams' => empty($blockData['params']) ? '' : json_encode($blockData['params']), // 请求参数
    		'type' 			=> empty($logInfoData['type']) ? 0 : $logInfoData['type'], // 日志类型
    		'userId' 		=> empty($logInfoData['userId']) ? 0 : $logInfoData['userId'], // 操作用户id
    		'refer' 		=> empty($logInfoData['refer']) ? '' : $logInfoData['refer'], // 关联id
    		'num' 			=> empty($logInfoData['num']) ? 0 : $logInfoData['num'], // 变化数量
    		'param1' 		=> empty($logInfoData['param1']) ? '' : $logInfoData['param1'], // 参数1
    		'param2' 		=> empty($logInfoData['param2']) ? '' : $logInfoData['param2'], // 参数2
    		'param3' 		=> empty($logInfoData['param3']) ? '' : $logInfoData['param3'], // 参数3
    		'param4' 		=> empty($logInfoData['param4']) ? '' : $logInfoData['param4'], // 参数4
    		'other' 		=> empty($logInfoData['other']) ? '' : $logInfoData['other'], // 其他
    	);
    }

    /**
     * 将数据写入到数据库
     */
    private function flush($dataList, $time = null)
    {
        $writeResult = $this->writeInLog($dataList);
      	if (empty($writeResult)) { // 写入失败
            syslog(LOG_ERR, "writeInLog failed.");
            return false;
      	}
      	$this->log('%.6d data(s) on #%d flushed.', count($dataList), 0);
    	$block = pack('V', $this->head);
        $logfile = $this->logfile;
        if (flock($logfile, LOCK_EX, $wouldblock) === false) {
            syslog(LOG_EMERG, "flock(LOCK_EX) failed on logfile.");
            return;
        }
        if (fseek($logfile, $this->logfile_header_head_offset, SEEK_SET) !== 0) {
            syslog(LOG_ERR, "fseek() failed on logfile.");
            exit(127);
        }
        if (fwrite($logfile, $block, $this->logfile_header_head_size) !== $this->logfile_header_head_size) {
            syslog(LOG_ERR, "Can not update logfile header head: ".$this->head);
        } else {
            if (fflush($logfile) === false) {
                syslog(LOG_EMERG, "fflush() failed on logfile.");
            }
        }
        if (flock($logfile, LOCK_UN, $wouldblock) === false) {
            syslog(LOG_ERR, "Can not release lock on logfile.");
        }
        return true;
    }

    private static $errno;
    private static $error;
    private static $sleep = 0;
      
    /**
     * 将数据写入数据库
     *
     * @param   array   $dataList   数据
     * @param   int     $serverId   服务器id
     * 
     * @return int|bool
     */
    private function writeInLog($dataList, $serverId = 0)
    {
    	$table = 'trace_' . date('Ymd', $this->frame->now);
    	$fieldArr = array('serverId', 'traceTime', 'requestOp', 'requestParams', 'type', 'userId', 'refer', 'num', 'param1', 'param2', 'param3', 'param4', 'other');
    	return $this->db->addBat($table, $fieldArr, $dataList);
    }
    
}