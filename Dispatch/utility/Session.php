<?php
/**
 * session 封装
 *  
 * @author wangwei
 */
class Session 
{
	/**
     * 缓存前缀
     *
     * @var string
     */
    public $cachePrefix = 'session:';
	
	/**
	 * 缓存句柄
	 * 
	 * @var Cache
	 */
	private $handler = null;
	
	/**
	 * 构造函数
	 * 
	 * @param	ICache		$cache		缓存实例
	 * @param	array		$conf		服务器配置
	 * 
	 * @return
	 */
	public function __construct($cache, $conf)
	{	
		if (!empty($cache) && $cache->status() && false) { // 保存在cache 缓存中
			$this->handler = $cache;
			session_set_save_handler(
				array($this, 'open'),
				array($this, 'close'),
				array($this, 'read'),
				array($this, 'write'),
				array($this, 'destroy'),
				array($this, 'gc')
			);
		} elseif(!empty($conf['id'])) { // 保存在文件缓存中
			$sessionDir = '/tmp/phpsession_' . $conf['id'] .'/';
			if(!is_dir($sessionDir)) mkdir($sessionDir);
		    ini_set('session.save_path', $sessionDir);
            ini_set('session.gc_maxlifetime', 21600);
            ini_set('session.gc_probability', 1);
            ini_set('session.gc_divisor', 1);
		}
		session_start();
	}
	
	/**
	 * 打开
	 * 
	 * @param 	string 		$savePath		路径
	 * @param 	string 		$sessionName	名称
	 * 
	 * @return bool
	 */
	public function open($savePath, $sessionName)
	{
		return true;
	}
	
	/**
	 * 结束
	 * 
	 * @return bool
	 */
	public function close()
	{
		return true;
	}
	
	/**
	 * 读取
	 * 
	 * @param	string 	$sessionId	sessionId
	 * 
	 * @return string
	 */
	public function read($sessionId)
	{	
		return $this->handler->get($this->cachePrefix . $sessionId);
	}
	
	/**
	 * 写入
	 * 
	 * @param	string 		$sessionId		sessionId
	 * @param	string 		$data			内容
	 * 
	 * @return bool
	 */
	public function write($sessionId, $data)
	{
		if (!empty($data)) {
			$this->handler->set($this->cachePrefix . $sessionId, $data, 0);
		}
		return true;
	}
	
	/**
	 * 销毁
	 * 
	 * @param	string 	 $sessionId		sessionId
	 * 
	 * @return bool
	 */
	public function destroy($sessionId)
	{	
		$this->handler->delete($this->cachePrefix . $sessionId);
		return true;
	}
	
	/**
	 * 回收
	 * 
	 * @param int $maxLifeTime	时间
	 * 
	 * @return bool
	 */
	public function gc($maxLifeTime)
	{
		return true;
	}
	
}