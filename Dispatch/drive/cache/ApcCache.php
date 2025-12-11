<?php
namespace drive\cache;

/**
 * apc缓存
 * 
 * @author wangwei
 *
 * @package drive\ApcCache
 */
class ApcCache implements ICache
{
	/**
     * 缓存前缀头
     *
     * @var string
     */
    private static $cacheHead = null;
    
    /**
     * 缓存前缀
     *
     * @var string
     */
    public $cachePrefix = null;

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
     * 构造方法
     */
    public function __construct(array $args)
    {
    	self::$cacheHead = $args['serverId'] . ':'; // 服务器Id
        unset($args['serverId']);
    }
    
	/**
     * 组装key
     *
     * @param   string|array    $key  键名
     *
     * $return void
     */
    private function assembleKey(&$key)
    {
    	if (is_null(self::$cacheHead)) {
    		return;
    	}
        if (is_array($key)) {
            foreach($key as &$k) {
                $k = $this->cachePrefix ? self::$cacheHead . $this->cachePrefix . '|' . $k 
                	: self::$cacheHead . $k;
            }
        } else {
            $key = $this->cachePrefix ? self::$cacheHead . $this->cachePrefix . '|' . $key 
            	: self::$cacheHead . $key;
        }
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
    public function set($key, $value = null, $ttl = 0)
    {
        $this->assembleKey($key);
        $success = true;
        if (is_array($key) && empty($key)) {
            return $success;
        }
        if ($this->inTransaction) {
            if (is_array($key)) {
                $this->transactionBuffer = array_merge($this->transactionBuffer, $key);
                $this->buffer = array_merge($this->buffer, $key);
            } else {
                $this->transactionBuffer[$key] = $value;
                $this->buffer[$key] = $value;
            }
        } else {
            $success = apc_store($key, $value, $ttl);
            if ($success) {
                if (is_array($key)) {
                    $this->buffer = array_merge($this->buffer, $key);
                } else {
                    $this->buffer[$key] = $value;
                }
            }

        }
        return $success;
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
    public function add($key, $value = null, $ttl = 0)
    {
        $this->assembleKey($key);
        $success = true;
        if ($this->inTransaction) {
            if (is_array($key)) {
                foreach ($key as $k => $val) {
                    if (!isset($this->buffer[$k])) {
                        $this->buffer[$k] = $val;
                    }
                    if (!isset($this->transactionBuffer[$k])) {
                        $this->transactionBuffer[$key] = $val;
                    } else {
                        $success = false;
                    }
                }
            } else {
                if (!isset($this->buffer[$key])) {
                    $this->buffer[$key] = $value;
                }
                if (!isset($this->transactionBuffer[$key])) {
                    $this->transactionBuffer[$key] = $value;
                } else {
                    $success = false;
                }
            }
        } else {
            $success = apc_add($key, $value, $ttl);
            if ($success) {
                if (is_array($key)) {
                    foreach ($key as $k => $val) {
                        if (!isset($this->buffer[$k])) {
                            $this->buffer[$k] = $val;
                        }
                    }
                } else {
                    if (!isset($this->buffer[$key])) {
                        $this->buffer[$key] = $value;
                    }
                }
            }
        }
        return $success;
    }

    /**
     * 获取
     *
     * @param   string|array    $key        键名
     * @param   bool            &$success   是否成功
     *
     * @return mixed
     */
    public function get($key, &$success = null)
    {
        $this->assembleKey($key);
        $data = array();
        if (is_array($key)) {
            foreach ($key as $index => $k) {
                if (isset($this->buffer[$k])) {
                    $data[$k] = $this->buffer[$k];
                    unset($key[$index]);
                }
            }
            if (!empty($key)) {
                $cache = apc_fetch(array_values($key), $success);
                $this->buffer = array_merge($this->buffer, $cache);
                $data = array_merge($data, $cache);
            }
        } else {
            if (isset($this->buffer[$key])) {
                $data = $this->buffer[$key];
            } else {            	
                $data = apc_fetch($key, $success);
                $this->buffer[$key] = $data;
            }
        }
        return $data;
    }

    /**
     * 更新
     *
     * @param   string  $key    键
     * @param   int     $old    旧值
     * @param   int     $new    新值
     *
     * @return bool
     */
    public function cas($key, $old, $new)
    {
    	$this->assembleKey($key);
    	$this->assembleKey($old);
        $success = apc_cas($key, $old, $new);
        if ($success) {
            $this->buffer[$key] = $new;
        }
        return $success;
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
        return apc_exists($key);
    }

    /**
     * 删除
     *
     * @param   string|array    $key     键名
     *
     * @return bool
     */
    public function delete($key)
    {
    	$this->assembleKey($key);
        $success = true;
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
            $success = apc_delete($key);
            if ($success) {
                if (is_array($key)) {
                    foreach ($key as $k) {
                        $this->buffer[$k] = null;
                    }
                } else {
                    $this->buffer[$key] = null;
                }
            }
        }
        return $success;
    }

    /**
     * 刷新
     *
     * @param   string  $cacheType  缓存类型
     *
     * @return bool
     */
    public function flush($cacheType = 'user')
    {
        $success = apc_clear_cache($cacheType);
        if ($success && $cacheType === 'user') {
            $this->buffer = array();
            $this->transactionBuffer = array();
        }
        return $success;
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
     * 事物提交
     *
     * @return void
     */
    public function commit()
    {
        $this->inTransaction = false;
        apc_store($this->transactionBuffer, null, 0);
        $this->transactionBuffer = array();
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
        return;
    }

    /**
     * 获取缓存服务器状态
     *
     * @return array
     */
    public function status()
    {
        $reporting = error_reporting(0);
        $stats = apc_sma_info();
        error_reporting($reporting);
        return $stats;
    }

}