<?php
namespace dao;

/**
 * shell 数据库操作 类
 *
 * @author wangwei
 */
class Shell extends DaoBase
{
	/**
     * 单例
     *
     * @var \dao\Shell
     */
    private static $instance;

    /**
     * 获取单例
     *
     * @return \dao\Shell
     */
    public static function singleton()
    {
    	if (!isset(self::$instance)) {
            self::$instance = new Shell();
        }
        return self::$instance;
    }
	
    /**
     * 清空动态缓存
     *
     * @return bool
     */
    public function flushCache()
    {
    	$this->dao->flush();
    	// 删除缓存记录
    	$cache = $this->cache;
    	$allKeys = $cache->getKeys('entity:ArenaMorror*');
    	if (!empty($allKeys)) {
    		$cache->execDelete($allKeys);
    	}
        return $this->cache->flush();
    }
    
	/**
     * 获取所有的表
     * 
     * @param	string		$database 	数据库名
     *
     * @return array
     */
    public function getAllTables($database = '')
    {
    	$daoHelper = $this->daoHelper;
    	$database = empty($database) ? $daoHelper::$dbName : $database;    	
    	$sql = "SHOW TABLE STATUS FROM `{$database}` WHERE ENGINE IS NOT NULL";
    	return $this->readDataBySql($sql);
    }

}