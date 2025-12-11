<?php
namespace Dao;
use \PDO;
use \PDOStatement;

/**
 *  数据操作对象PDO封装
 *  
 *  @author wangwei
 */
class PDODb
{
	/**
	 * 查询模式：以一维数组的方式获取一条数据
	 *
	 * @var int
	 */
	const FETCH_MODE_ARRAY_ONE = 1;
	
	/**
	 * 查询模式：以单个对象的方式获取一条数据
	 *
	 * @var int
	 */
	const FETCH_MODE_OBJECT_ONE = 2;
	
	/**
	 * 查询模式：以二维数组的方式获取多条数据
	 *
	 * @var int
	 */
	const FETCH_MODE_ARRAY = 3;
	
	/**
	 * 查询模式：以数组对象的方式获取
	 *
	 * @var int
	 */
	const FETCH_MODE_OBJECT_ARRAY = 4;
	
	
	private static $connArgs;  // 连接参数
	private $link;             // 连接对象

	/**
	 * 已注册的实体类列表
	 * 
	 * @var array
	 */
	protected $entityList = array();
    protected $database;
	
    protected $sqlList = array(); // 执行的sql列表
    /**
     * 事务计数器
     *
     * @var int|null
     */
    public $transactionNum = 0;

    /**
     * 回滚数量
     *
     * @var int|null
     */
    public $rollBackNum = 0;

    /**
     * 提交数量
     *
     * @var int|null
     */
    public $commitNum = 0;

    /**
     * 注入一个实体类
     *
     * @param	string 	$table		表名
     * @param	string 	$entity 	实体名
     *
     * @return void
     */
    public function registerEntity($table, $entity)
    {
        $this->entityList[$table] = $entity;
    }
    
	/**
     * 获取注册的实体类
     * 
     * @param	string 	$table		表名
     * 
     * @return string
     */
    private function getEntity($table)
    {
        if (empty($this->entityList[$table]) || !in_array($this->dbType, array(
        	self::DB_DATA_PLATFORM, 
        	self::DB_APP_SERVER
        ))) {
        	return 'stdClass';
        } else {
        	return $this->entityList[$table];
        }
    }

	/**
     * 设置连接参数
     * 
     * @param array $args 连接参数
     * 
     * @return void
     */
    public static function init($args = array())
    {
        self::$connArgs = $args;
        return;
    }

    /**
     * 构造函数, 数据库连接
     *
     * @param   array   $args   连接参数
     *
     * @return
     */
    public function __construct($args = array())
    {
        self::init($args);
        return;
    }

    /**
     * 获取连接句柄
     *
     *  @return recoure
     */
    private function initLink()
    {
        if (empty($this->link)) {
            if ($this->connect() === false) {
            	die('数据库连接错误');
            }
        }
        return $this->link;
    }
    
	/**
     * 断开连接句柄
     *
     * @return bool
     */
    public function disconnect()
    {
        return $this->link = null;
    }

    /**
     * 开始一个MySQL事务
     *
     * @return void
     */
    public function begin()
    {
        $this->initLink();
        $this->link->beginTransaction();
    }

    /**
     * 提交事务
     *
     * @return void
     */
    public function commit()
    {
        $this->initLink();
        $this->link->commit();
    }

    /**
     * 事务失败回滚
     *
     * @return void
     */
    public function rollBack()
    {
        $this->initLink();
        $this->link->rollBack();
    }

    /**
     * 执行一个SQL语句，并返回影响的行数
     *
     * @param   string  $sql  SQL语句
     *
     * @return  array 
     * array(
        	'lastInsertId' 	=> $lastInsertId,
        	'affectNum' 	=> $affectNum,
        ); 
     */
    public function execBySql($sql)
    {
// print_r($sql . "\n");
        $this->initLink();
        $affectNum =  $this->link->exec($sql);
        $lastInsertId = empty($affectNum) ? 0 : $this->link->lastInsertId();
        return array(
        	'lastInsertId' 	=> $lastInsertId,
        	'affectNum' 	=> $affectNum,
        );
    }

    /**
     * 创建到数据库服务器的连接
     *
     * @throws \Exception|\PDOException
     *
     * @return resource
     */
    private function connect()
    {
        if (empty(self::$connArgs)) {
        	return false;
        }
        $connArg = self::$connArgs;
        $this->database = empty($connArg['db_name']) ? null : $connArg['db_name'];
        // 连接数据库
        $dsn = 'mysql:';
        if (isset($connArg['db_host'])) $dsn .= 'host='         . $connArg['db_host']	. ';';
        if (isset($connArg['db_port'])) $dsn .= 'port='         . $connArg['db_port']   . ';';
        if (isset($connArg['db_name'])) $dsn .= 'dbname='       . $connArg['db_name']   . ';';
        if (isset($connArg['socket']))  $dsn .= 'unix_socket='  . $connArg['socket']    . ';';
        try {
            $this->link = new PDO(
                $dsn,
                isset($connArg['db_user']) ? $connArg['db_user'] : '',
                isset($connArg['db_pass']) ? $connArg['db_pass'] : '',
                array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8';",
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => isset($connArg['persistence']) && $connArg['persistence'] ? true : false,
                )
            );
        } catch (\PDOException $e) {
            throw $e;
        }
        return $this->link;
    }

    /**
     * 根据PDOStatement和$fetchMode获取结果
     * 
     * @param   string  		$table      	表名
     * @param   PDOStatement    $statement      PDO执行结果对象
     * @param   int             $fetchMode      获取模式
     * @param   string          $intoObj        绑定对象
     * 
     * @return Object|array|bool
     */
    private function fetch($table, PDOStatement $statement, $fetchMode = self::FETCH_MODE_ALL, &$intoObj = null)
    {
        switch ($fetchMode) {
        	case self::FETCH_MODE_ARRAY_ONE : 					// 一维数组形式获取一条数据
        		return $statement->fetch(PDO::FETCH_ASSOC);
        	case self::FETCH_MODE_ARRAY : 						// 二维数组形式获取多条
        		return $statement->fetchAll(PDO::FETCH_ASSOC);
            case self::FETCH_MODE_OBJECT_ONE : 					// 单个对象的形式获取1条
                if (is_null($intoObj)) { // 没有绑定对象
                	$statement->setFetchMode(PDO::FETCH_CLASS, $this->getEntity($table));
                } else {
                	$statement->setFetchMode(PDO::FETCH_INTO, $intoObj);
                }
                return $statement->fetch();
           	case self::FETCH_MODE_OBJECT_ARRAY : 				// 获取对象数组
        		if (is_null($intoObj)) { // 没有绑定对象
                	$statement->setFetchMode(PDO::FETCH_CLASS, $this->getEntity($table));
                } else {
                	$statement->setFetchMode(PDO::FETCH_INTO, $intoObj);
                }
                return $statement->fetchAll();
          	default:
          		return false;
        }
        return false;
    }

    /**
     * 执行一个SQL, 返回PDOStatement
     *
     * @param   string  $sql    SQL语句
     * @param   array   $param  PDO执行参数
     *
     * @return PDOStatement
     */
    private function query($sql, $param = null)
    {
//print_r($sql . "\n");
        $this->initLink();
        $startTime = microtime();
        if (!empty($param['baseName'])) {
            $param['baseName'] = $this->database;
        }
        $statement = $this->link->prepare($sql);
        $result = $statement->execute($param);
        return $statement;
    }

    /**
     * 执行一个SQL查询语句，并根据$fetchMode返回结果
     *
     * @param   string  $sql        SQL语句
     * @param   string  $table      表名
     * @param   array   $param      PDO执行参数
     * @param   int     $fetchMode  获取模式[IDaoHelper::FETCH_MODE_X]
     * @param   object  $intoObj    数据绑定对象
     *
     * @return object
     */
    public function fetchBySql($sql, $table = null, $param = array(), $fetchMode = self::FETCH_MODE_ARRAY, &$intoObj = null)
    {
        return $this->fetch($table, $this->query($sql, $param), $fetchMode, $intoObj);
    }

    /**
     * 构造SQL语句
     *
     * @param   string      $table      表名
     * @param   string      $where      SQL语句的where条件
     * @param   string      $fields     获取的字段列表，默认为*
     *
     * @return string
     */
    private function generateSelectSql($table, $fields = '*', $where = '1')
    {
        $sql = "SELECT {$fields} FROM `{$table}` WHERE {$where}";
        return $sql;
    }

    /**
     * 获取所有记录
     *
     * @param   string  $table      表名
     * @param   string  $fields     字段
     * @param   string  $where      条件
     *
     * @return array
     */
    public function fetchAll($table, $fields = '*', $where = '1', $fetchMode = self::FETCH_MODE_ARRAY)
    {
        return $this->fetchBySql($this->generateSelectSql($table, $fields, $where), $table, array(), $fetchMode);
    }

    /**
     * 获取一条记录
     *
     * @param   string  $table  	表名
     * @param   string  $fields     字段
     * @param   string  $where      条件
     * @param   entity  $intoObj    绑定对象
     *
     * @return object
     */
    public function fetchObj($table, $fields = '*', $where = '1', &$intoObj = null)
    {
        $where .= ' LIMIT 1;';
        return $this->fetchBySql($this->generateSelectSql($table, $fields, $where), $table, null, self::FETCH_MODE_OBJECT_ONE, $intoObj);
    }

    /**
     * 获取一个字段
     * 
     * @param   string  	$table  	表名
     * @param   string  	$fields     字段
     * @param   string  	$where      条件
     * @param   int  		$mode      	数据模型   1 对象  2 数组
     * 
     * @return object|array
     */
    public function fetchOne($table, $fields, $where = '1', $mode = 2)
    {
   		if ($mode == 1) { // 对象
   			$fetchMode = self::FETCH_MODE_OBJECT_ONE;
   		} else {
   			$fetchMode = self::FETCH_MODE_ARRAY_ONE;
   		}
        $where .= ' LIMIT 1;';
        return $this->fetchBySql($this->generateSelectSql($table, $fields, $where), $table, array(), $fetchMode);
    }
    
    /**
     * 删除
     * 
     * @param   string  $where  条件
     * @param   string  $table  表名
     * 
     * @return int
     */
    public function remove($where, $table)
    {
        return $this->execBySql("DELETE FROM {$table} WHERE {$where}");
    }

    /**
     * 更新
     * 
     * @param   string  	$table  	表名
     * @param 	array     	$setFields  set值
     * @param 	array     	$addFields  add值
     * @param 	string    	$where      条件
     * @param 	array     	$min        字段上限
     * @param 	array     	$max        字段下限
     * 
     * @return boolen
     */
    public function update($table, $setFields, $addFields, $where, $min = array(), $max = array())
    {
        if (!is_array($setFields) || !is_array($addFields)) return false;
        $arrStr = array();      
        foreach ($addFields as $key => $val) {	
            $_tmpVal = "`{$key}` + '{$val}'";
            if (isset($min[$key])) {
                // 下限
                $_tmpVal = "GREATEST('{$min[$key]}',{$_tmpVal})";
            }
            if (isset($max[$key])) {
                // 上限
                $_tmpVal = "LEAST('{$max[$key]}',{$_tmpVal})";
            }
            $arrStr[] = "`{$key}` = {$_tmpVal}";
        }
        foreach ($setFields as $key => $val) {
            $_tmpVal = "'". addslashes($val) . "'";
            if (isset($min[$key])) {
                // 下限
                $_tmpVal = "GREATEST('{$min[$key]}',{$_tmpVal})";
            }
            if (isset($max[$key])) {
                // 上限
                $_tmpVal = "LEAST('{$max[$key]}',{$_tmpVal})";
            }
            $arrStr[] = "`{$key}`={$_tmpVal}";
        }
        return $this->execBySql("UPDATE `{$table}` SET " . implode(",", $arrStr) . " WHERE {$where};");
    }

    /**
     * 单条插入并返回auto_increment
     * 
     * @param   string  	$table  			表名
     * @param   array   	$arrFields          插入的数据
     * @param   array   	$onDuplicateUpdate  冲突时要更新的数据
     * 
     * @return int auto_increment
     */
    public function add($table, $arrFields, $onDuplicateUpdate = array())
    {
        if (!is_array($arrFields)) return false;
        $keyArr = array_keys($arrFields);
        return $this->addBat($table, $keyArr, array($arrFields), $onDuplicateUpdate);
    }

    /**
     * 批量插入
     *
     * @param   string  $table  			表名
     * @param   array   $fieldArr           字段列表
     * @param   array   $valueArr           值列表
     * @param   array   $onDuplicateUpdate  冲突时要更新的数据
     *
     * @return bool|int
     */
    public function addBat($table, $fieldArr, $valueArr, $onDuplicateUpdate = array())
    {
        if (!is_array($fieldArr) || !is_array($valueArr)) return false;
        $insertArr = array();
        foreach ($valueArr as $val) {
            $insertArr[] = "('" . implode("','", array_map('addslashes', $val)) . "')";
        }
        $updateStr = empty($onDuplicateUpdate) ? '' : ' ON DUPLICATE KEY UPDATE ' . implode(',', $onDuplicateUpdate);
        $sql = "INSERT INTO `{$table}` (`" . implode("`,`", $fieldArr) . "`) VALUES" . implode(',', $insertArr) . $updateStr . ';';
        $result = $this->execBySql($sql);
        return empty($result) ? $result : $this->link->lastInsertId();
    }
	
	/**
     * 进程退出回滚
     * 
     * @return void
     */
    public function flush() 
    {
        if ($this->transactionNum > 0) {     
            $this->rollBack();
            $this->disconnect();
        }
        return;
    }
    
}