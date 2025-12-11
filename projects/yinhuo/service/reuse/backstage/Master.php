<?php
namespace service;

/**
 * 主队列类
 * 用于管理子进程的启动
 * 
 * @author wangwei
 */
class Master extends Service
{
    /**
     * 服务器列表
     *
     * @var array
     */
    private $servers = array();

    /**
     * 构造函数
     *
     * @return \service\Master
     */
    public function __construct()
    {
    	parent::__construct(__CLASS__);
    }

    /**
     * 初始化
     *
     * @return bool
     */
    protected function initialize()
    {
        if (posix_geteuid() == 0) {
            $user = cfg('server.environ.user', null, false);
            $pw = posix_getpwnam($user);
            if (!posix_setgid($pw['gid'])) {
                $errno = posix_get_last_error();
                fprintf(STDERR, "#%d: %s\n", $errno, posix_strerror($errno));
                exit(127);
            }
            if (!posix_setuid($pw['uid'])) {
                $errno = posix_get_last_error();
                fprintf(STDERR, "#%d: %s\n", $errno, posix_strerror($errno));
                exit(127);
            }
        }
        // 必须在写入pid之前检查锁定
        $lockFile = $this->openFile($this->locks . DS . $this->server['name'] . '.lck', 'a');
        if ($lockFile === false) {
			exit(129);
        }    
        if (!flock($lockFile, LOCK_SH | LOCK_NB, $wouldblock) || !flock($lockFile, LOCK_EX | LOCK_NB, $wouldblock)) {
			fprintf(STDERR, "Already running...\n");
			exit(128);
        }
        parent::initialize();
        $pidDir = $this->runtimeDir . $this->pidDir;
        $dirHander = opendir($pidDir);
        if ($dirHander !== false) {
            while ($filename = readdir($dirHander)) {
                if (substr($filename, -4) != '.pid' || substr($filename, 0, -4) == $this->server['name']) {
                    continue;
                }
                $fp = fopen($pidDir . DS . $filename, 'r');
                if ($fp === false) {
                    syslog(LOG_EMERG, "fopen($filename) failed.");
                    exit(128);
                }
                $pid = fread($fp, 64);
                if (fclose($fp) === false) {
                    exit(129);
                }
                if ($pid > 0) {
                    $this->log("kill %d with signal 9, pidfile: %s", $pid, $filename);
                    posix_kill((int)$pid, 9);
                }
            }
            if (closedir($dirHander) === false) {
                syslog(LOG_EMERG, "closedir() failed.");
                exit(129);
            }
        }
        $this->servers = array();
		$servers = cfg('server.queue', null, false);
        if (!empty($servers)) foreach ($servers as $name => $service) {
            if (empty($service['switch'])) {
            	continue;
            }
			$server = self::initServer($name);
            if (empty($server) || $server['interval'] > 0 || $name == 'master') {
                continue;
            }
            for ($index = 0, $num = $server['queue_num']; $index < $num; $index++) {
                $server['num'] = $index;
                $this->startServer($server);
            }
        }
        return true;
    }

    /**
     * 启动服务
     *
     * @param   array   $server   服务配置
     *
     * @return int
     */
    private function startServer($server)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            syslog(LOG_EMERG, 'master: pcntl_fork() failed');
            exit(125);
        } elseif ($pid == 0) {
            $instance = $this->locator->getService($server['class']);
            $instance->num = $server['num'];
            $instance->serverConfs = $this->serverConfs;
			$instance->start();
            // 子进程异常退出
            syslog(LOG_EMERG, 'master: daemon process <' . $server['name'] . '> unexpect exited.');
            exit(0);
        } else {
            $this->servers[$pid] = $server;
            return $pid;
        }
        return false;
    }

    /**
     * 启动
     *
     * 初始化时启动其他服务,将没启动的服务放到$this->servers 在start中循环处理
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
            $pid = pcntl_wait($status); // 返回退出的子进程进程号,发生错误时返回-1,没有有可用子进程时返回0
            if ($pid > 0 && isset($this->servers[$pid])) {    	
                $server = $this->servers[$pid];
                unset($this->servers[$pid]);
                $this->log("restarting server %s/%d:%d...", $server['name'], $server['num'], $server['queue_num'], $pid);
                usleep(500000);
                $pid = $this->startServer($server);
                $this->log("%s/%d:%d process %d started", $server['name'], $server['num'], $server['queue_num'], $pid);
            }
            usleep(1000000);
        } while (!$this->signal());
        return true;
    }

    /**
     * 查看子进程状态
     *
     * @return array
     */
    public function statusList()
    {
        $result = array();
        foreach (cfg('server.queue', null, false) as $name => $service) {
            if (empty($service['switch'])) {
            	continue;
            }
            $server = self::initServer($name);
            if ($server['interval'] > 0) {
                continue;
            }
            for ($index = 0, $num = $server['queue_num']; $index < $num; $index++) {
                $server['num'] = $index;
                $instance = $this->locator->getService($server['class']);
                $instance->num = $server['num'];
                $instance->serverConfs = $this->serverConfs;
                $result[] = array_merge($server, array('status' => $instance->status()));
            }
        }

        return $result;
    }

    /**
     * 改变所有服务进程
     *
     * @return array
     */
    public function stopList()
    {
        $result = array();
        foreach (cfg('server.queue', null, false) as $name => $service) {
            if (empty($service['switch'])) continue;
            $server = self::initServer($name);
            if ($server['interval'] > 0) {
                continue;
            }
            for($index = 0, $num = $server['queue_num']; $index < $num; $index++) {
                $server['num'] = $index;               
                $instance = $this->locator->getService($server['class']);
                $instance->num = $server['num'];
                $instance->serverConfs = $this->serverConfs; 
                $result[] = array_merge($server, array('status' => $instance->stop()));
            }           
        }
    	$lockFile = $this->openFile($this->locks . DS . $this->server['name'] . '.lck', 'a', true);
       	if (is_file($lockFile)) {
        	unlink($lockFile);
      	}
        return $result;
    }

}