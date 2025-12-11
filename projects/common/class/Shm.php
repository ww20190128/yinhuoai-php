<?php
namespace service;

/**
 * shm 共享内存类(shared memory) 的封装
 * 
 * @author
 */
class Shm
{
	/**
     * 共享的内存块id
     *
     * @var int
     * 
     * @access protected
     */
    protected $memoryId;
	
	/**
     * 共享内存访问 ID
     *
     * @var int
     * 
     * @access protected
     */
    protected $shmId;
    
    /**
     * 权限
     *
     * @var int
     * 
     * @access protected
     */
    protected $permissions = 0644;

    /**
     * 创建共享内存
     *
     * @param $size         int         内存大小 kb
     * @param $mode         string      内存模式    'a':只读 , 'w':可读写, 'c':如果已存在,尝试打开它进行读写, 'n':已存在则会失败
     * @param $permissions  int         权限  (八进制格式)
     * @param $memoryId     int         内存id
     *
     * @return \service\Shm
     */
    public function __construct($size = 1024, $mode = 'c', $permissions = 0755, $memoryId = null)
    {    	
    	$this->memoryId = is_null($memoryId) ? mt_rand(1, 65535) : intval($memoryId);
    	$this->permissions = $permissions;
    	if ($this->exists($this->memoryId)) {	
    	    $this->shmId = shmop_open($this->memoryId, $mode, $this->permissions, $size);
    	}
        return;
    }
    
    /**
     * 关闭内存
     * 
     * @access public
     */
    public function __destruct()
    {
        shmop_close($this->shmId);
        return;
    }
    
    /**
     * 检查共享内存是否存在
     * 
     * @param $memoryId   int     内存id 
     * 
     * @return bool
     */
    private function exists($memoryId)
    {
        return @shmop_open($memoryId, "a", 0, 0);
    }
    
    /**
     * 写入内存
     * 
     * @param   $data    数据 
     * 
     * @return bool
     */
    public function write($data) {
        if ($this->exists($this->memoryId)) {
            shmop_delete($this->shmId);
            shmop_close($this->shmId);  
        }
        $this->shmId = shmop_open($this->memoryId, "c", $this->permissions, mb_strlen($data, 'UTF-8'));
        return shmop_write($this->shmId, $data, 0);
    }
    
    /**
     * 设置内存
     * 
     * @param   $key    int     键 
     * @param   $data   mixed   数据 
     * 
     * @return bool
     */
    public function set($key, $data) {
        return shm_put_var($this->memoryId, $key, $data);
    }
    
    /**
     * 读取内存
     * 
     * @param   $key    int     键 
     * 
     * @return mixed
     */
    public function get($key) {
        return shm_get_var($this->memoryId);
    }
    
    /**
     * 读取内存
     * 
     * @return bool
     */
    public function read() {
        return shmop_read($this->shmId, 0, shmop_size($this->shmId));
    }
    
    /**
     * 删除内存
     * 
     * @access public
     */
    public function delete()
    {
        return shmop_delete($this->shmId);
    }
}