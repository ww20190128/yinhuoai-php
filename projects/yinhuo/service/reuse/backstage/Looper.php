<?php
namespace service;

/**
 * 每隔一段时间进程
 *
 * @author wangwei
 */
class Looper extends Service
{
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
     * @return Looper
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Looper();
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
     * @return bool
     */
    protected function initialize()
    {
        parent::initialize();
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
        $serverConfs = $this->serverConfs;
        do {
        	$this->frame->now = time();
            if ($this->frame->now >= $this->tomorrow) {
                $this->scroll($this->frame->now);
            }
            usleep($this->server['timeout'] * 1000000); // 10s 执行一次
            foreach ($serverConfs as $serverConf) {
            	$serverId = $serverConf['id'];
            	if (\Halo::init($serverId, true) === false) {
                    syslog(LOG_ERR, "Halo::init($serverId) failure!");
                    continue;
                };
if ($serverConf['mark'] != 'dev') { // 只在dev执行
	continue;
}
				$this->log("started, loop: %s", date('Y-m-d H:i:s', $this->frame->now));
                $arenaGuildTeamSv = \service\ArenaGuildTeam::singleton();
        		$arenaGuildTeamSv->matchTeam();
				$arenaTeamSv = \service\ArenaTeam::singleton();
        		$arenaTeamSv->autoDismissTeam();
        		$this->log("over, loop: %s", date('Y-m-d H:i:s', $this->frame->now));
            }
        } while (!$this->signal());
        return true;
    }

}