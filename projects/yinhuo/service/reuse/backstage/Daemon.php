<?php
namespace service;

/**
 * 守护进程
 * 对外接口: send
 * <code>
 * Daemon::send($keys);
 * </code>
 * 用于对系统异常的处理, 例如 缓存key的删除
 *
 * @author wangwei
 */
class Daemon extends Service
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return Daemon
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Daemon();
        }
        return self::$instance;
    }

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct(__CLASS__);
        $cache = Framework::$Cache;
        $this->cache = array_shift($cache);
    }

    /**
     * 失败回调
     *
     * @param   array   $keys   key数组
     *
     * @return bool
     */
    public static function send($keys)
    {
        $self = Online::singleton();
        return msg_send($self->queue, $self->server['desiredmsgtype'], $keys, true, false, $errno);
    }

    /**
     * 启动服务
     *
     * @return bool
     */
    public function start()
    {
        $this->initialize();
        $desiredmsgtype = $this->server['desiredmsgtype'];
        do {
            try {
                $ok = msg_receive($this->queue, $desiredmsgtype, $msgtype, 65536, $keys, true, 0, $errno);
                if ($ok === false) {
                    $error = posix_strerror($errno);
                    syslog(LOG_ERR, "msg_receive() failed: #$errno $error");
                    continue;
                }
                $this->frame->now = time();
                if ($this->frame->now >= $this->tomorrow) {
                    $this->scroll($this->frame->now);
                }
                if (!is_array($keys)) {
                    $keys = array($keys);
                }
                foreach ($keys as $key) {
                    $tries = 0;
                    do {
                        $ok = $this->cache->delete($key, false);
                    } while (++$tries < 16 && !$ok);
                    if ($ok) {
                        $this->log("deleted cache key: $key in $tries times tries.");
                    } else {
                        syslog(LOG_EMERG, "Cannot delete cache key: $key in $tries times tries.");
                    }
                }
            } catch (Exception $e) {
                $this->log('ERROR: %s', $e->getMessage());
            }
        } while (!$this->signal());
        return true;
    }

}