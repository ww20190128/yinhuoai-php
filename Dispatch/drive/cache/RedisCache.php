<?php
namespace drive\cache;
use \Redis;

/**
 +----------------------------------------
 * Redis缓存
 +----------------------------------------
 * @package drive\cache\RedisCache
 * 
 * @author wangwei
 +----------------------------------------
 */
class RedisCache implements ICache
{
    /**
     * 连接实例
     *
     * @var \Redis
     */
    private $client;
    
    private static $switch;  // 缓存是否已经开启
    
    /**
     * 缓存前缀
     *
     * @var string
     */
    public $cachePrefix = null;
    
    /**
     * 运行方式
     *
     * @var string
     */
    private $runType = 'http';
    
    /**
     * 查询缓冲区
     *
     * @var array
     */
    private $buffer = array();
	
    /**
     * 尝试次数
     *
     * @var obj
     */
    private $tries;
    
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
    
    private static $connArgs;  // 连接参数

    /**
     * 动态缓存
     *
     * @var int
     */
    const CACHE_DYNAMIC = 1;

    /**
     * 静态缓存
     *
     * @var int
     */
    const CACHE_STATIC = 2;
    
	/**
     * 创建到缓存服务器的连接
     *
     * @throws \Exception|\RedisException
     *
     * @return bool
     */
    public function connect($cacheType)
    {
        if (empty(self::$connArgs)) {
        	return false;
        }
        switch ($cacheType) {
        	case self::CACHE_DYNAMIC :
                $connArg = empty(self::$connArgs['dynamic']) ? array() : self::$connArgs['dynamic'];
                break;
            case self::CACHE_STATIC :
                $connArg = empty(self::$connArgs['static']) ? array() : self::$connArgs['static'];
                break;
            default:
            	$connArg = empty(self::$connArgs['dynamic']) ? array() : self::$connArgs['dynamic'];            
        }
        if (empty($connArg)) {
        	return false;
        }
        $switch = true;
    	try {
           	$this->client = new \Redis();
            if (!isset($connArg['once'])) {
            	$cliented = $this->client->pconnect($connArg['cache_host'], $connArg['cache_port']);
			} else {
				$cliented = $this->client->connect($connArg['cache_host'], $connArg['cache_port']);
			} 
            if (empty($cliented)) {
	            $cliented = $this->failBack($connArg['cache_host'], $connArg['cache_port'], isset($connArg['once']) ? false : true);
            }
	    	if (empty($cliented)) {
	           	$switch = false;
	      	}
           	if (!empty($connArg['auth'])) {
        		$this->client->auth($connArg['auth']);
        	}
            if (!empty($connArg['serialize'])) {
            	if (substr(PHP_VERSION, 0, 1) == '7') {
            		$this->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            	} else {
            		$this->client->setOption(0, 0);
            	}   
        	}
    		if (isset($connArg['database'])) {
        		$this->client->select($connArg['database']);
        	}
     	} catch (\RedisException $exception) {
     		$switch = false;
     		$this->client = null;
     	}       
        if (empty($this->client)) {
            $switch = false;
       	}
// 关闭缓存       	
$switch = false;
       
       	
       	$this->buffer = array();
      	$this->transactionBuffer = array();
        return $switch;
    }
	
    /**
     * 构造函数 连接
     *
     * @param 	int 	$args  			连接参数
     * @param 	int 	$cacheType  	缓存类型
     * 
     * @return this
     */
    public function __construct(array $args)
    {
    	self::$connArgs = $args;
    	$switch = isset($args['switch']) && $args['switch'] == false ? false : true;
    	$this->cachePrefix = empty($args['serverId']) ? '0' :  $args['serverId'] . ':'; // 服务器Id
    	if ($switch) {
    		$switch = $this->connect(self::CACHE_DYNAMIC);
        }
        $this->reset();
		// 运行模式
       	$this->runType = in_array(substr(PHP_SAPI, 0, 3), array('cgi', 'cli')) ? 'shell' : 'http';
        self::$switch = $switch;
        return;
    }
    
	/**
     * 故障恢复
     *
     * @param   string  	$host    	host
     * @param   int     	$port    	端口号
     * @param   bool     	$pconnect   是否持久化连接
     * 
     * @return bool
     */
    public function failBack($host = null, $port = null, $pconnect = true)
    {
    	// $this->client->close();
    	$tries = $this->tries;
      	$cliented = false; // 是否连接上	
       	while (--$tries > 3) {
	      	if ($this->client->{$pconnect ? 'pconnect' : 'connect'}($host, $port)) {
	        	syslog(LOG_WARNING, "redis connection established, pid: " . posix_getpid());
	           	$cliented = true;
	        	break;
	       	}
        }
        return $cliented;
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
     * 获取
     *
     * @param   string|array    $key        键名
     * @param   bool            $success    是否获取成功
     *
     * @return mixed
     */
    public function get($key, &$success = null) 
    {  
//return false;
    	if (!$this->status()) {
    		return false;
    	} 
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
     * 获取
     *
     * @param   string|array    $key   键名
     *
     * @return mixed
     */
    private function execGet($key) 
    {
        if (is_array($key)) { // 数组
            $data = array();
            foreach ($key as $k) {
	            $v = $this->client->hGetAll($k);
	            // 储存值的类型1 数组 或者对象  2 字符串或者数字
	            if (!isset($v['T']) || !isset($v['T'])) {
	            	if (!empty($v)) {
	            		$this->client->delete($k); // 将不合法的键删除
	            	}
	            	continue;
	            } elseif (empty($v['Z'])) { // 数据没有压缩过
		            if ($v['T'] == 1) { // 数组或者对象, 根据类型处理
				      	if (empty($v['V'])) {
		            		$this->client->delete($k); // 将不合法的键删除
		            		continue;
		            	}
	            		$v = unserialize($v['V']); // 反序列化
	            	} else { // 字符串或者整数
	            		$v = $v['V'];
	            	}
	            } else { // 数据有压缩过, 先解压, 再根据类型处理
	            	if (empty($v['V'])) {
		            	$this->client->delete($k); // 将不合法的键删除
		            	continue;
		           	}
		            $v['V'] = gzuncompress($v['V']);  // 解压
	            	if (empty($v['V'])) { // 解压后的数据为空, 解压失败
	            		syslog(LOG_EMERG, 'redis gzuncompress error key:' . $k);
	            		$this->client->delete($k); // 将不合法的键删除
	            		continue;
	            	}
	            	if ($v['T'] == 1) { // 数组或者对象
	            		$v = unserialize($v['V']); // 反序列化
	            	} else {
	            		$v = $v['V'];
	            	}
	            }
	            $data[$k] = $v;
            }
        } else { // 非数组
          	$data = $this->client->hGetAll($key); // 对数据进行过zip压缩, 使用数据时 1.ungzcompress -> (如果 t == 1 则进行  2. serialize)
            // 储存值的类型 1 数组 或者对象  2 字符串或者数字
            if (!isset($data['T']) || !isset($data['V'])) {
            	if (!empty($data)) {
            		$this->client->delete($key); // 将不合法的键删除
            	}
            	$data = false;
            } elseif (empty($data['Z'])) { // 数据没有压缩过
            	if ($data['T'] == 1) { // 数组或者对象, 根据类型处理
	            	if (empty($data['V'])) {
	            		$this->client->delete($key); // 将不合法的键删除
	            		return false;
	            	}
            		$data = unserialize($data['V']); // 反序列化
            	} else { // 字符串或者整数
            		$data = $data['V'];
            	}
            } else { // 数据有压缩过, 先解压, 再根据类型处理
            	if (empty($data['V'])) {
            		$this->client->delete($key); // 将不合法的键删除
            		return false;
            	}
            	$data['V'] = gzuncompress($data['V']);  // 解压
            	if (empty($data['V'])) { // 解压后的数据为空, 解压失败
            		syslog(LOG_EMERG, 'redis gzuncompress error key:' . $key 
            			. ' data:' . var_export($data, true) . ' req:' . var_export($_REQUEST, true));
            		$this->client->delete($key); // 将不合法的键删除
            		return false;
            	}
            	if ($data['T'] == 1) { // 数组或者对象
            		$data = unserialize($data['V']); // 反序列化
            	} else {
            		$data = $data['V'];
            	}
            }
        }
        return $data;
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
    	if (!$this->status()) {
    		return false;
    	}
    	$this->assembleKey($key);
        $ok = true;
        if ($this->inTransaction) { // 事物中
            if (is_array($key)) {
                foreach ($key as $k => $val) {
               		$this->addActions('selfSet', array($k, $val));
                }
                $this->buffer = array_merge($this->buffer, $key);
            } else {
                $this->addActions('selfSet', array($key, $value));
                $this->buffer[$key] = $value;
            }
        } else {
            if (is_array($key)) { // 数组
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
    	$valueType = 2; // 储存值的类型 1 数组 或者 对象, 2 字符串或者数字
    	if (is_array($value) || is_object($value)) { // 数组或者对象
 			$value = serialize($value); // 将数组或者对象序列化	
    		$valueType = 1;
    	} elseif (!is_string($value) && !is_numeric($value)) { // 既不是字符串并且也不是数字,不进行缓存
    		return false;
    	} 
    	$zip = false; 	// 是否用zip压缩 (true 用于压缩 , false 不压缩)
    	if (strlen($value) >= 10240) { // 数据   >=10k 将数据压缩
 			$zipValue = gzcompress($value, 9);
 			if (!empty($zipValue)) { // 压缩成功
 				$zip = true;
 				$value = $zipValue;
 			}
 		}
    	$value = array(
    		'T' => $valueType, 	// 储存值的类型
    		'V' => $value,		// 存储值
    	);
    	if (!empty($zip)) {
    		$value['Z'] = 1; // 对数据进行过zip压缩, 使用数据时 1.gungzcompress -> (如果 t == 1 则进行  2. serialize)
    	}
        $tries = $this->tries;
        do {
         	$ok = $this->client->hMset($key, $value);
         	if ($ok && $ttl) {
	            $ok = $this->client->expire($key, $ttl);
	        }
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
     * 删除
     *
     * @param   string|array    $key     	键名
     * @param   bool    		$assemble  	是否重新组装key
     *
     * @return bool
     */
    public function delete($key, $assemble = true) 
    {
    	if (!$this->status()) {
    		return false;
    	}
    	$assemble and $this->assembleKey($key);
        $ok = true;
        if ($this->inTransaction) {
            if (is_array($key)) {
                foreach ($key as $k) {
                	$this->addActions('selfDelete', array($k, null));
                    $this->buffer[$k] = null;
                }
            } else {
                $this->addActions('selfDelete', array($key, null));
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
     * 删除
     *
     * @param string|array  $key   键名
     *
     * @return bool
     */
    public function execDelete($key) 
    {
        $tries = $this->tries;
        do {
            $ok = $this->client->delete($key);
        } while (!$ok && --$tries > 0);
        return $ok;
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
    	if (!$this->status()) {
    		return false;
    	}
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
            $ok = $this->execSet($key, $value, $ttl);
            if ($ok !== false) {
                $this->buffer[$key] = $value;
            }
        }
        return $ok;
    }
    
	/**
     * 获取缓存服务器的状态
     * 
     * @return boolen
     */
    public function status()
    {
        return self::$switch;
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
        $this->client->multi(Redis::PIPELINE);
    	foreach ($this->transactionBuffer as $action) {
    		$func = $action['func'];
			$args = $action['args'];
			if ($func == 'selfSet') {
				call_user_func_array(array($this, 'set'), $args);
			} elseif ($func == 'selfDelete') {
				call_user_func_array(array($this, 'delete'), $args);
			} else {
				call_user_func_array($this->client, $args);
			}		
		}
        $this->transactionBuffer = array();
		return $this->client->exec();
    }
    
	/**
     * 判断指定键名是否存在
     *
     * @param string    $key  键
     * 
     * @return bool
     */
    public function exists($key)
    {
    	$this->assembleKey($key);
        return $this->client->get($key) !== false;
    }
    
	/**
     * 刷新
     *
     * @return bool
     */
    public function flush() 
    {
        $ok = $this->client->flushDB(); // 刷新当前db
        if ($ok) {
            $this->buffer = array();
            $this->transactionBuffer = array();
        }
        return $ok;
    }
    
	/**
     * 获取匹配键名
     *
     * @param 	string    $key  键
     * 
     * @return bool
     */
    public function getKeys($key)
    {
    	$this->assembleKey($key);
        return $this->client->keys($key);
    }
    
	/**
	 * 添加事件
	 * 
	 * @param 	string 	$func	方法 
	 * @param 	mix	 	$args	参数
	 * 
	 * @return void
	 */
	public function addActions($func, $args)
	{
		$this->transactionBuffer[] = array('func' => $func, 'args' => $args);
	}
// --------以上为兼容memcached为daobase 封装 的缓存方法------注意 add,set,get,delete 方法已占用------------------------
	/**
	 * 向名称为key的zset中添加成员
	 * 
	 * @see Redis::zAdd
	 */
	public function zAdd($key, $score, $member, $encode = false)
	{
		if ($encode) {
			$member = json_encode($member);
		}
		if ($this->inTransaction) {
           	$this->addActions(__FUNCTION__, array($key, $score, $member));
        } else {
        	return $this->client->zAdd($key, $score, $member);
        }
        return true;
	}
	
	/**
	 * 返回有序集key中，指定区间内的成员
	 * 
	 * @see Redis::zRevrange
	 */
	public function zRevrange($key, $start, $stop, $withscores = false, $decode = false)
	{
		$list = $this->client->zRevrange($key, $start, $stop, $withscores);
		if (empty($list)) {
			return null;
		}
		if ($decode) {
			foreach($list as &$item) {
				$item = json_decode($item, true);
			}
		}
		return $list;
	}
	
	/**
	 * 返回有序集中，score介于min和max之间的成员
	 * 
	 * @see Redis::zRangebyscore 小到大
	 */
	public function zRangeByScore($key, $min, $max, $decode = false, $options=array())
	{
		$list = $this->client->zRangeByScore($key, $min, $max, $options);
		if (empty($list)) {
			return null;
		}
		if ($decode) {
			foreach($list as &$item) {
				$item = json_decode($item,true);
			}
		}
		return $list;
	}
	
	/**
	 * 返回有序集中，score介于min和max之间的成员
	 * 
	 * @see Redis::zRangebyscore 大到小
	 */
	public function zRevRangeByScore($key, $min, $max, $decode = false, $options = array())
	{
		$list = $this->client->zRevRangeByScore($key, $min, $max, $options);
		if (empty($list)) {
			return array();
		}
		if ($decode) {
			foreach($list as &$item) {
				$item = json_decode($item, true);
			}
		}
		return $list;
	}
	
	/**
	 * 返回有序集中,特定范围内的排序元素
	 * 
	 * @see Redis::zRange
	 */
	public function zRange($key, $min, $max, $decode = false, $options = false)
	{
		$list = $this->client->zRange($key, $min, $max, $options);
		if (empty($list)) {
			return array();
		}
		if ($decode) {
			foreach($list as &$item) {
				$item = json_decode($item, true);
			}
		}
		return $list;
	}
	
	/**
	 * 删除序集中，score介于min和max之间的成员
	 * 
	 * @see Redis::zRemRangeByScore
	 */
	public function zRemRangeByScore($key, $min, $max) 
	{
		if ($this->inTransaction) {
           	$this->addActions(__FUNCTION__,array($key, $min, $max));
        } else {
        	return $this->client->zRemRangeByScore($key, $min, $max);
        }
	}
	
	/**
	 * 有序集中删除member
	 * 
	 * @see Redis::zRem
	 */
	public function zRem($key, $fields)
	{
		if ($this->inTransaction) {
           	$this->addActions(__FUNCTION__, array($key, $fields));
        } else {
        	return $this->client->zRem($key, $fields);
        }
        return true;
	}
	
	/**
	 * 删除集合元素
	 * 
	 * @see Redis::sRem
	 */
	public function sRem($key, $value)
	{
		if ($this->inTransaction) {
           	$this->addActions(__FUNCTION__, array($key, $value));
        } else {
        	return $this->client->sRem($key, $value);
        }
        return true;
	}

	/**
     * 判断是否重复的，写入值
     *
     * @param string    $key          键
     * @param mixed     $value        值
     * 
     * @return bool
     */
    public function setUnique($key, $value, $decode = false)
    {
    	if ($decode) {
			$value = json_encode($value);
		}
    	if ($this->inTransaction) {
			$this->addActions('setnx', array($key, $value));
        } else {
        	return $this->client->setnx($key, $value);
        }
        return true;
    }
	
	/**
	 * 设置时间
	 * 
	 * @param 	string  $key   	键
     * @param 	int		$time   值
     * 
     * @return bool
	 */
	public function expire($key, $time = 60)
	{
		if ($this->inTransaction) {
			$this->addActions(__FUNCTION__, array($key, $time));
        } else {
        	return $this->client->expire($key, $time);
        }
		return true;
	}
	
	/**
	 * 向名称为key的list添加尾元素
	 *
	 * @see Redis::rPush
	 * 
	 * @return bool
	 */
	public function rPush($key, $value, $encode = false)
	{
		if ($encode) {
			$value = json_encode($value);
		}
		if ($this->inTransaction) {
			$this->addActions(__FUNCTION__, array($key, $value));
        } else {
        	return $this->client->rPush($key, $value);
        }
		return true;
	}
	
	/**
	 * 获取队列数据
	 *
	 * @see Redis::blPop
	 * 
	 * @return bool
	 */
	public function blPop($key, $timeout)
	{
		return $this->client->blPop($key, $timeout);
	}
	
	/**
     * 调用phpredis扩展的对应方法
     *
     * @param 	string 	$method 	方法名
     * @param 	array  	$args   	参数列表
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        //var_dump($method); // TODO 统计未封装方法的调用频率，选择性封装
        return call_user_func_array(array($this->client, $method), $args);
    }
    
    /**
     * 设置
     *
     * @return bool
     */
    public function hMset($key, $value)
    {
    	if ($this->inTransaction) {
    		$this->addActions(__FUNCTION__, array($key, $value));
    	} else {
    		$tries = $this->tries;
    		do {
    			$ok = $this->client->hMset($key, $value);
    		} while (!$ok && --$tries > 0);
    		return $ok;
    	}
    	return true;
    }
    
    /**
     * 设置
     *
     * @return bool
     */
    public function hSet($key, $field, $value)
    {
    	if ($this->inTransaction) {
    		$this->addActions(__FUNCTION__, array($key, $field, $value));
    	} else {
    		$tries = $this->tries;
    		do {
    			$ok = $this->client->hSet($key, $field, $value);
    		} while (!$ok && --$tries > 0);
    		return $ok;
    	}
    	return true;
    }
    
    /**
     * 获取
     *
     * @return array
     */
    public function hGet($key, $field)
    {
    	if ($this->inTransaction) {
    		$this->addActions(__FUNCTION__, array($key, $field));
    	} else {
    		$tries = $this->tries;
    		do {
    			$ok = $this->client->hGet($key, $field);
    		} while (!$ok && --$tries > 0);
    		return $ok;
    	}
    	return true;
    }
    
    /**
     * 获取
     *
     * @return bool
     */
    public function hGetAll($key)
    {
    	if ($this->inTransaction) {
    		$this->addActions(__FUNCTION__, array($key));
    	} else {
    		$tries = $this->tries;
    		do {
    			$ok = $this->client->hGetAll($key);
    		} while (!$ok && --$tries > 0);
    		return $ok;
    	}
    	return true;
    }
    
    /**
     * 获取
     *
     * @return bool
     */
    public function hmGet($key, $field)
    {
    	if ($this->inTransaction) {
    		$this->addActions(__FUNCTION__, array($key, $field));
    	} else {
    		$tries = $this->tries;
    		do {
    			$ok = $this->client->hmGet($key, $field);
    		} while (!$ok && --$tries > 0);
    		return $ok;
    	}
    	return true;
    }
    
    /**
     * 删除
     *
     * @return bool
     */
    public function hDel($key, $field)
    {
    	if ($this->inTransaction) {
    		$this->addActions(__FUNCTION__, array($key, $field));
    	} else {
    		$tries = $this->tries;
    		do {
    			$ok = $this->client->hDel($key, $field);
    		} while (!$ok && --$tries > 0);
    		return $ok;
    	}
    	return true;
    }
}
