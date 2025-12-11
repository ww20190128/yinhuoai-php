<?php
namespace drive\cache;

/**
 * 缓存接口 
 * 
 * @author wangwei
 */
interface ICache
{
	/**
	 * 构造函数，处理连接
	 * 
	 * @param object
	 */
    public function __construct(array $args);

    /**
     * 储存
     *
     * @param   string  $key        键名
     * @param   mixed   $value      值
     *
     * @return bool
     */
    public function set($key, $value = null);

    /**
     * 添加  保存成功返回true,存在同样key返回false
     *
     * @param   string|array    $key        键名
     * @param   mixed           $value      值
     *
     * @return bool
     */
    public function add($key, $value = null);

    /**
     * 获取
     *
     * @param   string|array    $key        键名
     * @param   bool            $success    是否获取成功
     *
     * @return mixed
     */
    public function get($key, &$success = null);

    /**
     * 检查是否存在
     *
     * @param   string|array    $key     键名
     *
     * @return bool
     */
    public function exists($key);

    /**
     * 删除
     *
     * @param   string|array    $key     键名
     *
     * @return bool
     */
    public function delete($key);

    /**
     * 刷新
     *
     * @return bool
     */
    public function flush();

    /**
     * 事物开始
     *
     * @return void
     */
    public function begin();

    /**
     * 事物提交
     *
     * @return void
     */
    public function commit();

    /**
     * 事物回滚
     *
     * @return void
     */
    public function rollBack();

    /**
     * 获取缓存服务器状态
     *
     * @return array
     */
    public function status();
}