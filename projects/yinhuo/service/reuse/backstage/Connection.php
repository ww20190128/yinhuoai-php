<?php
namespace service;

/**
 * socket连接模板类
 *
 * @author wangwei
 */
class Connection
{
    /**
     * 服务器id
     *
     * @var int|null
     */
    public $serverId = null;

    /**
     * socket句柄
     *
     * @var resource
     */
    public $socket = null;

    /**
     * host
     *
     * @var string
     */
    public $host = null;

    /**
     * 端口号
     *
     * @var int
     */
    public $port = null;

    /**
     * 数据接收缓冲区
     *
     * @var string
     */
    public $receiveBuffer = null;

    /**
     * 数据发送缓冲区
     *
     * @var string
     */
    public $sendBuffer = null;

    /**
     * 过期时间
     *
     * @var int
     */
    public $ttl = 86400;

    /**
     * 等待资源包
     *
     * @var array
     */
    public $pending = array();

    /**
     * 最后连接时间
     *
     * @var null
     */
    public $lastConnectTime = null;

    /**
     * 构造函数
     *
     * @param   int         $serverId   服务器id
     * @param   resource    $socket     socket句柄
     *
     * @return \service\Connection
     */
    public function __construct($serverId, $socket)
    {
        $this->serverId = $serverId;
        $this->socket   = $socket;
    }

}