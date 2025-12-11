<?php
namespace service;

/**
 * 消息进程
 * 
 * 对外接口: send
 * <code>
 *Messager::send(Messager::TYPE_USER_MENTANNOUNCEMENT, $userId, array(
 *  'ip'            => Client::getIP(),
 *  'requestMethod' => Client::requestMethod(),
 *  'browser'       => Client::getBrowser(),
 *  'os'            => Client::getOS(),
 *));
 * </code>
 * 用于广播,公告,消息的推送
 * 
 * @author wangwei
 * @package service
 */
class Messager extends Service
{
	
    /**
     * 消息包计数器
     *
     * @var int
     */
    private $counter = 0;

    /**
     * 未连接的socket
     *
     * @var array
     */
    private $disconnections;

    /**
     * 所有的socket
     *
     * @var array
     */
    private $sockets;

    /**
     * 已经连接上的socket
     *
     * @var array
     */
    private $connections;

    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return Messager
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Messager();
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
     * 发送消息包
     *
     * @param   int         $type      	消息类型
     * @param   int         $sender    	发送者id
     * @param   array       $data    	推送的数据
     * @param   int|array  	$clientId   接受客户端
     * @param   string      $callBack  	回调函数
     *
     * @return bool
     */
    public static function send($type, $sender = 0, $data = array(), $clientId = null, $callBack = null)
    {
        $self = Messager::singleton();
    	$server = $self->server;
    	$clientId = is_null($clientId) ? $sender : $clientId; // 如果不写接受者 那接受者为发送者即玩家之间
    	if (empty($server['switch'])) { // Messager 进程未开启，发送到redis里
    		$ok = \service\epoll\QueueWrite::push($type, $data, $clientId);
    	} else { // Messager 进程已开启，发送到消息队列里
    		$packet = new Packet($type);
	        if ($sender > 0) {
	        	$data['userId']	= $sender; // 发送者角色id
	        }
	        $packet->clientId 	= $clientId;
	        $packet->number     = 0;            // 消息编号
	        $packet->data     	= $data;      	// 数据包
	        $packet->callBack   = $callBack;    // 回调函数
	        $serverId = $self->frame->id;       // 服务器id
	        $packet->serverId = $serverId;
	        $reporting = error_reporting(0);
	        $ok = msg_send($self->queue, ($self->server['desiredmsgtype'] + ($serverId % $self->server['queue_num']) * 2), 
        			$packet, true, false, $errno);
       		error_reporting($reporting);
	    }
        return $ok;
    }

    /**
     * 连接到socket服务器
     *
     * @param   string  $host   网关
     * @param   int     $port   端口
     *
     * @return bool|resource
     */
    private function connect($host, $port)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket === false) {
            syslog(LOG_EMERG, 'socket_create() failed.');
            return false;
        }
        $ok = socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        if ($ok === false) {
            syslog(LOG_EMERG, 'socket_set_option() failed.');
            return false;
        }
        $ok = socket_set_option($socket, 6, TCP_NODELAY, 1);
        if ($ok === false) {
            syslog(LOG_EMERG, 'socket_set_option() failed.');
            return false;
        }
        $ok = socket_connect($socket, $host, $port);
        if ($ok === false) {
            syslog(LOG_ERR, "socket_connect($host, $port) failed.");
            return false;
        }
        $ok = socket_set_nonblock($socket);
        if ($ok === false) {
            syslog(LOG_EMERG, 'socket_set_nonblock() failed.');
            return false;
        }
        return $socket;
    }

    /**
     * 初始化
     *
     * @return bool
     */
    protected function initialize()
    { 	
        parent::initialize();
        $connections = $disconnections = array();
        $sockets = array();
        $queueNum = $this->server['queue_num'];       
        if (!empty($this->serverConfs)) foreach ($this->serverConfs as $serverId => $conf) {
            if (($serverId % $queueNum) != $this->num || empty($conf['communicate']['socket'])) {
                unset($this->serverConfs[$serverId]);
                continue;
            }  
            $host = $conf['communicate']['socket']['host'];
            $port = $conf['communicate']['socket']['app_port'];
           	$socket = $this->connect($host, $port);        
            $sockets[$serverId] = $socket;
            $this->log("Connecting to %s:%d... %s.", $host, $port, ($socket ? 'established' : 'failed'));
            $connection = new Connection($serverId, $socket);
            $connection->host = $host;
            $connection->port = $port;
            $connection->lastConnectTime = $this->frame->now;
            if ($socket === false) {
                $disconnections[] = $connection;
            } else {
                $connections[(int)$socket] = $connection;
            }
        }
        $this->connections = $connections;
        $this->sockets = $sockets;
        $this->disconnections = $disconnections;
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
        $desiredmsgtype = ($this->server['desiredmsgtype'] + $this->num * 2);
        $timeout = $this->server['timeout'] * 1000000; 
        do {
            $this->frame->now = time();
            if ($this->frame->now >= $this->tomorrow) {
                $this->scroll($this->frame->now);
            }
            // 断开5秒后重连
            if (!empty($this->disconnections)) {
                foreach ($this->disconnections as $index => $connection) {
                    if ($connection->lastConnectTime + 5 > $this->frame->now) {
                        continue;
                    }
                    $connection->lastConnectTime = $this->frame->now;
                    $socket = $this->connect($connection->host, $connection->port);
                    if ($socket === false) {
                        continue;
                    }
                    unset($this->disconnections[$index]);
                    $this->sockets[$connection->serverId] = $connection->socket = $socket;
                    $this->connections[(int)$socket] = $connection;
                    $this->log("Connecting to %s:%d... %s.", $connection->host, $connection->port, ($socket ? 'established' : 'failed'));
                }
            }
            // 读取 socks, 写入 socks, 等待 socks
            $readSockets = $writeSockets = $exceptSockets = array();
            foreach ($this->connections as $connection) {
                $readSockets[] = $exceptSockets[] = $connection->socket;
                if ($connection->sendBuffer) {
                    $writeSockets[] = $connection->socket;
                }
            }
            if (empty($readSockets)) {
                usleep($timeout);
                continue;
            }
            // 处理sockets
            if (socket_select($readSockets, $writeSockets, $exceptSockets, 0, $timeout) > 0) {
                // $readSockets 读取 socks 用于从socket中读取外部发来的包,将包发送到后台线程
                /*foreach ($readSockets as $socket) {
                    $socketId = (int)$socket;
                    $connection = $this->connections[$socketId];
                    if (socket_recv($socket, $buffer, 8192, 0) <= 0) { // 获取不到数据
                        socket_close($socket);
                        $connection->pending = array();
                        $this->log("Connection to %s:%d closed by remote host.", $connection->host, $connection->port);
                        $this->sockets[$connection->serverId] = $connection->socket = false;
                        $this->disconnections[] = $connection;
                        unset($this->connections[$socketId]);
                    } else { // 收集处理获得的数据
                        $connection->receiveBuffer .= $buffer;
                        $packet = new Packet();
                        while ($packet->parseFromString($connection->receiveBuffer)) {
                            if (!isset($connection->pending[$packet->number])) {
                                syslog(LOG_ERR, 'messager: protocol error/' . bin2hex($packet));
                                continue;
                            }
                            $pendPack = $connection->pending[$packet->number];
                            unset($connection->pending[$packet->number]);
                            if ($packet->status != 0) {
                                syslog(LOG_ERR, 'messager: invalid response: ' . bin2hex((string)$packet) . ' on request: ' . bin2hex((string)$packet));
                                continue;
                            }
                            if ($pendPack->callBack) {
                                Thread::send($pendPack->callBack, $pendPack->serverId, $packet);
                            }
                        }
                    }
                }*/
                // 将socket的发送缓冲区的内容发送到socket中, 该包的内容来自于消息队列
                foreach ($writeSockets as $socket) {
                    $connection = $this->connections[(int)$socket];
                    $num = socket_send($socket, $connection->sendBuffer, strlen($connection->sendBuffer), 0);
                    $connection->sendBuffer = substr($connection->sendBuffer, $num);
                }
                // 所有的sock重新连接
                foreach ($exceptSockets as $socket) {
                    $socketId = (int)$socket;
                    $connection = $this->connections[$socketId];
                    socket_close($socket);
                    $socket = $this->connect($connection->host, $connection->port);
                    $connection->socket = $socket;
                    if ($socket !== false) {
                        $this->connections[(int)$socket] = $connection;
                        unset($this->connections[$socketId]);
                    }
                    $this->log("Connecting to %s:%d... %s.", $connection->host, $connection->port, ($socket ? 'established' : 'failed'));
                }
            }
            // 从消息队列中获取包, 将包放到socket发送缓冲区及待处理缓冲区中
            while (msg_receive($this->queue, $desiredmsgtype, $msgtype, 65536, $packet, true, MSG_IPC_NOWAIT, $errno)) {
                if (!isset($this->sockets[$packet->serverId])) {
                    syslog(LOG_ERR, 'messager: #' . ($packet->serverId) . ' not register.');
                    continue;
                }
                $packet->number = ++$this->counter;
                if ($this->sockets[$packet->serverId] === false) { // sock连接不存在,建立连接
                    $serverConf = $this->serverConfs[$packet->serverId];
                    $host = $serverConf['communicate']['socket']['host'];
                    $port = $serverConf['communicate']['socket']['app_port'];
                    $socket = $this->connect($host, $port);
                    if ($socket === false) {
                        syslog(LOG_ERR, 'messager: #' . ($packet->serverId) 
                        	. ' ' . bin2hex((string)$packet) . ' discard.');
                        continue;
                    }
                    $connection = new Connection($packet->serverId, $socket);
                    $connection->sendBuffer .= (string)$packet; // 打包成二进制
                    $connection->pending[$packet->number] = $packet;
                    $this->connections[(int)$socket] = $connection;
                    $this->sockets[$packet->serverId] = $socket;
                } else {
                    $socket = $this->sockets[$packet->serverId];
                    $connection = $this->connections[(int)$socket];
                    $connection->sendBuffer .= (string)$packet; // 打包成二进制
                    $connection->pending[$packet->number] = $packet;
                }
            }
        } while (!$this->signal());
        return true;
    }

}