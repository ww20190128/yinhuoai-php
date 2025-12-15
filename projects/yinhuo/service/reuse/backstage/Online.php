<?php
namespace service;

/**
 * 在线队列
 *
 * 对外接口: login,logout
 * <code>
 * // 用户上线
 * Online::login($userId, array('loginTime' => $this->frame->now));
 * // 用户下线
 * Online::logout($userId, array('loginTime' => $this->frame->now));
 * </code>
 *
 * @author wangwei
 */
class Online extends Service
{
    /**
     * 将用户添加进入队列
     *
     * @var int
     */
    const USER_ADD = 1;

    /**
     * 将用户删除队列
     *
     * @var array
     */
    const USER_DELETE = 2;

    /**
     * 运行时保存进度的路径
     *
     * @var string
     */
    protected $progressFile;

    /**
     * 在线线程
     *
     * @var array
     */
    private $onlineThread = array();

    /**
     * 离线线程
     *
     * @var array
     */
    private $offlineThread = array();

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
            self::$instance = new Online();
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
        $this->progressFile = $this->runtimeDir . DS . $this->server['name'] . DS . 'progress.txt';
        if (is_file($this->progressFile)) {
            $progress = include($this->progressFile);
            if (is_array($progress)) {
                $this->onlineThread  = $progress['onlineThread'];
                $this->offlineThread = $progress['offlineThread'];
            }
        } else {
            $pathname = dirname($this->progressFile);
            if (!is_dir($pathname)) {
                mkdir($pathname, 0777, true);
            }
        }
        return;
    }

    /**
     * 用户登录
     *
     * @param   int     $userId     角色id
     * @param   array   $extInfo    其他信息
     *
     * @return bool
     */
    public static function login($userId, $extInfo = array())
    {
        $self = Online::singleton();
        $reporting = error_reporting(0);
        $msg = array(
                    'serverId'  => $self->frame->id,
                    'action'    => self::USER_ADD,
                    'userId'    => $userId,
                    'params'    => $extInfo['loginTime'],
               );
        $ok = msg_send($self->queue, $self->server['desiredmsgtype'], $msg, true, false, $errno);
        error_reporting($reporting);
        return $ok;
    }

    /**
     * 用户注销
     *
     * @param   int     $userId     角色id
     * @param   array   $extInfo    其他信息
     *
     * @return bool
     * @throws Exception
     */
    public static function logout($userId, $extInfo = array())
    {
        $self = Online::singleton();
        $reporting = error_reporting(0);
        $msg = array(
            'serverId'  => $self->frame->id,
            'action'    => self::USER_DELETE,
            'userId'    => $userId,
            'params'    => $extInfo['logoutTime'],
        );
        $ok = msg_send($self->queue, $self->server['desiredmsgtype'], $msg, true, false, $errno);
        error_reporting($reporting);
        return $ok;
    }

    /**
     * 启动
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
            // 收集用户信息
            while (($ok = msg_receive($this->queue, $this->server['desiredmsgtype'], $msgtype, 65536, $msg, true, MSG_IPC_NOWAIT, $errno))) {
                if (empty($msg['serverId']) || empty($msg['action']) || empty($msg['params'])) {
                    continue;
                }
                $action   = $msg['action'];
                $serverId = $msg['serverId'];
                $params   = $msg['params'];
                $userId   = $msg['userId'];
                try {
                    if ($action == self::USER_ADD) { // 将用户添加进入队列
                        list($loginTime) = $params; // 参数
                        if (!empty($this->offlineThread[$serverId][$userId])) { // 刷新页面，延迟离线处理过程中再次进入
                            unset($this->offlineThread[$serverId][$userId]); // 删除离线队列
                            $this->log('[%d] #%0.6d come back!', $serverId, $userId);
                        } else { // 正常登录
                            if (!empty($this->onlineThread[$serverId][$userId])) { // 已在在线队列中
                                continue;
                            }
                            // 添加用户到在线线程
                            $this->onlineThread[$serverId][$userId] = array(
                                'loginTime'     => $loginTime, // 登录时间
                                'times'         => 1, // 登录次数
                                'onlineTime'    => $this->frame->now - $loginTime, // 在线时长
                            );
                        }
                    } elseif ($action == self::USER_DELETE) { // 将用户删除队列
                        list($logoutTime) = $params; // 参数
                        if (empty($this->onlineThread[$serverId][$userId]) || !empty($this->offlineThread[$serverId][$userId])) {
                            continue;
                        }
                        $this->offlineThread[$serverId][$userId] = array(
                            'logoutTime' => $logoutTime,
                            'loginTime'  => $this->onlineThread[$serverId][$userId]['loginTime'], // 登录时间
                            'onlineTime' => $this->frame->now - $this->onlineThread[$serverId][$userId]['loginTime'], // 在线时长
                        );
                    }
                } catch (Exception $e) {
                    $this->log('ERROR: %s', $e->getMessage());
                }
            }
            // 处理用户
            foreach ($this->onlineThread as $serverId => $userIds) { // 处理提醒
                // 启动相应的服务
                foreach ($userIds as $userId => $info) {
                    $this->log('服务器id:%d, 用户id:%d, 在[%s]登录, 累计在线时长[%d]秒',
                        $serverId, $userId, date('Y-m-d H:i:s', $info['loginTime']),
                        $info['onlineTime']);
                }
                $this->log('服务器id:%d, 目前有%d个用户在线', $serverId, count($this->onlineThread[$serverId]));
            }
            // 离线处理
            foreach ($this->offlineThread as $serverId => $userIds) {
                foreach ($userIds as $userId => $info) {
                    $this->log('服务器id:%d, 用户id:%d, 在[%s]注销, 累计在线时长[%d]秒',
                        $serverId, $userId, date('Y-m-d H:i:s', $info['logoutTime']), $info['onlineTime']);
                    unset($this->onlineThread[$serverId][$userId]);
                    unset($this->offlineThread[$serverId][$userId]);
                }
                $this->log('服务器id:%d, 目前有%d个用户在线', $serverId, count($this->onlineThread[$serverId]));
            }
            // 保存进度
            if (is_dir(dirname($this->progressFile)) && (!(empty($this->onlineThread) && empty($this->offlineThread)))) {
                $this->progressFile and file_put_contents($this->progressFile,
                    "<?php\nreturn ".var_export(array(
                        'onlineThread'  => $this->onlineThread,
                        'offlineThread' => $this->offlineThread,
                    ), true) . ";\n");
            }
            usleep($this->server['interval'] * 1000000);
        } while (!$this->signal());
        return true;
    }

}