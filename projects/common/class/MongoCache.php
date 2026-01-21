<?php
namespace service;
use \Mongo;

/**
 * Mongo操作类
 * 
 * @author 	wangwei
 * @version v1.0
 * 
 * 
 * 
 * 调试例子:	
 * 		$args = array(
    		'host'		=> '192.168.0.170',
    		'port'		=> '27017',
    		'user'		=> 'www',
    		'pass'		=> '295012469',
    		'dbname'	=> 'log_db',
    		'table'		=> 'user',
    	);
		$mongoCache = new \service\MongoCache($args);		
		$db 		= $mongoCache->selectDb('log_db'); 							// 选择数据库
		$dbList 	= $mongoCache->getDbList();									// 获取数据库列表
		$collection = $mongoCache->selectCollection('user');					// 选择集合
		$collection = $db->user;												// 选择集合(不建议)
		//$result 	= $mongoCache->dropDb('log_db');							// 删除数据库(在没有权限时删除失败返回false)
		//$result 	= $mongoCache->close();										// 强制关闭数据库的连接
		$dbList 	= $mongoCache->getDbList();									// 获取数据库列表
		$document = array(
			"key" 	=> "mak",
			'test' 	=> 'this is a test',
		);
		$result = $mongoCache->insert($document);  // ！注意 内部的引用会导致$document值的修改
		$documentArr = array();
		for ($index = 1; $index <= 100; $index ++) {
			$document['id'] =  $index;
			$documentArr[] = $document;
		}
		$result = $mongoCache->insertBatch($documentArr); // 批量插入
		
		$whereArr = array('id' => array('$gt' => 20), 'id' => array('$lt' => 40)); // id > 20 and id < 40
		$fieldArr = array('test', 'id');
		$dataList = $mongoCache->fetch($whereArr, $fieldArr, 10000, array('id' => -1), false); // 查
		$result = $mongoCache->removeByDataList($dataList); // 删
		$whereArr = array('id' => array('$gt' => 0), 'id' => array('$lt' => 1000)); // id > 20 and id < 40
		$result = $mongoCache->removeByWhere($dataList);
		$result = $mongoCache->removeAll();
		$result = $mongoCache->fetch(array(), array(), null, array(), true);
		print_r($result);exit;
 */
class MongoCache
{
	private static $connArgs;  // 连接参数
	
	/**
	 * mongo连接实例
	 *
	 * @var \Mongo
	 */
	private $mongo;
	
	/**
	 * 数据库实例
	 *
	 * @var \MongoDB
	 */
	private $db;
	
	/**
	 * 集合实例
	 *
	 * @var \MongoCollection
	 */
	private $collection;
	
	/**
	 * 运行方式
	 *
	 * @var string
	 */
	private $runType = 'http';
	
	/**
	 * 尝试次数
	 *
	 * @var obj
	 */
	private $tries;
	
	/**
	 * 获取当前的版本
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return \MongoClient::VERSION;
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
		if (empty($args['host']) || empty($args['port']) || empty($args['dbname'])) {
			return false;
		}
		$switch = isset($args['switch']) && $args['switch'] == false ? false : true;
		self::$connArgs = array_map('trim', $args);
		if ($switch) {
			$switch = $this->connect();
		}		
		// 运行模式
		$this->runType = in_array(substr(PHP_SAPI, 0, 3), array('cgi', 'cli')) ? 'shell' : 'http';
		return;
	}
	
	/**
	 * 析构函数 断开连接
	 *
	 * @return void
	 */
	public function __destruct() 
	{
		$this->close();
	}
	
	
	/**
	 * 创建到服务器的连接
	 *
	 * @throws \Exception|\MongoConnectionException
	 *
	 * @return bool
	 */
	private function connect() 
	{
		$connectionString = "mongodb://"; // 链接信息
		if (!empty(self::$connArgs['user']) && !empty(self::$connArgs['pass'])) { // 账号密码验证
			$connectionString .= self::$connArgs['user'] . ':' . self::$connArgs['pass'] . '@';
		}
		$connectionString .= self::$connArgs['host'];
		if (!empty(self::$connArgs['port'])) { // 如果没有指定使用默认端口27017连接
			$connectionString .= ':' . self::$connArgs['port'];
		}
		$options = array(
			'connect'			=> true, 																	// 是否持久链接
			'connectTimeoutMS'	=> empty(self::$connArgs['timeout']) ? -1 : self::$connArgs['timeout'], 		// 超时时间, 默认不超时
		);
		try {
			$cliented  	= new \MongoClient($connectionString, $options); 			// 创建Mongo的连接实例
			if (empty($cliented)) {
				$cliented = $this->failBack($connectionString, $options);
			}
			if (empty($cliented)) {
				return false;
			}
			$this->mongo = $cliented;
			if (!empty(self::$connArgs['dbname'])) {
				$this->db = $this->mongo->{self::$connArgs['dbname']}; // 选择数据库	
			}
		} catch (MongoConnectionException $e) {
			return false;
		}
		return true;
	}
	
	/**
	 * 故障恢复
	 *
	 * @param   string  	$connectionString    	连接参数
	 * @param   array     	$options    			选项
	 * 
	 * @return 		Mongo|bool
	 */
	public function failBack($connectionString, $options)
	{
		$tries = $this->tries;
		$cliented = false; // 是否连接上
		while (--$tries > 3) {
			$cliented  = new \Mongo($connectionString, $options); 	// 创建Mongo的连接实例
			if ($cliented) {
				syslog(LOG_WARNING, "Mongo connection established, pid: " . posix_getpid());
				break;
			}
		}
		return $cliented;
	}
	
	/**
	 *  选择数据库
	 *  
	 *  @param 		string  	$dbname 	库名
	 *  
	 *  @return  MongoDB|bool
	 */
	public function selectDb($dbname) 
	{
		try {
			$this->db = $this->mongo->{$dbname};
		} catch (Exception $e) {
			return false;
		}
		return $this->db;
	}
	
	/**
	 * 获取所有的数据库列表
	 *
	 * @return array
	 */
	public function getDbList()
	{
		return empty($this->mongo) ? array() : $this->mongo->listDBs();
	}
	
	/**
	 *  选择集合
	 *
	 *  @param 		string  	$dbname 	库名
	 *
	 *  @return  MongoCollection|bool
	 */
	public function selectCollection($tableName)
	{
		try {
			$this->collection = empty($this->db)? false : $this->db->{$tableName};
		} catch (Exception $e) {
			return false;
		}
		return $this->collection;
	}
	
	/**
	 *  删除集合
	 *
	 *  @param 		string  	$dbname 	库名
	 *
	 *  @return  MongoCollection|bool
	 */
	public function dropCollection($tableName)
	{
		try {
			$this->db = empty($this->db)? false : $this->db->dropCollection($tableName);
		} catch (Exception $e) {
			return false;
		}
		return $this->db;
	}
	
	/**
	 * 删除数据库
	 *
	 * @param 		string   	$dbname
	 *
	 * @return bool
	 */
	public function dropDb($dbname)
	{
		$retult = $this->mongo->dropDB($dbname);
		return empty($retult['ok']) ? false : true;
	}
	
	/**
	 * 关闭mongo的链接
	 * 
	 * @return bool
	 */
	public function close()
	{
		return $this->mongo->close(true);
	}
	
	/**
	 * 插入一条数据(增)
	 * 
	 * @param 		array 		$document      文档数据
	 * 
	 * @return  bool
	 */
	public function insert($document)
	{
		if (empty($this->collection) || empty($document)) {
			return false;
		}
		try {
			$result = $this->collection->insert((array)$document); // insert 传递的是个引用
		} catch (MongoException $e) {
			$this->throwError($e->getMessage());
		}
		return $result;
	}
	
	/**
	 * 批量插入数据(增)
	 * 
	 * (控制在单条数据  16M 以内  批量数据在48M以内),  数组元素在10w以内
	 *
	 * @param 		array 		$documentArr     数据列表
	 *
	 * @return  bool
	 */
	public function insertBatch($documentArr)
	{
		if (empty($this->collection) || !is_array($documentArr)) {
			return false;
		}
		foreach ($documentArr as $key => $document) {
			if (!is_array($document)) {
				return false;
			}
			$documentArr[$key] = (array)$document; // batchInsert函数的bug
		}
		try {
			$result = $this->collection->batchInsert($documentArr);
		} catch (MongoException $e) {
			$this->throwError($e->getMessage());
		}
		return $result;
	}
	
	/**
	 * 根据条件查找文档(查)
	 * 
	 * @param 		array 		$whereArr     	查询的条件列表
	 * @param 		array 		$fieldArr     	查询的字段列表
	 * @param 		int 		$skipNum     	跳过前N条数据
	 * @param 		int 		$limitNum     	获取的数量
	 * @param 		array 		$sortArr     	排序规则列表
	 * @param 		bool 		$onlyCount     	是否只获取中数量
	 * 
	 * $whereArr 条件举例说明:
	 * 
	 * array('id' => 5) 												// id = 5
	 * array('id' => array('$ne' 	=> 5)) 								// id != 5
	 * array('id' => array('$gt' 	=> 20))								// id > 20 
	 * array('id' => array('$gte' 	=> 20))								// id >= 20 
	 * array('id' => array('$lt' 	=> 20))								// id < 20
	 * array('id' => array('$lte' 	=> 20))								// id <= 20
	 * array('$or' => array(array('id1' => 10), array('id2' => 80))) 	// id1 = 10 or  id2 = 80
	 * array('$and'=> array(array('id1' => 12), array('id2' => 70)))) 	// id1 = 12 and id2 = 70
	 * array('id1' => 12, 'id2' => 70) 									// id1 = 12 and id2 = 70
	 * 
	 * $sortArr 排序规则说明:
	 * array('age' => -1, 'type' => 1)  // 1 表示降序 -1表示升序,参数的先后影响排序顺序
	 * 
	 * @return MongoCursor|bool
	 */
	public function fetch($whereArr = array(), $fieldArr = array(), $skipNum = null, $limitNum = null, $sortArr = array(), $onlyCount = false)
	{
		$fields = array(); // 需要获取的字段
		if (is_array($fieldArr)) foreach ($fieldArr as $field) {
			$fields[$field] = true;
		}
		try {
			if ($limitNum == 1) { // 获取1条
				$cursor = $this->collection->findOne($whereArr, $fields);	
			} else{
				if ($onlyCount) { // 只获取总数量
					$cursor = $this->collection->count($whereArr);
				} else {
					$cursor = $this->collection->find($whereArr, $fields);
					if ($skipNum > 0) { // 跳过N条数据
						$cursor = $cursor->skip($skipNum);
					}
					if ($limitNum > 0) { // 获取的数量限制
						$cursor = $cursor->limit($limitNum);
					}
					if (is_array($sortArr) && !empty($sortArr)) {
						$cursor = $cursor->sort($sortArr);
					}	
				}
			}
		} catch (MongoException $e) {
			$this->throwError($e->getMessage());
		}
		if ($onlyCount) { // 只获取总数量
			return $cursor;
		}
		// 组装结果
		$result = array();
		foreach ($cursor as $document) {
			unset($document['_id']);
			$result[] = $document;
		}
		return $result;
	}
	
	/**
	 * 根据条件删除(删)
	 *
	 * @param 		array 		$whereArr     	条件列表
	 * 
	 * @return int
	 */
	public function removeByWhere($whereArr)
	{	
		if (empty($whereArr) || !is_array($whereArr)) {
			return false;
		}
		try {
			$result = $this->collection->remove($whereArr);
		} catch (MongoException $e) {
			$this->throwError($e->getMessage());
		}
		return $result;
	}
	
	/**
	 * 根据数据删除(删)
	 *
	 * @param 		array 		$data      数据列表
	 *
	 * @return int
	 */
	public function removeByDataList($dataList)
	{
		if (empty($dataList) || !is_array($dataList)) {
			return false;
		}
		$num = 0;
		try {
			foreach ($dataList as $row) {
				if (empty($row['_id']) || !($row['_id'] instanceof \MongoId)) {
					continue;
				}
				$result = $this->collection->remove(array('_id' => $row['_id']));
				if (!empty($result)) {
					$num++;
				}
			}
		} catch (MongoException $e) {
			$this->throwError($e->getMessage());
		}
		return $num;
	}
	
	/**
	 * 删除所有数据(删)
	 * 
	 * @return int
	 */
	public function removeAll()
	{
		try {
			$result = $this->collection->remove();
		} catch (MongoException $e) {
			$this->throwError($e->getMessage());
		}
		return $result;
	}
	
	/**
	 * 更新(改)
	 *
	 * @param 		array 		$whereArr     	条件列表
	 * @param 		array 		$updateArr     	更新列表
	 * 
	 * $updateArr 格式说明:
	 * 
	 * http://www.blogjava.net/qileilove/archive/2014/05/29/414231.html
	 * // $inc 如果记录的该节点存在, 让该节点的数值加N；如果该节点不存在，让该节点值等于N
	 * array('$inc' => array('age' => 5))
	 * // $set 让某节点等于给定值
	 * array('$set' => array('age' => 5))
	 * // $unset 删除某节点
	 * array('$unset' => 'age')
	 * // $push 如果对应节点是个数组，就附加一个新的值上去；不存在，就创建这个数组，并附加一个值在这个数组上；如果该节点不是数组，返回错误。
	 * array('$push' => array('desc' => 'this is a test'))
	 * // $addToSet 如果该阶段的数组中没有某值，就添加之
	 * array('$addToSet' => array('desc' => 'this is a test'))
	 * // $pop 删除某数组节点的最后一个元素:
	 * array('$pop' =>array('desc' => 1))
	 * // $pop 删除某数组节点的第一个元素:
	 * array('$pop' =>array('desc' => -1))
	 * // $pull 如果该节点是个数组，那么删除其值为value的子项，如果不是数组，会返回一个错误。
	 * array('$pull' => array('info' => 'age'))
	 * // $pullAll与$pull类似，只是可以删除一组符合条件的记录
	 * 
	 * @return boolen
	 */
	public function update($whereArr, $updateArr)
	{
		if (!is_array($whereArr) || !is_array($updateArr) || empty($whereArr) || empty($updateArr)) {
			return false;
		}
		try {
			$result = $this->collection->update($whereArr, $updateArr);
		} catch (MongoException $e) {
			$this->throwError($e->getMessage());
		}
		return $result;
	}
	
	/**
	 * 创建一个容器
	 *
	 * @param 		sting 		$name      容器名称
	 *
	 * @return  bool
	 */
	public function createCollection($name)
	{
		if (empty($this->db) || empty($name)) {
			return false;
		}
		try {
			$result = $this->db->createCollection($name);
		} catch (MongoException $e) {
			$this->throwError($e->getMessage());
		}
		return $result;
	}
	
	/**
     * 调用扩展的对应方法
     *
     * @param 	string 	$method 	方法名
     * @param 	array  	$args   	参数列表
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        //var_dump($method); // TODO 统计未封装方法的调用频率，选择性封装
        return call_user_func_array(array($this->collection, $method), $args);
    }
    
}