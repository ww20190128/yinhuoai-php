<?php
namespace Dao;

/**
 * 数据库操作器
 * 
 * @author wangwei
 */
class DaoHelper
{
	/**
	 * 配置参数
	 *
	 * @var array
	 */
	private static $conf = array();  // 配置参数

	/**
	 * redis缓存器
	 *
	 * @var db\RedisCache
	 */
	public $cache;
	
	/**
     * pdo数据库操作组件
     * 
     * @var object
     */
    protected $pdo;
    
	/**
	 * 单例
	 *
	 * @var object
	 */
	private static $instance;
	
	/**
	 * 单例模式
	 *
	 * @return DaoHelper
	 */
	public static function singleton()
	{
		if (!isset(self::$instance)) {
			self::$instance = new DaoHelper();
		}
		return self::$instance;
	}
	
	/**
	 * 开始一个MySQL事务
	 *
	 * @return void
	 */
	public function begin()
	{
		$this->pdo->begin();
	}
	
	/**
	 * 提交事务
	 *
	 * @return void
	 */
	public function commit()
	{
		$this->pdo->commit();
	}
	
	/**
	 * 事务失败回滚
	 *
	 * @return void
	 */
	public function rollBack()
	{
		$this->pdo->rollBack();
	}
	
    /**
     * 初始化服务
     *
     * @param   array    $conf     配置
     *
     * @return DaoHelper
     */
    public function init($conf)
    {
    	if (empty($conf['mysql']) || empty($conf['redis'])) {
    		return false;
    	}
    	$this->initMysql($conf['mysql']);
    	$this->initCache($conf['redis']);
    	return $this;
    }
    
    /**
     * 初始化数据库
     *
     * @param   array    $conf     配置
     *
     * @return DaoHelper
     */
    private function initMysql($conf)
    {
    	$pdo = new PDODb($conf);
    	if (!empty($pdo)) {
    		$this->pdo = $pdo;
    	} else {
    		return false;
    	}
    	self::$conf['mysql'] = $conf;
    	return $this;
    }
    
    /**
     * 初始化缓存
     *
     * @param   array    $conf     配置
     *
     * @return DaoHelper
     */
    private function initCache($conf)
    {
    	$redisCache = new RedisCache($conf);
    	if (!empty($redisCache)) {
    		$this->cache = $redisCache;
    	}
    	self::$conf['redis'] = $conf;
    	return $this;
    }
    
    /**
     * 获取新的数据模板
     * 
     * @param   string    	$tableName    		表名
     * @param   boo1    	$needPrimary    	是否需要主键, 默认不需要
     * 
     * @return array
     */
    public function getNewTemplet($tableName, $needPrimary = false)
    {
    	$tableStructure = $this->getTableInfo($tableName); 	// 获取数据表结构  
    	$newTemplet = $tableStructure['column'];
    	if (empty($needPrimary)) {
    		unset($newTemplet[$tableStructure['primary']]);
    	}
    	return $newTemplet;
    }
    
    /**
     * 添加一条数据(增)
     *
     * @param   string      $tableName      表名
     * @param  	array  		$data 			数据
     * @param  	bool  		$clearCache 	是否强刷缓存
     *
     * 示例代码：
     * <code>
     * $tableName = 'userAction'; 							// 操作的数据表
     * $dataTpl = $daoHelper->getNewTemplet($tableName); 	// 获取一个空模板
     * // 赋值
     * $dataTpl['organizationName'] 	= 'test_organizationName';
     * $dataTpl['serialNumber'] 		= 'test_serialNumber';
     * $result = $daoHelper->create($tableName, $dataTpl);
     * </code>
     *
     * @return int|bool
     */
    public function create($tableName, $data, $clearCache = false)
    {
    	$tableStructure = $this->getTableInfo($tableName);
    	$dataInfo = $this->getDataInfo($data, $tableStructure);
    	if ($dataInfo) {
    		$return = $this->pdo->add($tableName, $dataInfo['dataArr']);
    		if (is_numeric($return)) { // 单一主键, 获取更新之后的数据实体
    			$primaryKey 		= $dataInfo['primaryKey'];
    			$data[$primaryKey] 	= $return; 					// 主键赋值
    			$dataInfo = $this->getDataInfo($data, $tableStructure);
    		}
    	} else {
    		return array(
    			'errorMsg' => '创建数据失败',
    		);
    	}
    	if ($this->cache && is_numeric($return) && !empty($dataInfo['cacheKeys'])) {
    		$ok = true; // 是否更新缓存成功
    		if ($clearCache === false) {
    			foreach ($dataInfo['cacheKeys'] as $cacheName => $cacheKey) {
    				if ($cacheName == 'PRIMARY') { // 主键
    					if ($this->filterDirtyData($data, $cacheKey, $dataInfo, $tableStructure)) {
    						$setResult = $this->cache->set($cacheKey, $data);
    					} else {
    						$ok = false;
    					}
    					if ($setResult !== true) {
    						$ok = false;
    					}
    				} else { // 索引
    					$cacheData = $this->cache->get($cacheKey);
    					if ($cacheData !== false) { // 获取缓存数据成功则更新缓存，获取失败则不处理
    						if ($this->filterDirtyData($data, $cacheKey, $dataInfo, $tableStructure)) {
    							$cacheData[] = $data;
    							$setResult = $this->cache->set($cacheKey, $cacheData);
    							if ($setResult !== true) {
    								$ok = false;
    							}
    						} else {// 脏数据
    							$ok = false;
    						}
    					}
    				}
    				if ($ok == false) {
    					break;
    				}
    			}
    		}
    		if ($ok == false || $clearCache === true) {
    			foreach ($dataInfo['cacheKeys'] as $cacheKey) {
    				$this->cache->delete($cacheKey);
    			}
    		}
    	}
    	return $return;
    }
    
    /**
     * 根据主键获取一条数据
     * 
     * @param   string    		$tableName    	表名
     * @param   int|string    	$primaryValue   主键值
     * @param   string    		$primaryName    主键名称
     * 
     * @return  array|bool
     */
    public function readByPrimary($tableName, $primaryValue, $primaryName = 'id')
    {
    	// 检查查询条件
    	$conditionInfo = $this->getConditionInfo($primaryValue, $tableName, $primaryName);	
    	if ($conditionInfo === false && empty($conditionInfo['primary'])) {
    		return array(
    			'errorMsg' => '查询条件错误',
    		);
    	}
    	if ($this->cache) {
    		$tableStructure = $this->getTableInfo($tableName);
    		foreach ($conditionInfo['keys'] as $cacheKey) {
    			$cacheValue = $this->cache->get($cacheKey);
    			if (false !== $cacheValue) {
    				$dataInfo = $this->getDataInfo($cacheValue, $tableStructure);
    				if ($this->filterDirtyData($cacheValue, $cacheKey, $dataInfo, $tableStructure)) {
    					return $cacheValue;
    				}
    			}
    		}
    	}
    	// 读取失败返回null
    	$data = $this->pdo->fetchOne($tableName, '*', $conditionInfo['where']);
    	if ($this->cache && !empty($data)) {
    		$dataInfo 	= $this->getDataInfo($data, $tableStructure);
    		$cacheKeys 	= $dataInfo['cacheKeys'];
    		$primaryKey = $dataInfo['primaryKey']; // 主键字段
    		$ok = true; // 是否更新缓存成功
    		foreach ($cacheKeys as $cacheName => $cacheKey) {
    			if ($cacheName == 'PRIMARY') { // 主键
    				if ($this->filterDirtyData($data, $cacheKey, $dataInfo, $tableStructure)) {
    					$setResult = $this->cache->set($cacheKey, $data);
    				} else {
    					$ok = false;
    				}
    			} else { // 索引
    				$cacheDataList = $this->cache->get($cacheKey);
    				if ($cacheDataList !== false) { // 获取缓存成功, 将新数据添加到缓存中
    					if (in_array($data, $cacheDataList)) { // 已经存在缓存中则跳过
    						continue;
    					}
    					$newDataList = array();
    					foreach ($cacheDataList as $row) {
    						$newDataList[$row[$primaryKey]] = $row;
    					}
    					if (!$this->filterDirtyData($data, $cacheKey, $dataInfo, $tableStructure)) { // 有脏数据
    						$ok = false;
    						break;
    					}
    					$newDataList[$data[$primaryKey]] = $data;
    					$setResult = $this->cache->set($cacheKey, $newDataList);
    					if ($setResult !== true) { // 索引缓存set设置失败,将该缓存key删除
    						$delResult = $this->cache->delete($cacheKey);
    						if ($delResult !== true) {
    							$ok = false;
    							break;
    						}
    					}
    				}
    			}
    		}
    		if ($ok == false) { // 更新失败了则删除
    			foreach ($cacheKeys as $cacheKey) {
    				$this->cache->delete($cacheKey);
    			}
    		}
    	}
    	return empty($data) ? array() : $data;
    }
    
    /**
     * 根据主键列表获取数组列表
     *
     * @param   string    		$tableName    			表名
     * @param   array    		$primaryValueList   	主键值列表
     * @param   string    		$primaryName    		主键名称
     *
     * @return  array|bool
     */
    public function readListByPrimary($tableName, $primaryValueList, $primaryName = 'id')
    {
    	// 检查查询条件
    	$conditionInfo = $this->getConditionInfo($primaryValueList, $tableName, $primaryName);
    	if ($conditionInfo === false || empty($conditionInfo['primary'])) {
    		return array(
    			'errorMsg' => '查询条件错误',
    		);
    	}
    	$list = array();
    	if ($this->cache) {
    		$tableStructure = $this->getTableInfo($tableName);	// 表结构
    		$cacheKeys = $conditionInfo['keys'];
    		$cacheReturn = true;
    		foreach ($cacheKeys as $cacheKey) {
    			$cacheValue = $this->cache->get($cacheKey);
    			if (false === $cacheValue || !is_array($cacheValue)) {
    				$cacheReturn = false;
    				break;
    			} elseif (!empty($cacheValue)) {
    				if (!is_array(reset($cacheValue))) { // 1维数组
    					if ($this->filterDirtyData($cacheValue, $cacheKey, array(), $tableStructure)) {
    						$list[] = $cacheValue;
    					} else { // 脏数据
    						$cacheReturn = false;
    						break;
    					}
    				} else  { // 2维数组
    					$list = array_merge($list, $cacheValue);
    				}
    			}
    		}
    		if ($cacheReturn) {
				return $list;
    		}
    	}
    	$dataList = $this->pdo->fetchAll($tableName, '*', $conditionInfo['where']);
    	if ($this->cache) {
    		if (!empty($dataList)) foreach ($dataList as $data) {
    			$dataInfo = $this->getDataInfo($data, $tableStructure);
    			if (is_array($data) && !empty($dataInfo['cacheKeys'])) {
    				$ok = true; // 是否更新缓存成功
    				foreach ($dataInfo['cacheKeys'] as $cacheName => $cacheKey) {
    					if ($cacheName == 'PRIMARY') {
    						if ($this->filterDirtyData($data, $cacheKey, $dataInfo, $tableStructure)) {
    							$this->cache->set($cacheKey, $data);
    						} else {
    							$ok = false;
    						}
    					} else {
    						$cacheDataList = $this->cache->get($cacheKey);
    						if ($cacheDataList !== false) {
    							if (in_array($data, $cacheDataList)) { // 已经存在缓存中则跳过
    								continue;
    							}
    							if (!$this->filterDirtyData($data, $cacheKey, $dataInfo, $tableStructure)) { // 有脏数据
    								$ok = false;
    								break;
    							}
    							$newDataList = array();
    							foreach ($cacheDataList as $row) {
    								$newDataList[$row[$primaryName]] = $row;
    							}
    							$newDataList[$data[$primaryName]] = $data;
    							$setResult = $this->cache->set($cacheKey, $newDataList);
    							if ($setResult !== true) { // 索引缓存set设置失败,将该缓存key删除
    								$delResult = $this->cache->delete($cacheKey);
    								if ($delResult !== true) {
    									$ok = false;
    									break;
    								}
    							}
    						}
    					}
    				}
    				if ($ok == false) { // 更新失败了则删除
    					foreach ($cacheKeys as $cacheKey) {
    						$this->cache->delete($cacheKey);
    					}
    				}
    			}
    		}
    	}
    	return empty($dataList) ? array() : $dataList;
    }

    /**
     * 根据索引获取数组列表
     *
     * @param   string    		$tableName    	表名
     * @param   array    		$condition   	查询条件
     * @param   bool    		$one   			是否只获取1条数据
     * 
     * @return  array|bool
     */
    public function readListByIndex($tableName, $condition, $one = false)
    {
    	$tableStructure = $this->getTableInfo($tableName); // 表结构
    	if (empty($tableStructure)) {
    		return array(
    			'errorMsg' => '查询条件错误',
    		);
    	}
    	$indexs = $tableStructure['indexArr']; // 索引列表
    	unset($indexs['PRIMARY']);
    	$fields = array();
    	$firstElement = reset($condition);
    	$cacheKeys = array();
    	$where = array();
    	$cacheKeyHead = "`{$tableName}`:"; // 缓存key头部
    	if (is_array($firstElement)) { // 二维数组
    		$fields = array_keys($firstElement);
    		sort($fields);
    		$fieldCount = count($fields) * 2;
    		foreach ($condition as $conditionArr) {
    			if (count(array_merge($fields, array_keys($conditionArr))) != $fieldCount) {
    				return array(
    					'errorMsg' => '查询条件错误',
    				);
    			}
    			$cacheKeyBody = array(); // 缓存key 字段体
    			$whereElement = array();
    			foreach ($fields as $field) {
    				$conditionElement = $conditionArr[$field];
    				if (!empty($conditionElement) && is_array($conditionElement)) {
    					$whereTmp = array();
    					foreach ($conditionElement as $row) {
    						$cacheKeyBody[$field][] = "[`$field`='$row']";
    						$whereTmp[] = $row;
    					}
    					$whereElement[] = "`$field` in  ('" . implode("','", array_unique($whereTmp)) . "')";
    				} else {
    					$cacheKeyBody[$field][] = "[`$field`='$conditionElement']";
    					$whereElement[] = "`$field`='$conditionElement'";
    				}
    			}
    			$cacheKeyBody = array_values($cacheKeyBody);
    			$countTotal = 1; // 总数
    			foreach ($cacheKeyBody as $row) {
    				$countTotal *= count($row);
    			}
    			$cacheKeyBodyList = array();
    			for ($cacheKeyBodyIndex = 0; $cacheKeyBodyIndex < count($cacheKeyBody); $cacheKeyBodyIndex++) {
    				$target = $cacheKeyBody[$cacheKeyBodyIndex];
    				$targetNum = $countTotal / count($target);
    				for($j = 1; $j < $targetNum; $j++) {
    					$target = array_merge($target, $cacheKeyBody[$cacheKeyBodyIndex]);
    				}
    				$cacheKeyBodyList[] = $target;
    			}
    			$cacheKeyBodyNum = count($cacheKeyBodyList);
    			while ($cacheKeyBodyList) {
    				$key = null;
    				for($cacheKeyBodyIndex = 0; $cacheKeyBodyIndex < $cacheKeyBodyNum; $cacheKeyBodyIndex++) {
    					$key .= array_pop($cacheKeyBodyList[$cacheKeyBodyIndex]);
    					$cacheKeyBodyList[$cacheKeyBodyIndex] = $cacheKeyBodyList[$cacheKeyBodyIndex];
    					if (empty($cacheKeyBodyList[$cacheKeyBodyIndex])) {
    						unset($cacheKeyBodyList[$cacheKeyBodyIndex]);
    					}
    				}
    				if (!empty($key)) {
    					$cacheKeys[] = $cacheKeyHead . $key;
    				}
    			}
    			$where[] = '(' . implode(' AND ', $whereElement) . ')';
    		}
    	} else { // 一维数组
    		$fields = array_keys($condition);
    		sort($fields);
    		$whereElement = array();
    		$cacheKeyBody = array(); // 缓存key 字段体
    		foreach ($fields as $field) {
    			$conditionElement = $condition[$field];
    			if (is_array($conditionElement)) {
    				$whereTmp = array();
    				foreach ($conditionElement as $row) {
    					$cacheKeyBody[$field][] = "[`$field`='$row']";
    					$whereTmp[] = $row;
    				}
    				$whereElement[] = "`$field` in  ('" . implode("','", array_unique($whereTmp)) . "')";
    			} else {
    				$cacheKeyBody[$field][] = "[`$field`='$condition[$field]']";
    				$whereElement[] = "`$field`='$condition[$field]'";
    			}
    		}
    		$cacheKeyBody = array_values($cacheKeyBody);
    		$countTotal = 1; // 总数
    		foreach ($cacheKeyBody as $row) {
    			$countTotal *= count($row);
    		}
    		$cacheKeyBodyList = array();
    		for ($cacheKeyBodyIndex = 0; $cacheKeyBodyIndex < count($cacheKeyBody); $cacheKeyBodyIndex++) {
    			$target = $cacheKeyBody[$cacheKeyBodyIndex];
    			$targetNum = $countTotal / count($target);
    			for($j = 1; $j < $targetNum; $j++) {
    				$target = array_merge($target, $cacheKeyBody[$cacheKeyBodyIndex]);
    			}
    			$cacheKeyBodyList[] = $target;
    		}
    		$cacheKeyBodyNum = count($cacheKeyBodyList);
    		while ($cacheKeyBodyList) {
    			$key = null;
    			for($cacheKeyBodyIndex = 0; $cacheKeyBodyIndex < $cacheKeyBodyNum; $cacheKeyBodyIndex++) {
    				$key .= array_pop($cacheKeyBodyList[$cacheKeyBodyIndex]);
    				$cacheKeyBodyList[$cacheKeyBodyIndex] = $cacheKeyBodyList[$cacheKeyBodyIndex];
    				if (empty($cacheKeyBodyList[$cacheKeyBodyIndex])) {
    					unset($cacheKeyBodyList[$cacheKeyBodyIndex]);
    				}
    			}
    			$cacheKeys[] = $cacheKeyHead . $key;
    		}
    		$where[] = '(' . implode(' AND ', $whereElement) . ')';
    	}
    	$useCache = true;
    	if (!$queryIndexName = array_search($fields, $indexs)) {
    		$useCache = false;
    		//return false;
    	}
    	$cacheKeys = array_unique($cacheKeys); // 缓存的key列表
    	$where = implode(' OR ', array_unique($where));
    	if (($useCache && empty($cacheKeys)) || empty($where)) {
    		return array(
    			'errorMsg' => '查询条件错误',
    		);
    	}
    	$list = array();
   		$primaryKey = $tableStructure['primary']; // 主键
    	if ($this->cache && $useCache) {
    		$cacheReturn = true; // 是否从缓存中获取
    		foreach ($cacheKeys as $cacheKey) {
    			$cacheValue = $this->cache->get($cacheKey);
    			if (false === $cacheValue || !is_array($cacheValue)) { // 从缓存获取失败
    				$cacheReturn = false;
    				break;
    			} elseif ($cacheValue) {
    				if ($primaryKey && !is_array(reset($cacheValue))) { // 1 维数组
    					if ($this->filterDirtyData($cacheValue, $cacheKey, array(), $tableStructure)) {
    						$list[$cacheValue[$primaryKey]] = $cacheValue;
    					} else { // 脏数据
    						$cacheReturn = false;
    						break;
    					}
    				} else {
    					$list = array_merge($list, $cacheValue);
    				}
    			} elseif (is_null($cacheValue)) {
    				$cacheReturn = false;
    				break;
    			}
    		}
    		if ($cacheReturn) {
				return $one ? reset($list) : $list;
    		}
    	}
    	$dataList = $this->pdo->fetchAll($tableName, '*', $where);
    	$return = array();
    	if ($this->cache && $useCache) {
    		$ok = true; // 是否更新缓存成功
    		$indexCaches = array(); // 索引缓存key列表
    		if (!empty($dataList)) foreach($dataList as $data) {
    			$dataInfo = $this->getDataInfo($data, $tableStructure);
    			if (!empty($dataInfo['cacheKeys'])) {
    				foreach ($dataInfo['cacheKeys'] as $cacheName => $cacheKey) {
    					if (!$this->filterDirtyData($data, $cacheKey, $dataInfo, $tableStructure)) {
    						$ok = false;
    						break;
    					}
    					if ($cacheName == 'PRIMARY') { // 主键
    						$this->cache->set($cacheKey, $data);
    					} elseif ($queryIndexName == $cacheName) { // 指定查询的索引
    						$indexCaches[$cacheKey][$data[$primaryKey]] = $data;
    					}
    				}
    			}
    			if ($primaryKey) {
    				$return[$data[$primaryKey]] = $data;
    			} else {
    				$return[] = $data;
    			}
    		}
    		if ($ok && !empty($indexCaches)) foreach ($indexCaches as $cacheKey => $data) {
    			$setResult = $this->cache->set($cacheKey, $data);
    			if ($setResult !== false) { // 缓存设置成功
    				if (($key = array_search($cacheKey, $cacheKeys)) !== false) {
    					unset($cacheKeys[$key]);
    				}
    			} else { // 缓存设置失败
    				$ok = false;
    				break;
    			}
    		}
    		if ($ok === false) { // 缓存设置失败, 删除缓存
    			foreach($indexCaches as $cacheKey => $row) {
    				$this->cache->delete($cacheKey);
    			}
    		} else {
    			// 缓存空数组
    			if (!empty($cacheKeys)) foreach ($cacheKeys as $cacheKey) {
    				$this->cache->set($cacheKey, array());
    			}
    		}
    	} else {
    		foreach($dataList as $data) {
    			$return[$data[$primaryKey]] = $data;
    		}
    	}
    	return $one ? reset($return) : $return;
    }
    
    /**
     * 根据主键更新实体(改)
     *
     * 不能修改主键
     * @param   string      $tableName      表名
     * @param   array  		$data 			数据
     *
     * @return array|bool
     */
    public function update($tableName, $updateData)
    {
    	$tableStructure = $this->getTableInfo($tableName);	// 获取表结构
    	$updateDataInfo = $this->getDataInfo($updateData, $tableStructure);
    	$primaryName = $updateDataInfo['primaryKey']; // 主键
    	if (!isset($updateData[$primaryName])) { // 没有主键不允许修改
    		return false;
    	}
    	$primaryValue = $updateData[$primaryName]; // 主键值
    	$oldData = $this->readByPrimary($tableName, $primaryValue, $primaryName);
    	if (empty($oldData)) { // 没数据不允许修改
    		return false;
    	}
    	$oldDataKeys = array_keys($oldData);
    	// 对比数据是否有修改
    	foreach ($updateData as $key => $value) {
    		if (!in_array($key, $oldDataKeys) || $oldData[$key] == $value)	{
    			unset($updateData[$key]);
    		}
    	}
    	unset($updateData[$primaryName]);
    	// 实体没有变更时，不做处理直接返回
    	if (empty($updateData)) {
    		return $oldData;
    	}
    	$oldDataInfo = $this->getDataInfo($oldData, $tableStructure);
    	$setParams = $updateData;
    	$addParams = array();
    	$fields = $oldDataInfo['fields'];
    	// 执行更新
    	$return = $this->pdo->update($tableName, $setParams, $addParams, $oldDataInfo['where']);
    	$newData = $oldData;
    	foreach ($updateData as $key => $value) {
    		$newData[$key] = $value;
    	}
    	$newDataInfo = $this->getDataInfo($newData, $tableStructure);
    	// 设置缓存
    	if ($return && $this->cache) {
    		// 处理旧数据的缓存(将旧数据从缓存中删除)
    		$oldOk = true; // 是否更新缓存成功
    		foreach ($oldDataInfo['cacheKeys'] as $cacheName => $cacheKey) {
    			if ($cacheName == 'PRIMARY') { // 主键
    				if ($this->filterDirtyData($newData, $cacheKey, $newDataInfo, $tableStructure)) {
    					$setResult = $this->cache->set($cacheKey, $newData); // 将旧数据替换成新数据
    					if ($setResult !== true) {
    						$oldOk = false;
    					}
    				} else {
    					$oldOk = false;
    				}
    			} else {
    				$cacheDataList = $this->cache->get($cacheKey);
    				if ($cacheDataList !== false) { // 获取缓存数据成功则更新缓存将旧数据从缓存中删除，获取失败则不处理
    					if (!empty($cacheDataList)) { // 数组
    						$newDataList = array();
    						foreach ($cacheDataList as $row) {
    							if (isset($row[$primaryName]) && $row[$primaryName] == $primaryValue) {
    								continue;
    							}
    							$newDataList[] = $row;
    						}
    						if (count($newDataList) == count($cacheDataList)) {
    							$oldOk = false;
    							break;
    						}
    						$delResult = $this->cache->delete($cacheKey);
    						if ($delResult !== true) {
    							$oldOk = false;
    						} else {
    							$setResult = $this->cache->set($cacheKey, $newDataList);
    							if ($setResult !== true) {
    								$oldOk = false;
    							}
    						}
    					}
    				}
    			}
    			if ($oldOk == false) {
    				break;
    			}
    		}
    		if ($oldOk == false) { // 旧数据的缓存更新失败了则删除缓存
    			foreach ($oldDataInfo['cacheKeys'] as $cacheKey) {
    				$this->cache->delete($cacheKey);
    			}
    		}
    		// 处理新数据的缓存
    		$newOk = true; // 是否更新缓存成功
    		$newDataInfo = $this->getDataInfo($newData, $tableStructure);
    		if (!empty($newDataInfo)) foreach ($newDataInfo['cacheKeys'] as $cacheName => $cacheKey) {
    			if ($cacheName == 'PRIMARY') { // 主键
    				if ($oldOk === false) {
    					$setResult = $this->cache->set($cacheKey, $newData); // 将旧数据替换成新数据
    					if ($setResult !== true) {
    						$newOk = false;
    					}
    				} else {
    					continue;
    				}
    			} else { // 索引
    				$cacheDataList = $this->cache->get($cacheKey);
    				if ($cacheDataList !== false) { // 获取缓存数据成功则更新缓存将新数据添加到缓存中，获取失败则不处理
    					$cacheDataList[] = $newData;
    					$setResult = $this->cache->set($cacheKey, $cacheDataList);
    					if ($setResult !== true) {
    						$newOk = false;
    					}
    				}
    			}
    			if ($newOk == false) {
    				break;
    			}
    		}
    		if ($newOk == false) { // 更新失败了则删除
    			foreach ($dataInfo['cacheKeys'] as $cacheKey) {
    				$this->cache->delete($cacheKey);
    			}
    		}
    	}
    	return $newData;
    }
    
    /**
     * 删除一条数据
     *
     * @param   string      $tableName      表名
     * @param   array  		$removeData 	数据
     *
     * @return bool | int
     */
    public function remove($tableName, $removeData)
    {
    	$return = false;
    	$tableStructure = $this->getTableInfo($tableName); // 获取表结构
    	$removeDataInfo = $this->getDataInfo($removeData, $tableStructure);
    	$primaryName = $tableStructure['primary']; // 主键
    	if (!isset($removeData[$primaryName])) { // 没有主键不允许删除
    		return false;
    	}
    	$return = $this->pdo->remove($removeDataInfo['where'], $tableName);
    	if ($return && $this->cache) {
    		$ok = true; // 是否更新缓存成功
    		foreach($removeDataInfo['cacheKeys'] as $cacheName => $cacheKey) { // 遍历key进行摘除处理
    			if ($cacheName == 'PRIMARY') { // 主键
    				$delResult = $this->cache->delete($cacheKey);
    				if ($delResult !== true) {
    					$ok = false;
    				}
    			} else { // 索引
    				$cacheDataList = $this->cache->get($cacheKey);
    				if ($cacheDataList !== false) { // 获取缓存数据成功则更新缓存将目标从缓存中删除，获取失败则不处理
    					if (!empty($cacheDataList)) { // 数组
    						$newDataList = array(); // 新数据
    						foreach ($cacheDataList as $row) {
    							if ($row[$primaryName] == $removeData[$primaryName]) { // 去掉目标
    								continue;
    							}
    							$newDataList[] = $row;
    						}
    						if (count($newDataList) == count($cacheDataList)) {
    							$ok = false;
    							break;
    						}
    						$delResult = $this->cache->delete($cacheKey);
    						if ($delResult !== true) {
    							$ok = false;
    						} else {
    							$setResult = $this->cache->set($cacheKey, $newData);
    							if ($setResult !== true) {
    								$ok = false;
    							}
    						}
    					}
    				}
    			}
    			if ($ok == false) {
    				break;
    			}
    		}
    		if ($ok == false) { // 更新失败了则删除
    			foreach ($removeDataInfo['cacheKeys'] as $cacheKey) {
    				$this->cache->delete($cacheKey);
    			}
    		}
    	}
    	return $return;
    }
    
    /**
     * 根据主键列表获取对象列表(不走缓存)
     *
     * @param   string      $tableName      表名
     * @param   string      $where      	where 条件
     * @param   mix         $fields     	要获取的属性列表
     *
     * @throws
     * @return  array
     */
    public function readListByWhere($tableName, $where = 1, $fields = null)
    {
    	return $this->pdo->fetchAll($tableName, self::getField($fields), $where);
    }
    
    /**
     * 根据sql读取数据(查)(不走缓存)
     *
     * @param  string  $sql	 sql语句
     *
     * @return  array | null
     */
    public function readDataBySql($sql)
    {
    	$dataList = $this->pdo->fetchBySql($sql);
		return $dataList;
    }
    
    /**
     * 执行sql语句
     *
     * @param  string  $sql	 sql语句
     *
     * @return array
     * array(
        	'lastInsertId' 	=> $lastInsertId,
        	'affectNum' 	=> $affectNum,
        );
     */
    public function execBySql($sql)
    {
    	return $this->pdo->execBySql($sql);
    }
    
    /**
     * 清理整个组件缓存
     * 
     * @param   string      $tableName      表名
     * 
     * @return bool
     */
    public function flush($tableName = '*')
    {
    	$cache = $this->cache;
    	$tableStructureKeys = $cache->getKeys("cachePrefix:{$tableName}"); 	// 表结构缓存
    	$dataKeys = $cache->getKeys("`{$tableName}`:*"); 				// 数据缓存
    	if (!empty($tableStructureKeys)) {
    		$cache->execDelete($tableStructureKeys);
    	}
    	if (!empty($dataKeys)) {
    		$cache->execDelete($dataKeys);
    	}
    	return true;
    }
    
    /**
     * 监控
     * 
     * 每10分钟执行1次
     * 
     * @return bool
     */
    public function monitor()
    {
    	$databaseName = self::$conf['mysql']['db_name'];
    	$cache = $this->cache;
    	$keys = array( // 需要监控的键列表
    		'data_sync',
    	);
    	$sql = "SELECT * from `tmp_global_key` where `key` in ('" . implode("','", $keys) . "')";
    	$changeDataList = $this->readDataBySql($sql);
    	$syncMapCacheKey = 'SYNC_MAP';
    	$syncMap = $cache->get($syncMapCacheKey); // 获取数据同步信息
    	$now = time();
    	if (!empty($changeDataList)) foreach ($changeDataList as $changeData) {
    		if ($changeData['key'] == 'data_sync') {
    			$time = strtotime($changeData['value']); // 数据最近一次修改数据的时间点
    			$lastSyncTime = empty($syncMap['lastSyncTime']) ? 0 : $syncMap['lastSyncTime']; // 最近一次同步缓存的时间点
    			if ($lastSyncTime >= $time) {
    				continue;
    			}
    			$this->flush(); 									// 清空组件缓存
    			$syncMap['lastSyncTime'] = $now; 					// 最近一次数据同步的时间点
    			$cache->set($syncMapCacheKey, $syncMap);
    		}
    	}
    	return true;
    }
    
    /**
     * 过滤脏数据
     *
     * @param	array 		$data				数据
     * @param	string 		$targetKey			验证key
     * @param	array 		$dataInfo			数据信息详情
     * @param	array 		$tableStructure		数据表结构
     * 
     * @return	bool	脏数据给予清理并且返回false
     */
    private function filterDirtyData($data, $targetKey, $dataInfo, $tableStructure)
    {
    	if (empty($this->cache)) {
    		return false;
    	}
    	if (empty($dataInfo)) {
    		$dataInfo = $this->getDataInfo($data, $tableStructure);
    	}	
    	if (!empty($dataInfo['cacheKeys']) && !in_array($targetKey, $dataInfo['cacheKeys'])) { // 脏数据
    		foreach ($dataInfo['cacheKeys'] as $cacheKey) {
    			$this->cache->delete($cacheKey);
    		}
    		return false;
    	}
    	return true;
    }
    
    /**
     * 获取条件信息
     *
     * @param   mix         $condition    		条件
     * @param   string      $tableName      	表名
     * @param   string    	$primaryName    	主键名称 
     * @param   array   	$tableStructure     表结构
     *
     * @return array
     *
     * array(
     *      'primary'   => true,    // 是否为主键
     *      'index'     => false,   // 是否为索引
     *      'cache'     => true,    // 是否可缓存
     *      'key'       => 'user[id=1][name='xiao']'
     * )
     *
     * 可用的条件列表:
     * 区间: [ ( , ) ] -∞ +∞
     * 取反: !
     * 近似 %
     * 集合 { }
     *
     * $condition = array(
     *      'a'     => 1,
     *      'b'     => 'yy',
     *      'c1'    => '[1, 5]',            // c1 >= 1 and c1 <= 5
     *      'c2'    => '(1, 5)',            // c2 > 1 and c2 < 5
     *      'c3'    => '[1, 5)',            // c3 >= 1 and c3 < 5
     *      'c4'    => '(1, 5]',            // c4 > 1 and c4 <= 5
     *      'c5'    => '(-∞, 5]',           // c5 <=5
     *      'c6'    => '(-∞, 5)',           // c6 < 5
     *      'c7'    => '(5, +∞)',           // c7 > 5
     *      'c8'    => '[5, +∞)',           // c8 >= 5
     *      'd'     => '!3',                // d != 3
     *      'e1'    => '%kk%',              // e1 like '%kk%'
     *      'e2'    => '%kk',               // e2 like '%kk'
     *      'e3'    => 'kk%',               // e3 like 'kk%'
     *      'f'     => '{1, 2, 3, 'a'}',    // f in (1, 2, 3, 'a')
     * );
     * 
     * 支持的模式
     * 
     * 1. 根据主键查询
     *     1  或者 'nlc'
     * 2. 根据主键列表查询
     * 	 	array(1, 2, 3) 或者 array('nlc', 'hlhg')
     * 3. 根据索引查询
     * 		array('name' => 'nlc', 'sex' => 1) 或者 array(array('name' => 'nlc', 'sex' => 1))
     */
    private function getConditionInfo($condition, $tableName = null, $primaryName = 'id')
    {
    	$primary 	= false;       		// 是否为主键
    	$index 		= false;         	// 是否为索引
    	$cache 		= false;         	// 是否可缓存
    	$keys 		= null;           	// 缓存键数组  格式: `user`[`id`=1][`name`='xiao']
    	$fieldMap 	= array();    		// 字段 值
    	if (empty($tableName) || empty($primaryName) || !is_string($primaryName)) {
    		return false;
    	}
    	$primaryName = trim($primaryName);
    	if (!is_array($condition)) { // 值必须为主键, 而且主键为单一字段
    		$primary = true;
    		$cache = true;
    		$keys[] = "`{$tableName}`:[`$primaryName`='$condition']";
    		$fieldMap[$primaryName] = $condition;
    	} else {
    		$symbolMap = array(
    			'(' => '>',
    			'[' => '>=',
    			')' => '<',
    			']' => '<=',
    			'{' => 'in',
    			'}' => 'in',
    			'!' => '!=',
    			'%' => 'like',
    		);
    		$conPrimary = false; // 是否联合主键		
    		foreach ($condition as $key => $value) {
    			if (is_numeric($key) && !is_array($value)) {
    				$primary = true;
    				$cache = true;
    				$fieldMap[$primaryName][] = $value;
    			} else {
    				if (is_array($value)) {
    					$cacheKey = "`{$tableName}`:";
    					if (count($value) != count($primaryKey) || array_diff(array_keys($value), $primaryKey)) {
    						return false;
    					} else {
    						foreach ($value as $k => $val) {
    							$fieldMap[$k][] = $val;
    							$cacheKey .= "[`$k`='$val']";
    						}
    					}
    					$keys[] = $cacheKey;
    					$conPrimary = true;
    				} else {	
    					if (preg_match("/(^[\[\(])(.*)(,)(.*)([\]\)])$/", $value, $matches)) { // 区间
    						$matches = array_map('trim', $matches);
    						$min = $matches['2'];
    						$max = $matches['4'];
    						$leftSymbol  = $matches['1'];
    						$rightSymbol = $matches['5'];
    						if ((!is_numeric($min) && ($leftSymbol != '(' || $min != '-∞'))
    							|| (!is_numeric($max) && ($rightSymbol != ')' || $max != '+∞'))
    							|| (is_numeric($min) && is_numeric($max) && $min > $max)) {
    							continue;
    						}
    						if (is_numeric($min)) {
    							$fieldMap[$key][] = "[`{$key}` $symbolMap[$leftSymbol] $min]";
    						}
    						if (is_numeric($max)) {
    							$fieldMap[$key][] = "[`{$key}` $symbolMap[$rightSymbol] $max]";
    						}
    					} elseif (preg_match("/(^{)(.*)(})$/", $value, $matches)) { // 集合 in
    						$leftSymbol  = $matches['1'];
    						$fieldMap[$key][] = "[`{$key}` $symbolMap[$leftSymbol] (" . $matches['2'] . ")]";
    					} elseif (preg_match("/(^!)(.*)$/", $value, $matches)) { // 取反
    						$leftSymbol  = $matches['1'];
    						$fieldMap[$key][] = "[`{$key}` $symbolMap[$leftSymbol] '" . $matches['2'] . "']";
    
    					} elseif (preg_match("/^%/", $value, $matches) || preg_match("/%$/", $value, $matches)) { // 近似 %
    						$leftSymbol  = $matches['0'];
    						$fieldMap[$key][] = "[`{$key}` $symbolMap[$leftSymbol] '{$value}']";
    					} else { // 等于
    						$fieldMap[$key] = $value;
    					}
    				}
    			}
    		}
    		// 检查是否主键或索引
    		if ($fieldMap && $conPrimary === false) {
    			$conPrimary = true;
    			if (count($fieldMap) == count(array($primaryName)) && !array_diff(array_keys($fieldMap), array($primaryName))) {
    				$key = "`{$tableName}`:";
    				foreach (array($primaryName) as $field) {
    					if (is_array($fieldMap[$field])) {
    						if (count(array($primaryName) == 1)) {
    							foreach($fieldMap[$field] as $val) {
    								if (preg_match("/(^\[)(.*)(\])$/", $val, $matches)) {
    									$conPrimary = false;
    									break;
    								}
    								$keys[] = "`{$tableName}`:[`$field`='$val']";
    							}
    							$key = null;
    						} else {
    							$conPrimary = false;
    							break;
    						}
    					} else {
    						$key .= "[`$field`='$fieldMap[$field]']";
    					}
    				}
    				if ($conPrimary && !empty($key)) {
    					$keys[] = $key;
    				}
    			} else {
    				$conPrimary = false;
    				$keys = array();
    			}
    			if ($conPrimary) {
    				$primary = true;
    				$cache = true;
    			} else {
    				unset($key);
    			}
    
    			if (!$primary) {
    				$tableStructure = self::getTableInfo($tableName);
    				ksort($fieldMap);
    				if (!empty($tableStructure['indexStr']) && $indexStr = array_search(implode(',', array_keys($fieldMap)), $tableStructure['indexStr'])) {
    					$cache = true;
    					$index = true;
    					$key = "`{$tableName}`:";
    					if (is_array($tableStructure['indexStr'][$indexStr])) {
    						foreach ($tableStructure['indexStr'][$indexStr] as $field) {
    							if (is_array($fieldMap[$field])) {
    								$index = false;
    								unset($key);
    								break;
    							}
    							$key .= "[`$field`='$fieldMap[$field]']";
    						}
    						if (isset($key)) {
    							$keys[] = $key;
    						}
    					} else {
    						$field = $tableStructure['indexStr'][$indexStr];
    						$key .= "[`$field`='$fieldMap[$field]']";
    						$keys[] = $key;
    					}
    					if (!$index) {
    						unset($key);
    						$cache = false;
    					}
    				}
    			}
    		} else {
    			if ($conPrimary && $keys) {
    				$primary = true;
    				$cache = true;
    			} else {
    				return false;
    			}
    		}
    	}
    	if (empty($index) && empty($cache)) {
    		$keys = null;
    	}
    	$where = array();
    	$excision  = ' AND ';
    	foreach ($fieldMap as $field => $value) {
    		if (is_array($value)) {
    			$tmp = array();
    			foreach($value as $i => $val) {
    				if (preg_match("/(^\[)(.*)(\])$/", $val, $matches)) {
    					$val = $matches['2'];
    					$where[] = $val;
    				} elseif (!$conPrimary) {
    					$tmp[] = $val;
    				}
    			}
    			if ($tmp) {
    				$where[] = "`$field` in  ('" . implode("','", array_unique($tmp)) . "')";
    			}
    		} else {
    			$where[] = "`$field`='{$value}'";
    		}
    	}
    	if (empty($where) && $conPrimary) {
    		$count = count(end($fieldMap));
    		for ($i = 0; $i < $count; $i++) {
    			$tmp = array();
    			foreach(array($primaryName) as $field) {
    				$tmp[] = "`$field`='{$fieldMap[$field][$i]}'";
    			}
    			$where[] = '(' . implode(' AND ', array_unique($tmp)) . ')';
    		}
    		$excision = ' OR ';
    	}
    	return array(
    		'primary'   => $primary,                                    	// 是否为主键
    		'index'     => $index,                                      	// 是否为索引
    		'cache'     => $cache,                                      	// 是否可缓存
    		'keys'      => empty($keys) ? array() : array_unique($keys),	// 缓存的keys
    		'where'		=> implode($excision, array_unique($where)),    	// sql where  // 主键缓存key
    	);
    }
    
    /**
     * 根据数据获取数据信息详情
     *
     * @param   array  		$data 					数据
     * @param   array  		$tableStructure 		数据表结构
     * 
     * @return array|bool
     */
    private function getDataInfo($data, $tableStructure)
    {
    	if (is_array($data) && !empty($tableStructure)) {
    		$cacheByPrimary = true; // 是否根据主键缓存
    		$primaryKey = $tableStructure['primary']; // 根据数据回去主键字段
    		$where = '';
    		if (isset($data[$primaryKey])) {
    			$where = "`{$primaryKey}`='{$data[$primaryKey]}'";
    		} else {
    			$cacheByPrimary = false;
    		}
    		$fields = array_keys($data);
    		$values = array_values($data);
    		$cacheKeys = $this->getCacheKeyByData($data, $tableStructure);
    		if ($cacheKeys === false) {
    			return false;
    		}
    		if (empty($cacheByPrimary) && !empty($cacheKeys['PRIMARY'])) {
    			unset($cacheKeys['PRIMARY']);
    		}
    	} else {
    		return false;
    	}
    	return array(
    		'cacheByPrimary'    => $cacheByPrimary,                     // 是否根据主键缓存
    		'primaryKey'        => $primaryKey,                         // 主键
    		'cacheKeys'         => $cacheKeys ? $cacheKeys : array(),   // 缓存的key列表
    		'where'             => $where,                              // sql where
    		'table'             => $tableStructure['tableName'],        // 表
    		'fields'            => $fields,                             // 字段
    		'values'            => $values,                             // 值
    		'dataArr'           => $data,              					// 字段 => 值
    	);
    }
    
    /**
     * 根据数据获取缓存键
     *
     * @param   array  		$data   				数据对象
     * @param   array  		$tableStructure 		数据表结构
     * 
     * @return array|bool
     */
    private function getCacheKeyByData($data, $tableStructure)
    {
    	$tableKey = '`' . $tableStructure['tableName'] . '`:';
    	$indexCacheKeyList = array();
    	foreach ($tableStructure['indexArr'] as $indexName => $fields) {
    		$indexCacheKey = $tableKey;
    		$unAdd = false;
    		foreach ($fields as $field) {
    			if (!isset($data[$field])) {
    				$unAdd = true;
    				continue;
    			}
    			$indexCacheKey .= "[`$field`='{$data[$field]}']";
    		}
    		$unAdd or $indexCacheKeyList[$indexName] = $indexCacheKey;
    	}
    	return $indexCacheKeyList;
    }
    
    /**
     * 获取表信息
     *
     * @param   string  	$tableName  	表名
     *
     * @return array
     */
    private function getTableInfo($tableName)
    {
    	$cacheKey = 'tables:' . $tableName;
    	$tableInfo = $this->cache->get($cacheKey);
    	if (!empty($tableInfo)) {
			return $tableInfo;
    	}
    	$indexs = $this->pdo->fetchBySql("show index from `$tableName`"); // 获取数据表的索引
    	$indexMap = array();
    	if (!empty($indexs)) foreach ($indexs as $index) {
    		$indexMap[$index['Key_name']][] = $index['Column_name'];
    	}
    	$primary = array();
    	$indexStr = array();
    	$fieldIndexInfo = array();
    	foreach ($indexMap as $name => $fields) {
    		sort($fields);
    		if ($name == 'PRIMARY') {
    			$primary = $fields;
    			unset($indexMap[$name]);
    		} else {
    			$indexStr[$name] = implode(',', $fields);
    		}
    		$indexMap[$name] = $fields;
    		foreach($fields as $field) {
    			$fieldIndexInfo[$field][] = $name;
    		}
    	}
    	$columns = $this->pdo->fetchBySql("show full columns from `$tableName`");
    	$columnMap = array();
    	$priProp = null;
    	$columnComment = array();
    	if (!empty($columns)) foreach($columns as $column) {
    		$columnMap[$column['Field']] = $column['Default'];
    		$columnComment[$column['Field']] = $column['Comment'];
    	}
    	$primary = end($primary); // 主键字段
    	$tableInfo = array(
    		'tableName'        	=> $tableName,    	// 表名
    		'primary'           => $primary,        // 主键
    		'indexStr'          => $indexStr,       // 索引字符串
    		'indexArr'          => $indexMap,       // 索引数组
    		'column'            => $columnMap,      // 字段信息
    		'comment'           => $columnComment,  // 字段描述
    		'fieldIndexInfo'    => $fieldIndexInfo, // 字段索引信息
    		'cacheTime'    		=> time(), 			// 缓存的时间
    	);
    	$this->cache->set($cacheKey, $tableInfo, 86400); // 将数据表结构放入缓存中
    	return $tableInfo;
    }
    
    /**
     * 处理字段
     *
     * @param   mix    $fields  字段
     *
     * @return string
     */
    private static function getField($fields)
    {
    	// 处理字段
    	$fieldArr = array();
    	if (is_array($fields)) foreach ($fields as $field => $as ) {
    		if (is_numeric($field)) {
    			$fieldArr[] = "`$as`";
    		} else {
    			$fieldArr[] = "`$field` as `$as`";
    		}
    	}
    	return empty($fieldArr) ? (is_string($fields) ? $fields : '*') : implode(',', $fieldArr);
    }

	/*
	 * 把数据直接存储在cache中
	 *
	 * @param key 键
	 *
	 * @param val 值
	 *
	 * */

	public function setCache($key, $val, $expTime = 86400){
		return $this->cache->set($key, $val, $expTime);
	}

	/*
	 * 从缓存在取出数据
	 *
	 * @param key 键
	 *
	 * @return array
	 *
	 * */
	public function getCache($key){
		$val = $this->cache->get($key);
        return $val;
	}

	/*
	 * 从缓存中删除
	 *
	 * @param key 键
	 *
	 * @return array
	 *
	 * */
	public function deleteCache($key){
        return $this->cache->delete($key);
	}
    
}