<?php
namespace drive\db;

/**
 *  数据操作对象mysql封装
 */
class MysqlDb implements IDaoHelper
{
	private static $connArgs;   // 连接参数
	private static $mysql;      // 连接对象

	
    protected $entityClass = 'stdClass';
    protected $tableName;    
    
	/**
     * 设置连接参数
     * 
     * @param array $args 连接参数
     * 
     * @return void
     */
    public static function setConnArgs($args = array())
    {   	
        self::$connArgs = $args;
        return ;
    }
    
    /**
     * 构造函数, 数据库连接
     * 
     * @param   array       $args		连接参数
     * @param   boolen      $connect 	是否连接
     * 
     * @return void
     */
    public function __construct($args = array(), $connect = false)
    { 	
    	if (empty(self::$connArgs) && $args) {
    		self::$connArgs = $args;
    	}
    	if (self::$mysql || !$connect) return;
    	$host = isset($args['host']) ? $args['host'] : 'localhost';
    	$host .= ':';
    	$host .= isset($args['port']) ? $args['port'] : (isset($args['unix_socket']) ? $args['unix_socket'] : 3306); 	
    	// 连接数据库
    	$mysql_connect = $args['pconnect'] ? 'mysql_connect' : 'mysql_connect';
    	self::$mysql = $mysql_connect($host, $args['username'], $args['password']);
    	if (!(self::$mysql && mysql_select_db($args["dbname"], self::$mysql) && mysql_set_charset('UTF8', self::$mysql))) {
  			die('Could not connect: ' . mysql_error());
  		}
       	return;
    }
    
    /**
     * 设置操作表
     * 
     * @param $tableName string  表名
     * 
     * @return void
     */
    public function setTable($tableName)
    {
        $this->tableName = $tableName;
        return ;
    }

    /**
     * 设置返回实例的类名
     * 
     * @param string $className  实体类名
     * 
     * @return void
     */
    public function setEntityClass($className = null)
    {
        $this->entityClass = is_null($className) ? "stdClass" : $className;
        return ;
    }

    /**
     * 根据PDOStatement和$fetchMode获取结果
     * 
     * @param PDOStatement  $statement  PDO执行结果对象
     * 
     * @param int           $fetchMode  获取模式
     * @param string        $intoObj    绑定对象
     * 
     * @return Obj
     */
    public function fetch(PDOStatement $statement, $fetchMode=self::FETCH_MODE_ALL, &$intoObj = null)
    {
        switch ($fetchMode) {
            case self::FETCH_MODE_ONE :
                return $statement->fetch(PDO::FETCH_COLUMN);
            case self::FETCH_MODE_COL :
                return $statement->fetchAll(PDO::FETCH_COLUMN);
            case self::FETCH_MODE_ARR_ROW :
                return $statement->fetch(PDO::FETCH_ASSOC);
            case self::FETCH_MODE_ARR_ALL :
                return $statement->fetchAll(PDO::FETCH_ASSOC);
            case self::FETCH_MODE_ROW :
                if (is_null($intoObj)) {
                    $statement->setFetchMode(PDO::FETCH_CLASS, $this->entityClass);
                } else {
                    $statement->setFetchMode(PDO::FETCH_INTO, $intoObj);
                }
                return $statement->fetch();
            case self::FETCH_MODE_ALL :
            	default :
                    $statement->setFetchMode(PDO::FETCH_CLASS, $this->entityClass);  
                return $statement->fetchAll();
        }
        return false;
    }

    /**
     * 执行一个SQL，返回PDOStatement
     * @param string $sql SQL语句
     * @param array $param PDO执行参数
     * @return PDOStatement
     */
    public function query($sql, $param = null)
    {
        $startTime = microtime();
   
        $statement = self::$pdo->prepare($sql);
        $result = $statement->execute($param);
        
        return $statement;
    }

    /**
     * 执行一个SQL语句，并返回影响的行数
     * @param $sql string SQL语句
     * @return int 影响的行数
     */
    public function execBySql($sql)
    {
        $result = self::$pdo->exec($sql);
        return $result;
    }

    /**
     * 执行一个SQL查询语句，并根据$fetchMode返回结果
     * @param string $sql SQL语句
     * @param int $fetchMode 获取模式[IDaoHelper::FETCH_MODE_X]
     * @param unknown_type $intoObj 数据绑定对象
     */
    public function fetchBySql($sql, $fetchMode=self::FETCH_MODE_ALL, &$intoObj = null)
    {
        $statement = $this->query($sql);
        return $this->fetch($statement, $fetchMode, $intoObj);
    }

    /**
     * 获取所有记录
     * 
     * @param string $fields 字段
     * @param string $where 条件
     * 
     * @return object
     */
    public function fetchAll($fields, $where="1")
    {
        $sql = "SELECT {$fields} FROM {$this->tableName} WHERE {$where}";
        return $this->fetchBySql($sql);
    }

    /**
     * 获取一条记录
     * @param string $fields 字段
     * @param string $where 条件
     * @param object $intoObj 绑定对象
     * 
     * @return object
     */
    public function fetchObj($fields, $where="1", &$intoObj = null)
    {
        $where .= " LIMIT 1;";
        $sql = "SELECT {$fields} FROM {$this->tableName} WHERE {$where}";
        return $this->fetchBySql($sql, self::FETCH_MODE_ROW, $intoObj);
    }

    /**
     * 获取一个字段
     * @param 	string 	$fields 	字段
     * @param 	string 	where 		条件
     * 
     * @return string
     */
    public function fetchOne($fields, $where = '1')
    {
        $where .= " LIMIT 1;";
        $sql = "SELECT {$fields} FROM {$this->tableName} WHERE {$where}";
        return $this->fetchBySql($sql, self::FETCH_MODE_ONE);
    }

    /**
     * 获取一列
     * 
     * @param 	string 	$fields 	字段
     * @param 	string 	$where 		条件
     * 
     * @return array
     */
    public function fetchCol($fields, $where = '1')
    {
        $sql = "SELECT {$fields} FROM {$this->tableName} WHERE {$where}";
        return $this->fetchBySql($sql, self::FETCH_MODE_COL);
    }
    
    /**
     * 删除
     * @param string $where 条件
     */
    public function remove($where)
    {
        $sql = "DELETE FROM {$this->tableName} WHERE {$where}";
        return $this->execBySql($sql);
    }

    /**
     * 更新
     * 
     * @param array     $setFields  set值
     * @param array     $addFields  add值
     * @param string    $where      条件
     * @param array     $min        字段上限
     * @param array     $max        字段下限
     * 
     * @return boolen
     */
    public function update($setFields, $addFields, $where, $min=array(), $max=array())
    {
        if (!is_array($setFields) || !is_array($addFields)) return false;
        $arrStr = array();      
        foreach ($addFields as $key => $val) {	
            $_tmpVal = "`{$key}`+'{$val}'";
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
        $sql = "UPDATE `{$this->tableName}` SET " . implode(",", $arrStr) . " WHERE {$where};";    
        return $this->execBySql($sql);
    }

    /**
     * 单条插入并返回auto_increment
     * 
     * @param array $arrFields 插入的数据
     * @param array $onDuplicateUpdate 冲突时要更新的数据
     * 
     * @return int auto_increment
     */
    public function add($arrFields, $onDuplicateUpdate = array())
    {
        if (!is_array($arrFields)) return false;
        $keyArr = array_keys($arrFields);
        $result = $this->addBat($keyArr, array($arrFields), $onDuplicateUpdate);  
        return empty($result) ? $result : self::$pdo->lastInsertId();
    }

    /**
     * 批量插入
     * 
     * @param array $fieldArr 字段列表
     * @param array $valueArr 值列表
     * 
     * @param array $onDuplicateUpdate 冲突时要更新的数据
     */
    public function addBat($fieldArr, $valueArr, $onDuplicateUpdate = array())
    {    	
        if (!is_array($fieldArr) || !is_array($valueArr)) return false;
        $insertArr = array();
        foreach ($valueArr as $val) {
            $insertArr[] = "('" . implode("','", array_map('addslashes', $val)) . "')";
        }
        $updateStr = empty($onDuplicateUpdate) ? "" : " ON DUPLICATE KEY UPDATE " . implode(",", $onDuplicateUpdate);       
        $sql = "INSERT INTO `{$this->tableName}` (`" . implode("`,`", $fieldArr) . "`) VALUES" . implode(",", $insertArr) . $updateStr . ";";      

        return $this->execBySql($sql);
    }
    
     /**
     * 开始一个MySQL事务
     *
     * @return void
     */
    public function begin() 
    {
    
    }

    /**
     * 提交事务
     *
     * @return void
     */
    public function commit()
    {
    	
    }
    
	/**
     * 事务失败回滚
     *
     * @return void
     */
    public function rollBack()
    {
    	if (!SWITCH_MYSQL_TRANSACTION) {
    		return ;
    	}
    }
    
}