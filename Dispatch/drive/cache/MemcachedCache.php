<?php
namespace drive\cache;
use \Memcached;

/**
 * Memcache缓存
 * 
 * @author wangwei
 * @package drive\cache\MemcachedCache
 */
class MemcachedCache implements ICache
{    
    /**
     * 缓存前缀
     *
     * @var string
     */
    public $cachePrefix = null;

    /**
     * 连接实例
     *
     * @var obj
     */
    private $client;

    /**
     * 尝试次数
     *
     * @var obj
     */
    private $tries;

    /**
     * 查询缓冲区
     *
     * @var array
     */
    private $buffer = array();

    /**
     * 事物缓冲区
     *
     * @var array
     */
    private $transactionBuffer = array();

    /**
     * 是否事物进行中
     *
     * @var bool
     */
    private $inTransaction = false;
	
    /**
     * 运行方式
     *
     * @var string
     */
    private $runType = 'http';
    
    /**
     * 连接参数
     * 
     * @var array
     */
    private $args;

    /**
     * 构造方法,连接缓存服务器
     *
     * @param   array 	$args   连接参数
     *
     * @return \drive\cache\MemcachedCache
     */
    public function __construct(array $args)
    { 	
        $this->client = new Memcached();
        $this->cachePrefix = $args['serverId'] . ':'; // 服务器Id
        unset($args['serverId']);
        foreach ($args as $arg) {        	
            list($host, $port) = array_values($arg);
            $cliented = $this->client->addserver($host, $port);
            if (empty($cliented)) {
            	$this->failBack($host, $port);
            }
        }
        $this->reset();
		// 运行模式
       	$this->runType = in_array(substr(PHP_SAPI, 0, 3), array('cgi', 'cli')) ? 'shell' : 'http';
        return ;
    }
	
	/**
     * 组装key
     *
     * @param string|array  $key  键名
     *
     * $return void
     */
    private function assembleKey(&$key)
    {
        if (is_array($key)) {
            foreach($key as &$k) {
                $k = $this->cachePrefix . $k;
            }
        } else {
            $key = $this->cachePrefix . $key;
        }
        return;
    }
    
    /**
     * 故障恢复
     *
     * @param   string  $host    host
     * @param   int     $port    端口号
     * 
     * @return void
     * 
     */
    public function failBack($host = null, $port = null)
    {
    	$pid = posix_getpid();
        if ($errno > 0) {
            $this->client->close();
            $tries = $this->tries;
            while (--$tries > 3) {
                if ($this->client->addserver($host, $port, 10)) {
                    syslog(LOG_WARNING, "memcached connection established, pid: $pid");
                    break;
                }
            }
        }
        return;
    }

    /**
     * 重置
     *
     * $return void
     */
    private function reset()
    {
        if ($this->inTransaction) {
            $this->rollBack();
            return;
        }
        $this->inTransaction = false;
        $this->buffer = array();
        $this->transactionBuffer = array();
        return;
    }

    /**
     * 设置缓存
     *
     * @param string|array      $key       键名
     * @param mixed             $value     值
     * @param int               $ttl       过期时间
     *
     * @return bool
     */
    public function set($key, $value = null, $ttl = null)
    {
    	$this->assembleKey($key);
        $ok = true;
        if ($this->inTransaction) { // 事物中
            if (is_array($key)) {
                $this->transactionBuffer = array_merge($this->transactionBuffer, $key);
                $this->buffer = array_merge($this->buffer, $key);
            } else {
                $this->transactionBuffer[$key] = $value;
                $this->buffer[$key] = $value;
            }
        } else {
            if (is_array($key)) {
                foreach ($key as $k => $val) {
                    if (is_null($val) || $val === false) { // 值为 null 或者 false 将缓存删除
                        $ok = $this->execDelete($k);
                        if ($ok) {
                            $this->buffer[$k] = null;
                        }
                    } else {
                        $ok = $this->execSet($k, $val, $ttl);
                        if ($ok !== false) {
                            $this->buffer[$k] = $val;
                        }
                    }
                }
            } else {
                if (is_null($value) || $value === false) {
                    $ok = $this->execDelete($key);
                    if ($ok) {
                        $this->buffer[$key] = null;
                    }
                } else {
                    $ok = $this->execSet($key, $value, $ttl);
                    if ($ok !== false) {
                        $this->buffer[$key] = $value;
                    }
                }
            }
        }
        return $ok;
    }

    /**
     * 获取
     *
     * @param   string|array    $key        键名
     * @param   bool            $success    是否获取成功
     *
     * @return mixed
     */
    public function get($key, &$success = null) 
    {
        $this->assembleKey($key);
    	if ($this->runType != 'http') { // 非http请求,清空框架缓存区
    		$this->buffer = array();
    	}
    	if (is_array($key)) {
            $data = array();
            foreach ($key as $index => $k) {
                if (isset($this->buffer[$k])) {
                    $data[$k] = $this->buffer[$k];
                    unset($key[$index]);
                }
            }
            if (!empty($key)) {
                $value = $this->execGet($key);
                if ($value !== false) {
                    $this->buffer = array_merge($this->buffer, $value);
                    $data = array_merge($data, $value);
                }
            }
        } else {
            if (isset($this->buffer[$key])) {  	
                $data = $this->buffer[$key];
            } else {
                $data = $this->execGet($key);
                $success = $data !== false;
                $this->buffer[$key] = $data;
            }
        }
        return $data;
    }

    /**
     * 添加
     *
     * @param   string|array    $key     键名
     * @param   mixed           $value   值
     * @param   int             $ttl     过期时间
     *
     * @return bool
     */
    public function add($key, $value = null, $ttl = null) 
    {
        $this->assembleKey($key);
    	if (is_array($key)) {
            $ok = true;
            foreach ($key as $k => $val) {
                if (!isset($this->buffer[$k])) {
                    $this->buffer[$k] = $val;
                    if (!$this->execAdd($k, $val, $ttl)) {
                        $ok = false;
                    }
                }
            }
        } else {
            $ok = $this->execAdd($key, $value, $ttl);
            if ($ok !== false) {
                $this->buffer[$key] = $value;
            }
        }
        return $ok;
    }

    /**
     * 删除
     *
     * @param   string|array    $key     	键名
     * @param   bool    		$assemble  	是否重新组装key
     *
     * @return bool
     */
    public function delete($key, $assemble = true) 
    {
    	$assemble and $this->assembleKey($key);
        $ok = true;
        if ($this->inTransaction) {
            if (is_array($key)) {
                foreach ($key as $k) {
                    $this->transactionBuffer[$k] = null;
                    $this->buffer[$k] = null;
                }
            } else {
                $this->transactionBuffer[$key] = null;
                $this->buffer[$key] = null;
            }
        } else {
            if (is_array($key)) {
                foreach ($key as $k) {
                    if ($this->execDelete($k)) {
                        $this->buffer[$k] = null;
                    }
                }
            } else {
                $ok = $this->execDelete($key);
                if ($ok) {
                    $this->buffer[$key] = false;
                }
            }
        }
        return $ok;
    }

    /**
     * 刷新
     *
     * @return bool
     */
    public function flush() 
    {
        $ok = $this->client->flush();
        if ($ok) {
            $this->buffer = array();
            $this->transactionBuffer = array();
        }
        return $ok;
    }

    /**
     * 检查是否存在
     *
     * @param   string|array    $key     键名
     *
     * @return bool
     */
    public function exists($key) 
    {
    	$this->assembleKey($key);
        return $this->client->get($key) !== false;
    }

    /**
     * 获取缓存服务器状态
     *
     * @return array
     */
    public function status() 
    {
        return $this->client->getstats();
    }

    /**
     * 设置
     *
     * @param string|array      $key       键名
     * @param mixed             $value     值
     * @param int               $ttl       过期时间
     *
     * @return bool
     */
    private function execAdd($key, $value = null, $ttl = null)
    {
        return $this->client->add($key, $value, $ttl);
    }

    /**
     * 删除
     *
     * @param string|array  $key   键名
     *
     * @return bool
     */
    private function execDelete($key) 
    {
        $tries = $this->tries;
        do {
            $ok = $this->client->delete($key);
        } while (!$ok && --$tries > 0);
        return $ok;
    }

    /**
     * 储存
     *
     * @param   string  $key        键名
     * @param   mixed   $value      值
     * @param   int     $ttl        过期时间
     *
     * @return bool
     */
    private function execSet($key, $value = null, $ttl = null) 
    {
        $tries = $this->tries;
        do {
            $ok = $this->client->set($key, $value, $ttl);
        } while ($ok === false && --$tries > 0);
    	if ($ok === false) {
            $error = sprintf('cache set(%s, %s, %s, %s) failed, pid: #%d.', is_string($key) ? $key : json_encode($key),
                is_string($value) ? $value : json_encode($value), 0, $ttl, posix_getpid());
            // syslog(LOG_ERR, $error);
     		if (@class_exists('\service\Daemon')) {
     			//#20150129 临时修改成立刻执行
     			//\service\Daemon::send($key);
     		}
        }
        return $ok;
    }

    /**
     * 获取
     *
     * @param   string|array    $key   键名
     *
     * @return mixed
     */
    public function execGet($key) 
    {
        if (is_array($key)) {
            $transform = $data = array();
            foreach ($key as $k) {
                $transform[$k] = $k;
            }
            $values = $this->client->get(array_keys($transform));
            foreach ($values as $k => $v) {
                $data[$transform[$k]] = $v;
            }
        } else {
            $data = $this->client->get($key);
        }
        return $data;
    }

    /**
     * 事物开始
     *
     * @return void
     */
    public function begin() 
    {
        $this->transactionBuffer = array();
        $this->inTransaction = true;
    }

    /**
     * 事物回滚
     *
     * @return void
     */
    public function rollBack()
    {
        $this->buffer = array();
        $this->transactionBuffer = array();
        $this->inTransaction = false;
    }

    /**
     * 事物提交
     *
     * @return void
     */
    public function commit() 
    {
        $this->inTransaction = false;
        foreach ($this->transactionBuffer as $key => $value) {
            if (empty($value)) {
                $this->execDelete($key);
            } else {
                $this->execSet($key, $value);
            }
        }
        $this->transactionBuffer = array();
    }
    
}