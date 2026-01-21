<?php
namespace service;
use service\reuse\Activity;

/**
 * 事件分发器
 *
 * @author wangwei
 */
class EventDispatcher extends ServiceBase
{
    /**
     * 监听器列表
     *
     * @var array
     */
    private $listeners = array();

    /**
     * 事件句柄列表
     *
     * @var array
     */
    private static $eventHandles = array();

    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return EventDispatcher
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new EventDispatcher();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     *
     * @return \service\EventDispatcher
     */
    public function __construct()
    {
        parent::__construct();
        $this->initialize();
        return;
    }

    /**
     * 分发事件
     *
     * @param   array   $userInfo       玩家的信息内容
     * @param   array   $listeners      未完成事件监听器列表
     *
     * @return bool
     */
    public static function dispatch($userInfo, $listeners)
    {
        $self = self::singleton();
        $eventHandles = self::$eventHandles;
        try {
            foreach ($listeners as $listener) {
                if (empty($listener['handler'])) {
                    syslog(LOG_WARNING, 'Error: Invalid handler from listener(' . json_encode($listener) . ')');
                    continue;
                }
                $handler = $listener['handler'];
                if (!empty($eventHandles[$handler])) {
                    // 事件处理
                    $self->$handler($listener, $userInfo);
                } else {
                    syslog(LOG_WARNING, 'Error: Not supported handler type "' . $userInfo['type'] . '"');
                }
            }
        } catch (Exception $e) {
            syslog(LOG_WARNING, 'Error: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * 初始化事件监听器
     *
     * @return bool
     */
    public function initialize()
    {
        $handleList = cfg('event.handle_list', null, false);
        self::$eventHandles = array();
        $this->listeners = array();
        foreach($handleList as $handle => $className) {
            if (class_exists($handle)) {
                self::$eventHandles[$className] = new $handle();
            }
        }
        if (self::$eventHandles) {
            $serverConfs = $this->Global_serverConfs;
            foreach($serverConfs as $serverId => $serverConf) {
                if (\Halo::init($serverId) === false) {
                    syslog(LOG_ERR, "Halo::init($serverId) failure!");
                    continue;
                };
                // 初始化每个服中的事件
                foreach(self::$eventHandles as $entity) {
                    $listeners = $entity->initListeners(); // 加载事件监听器
                    if ($listeners) {
                        $this->listeners[$serverId] = $listeners;
                    }
                }
            }
        } else {
            return false;
        }
        return true;
    }

    /**
     * 获取未完成的事件监听器
     *
     * @param   int     $serverId       服务器id
     * @param   int     $type           事件类型
     * @param   int     $userId         角色id
     * @param   array   $params         其他参数
     * @param   array   $userProgress   用户进度
     *
     * @return array
     */
    public function getEventListeners($serverId, $type, $userId, $params = array(), $userProgress = array())
    {
        $listeners = array();
        $eventHandles = self::$eventHandles;
        if (!empty($this->listeners[$serverId][$type])) {
            $userInfo = array( // 内容封装
                'type'      => $type,           // 类型
                'userId'    => $userId,         // 用户id
                'params'    => $params,         // 其他参数
                'progress'  => $userProgress,   // 用户进度
            );
            foreach($this->listeners[$serverId][$type] as $listener) {
                $tmpUserInfo = $userInfo; // 复制用户信息
                $handler = empty($listener['handle']) ? 'handle' : $listener['handle']; // 处理句柄
                if (!empty($eventHandles[$handler])) {
                    // 检测条件
                    if ($eventHandles[$handler]->satisfy($listener, $tmpUserInfo)) {
                        continue; // 过滤掉已经完成的事件
                    }
                } else {
                    syslog(LOG_WARNING, "Error: handle : {$handler} not supported listener type : {$userInfo['type']}");
                }
                $listeners[] = $listener; //  还未完成的事件
            }
        }
        return $listeners;
    }

}