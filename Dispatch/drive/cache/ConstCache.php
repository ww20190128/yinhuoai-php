<?php
namespace drive\cache;
use \ConstCache;

/**
 * ConstCache缓存
 * 
 * @author wangwei
 *
 * @package drive\ConstCache
 */
class ConstCache
{
	/**
     * 连接实例
     *
     * @var \ConstCache
     */
    private $client;
    
	/** 
	 * 构造函数
	 * 
	 */
	public function __construct()
	{
		$this->client = new \ConstCache();
	}

    /**
     * 取得连接实例
     * 
	 * @return \ConstCache
	 */
	function getClient() 
	{
		return $this->client;
	}
    
    /**
	 * 添加指定键名的数据
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return bool
     */
    public function add($key, $value)
    {
    	return $this->client->add($key, $value);
    }
    
    /**
     * 获取指定键名的数据
	 * 
	 * @param string $key
	 * @return mixed
     */
    public function get($key)
    {
    	return $this->client->get($key);
    }
    
    /**
     * 清空缓存
     * 
     * @return bool
     */
    public function flush()
    {
    	return $this->client->flush();
    }
    
	/**
	 * 获取服务器统计信息
	 * 
	 * @return array
	 */
    public function stat()
    {        
        return $this->client->stat();
    }
    
}