<?php
namespace service;
/**
 * 后台执行程序抽象类
 * 
 * @author wangwei
 */
abstract class Service extends ServiceBase
{
    /**
     * 服务配置
     *
     * @var array|null
     */
    protected $server = null;

    /**
     * 服务器配置列表
     *
     * @var array
     */
    public $serverConfs = array();

    /**
     * 运行时目录
     *
     * @var string
     */
    protected $runtimeDir = null;

    /**
     * 运行时pid目录
     *
     * @var string
     */
    protected $pidDir = 'pids';

    /**
     * 运行时日志目录
     *
     * @var string
     */
    protected $logDir = 'logs';

    /**
     * 运行时文件锁目录
     *
     * @var string
     */
    protected $locks = 'locks';

    /**
     * 进程数
     *
     * @var int
     */
    public $num = null;

    /**
     * 明天凌晨00:00:00的Unix时间戳
     *
     * @var null
     */
    protected $tomorrow = null;

    /**
     * 今日
     *
     * @var null
     */
    protected $today = null;

    /**
     * 日志文件
     *
     * @var null
     */
    protected $log = null;

    /**
     * 日志文件句柄
     *
     * @var resource
     */
    protected $stream = null;

    /**
     * 消息队列
     *
     * @var resource|null
     */
    protected $queue = null;

    /**
     * @var int|null
     */
    public $timestamp = null;

    /**
     * 初始化
     *
     * @param   string   $name   队列名
     *
     * @return array
     */
    public static function initServer($name)
    {
        $name = lcfirst(trim(substr($name, strpos($name, CS) - strlen($name)), CS)); // 队列名
        $conf = cfg("server.queue.{$name}", null, false); // 队列配置
        if (empty($conf)) {
        	return false;
        }
        $conf['name'] = $name;
        if (!isset($conf['queue_num'])) { // 需要生成的线程数
            $conf['queue_num'] = 1;
        }
        if (!isset($conf['interval'])) { // 间隔时间
            $conf['interval'] = 0;
        }
        if (!isset($conf['desiredmsgtype'])) { // 请求消息类型
            $conf['desiredmsgtype'] = -1;
        }
        // 控制消息类型
        $conf['controlmsgtype'] = (!isset($conf['controlmsgtype']) && $conf['desiredmsgtype'] != -1) 
        	? $conf['desiredmsgtype'] + 1 : -1;
        $conf['class'] = ucfirst($name);
        return $conf;
    }

    /**
     * 构造函数
     *
     * @param   array   $name   队列名
     *
     * @return \service\Service
     */
    public function __construct($name)
    {
        parent::__construct();
        $this->server = self::initServer($name);
        if (!isset($this->queue)) {
        	$this->queue = msg_get_queue(cfg('server.environ.msg_queue_key', null, false), 0666);
        }
        return;
    }

    /**
     * 初始化
     *
     * @retrun bool
     */
    protected function initialize()
    {
        global $argv;
        $this->runtimeDir = rtrim(cfg('server.environ.runtime_dir', null, false), DS) . DS;
        if (substr($this->runtimeDir, 0, 2) == '.' . DS) {
        	$this->runtimeDir = realpath(ROOT_PATH . ltrim($this->runtimeDir, '.')) . DS;
        }
        if (!isset($this->num) 
        	&& (($indexKey = array_search('-index', $argv)) 
        		&& isset($argv[$indexKey + 1]) && is_numeric($argv[$indexKey + 1]))) {
        	$this->num = $argv[$indexKey + 1];
        }
        $this->today = date('Y-m-d', $this->frame->now);
        $this->tomorrow = strtotime($this->today) + 86400;
        $name = $this->server['name'];
        $this->log = $this->logDir. DS . "%(date)s" . DS 
        	. (isset($this->num) ? "{$name}-%(num)d" : $name) . '.log';
        $this->stream = $this->openFile($this->log);
        $file = $this->pidDir . DS 
        	. (isset($this->num) ? "{$name}-%(num)d" : $name) . '.pid';	
       	$pidfile = $this->openFile($file, 'c');
    	if ($pidfile === false) {
            syslog(LOG_EMERG, "fopen($file, wb) failed.");
            exit(127);
        }
     	if (!flock($pidfile, LOCK_EX|LOCK_NB)) {
            syslog(LOG_ERR, sprintf("%s has already started.", $this->server['name']));
            exit(127);
        }
        if (!ftruncate($pidfile, 0)) {
            syslog(LOG_EMERG, "ftruncate($file, 0) failed.");
            exit(127);
        }
        $pid = sprintf("%d", posix_getpid());
        if (fwrite($pidfile, $pid) != strlen($pid)) {
            syslog(LOG_EMERG, "fwrite($pid) failed on pidfile: $file.");
            exit(127);
        }
        if (fclose($pidfile) === false) {
            syslog(LOG_ERR, "fclose($pidfile) failed.");
        }
        if (empty($this->serverConfs)) {
            $this->serverConfs = $this->Global_serverConfs;
        }
        if ($this->server['queue_num'] > 0 && isset($this->num)) {
        	$name .= '-' . $this->num;
        }
        $this->log("%s started, process: %d", $name, $pid);
        return true;
    }

    /**
     * 重新加载服务配置
     *
     * @param   string  $command    命令
     *
     * @return bool
     */
    protected function reload($command)
    {
        $php = cfg('server.environ.php_cli', null, false);
        if (pcntl_exec($php, $command) === false) {
            syslog(LOG_ERR, 'pcntl_exec() failed.');
        }
        return true;
    }

    /**
     * 日志文件按天归档
     *
     * @param   int   $now   当前时间
     *
     * @return bool
     */
    protected function scroll($now)
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->today = date('Y-m-d', $now);
        $this->tomorrow = strtotime($this->today) + 86400;
        $this->stream = $this->openFile($this->log);
        return true;
    }

    /**
     * 捕捉信号
     *
     * @return bool
     */
    protected function signal()
    {
        if ($this->server['controlmsgtype'] > 0 && isset($this->queue)) {
            while ($ok = msg_receive($this->queue, $this->server['controlmsgtype'], $msgtype, 65536, $command, true, MSG_IPC_NOWAIT, $errno)) {
                switch ($command) {
                    case 'stop':
                        $this->log('Caught signal, "'. $this->server['name'] . '" exit.');
                        return true;
                    case 'reload':
                        $this->reload($command);
                        break;
                    default:
                        syslog(LOG_ERR, "unsupport command \"$command\" received - " . $this->server['name']);
                        break;
                }
            }
            if ($errno != 42) {
                $error = posix_strerror($errno);
                syslog(LOG_ERR, "msg_receive() failed (#$errno $error)");
            }
            return $ok;
        }
        return false;
    }

    /**
     * 打开文件
     *
     * @param   string  $file    		文件
     * @param   string  $mode    		权限
     * @param   bool	$returnFile    	是否返回文件
     *
     * @return resource | string
     */
    protected function openFile($file, $mode = 'a', $returnFile = false)
    {
        if (strpos($file, '%(date)s') !== false) {
            $file = str_replace('%(date)s', str_replace('-', '', $this->today), $file);
        }
        if (strpos($file, '%(num)d') !== false) {
            $file = str_replace('%(num)d', $this->num, $file);
        }
        if (empty($this->runtimeDir)) {
            $this->runtimeDir = rtrim(cfg('server.environ.runtime_dir', null, false), DS) . DS;
        	if (substr($this->runtimeDir, 0, 2) == '.' . DS) {
        		$this->runtimeDir = ROOT_PATH . ltrim($this->runtimeDir, '.');
        	}
        }
        $file = $this->runtimeDir . $file;
        $dirName = dirname($file);
        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }
        $fh = fopen($file, $mode);
        return $returnFile ? $file : $fh;
    }

    /**
     * 记录日志
     *
     * @param   string  $format   格式
     *
     * @return bool
     */
    protected function log($format)
    {
        $return = false;
        if (isset($this->stream)) {
            $args = func_get_args();
            $record = vsprintf($format, array_slice($args, 1));
            $seconds = (($this->frame->now + 28800) % 86400);
            $s = ($seconds % 60);
            $m = (($seconds - $s) % 3600) / 60;
            $h = ($seconds - $s - $m * 60) / 3600;
            if ($h < 10) {
                $h = '0' . $h;
            }
            if ($m < 10) {
                $m = '0' . $m;
            }
            if ($s < 10) {
                $s = '0' . $s;
            }
            $datetime = '[' . $this->today . " $h:$m:$s]";
            if (substr($record, -1) != "\n") {
                $record .= "\n";
            }
            $record = $datetime .' ' . $record;
            fwrite($this->stream, $record);
            $return = fflush($this->stream);
        }
        return $return;
    }

    /**
     * 查看进程状态 (true 已启动  false 未启动)
     *
     * @return bool
     */
    protected function status()
    {
        $name = $this->server['name'];
        if ($name == 'master') {
        	$this->num = null;
        }
        $file = $this->pidDir . DS . (isset($this->num) ? "{$name}-%(num)d" : $name) . '.pid';
        if (strpos($file, '%(date)s') !== false) {
            $file = str_replace('%(date)s', str_replace('-', '', $this->today), $file);
        }
        if (strpos($file, '%(num)d') !== false) {
            $file = str_replace('%(num)d', $this->num, $file);
        }
    	if (!isset($this->runtimeDir)) {
        	$this->runtimeDir = rtrim(cfg('server.environ.runtime_dir', null, false), DS) . DS;
        	if (substr($this->runtimeDir, 0, 2) == '.' . DS) {
        		$this->runtimeDir = realpath(ROOT_PATH . ltrim($this->runtimeDir, '.')) . DS;
        	}
        }
        $pidfile = $this->runtimeDir . $file;
        exec("kill -0 `cat $pidfile 2> /dev/null` 2> /dev/null", $output, $status);
        return $status ? false : file_get_contents($pidfile);
    }

    /**
     * 关闭进程
     *
     * @return bool
     */
    protected function stop()
    {
        $name = $this->server['name'];
    	if ($name == 'master') {
        	$this->num = null;
        }
        $file = $this->pidDir . DS . (isset($this->num) ? "{$name}-%(num)d" : $name) . '.pid';
        if (strpos($file, '%(date)s') !== false) {
            $file = str_replace('%(date)s', str_replace('-', '', $this->today), $file);
        }
        if (strpos($file, '%(num)d') !== false) {
            $file = str_replace('%(num)d', $this->num, $file);
        }
        if (!isset($this->runtimeDir)) {
        	$this->runtimeDir = rtrim(cfg('server.environ.runtime_dir', null, false), DS) . DS;
        	if (substr($this->runtimeDir, 0, 2) == '.' . DS) {
        		$this->runtimeDir = realpath(ROOT_PATH . ltrim($this->runtimeDir, '.')) . DS;
        	}
        }
        $pidfile = $this->runtimeDir . $file;
        if (is_file($pidfile)) {
            exec("kill -0 `cat $pidfile 2> /dev/null` 2> /dev/null", $output, $status);
            if ($status == 0) {
                $pid = (int)file_get_contents($pidfile);
                posix_kill($pid, 9);
            }
        }
        $reporting = error_reporting(0);
        if (is_file($pidfile)) {
            unlink($pidfile);
        }
        if ($name == 'master') {
            $lockFile = $this->openFile($this->locks . DS . $this->server['name'] . '.lck' , 'a', true);
            if (is_file($lockFile)) {
                unlink($lockFile);
            }
        }
        error_reporting($reporting);
        exec("kill -0 `cat $pidfile 2> /dev/null` 2> /dev/null", $output, $status);
        return $status ? false : file_get_contents($pidfile);
    }

}