<?php
namespace service;

/**
 * 计划任务, 定时器进程(废弃)
 *
 * 1. 每隔xx秒执行
 * 2. 在xxx定点时间执行
 *
 * @package service
 */
class Timer extends Service
{
    /**
     * 时间类型: 间隔
     *
     * @var int
     */
    const TIME_TYPE_INTERVAL = 1;

    /**
     * 时间类型: 定点
     *
     * @var int
     */
    const TIME_TYPE_FIXED_POINT = 2;

    /**
     * 时间类型: 定时任务
     *
     * @var int
     */
    const TIME_TYPE_CRONTAB = 3;

    /**
     * 时间类型: 死循环
     *
     * @var int
     */
    const TIME_TYPE_LOOP = 4;

    /**
     * 数据缓冲区
     *
     * @var array
     */
    private $buffer = array();

    /**
     * 任务列表
     *
     * @var array
     */
    private $tasks = array();

    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return Crontab
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Timer();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct(__CLASS__);
        // 注册回调函数,在进程退出时处理
        $this->addShutdownCallBack(array($this, 'flush'));
    }

    /**
     * 按格式类型获取当前时间
     *
     * @param string $type 格式类型（枚举i,H,d,m,w，默认i）
     *
     * @return int
     */
    private function getNowByType($type = null)
    {
        return intval(date(in_array($type, array('i', 'H', 'd', 'm', 'w')) 
        	? $type : 'i', $this->frame->now));
    }

    /**
     * 检查任务是否满足时间条件
     *
     * @param string $str 任务间隔（格式类似crontab）
     *
     * @return bool
     */
    private function checkCrontabTime($str)
    {
        list($i, $H, $d, $m, $w) = array_pad(explode(' ', $str), 5, '*');
        // 分钟 不符合
        if (!$this->checkOneCrontabTime($i, "i")) {
        	return false;
        } 
        // 小时 不符合
        if (!$this->checkOneCrontabTime($H, "H")) {
        	return false;
        } 
        // 日 不符合
        if (!$this->checkOneCrontabTime($d, "d")) {
        	return false;
        } 
        // 月 不符合
        if (!$this->checkOneCrontabTime($m, "m")) {
        	return false;
        } 
        // 星期 不符合
        if (!$this->checkOneCrontabTime($w, "w")) {
        	return false;
        }
        return true;
    }

    /**
     * 按类型检查单条间隔是否满足
     *
     * @param   string  $str  时间格式的字符串
     * @param   string  $type 格式类型（枚举i,H,d,m,w，默认i）
     *
     * @return bool
     */
    private function checkOneCrontabTime($str, $type)
    {
        if ($str == "*") { // 星号，直接通过
            return true;
        } elseif (is_numeric($str)) {
            return intval($str) == $this->getNowByType($type); // 数字，判断是否相等
        } elseif (($sp = explode(",", $str)) && count($sp) > 1) {
            foreach ($sp as $s) { // 由“,”分割为多个条件，拆开判断每个单独的条件，“或”的关系
                if (!$this->checkOneCrontabTime($s, $type)) continue;
                return true;
            }
            return false;
        } elseif (($sp = explode("/", $str)) && count($sp) == 2) {
            // 由“/”分割时间和间隔，先判断时间是否在范围内，再判断间隔是否能整除
            if (!$this->checkOneCrontabTime($sp[0], $type)) {
                return false;
            }
            return $this->getNowByType($type) % $sp[1] == 0;
        } elseif (($sp = explode("-", $str)) && count($sp) == 2) {
            $v = $this->getNowByType($type); // 由“-”分割，判断时间是否在范围内
            if ($sp[0] > $sp[1]) {
                return $v >= $sp[0] || $v <= $sp[1];
            } else { // 开始大于结束的时候，“或”关系，否则“与”关系
                return $v >= $sp[0] && $v <= $sp[1];
            }
        }
        return false; // 默认不通过
    }

    /**
     * 新增任务
     *
     * @param   string      $time       时间（Linux-Crontab格式）
     * @param   callback    $callback   回调函数
     * @param   array       $params     参数列表
     * @param   int         $startTime  开始时间
     * @param   int         $endTime    结束时间
     * @param   int         $times      执行的次数
     * @param   int         $name      	任务名称
     *
     * 基本格式 : 分　时　日　月　周
     * 第1列表示分钟1～59 每分钟用*或者
     * 第2列表示小时1～23（0表示0点）
     * 第3列表示日期1～31
     * 第4列表示月份1～12
     * 第5列标识号星期0～6（0表示星期天）
     *
     * 任务格式
     *
     * 'type'       => // 执行方式
     * 'startTime' => // 执行开始时间
     * 'endTime'   => // 执行结束时间
     *
     * @return array
     */
    public static function addTask($time, $callback, $params = array(), 
    	$startTime = null, $endTime = null, $times = 9999999, $name = '')
    {
        $self = Timer::singleton();
        $startTime or $startTime = $self->frame->now;       // 执行开始时间
        $endTime or $endTime = $self->frame->now * 100;   	// 执行结束时间
        if ($startTime >= $endTime || $endTime <= $self->frame->now || (is_numeric($time) && $time <= 0)) {
        	return false;
        }
        $maxTimes = -1;
        $execTime = null;
        if (is_numeric($time) && $time < $self->frame->now) { // 定时回调, 多少秒后执行一次
            $type = self::TIME_TYPE_INTERVAL;
            $maxTimes = min($times, ceil($endTime - $startTime / $time)); // 最大可执行次数
            $execTime = $startTime + $time;
        } elseif (is_numeric($time) && $time > $self->frame->now) { // 具体的时间点执行 只执行一次
            $type = self::TIME_TYPE_FIXED_POINT;
            $maxTimes = 1;
            $execTime = $time;
        } elseif (empty($time)) { // 死循环一直执行
            $type = self::TIME_TYPE_LOOP;
        } else { // crontab时间格式
            $type = self::TIME_TYPE_CRONTAB;
        }
        $self->buffer[] = array(
            'serverId'  => $self->frame->id,    // 服务器id
            'type'      => $type,               // 任务类型
            'startTime' => $startTime,          // 执行开始时间
            'endTime'   => $endTime,            // 执行结束时间
            'callback'  => $callback,           // 回调函数
            'params'    => $params,             // 参数
            'counter'   => 0,                   // 计数器
            'maxTimes'  => $maxTimes,           // 最大执行次数
            'timeStr'   => $time,               // 时间格式
        	'execTime'  => $execTime,           // 执行时间点
        	'name'  	=> $name,           	// 任务名称
        );
    	if ($self->frame->runType == \Halo::HALO_SHELL) {
            $self->flush();
        }
        return true;
    }

    /**
     * 刷新缓冲区
     *
     * @return bool
     */
    public function flush()
    {
    	$ok = false;
        if (!empty($this->buffer)) {
            $reporting = error_reporting(0);
            foreach ($this->buffer as $message) {
            	$ok = msg_send($this->queue, $this->server['desiredmsgtype'], $message, true, false, $errno);
                if (!$ok) {
                    $error = posix_strerror($errno);
                    syslog(LOG_ERR, "msg_send failed with error #$errno ($error) for " . json_encode($message));
                }
            }
            $this->buffer = array();
            error_reporting($reporting);
        }
        return $ok;
    }

    /**
     * 初始化
     *
     * @return bool
     */
    protected function initialize()
    {
        parent::initialize();
        // 加载任务
        $crontabList = cfg('crontab');
        foreach ($crontabList as $crontab) {
            self::addTask($crontab['time'], $crontab['callback'], $crontab['params'],
                $crontab['startTime'], $crontab['endTime'], $crontab['times'], $crontab['name']);
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
        do {
        	$this->frame->now = time();
            if ($this->frame->now >= $this->tomorrow) {
                $this->scroll($this->frame->now);
            }
            // 收集任务信息
            while (($ok = msg_receive($this->queue, $this->server['desiredmsgtype'], $msgtype, 65536, $msg, true, MSG_IPC_NOWAIT, $errno))) {
            	if (empty($msg['serverId']) || empty($msg['type']) || empty($msg['callback'])
                    || empty($msg['startTime']) || empty($msg['endTime']) || $msg['endTime'] <= $this->frame->now) {
                    continue;
                }
                $this->tasks[] = $msg;
            }
            if (empty($this->tasks)) { // 当前没收集到任务，休息一会
            	echo "<没收到消息:". date('Y-m-d H:i:s') . "休息\n";
            	usleep(60000000); // 休息一分钟
            	echo "休息结束:". date('Y-m-d H:i:s') . ">\n";
            }
            // 执行任务
        	$waitPids = array();
            foreach ($this->tasks as $index => &$task) {
            	$type = $task['type']; // 任务类型
                if ($type == self::TIME_TYPE_INTERVAL) { // 间隔
                	// 检查时间
                	if ($task['counter'] >= $task['maxTimes']) {
                       	unset($this->tasks[$index]); // 注销任务
                        continue;
                	}
                	// 检查时间
                  	if ($task['execTime'] > $this->frame->now || $task['execTime'] + 5 < $this->frame->now || $task['endTime'] < $this->frame->now) {
                  		continue;
                  	}
                  	$task['execTime'] += $task['timeStr']; // 更正下次执行时间
            	} elseif ($type == self::TIME_TYPE_FIXED_POINT) { // 定点
                  	if ($task['counter'] >= $task['maxTimes']) {
                        unset($this->tasks[$index]);
                         continue;
               		}
                  	// 检查时间
                  	if ($task['execTime'] > $this->frame->now || $task['execTime'] + 5 < $this->frame->now) {
                  		continue;
                  	}
           		} elseif ($type == self::TIME_TYPE_CRONTAB) { // crontab 
                    if (!$this->checkCrontabTime($task['timeStr'])) {
                    	continue;
                	}
                	if (!empty($task['execTime']) && $task['execTime'] > time()) { // 执行时间未到
                		continue;
                	}
               	} else { // 死循环了
                 	unset($this->tasks[$index]); // 注销任务
                	continue;
             	}
            	// fork出子进程执行任务，防止由于前边报错而卡住后边的任务
             	$taskPid = pcntl_fork();
              	if ($taskPid < 0) {
                	continue;
           		} else if ($taskPid == 0) {
           			if (empty($task['execTime'])) {
           				$task['execTime'] = time();
           			}
           			echo date('Y-m-d H:i:s') . "任务：" . $task['timeStr'] . "\n";
           		//	\service\Thread::send($task['callback'], $task['serverId'], $task['params']);
         			$this->tasks[$index]['counter'] ++;
         			// 添加一个时间间隔
         			$task['execTime'] += 60;
        		} else {
            		// 父进程记录子进程ID
            		$waitPids[] = $taskPid;
              	}   	     
         	}
        	// 等待所有子进程退出后，主进程才退出
        	foreach ($waitPids as $pid) {
       			$status = null;
     			pcntl_waitpid($pid, $status);
        	}      		
        	usleep(60000000); // 每分钟执行一次
        } while (!$this->signal());
        return true;
    }

}