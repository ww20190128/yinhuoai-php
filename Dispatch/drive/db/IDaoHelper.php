<?php
namespace drive\db;

/**
 *  数据库操作接口
 *  
 *  1. 定义查询模式 
 *  2. 执行sql
 *  3. 执行查询sql
 */
interface IDaoHelper
{
    /**
     * 数据平台数据库
     *
     * @var int
     */
    const DB_DATA_PLATFORM = 1;

    /**
     * appServer数据库
     *
     * @var int
     */
    const DB_APP_SERVER = 2;
    
    /**
     * user_center数据库
     *
     * @var int
     */
    const DB_USER_CENTER = 3;

	/**
	 * 查询模式：获取一条
	 * 
	 * @var int
	 */
    const FETCH_MODE_ONE = 1;
    /**
	 * 查询模式：获取一列
	 * 
	 * @var int
	 */
    const FETCH_MODE_COL = 2;
    /**
	 * 查询模式：以数组的方式获取一列
	 * 
	 * @var int
	 */
    const FETCH_MODE_ARR_ROW = 3;
    /**
	 * 查询模式：获取所有
	 * 
	 * @var int
	 */
    const FETCH_MODE_ARR_ALL = 4;
    /**
	 * 查询模式：一条
	 * 
	 * @var int
	 */
    const FETCH_MODE_ROW = 5;
    /**
	 * 查询模式：所有
	 * 
	 * @var int
	 */
    const FETCH_MODE_ALL = 6;
    
    /**
     * 执行一个SQL语句，并返回影响的行数
     * 
     * @param string $sql sql语句
     * 
     * @return int 影响的行数
     */
    public function execBySql($sql);

    /**
     * 执行一个查询SQL语句，并返回结果
     * 
     * @param string $sql 	sql语句
     * @param string $table 表名
     * 
     * @return object 查询的结果
     */
    public function fetchBySql($sql, $table = null);

    /**
     * 开始一个MySQL事务
     *
     * @return void
     */
    public function begin();

    /**
     * 提交事务
     *
     * @return void
     */
    public function commit();

    /**
     * 事务失败回滚
     *
     * @return void
     */
    public function rollBack();

    /**
     * 批量插入
     *
     * @param 	string 	$table 				表名
     * @param   array   $fieldArr           字段列表
     * @param   array   $valueArr           值列表
     * @param   array   $onDuplicateUpdate  冲突时要更新的数据
     *
     * @return bool|int
     */
    public function addBat($table, $fieldArr, $valueArr, $onDuplicateUpdate = array());

    /**
     * 更新
     * 
     * @param 	string 		$table 				表名
     * @param 	array     	$setFields  		set值
     * @param 	array     	$addFields  		add值
     * @param 	string    	$where      		条件
     * @param 	array     	$min        		字段上限
     * @param 	array     	$max        		字段下限
     *
     * @return boolen
     */
    public function update($table, $setFields, $addFields, $where, $min = array(), $max = array());

    /**
     * 删除
     * 
     * @param 	string 		$table 				表名
     * @param   string  $where  条件
     *
     * @return int
     */
    public function remove($table, $where);

    /**
     * 获取所有记录
     * 
     * @param 	string 	$table 		表名
     * @param   string  $fields     字段
     * @param   string  $where      条件
     *
     * @return array
     */
    public function fetchAll($table, $fields, $where = '1');
    
}