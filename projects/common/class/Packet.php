<?php
namespace service;

/**
 * 消息包体封装类 Packet
 *
 * @author wangwei
 * 
 * @package service
 */
class Packet
{
    /**
     * 包体类型
     *
     * @var int
     */
    public $type;

    /**
     * 服务器id
     *
     * @var int
     */
    public $serverId;

    /**
     * 编号
     *
     * @var int
     */
    public $number = 0;

    /**
     * 接受客户端
     *
     * @var mix
     */
    public $clientId;
    
    /**
     * 参数
     *
     * @var array
     */
    public $data = array();

    /**
     * 回调函数
     *
     * @var string
     */
    public $callBack = null;

    /**
     * 包体头大小
     *
     * @var int
     */
    const PACKET_HEADER_SIZE = 12;

    /**
     * 包体头格式
     * 
     * @var int
     */
    const PACKET_HEADER_FMT = 'A4NNA*';

    /**
     * 包体头打包前格式
     *
     * @var int
     */
    const PACKET_HEADER_FMT_UNPACK = 'Apack/Nlen/Nzip/Adata';

    /**
     * 返回状态 0 表示成功
     * 
     * @var int
     */
    public $status;

    /**
     * 打包后的二进制数据
     *
     * @var int
     */
    private $packData;


    /**
     * 构造函数
     *
     * @param   int     $type   类型
     *
     * @return \service\Packet
     */
    public function __construct($type = 0)
    {
        $this->type = $type;
    }

    /**
     * 解析返回的数据
     *
     * @param   array   $buffer     缓冲区
     *
     * @return bool
     */
    public function parseFromString(&$buffer)
    {	
    	if (!is_string($buffer) || empty($buffer)) {
    		return false;
    	}
        $buffer = json_decode($buffer, true);
		if (is_object($buffer)) {
			return false;
		}
		if (!empty($buffer['op'])) {
			$this->type = $buffer['op'];
		}
    	if (isset($buffer['status'])) {
			$this->status = $buffer['status'];
		}
    	if (isset($buffer['data'])) {
			$this->data = $buffer['data'];
		}
    	if (isset($buffer['callBack'])) {
			$this->callBack = $buffer['callBack'];
		}
    	if (isset($buffer['serverId'])) {
			$this->serverId = $buffer['serverId'];
		}
        return true;
    }

    /**
     * 包的大小
     * 
     * @return int
     */
    public function size()
    {
        return strlen((string)$this);
    }

    /**
     * 转换成二进制
     *
     * @return string
     */
    public function __toString()
    {
		$this->data = array(
			'op' 		=> $this->type,
			'clientId'	=> $this->clientId,
			'data'		=> $this->data,
		);
    	$pack = gzcompress(json_encode($this->data), 6);
		$packLen = strlen($pack);
		$buffer = pack('A4NNA*', 'pack', 1, $packLen, $pack);
		$this->packData = $buffer;
        return $buffer;
    }

}