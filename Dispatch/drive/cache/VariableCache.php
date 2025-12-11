<?php
namespace drive\cache;
/**
 * 变量缓存
 * 
 * @author wangwei
 *
 * @package drive\cache\VariableCache
 */
class VariableCache implements ICache 
{
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
    public function __construct(array $args){}

    /**
     * 设置缓存
     *
     * @param string|array      $key       键名
     * @param mixed             $value     值
     *
     * @return bool
     */
    public function set($key, $value = null) 
    {
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
            if (is_array($key)) {
                $this->buffer = array_merge($this->buffer, $key);
            } else {
                $this->buffer[$key] = $value;
            }
        }
        return $success;
    }

    /**
     * 添加
     *
     * @param   string|array    $key     键名
     * @param   mixed           $value   值
     *
     * @return bool
     */
    public function add($key, $value = null) 
    {
        if ($this->inTransaction) {
            if (is_array($key)) {
                foreach ($key as $k => $val) {
                    if (!isset($this->buffer[$k])) {
                        $this->buffer[$k] = $val;
                    }
                    if (!isset($this->transactionBuffer[$k])) {
                        $this->transactionBuffer[$k] = $val;
                    }
                }
            } else {
                if (!isset($this->buffer[$key])) {
                    $this->buffer[$key] = $value;
                }
                if (!isset($this->transactionBuffer[$key])) {
                    $this->transactionBuffer[$key] = $value;
                }
            }
        } else {
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
        return true;
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
        $data = array();
        $success = true;
        if (is_array($key)) {
            foreach ($key as $index => $k) {
                if (isset($this->buffer[$k])) {
                    $data[$k] = $this->buffer[$k];
                } else {
                    $success = false;
                }
            }
        } else {
            if (isset($this->buffer[$key])) {
                $data = $this->buffer[$key];
            } else {
                $data = false;
                $success = false;
            }
        }
        return $data;
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
        if (is_array($key)) {
            foreach ($key as $k) {
                if (!isset($this->buff[$k])) {
                    return false;
                }
            }
        } else {
            if (!isset($this->buff[$key])) {
                return false;
            }
        }
        return true;
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
        if ($this->inTransaction) {
            if (is_array($key)) {
                foreach ($key as $k) {
                    $this->buffer[$k] = null;
                    $this->transactionBuffer[$k] = null;
                }
            } else {
                $this->transactionBuffer[$key] = null;
                $this->buffer[$key] = null;
            }
        } else {
            if (is_array($key)) {
                foreach ($key as $k) {
                    $this->buffer[$k] = null;
                }
            } else {
                $this->buffer[$key] = null;
            }
        }
        return true;
    }

    /**
     * 刷新
     *
     * @return bool
     */
    public function flush() 
    {
        $this->buffer = array();
        $this->transactionBuffer = array();
        return true;
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
        return;
    }

    /**
     * 事物提交
     *
     * @return void
     */
    public function commit() 
    {
        $this->inTransaction = false;
        $this->transactionBuffer = array();
        return;
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
     * @return void
     */
    public function status() 
    {
        return;
    }
}