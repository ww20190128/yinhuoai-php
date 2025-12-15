<?php
namespace dao;
use framework\exception\FrameException;
use Dispatch\FrameBase;

/**
 * 数据层抽象基类
 * 
 * @author wangwei
 */
abstract class DaoBase extends FrameBase 
{
	/**
     * 实体  默认为stdClass
     *
     * @var string
     */
    protected $entity = 'stdClass';

	/**
     * 主表名
     *
     * @var string
     */
    protected $mainTable = null;
    
    /**
     * 主键   默认为id
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * 表结构
     *
     * @var array
     */
    protected $tableMap = array();

    /**
     * 单例
     *
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return Department
     */
    public static function singleton()
    {
        $c = static::class;
        if (!isset(self::$instance[$c])) {
            self::$instance[$c] = new static();
        }
        return self::$instance[$c];
    }


    /**
     * 构造函数
     *
     * @return \dao\DaoBase
     */
    public function __construct()
    {
        parent::__construct(__NAMESPACE__);
    	$this->init(str_replace('dao', 'entity', get_class($this)));
        return;
    }
    
	/**
     * 初始化
     * 
     * @param	string 		$entityClass	实体名
     *
     * @return bool
     */
    public function init($entityClass)
    {
    	$daoHelper = $this->daoHelper;
    	if (class_exists($entityClass)) {
    		$this->entity      = $entityClass;
    	    $this->mainTable   = $entityClass::MAIN_TABLE;
            $this->primaryKey  = $entityClass::PRIMARY_KEY;
            $this->daoHelper->registerEntity($this->mainTable, $entityClass); // 注册实体到数据库操作
            $this->tableMap = $entityClass::getTableInfo($this->mainTable, $daoHelper::$dbName);
    		if (empty($this->tableMap)) {
    			throw new $this->exception('数据库内部错误');
    		}
            return true;           
    	}
        return false;
    }
    
    /**
     * 获取新的实体对象
     * 
     * @return entity
     */
    public function getNewEntity()
    {
        return new $this->entity;
    }
    
	/**
     * 根据键构造缓存键名
     *
     * @param  string 	$key
     *
     * @return string
     */
    public function getCacheKey($key)
    {
    	return str_replace(CS, ':', $this->entity) . '|' . $key;
    }
    
    /**
     * 创建一个对象(增)
     *
     * @param  entity  	$object 	对象实体
     * @param  bool  	$clearCache 是否强刷缓存
     *
     * 示例代码：
     * <code>
     * $testDao = \dao\Test::singleton(); // 获取对象单例
     * $testEntity = $testDao->getNewEntity(); // 获取对象实体(含默认值)
     * $testEntity->name = '王伟'; // 实体属性赋值
     * $testEntity->desc = '简介--abc'; // 实体属性赋值
     * $result = $testDao->create($testEntity); // 创建实体
     * </code>
     *
     * @return int|bool
     */
    public function create($object, $clearCache = false)
    {
  		$dataInfo = $this->getDataInfo($object);
    	if ($dataInfo) {
	    	$return = $this->daoHelper->add($dataInfo['table'], $dataInfo['dataArr']);
	    	if (is_numeric($return)) { // 单一主键, 获取更新之后的数据实体
	    		$primaryKey = $dataInfo['primaryKey'];
                $object->{$primaryKey} = $return; // 主键赋值
                $dataInfo = $this->getDataInfo($object);
	    	}
	  	} else {
	    	$return = false;
	 	}   
        if ($this->cache && is_numeric($return) && !empty($dataInfo['cacheKeys'])) {     
            $ok = true; // 是否更新缓存成功
            if ($clearCache === false) {
	            foreach ($dataInfo['cacheKeys'] as $cacheName => $cacheKey) {
					if ($cacheName == 'PRIMARY') { // 主键
						if ($this->filterDirtyData($object, $cacheKey, $dataInfo)) {			
							$setResult = $this->cache->set($this->getCacheKey($cacheKey), $object);
						} else {
							$ok = false;
						}
						if ($setResult !== true) {
							$ok = false;
						}
					} else { // 索引
						$data = $this->cache->get($this->getCacheKey($cacheKey));
						if ($data !== false) { // 获取缓存数据成功则更新缓存，获取失败则不处理
							if ($this->filterDirtyData($object, $cacheKey, $dataInfo)) {			
								$data[] = $object->unsetProtected();
		                        $setResult = $this->cache->set($this->getCacheKey($cacheKey), $data);
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
	        	foreach($dataInfo['cacheKeys'] as $cacheKey) {
	          		$this->cache->delete($this->getCacheKey($cacheKey));
	           	}
			}
        }
        return $return;
    }

    /**
     * 删除一个对象(删)
     *
     * @param   entity  $object 对象实体
     *
     * @return bool | int
     */
    public function remove($object)
    {
        $return = false;
        if (is_object($object)) {
            $dataInfo = $this->getDataInfo($object);
            if ($dataInfo ===  false) {
                return $return;
            }
            $return = $this->daoHelper->remove($dataInfo['where'], $dataInfo['table']);
            if ($return && $this->cache) {
            	$primaryKey = $dataInfo['primaryKey']; // 主键字段
            	$ok = true; // 是否更新缓存成功	            
            	foreach($dataInfo['cacheKeys'] as $cacheName => $cacheKey) { // 遍历key进行摘除处理
					if ($cacheName == 'PRIMARY') { // 主键
						$delResult = $this->cache->delete($this->getCacheKey($cacheKey));
						if ($delResult !== true) {
							$ok = false;
						}
					} else { // 索引
						$data = $this->cache->get($this->getCacheKey($cacheKey));
	                    if ($data !== false) { // 获取缓存数据成功则更新缓存将目标从缓存中删除，获取失败则不处理
	                    	if (is_iteratable($data)) { // 数组
		                    	$newData = array(); // 新数据
		                        foreach ($data as $row) {
		                        	if ($row->$primaryKey == $object->$primaryKey) { // 去掉目标
		                        		continue;
		                        	}
		                        	$newData[] = $row;
		                        }
		                        if (count($newData) == count($data)) {
		                        	$ok = false;
		                        	break;
		                        }
		                    	$delResult = $this->cache->delete($this->getCacheKey($cacheKey));
								if ($delResult !== true) {
									$ok = false;
								} else {
									$setResult = $this->cache->set($this->getCacheKey($cacheKey), $newData);
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
		        	foreach($dataInfo['cacheKeys'] as $cacheKey) {
		          		$this->cache->delete($this->getCacheKey($cacheKey));
		           	}
				}
            }
        }
        return $return;
    }

    /**
     * 根据主键更新实体(改)
     *
     * @param   entity  $object 对象实体
     *
     * @return bool | int
     */
    public function update($object)
    {
    	$changes = $object->loadChanges(); 
    	// 实体没有变更时，不做处理直接返回
        if (!is_object($object) || empty($changes) || empty($changes['oldEntity'])) {
        	return false;
        }
        $oldEntity = $changes['oldEntity']; // 旧数据实体  
        $oldDataInfo = $this->getDataInfo($oldEntity);
    	$setParams = array();
        $addParams = array();
        $fields = $oldDataInfo['fields'];
        foreach ($changes as $prop => $v) {
            if (!in_array($prop, $fields)) {
            	continue;
            }
            if ($v->changeType == $v::CHANGE_TYPE_SET) {
                $setParams[$prop] = $v->changeValue;
            } else if ($v->changeType == $v::CHANGE_TYPE_ADD) {
                $addParams[$prop] = $v->changeValue;
            }
        }    
        // 执行更新
        $return = $this->daoHelper->update($oldDataInfo['table'], $setParams, $addParams, $oldDataInfo['where']);
        // 清除实体对象的数据改变状态
   		$object->clearChanges(); 	
   		$dataInfo = $this->getDataInfo($object);		 		
   		// 设置缓存
        if ($return && $this->cache) {
        	$primaryKey = is_array($oldDataInfo['primaryKey']) 
        		? end($oldDataInfo['primaryKey']) : $oldDataInfo['primaryKey']; // 主键字段	
        	// 处理旧数据的缓存(将旧数据从缓存中删除)
        	$oldOk = true; // 是否更新缓存成功 	
        	foreach ($oldDataInfo['cacheKeys'] as $cacheName => $cacheKey) {		
        		if ($cacheName == 'PRIMARY') { // 主键	
        			if ($this->filterDirtyData($object, $cacheKey, $dataInfo)) {      				 
        				$setResult = $this->cache->set($this->getCacheKey($cacheKey), 
	        				$object->unsetProtected()); // 将旧数据替换成新数据
						if ($setResult !== true) {
							$oldOk = false;
						}
					} else {
						$oldOk = false;
					}	
				} else {
					$data = $this->cache->get($this->getCacheKey($cacheKey));				
					if ($data !== false) { // 获取缓存数据成功则更新缓存将旧数据从缓存中删除，获取失败则不处理
	            		if (is_iteratable($data)) { // 数组
	            			$newData = array();
		                	foreach ($data as $row) {
		                        if ($row->$primaryKey == $oldEntity->$primaryKey) {
		                        	continue;
		                        }
		                        $newData[] = $row;
		                 	}
		                	if (count($newData) == count($data)) {
		                        $oldOk = false;
		                        break;
		                  	}
		                    $delResult = $this->cache->delete($this->getCacheKey($cacheKey));
							if ($delResult !== true) {
								$oldOk = false;
							} else {			
								$setResult = $this->cache->set($this->getCacheKey($cacheKey), $newData);
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
        		foreach($oldDataInfo['cacheKeys'] as $cacheKey) {		
		          	$this->cache->delete($this->getCacheKey($cacheKey));
		      	}
			}
			// 处理新数据的缓存
			$newOk = true; // 是否更新缓存成功
			$dataInfo = $this->getDataInfo($object);
        	if (!empty($dataInfo)) foreach ($dataInfo['cacheKeys'] as $cacheName => $cacheKey) {
        		if ($cacheName == 'PRIMARY') { // 主键
        			if ($oldOk === false) {
	        			$setResult = $this->cache->set($this->getCacheKey($cacheKey), 
	        				$object->unsetProtected()); // 将旧数据替换成新数据
						if ($setResult !== true) {
							$newOk = false;
						}
        			} else {
        				continue;
        			}
				} else { // 索引
					$data = $this->cache->get($this->getCacheKey($cacheKey));
                    if ($data !== false) { // 获取缓存数据成功则更新缓存将新数据添加到缓存中，获取失败则不处理
                        $data[] = $object->unsetProtected();
                        $setResult = $this->cache->set($this->getCacheKey($cacheKey), $data);
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
        		foreach($dataInfo['cacheKeys'] as $cacheKey) {    			
		          	$this->cache->delete($this->getCacheKey($cacheKey));
		      	}
			}
        }
        return $return;
    }
    
    /**
     * 过滤脏数据
     * 
     * @param	object 	$object				数据对象
     * @param	string 	$targetKey			验证key
     * @param	array 	$dataInfo			数据信息详情
     * 
     * @return	bool	脏数据给予清理并且返回false
     */
    private function filterDirtyData($object, $targetKey, $dataInfo = array()) 
    {
    	if (empty($this->cache)) {
    		return false;
    	}
    	if (empty($dataInfo)) {
    		$dataInfo = $this->getDataInfo($object);
    	}
    	if (!empty($dataInfo['cacheKeys']) && !in_array($targetKey, $dataInfo['cacheKeys'])) { // 脏数据
    		foreach ($dataInfo['cacheKeys'] as $cacheKey) {
    			$this->cache->delete($this->getCacheKey($cacheKey));
    		}
			return false;
		}
		return true;
    }

    /**
     * 根据主键获取一个对象实体(查)
     *
     * @param   array | int | string    $primary    主键
     * @param   mix                     $fields     要获取的属性列表
     *
     * * 示例代码：
     * <code>
     * $testDao = \dao\Test::singleton(); // 获取对象单例
     * $primary = array( // 联合主键
     *  'age'   => '0',
     *  'id'    => 4,
     *  'sex'   => 10,
     * );
     * 或
     * $primary = array( // 单一主键
     *  'id'    => 4,
     * );
     * 或
     * $primary = 'a' // 单一主键
     * $testEtt = $testDao->readByPrimary($primary);
     * </code>
     *
     * @throws
     * @return  entity | null
     */
    public function readByPrimary($primary, $fields = null)
    {
        // 检查查询条件
        $conditionInfo = $this->getConditionInfo($primary);
        if ($conditionInfo === false && empty($conditionInfo['primary'])) {
            throw new $this->exception('查询条件错误');
        } 
        if ($this->cache) {
        	foreach($conditionInfo['keys'] as $cacheKey) {
           		$cacheValue = $this->cache->get($this->getCacheKey($cacheKey));
           		if (false !== $cacheValue) {	
           			$dataInfo = $this->getDataInfo($cacheValue);
           			if ($this->filterDirtyData($cacheValue, $cacheKey, $dataInfo)) {
           				return $cacheValue;
           			}
                }
            }
        }      
        $fields = self::getField($fields);
        $this->daoHelper->registerEntity($this->mainTable, $this->entity); // 注册实体到数据库操作 
        // 读取失败返回null
        $object = $this->daoHelper->fetchObj($this->mainTable, $fields, $conditionInfo['where']);
        if ($this->cache && is_object($object)) {
            $dataInfo = $this->getDataInfo($object); 
            $cacheKeys = $dataInfo['cacheKeys'];
			$primaryKey = is_array($dataInfo['primaryKey']) 
				? end($dataInfo['primaryKey']) : $dataInfo['primaryKey']; // 主键字段	
       		$ok = true; // 是否更新缓存成功          
            foreach ($cacheKeys as $cacheName => $cacheKey) {
                if ($cacheName == 'PRIMARY') {
                	if ($this->filterDirtyData($object, $cacheKey, $dataInfo)) {			
						$setResult = $this->cache->set($this->getCacheKey($cacheKey), $object);
					} else {
						$ok = false;
					}
                } else {
                	$data = $this->cache->get($this->getCacheKey($cacheKey)); 	
                    if ($data !== false) { // 获取缓存成功, 将新数据添加到缓存中
                    	if (in_array($object, $data)) { // 已经存在缓存中则跳过
             				continue;
                    	}
                    	$newData = array();
                    	foreach ($data as $row) {
                    		$newData[$row->$primaryKey] = $row;
                    	}	
	                    if (!$this->filterDirtyData($object, $cacheKey, $dataInfo)) { // 有脏数据	
	                    	$ok = false;
							break;
						}
                        $newData[$object->$primaryKey] = $object;	        
                     	$setResult = $this->cache->set($cacheKey, $newData);
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
		          	$this->cache->delete($this->getCacheKey($cacheKey));
		      	}
			}
        }
        return is_object($object) ? $object : null;
    }

    /**
     * 根据主键列表获取对象列表
     *
     * @param   $primaryList    array           主键
     * @param   $fields         mix             要获取的属性列表
     *
     * <code>
     * $testDao = \dao\Test::singleton(); // 获取对象单例
     * $primaryList = array( // 联合主键
     *  array(
     *      'age'   => '0',
     *      'id'    => 1,
     *      'sex'   => 1,
     *  ),
     *  array(
     *      'age'   => '0',
     *      'id'    => 1,
     *      'sex'   => 10,
     *  )
     * );
     * 或
     * $primary = array( // 单一主键
     *  1,2,3,5
     * );
     * $testEtt = $testDao->readListByPrimary($primaryList);
     * </code>
     *
     * @return array
     */
    public function readListByPrimary($primaryList, $fields = null)
    {
        // 检查查询条件
        $conditionInfo = $this->getConditionInfo($primaryList);
        if ($conditionInfo === false || empty($conditionInfo['primary'])) {
        	return array();
        }
        $list = array();
        if ($this->cache) {
            $cacheKeys = $conditionInfo['keys'];  
            $cacheReturn = true;
            foreach($cacheKeys as $cacheKey) {
                $cacheValue = $this->cache->get($this->getCacheKey($cacheKey));        
                if (false === $cacheValue) {
                    $cacheReturn = false;
                    break;
                } elseif ($cacheValue) {
                	if (is_object($cacheValue)) {
	                	if ($this->filterDirtyData($cacheValue, $cacheKey)) {
	           				$list[] = $cacheValue;
	           			} else { // 脏数据
	           				$cacheReturn = false;
                   	 		break;
	           			}
                	} else {
                		$list = array_merge($list, $cacheValue);
                	}
                }
            }
            if ($cacheReturn) {
                return $list;
            }
        }
        $fields = self::getField($fields);
        $this->daoHelper->registerEntity($this->mainTable, $this->entity); // 注册实体到数据库操作 
        $list = $this->daoHelper->fetchAll($this->mainTable, $fields, $conditionInfo['where']);     
        if ($this->cache) {
        	$primaryId = (count($this->tableMap['primary']) == 1) 
        		? reset($this->tableMap['primary']) : null;
            foreach($list as $object) {
                $dataInfo = $this->getDataInfo($object);
                if (is_object($object) && !empty($dataInfo['cacheKeys'])) {
                    $ok = true; // 是否更新缓存成功
                	foreach ($dataInfo['cacheKeys'] as $cacheName => $cacheKey) {
                    	if ($cacheName == 'PRIMARY') {
	                    	if ($this->filterDirtyData($object, $cacheKey, $dataInfo)) {			
								$this->cache->set($this->getCacheKey($cacheKey), $object);
							} else {
								$ok = false;
							}
                		} else {
                			$data = $this->cache->get($this->getCacheKey($cacheKey));
                    		if ($data !== false) {
                    			if (in_array($object, $data)) { // 已经存在缓存中则跳过
             						continue;
                    			}
                    			if (!$this->filterDirtyData($object, $cacheKey, $dataInfo)) { // 有脏数据	
	                    			$ok = false;
									break;
								}
                    			$newData = array();
                    			foreach ($data as $row) {
                    				$newData[$row->$primaryId] = $row;
                    			}	
                        		$newData[$object->$primaryId] = $object;	        
                     			$setResult = $this->cache->set($cacheKey, $newData);
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
		          			$this->cache->delete($this->getCacheKey($cacheKey));
		      			}
					}
                }
            }
        }
        return $list;
    }

    /**
     * 根据索引获取对象列表
     *
     * @param   array   	$condition     	索引
     * @param   bool   		$one     		是否只取一条数据
     * 
     * <code>
     * $testDao = \dao\Test::singleton(); // 获取对象单例
     * $index = array( // 读取多个索引
     *  array(
     *      'age'   => '0',
     *      'sex'   => 1,
     *  ),
     *  array(
     *      'age'   => '0',
     *      'sex'   => 10,
     *  )
     * );
     * 或
     * $index = array( // 读取单个索引
     *      'age'   => '0',
     *      'sex'   => 1,
     * );
     * 
     * $testEtt = $testDao->readListByIndex($index);
     * </code>
     *
     * @throws
     * @return  array | object
     */
    public function readListByIndex($condition, $one = false)
    { 	
    	// 检查查询条件
    	$indexs = $this->tableMap['indexArr']; // 索引列表
    	unset($indexs['PRIMARY']);
    	$fields = array();
    	$firstElement = reset($condition);
    	$cacheKeys = array();
    	$where = array();
    	$cacheKeyHead = "`{$this->mainTable}`"; // 缓存key头部
    	if (is_array($firstElement)) { // 二维数组
    		$fields = array_keys($firstElement);
    		sort($fields);
    		$fieldCount = count($fields) * 2;
    		foreach ($condition as $conditionArr) {		
    			if (count(array_merge($fields, array_keys($conditionArr))) != $fieldCount) {
    				return false;
    			}
    			$cacheKeyBody = array(); // 缓存key 字段体
    			$whereElement = array();
    			foreach ($fields as $field) {
    				$conditionElement = $conditionArr[$field];
    				if (is_iteratable($conditionElement)) {
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
	    		for($cacheKeyBodyIndex = 0; $cacheKeyBodyIndex < count($cacheKeyBody); $cacheKeyBodyIndex++) {
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
    			if (is_iteratable($conditionElement)) {
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
    		for($cacheKeyBodyIndex = 0; $cacheKeyBodyIndex < count($cacheKeyBody); $cacheKeyBodyIndex++) {
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
    	if (!$queryIndexName = array_search($fields, $indexs)) {
    		return false;
    	}
    	$cacheKeys = array_unique($cacheKeys); // 缓存的key列表   	
    	$where = implode(' OR ', array_unique($where));      
    	if (empty($cacheKeys) || empty($where)) {
            throw new $this->exception('查询条件错误');
        } 
        $list = array();
        $primaryKey = (count($this->tableMap['primary']) == 1) ? reset($this->tableMap['primary']) : null;
        if ($this->cache) {
            $cacheReturn = true; // 是否从缓存中获取
            foreach($cacheKeys as $cacheKey) {	
                $cacheValue = $this->cache->get($this->getCacheKey($cacheKey));
                if (false === $cacheValue) { // 从缓存获取失败
                    $cacheReturn = false;
                    break;
                } elseif ($cacheValue) {
                	if ($primaryKey && is_object($cacheValue)) {
                		if ($this->filterDirtyData($cacheValue, $cacheKey)) {
	           				$list[$cacheValue->$primaryKey] = $cacheValue;
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
        $this->daoHelper->registerEntity($this->mainTable, $this->entity); // 注册实体到数据库操作     
        $list = $this->daoHelper->fetchAll($this->mainTable, '*', $where);
        $return = array();
        if ($this->cache) {
        	$ok = true; // 是否更新缓存成功
        	$indexCaches = array(); // 索引缓存key列表     	
            foreach($list as $object) {
            	$dataInfo = $this->getDataInfo($object);		
                if (!empty($dataInfo['cacheKeys'])) {       	
                    foreach ($dataInfo['cacheKeys'] as $cacheName => $cacheKey) {
                    	if (!$this->filterDirtyData($object, $cacheKey, $dataInfo)) {			
							$ok = false;
							break;
						}
                       	if ($cacheName == 'PRIMARY') { // 主键
	                       	$this->cache->set($this->getCacheKey($cacheKey), $object);  		
                       	} elseif ($queryIndexName == $cacheName) { // 指定查询的索引
                       		$indexCaches[$cacheKey][$object->$primaryKey] = $object;
                       	}
                    }
                }       
                if ($primaryKey) {
                	$return[$object->$primaryKey] = $object;
                } else {
                	$return[] = $object;
                }
            }   
            if ($ok) foreach ($indexCaches as $cacheKey => $data) {         		
            	$setResult = $this->cache->set($this->getCacheKey($cacheKey), $data);	  	
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
	          		$this->cache->delete($this->getCacheKey($cacheKey));
	           	}
			} else {
				// 缓存空数组
	            if (is_iteratable($cacheKeys)) foreach ($cacheKeys as $cacheKey) {
	            	$this->cache->set($this->getCacheKey($cacheKey), array());
	            }
			}
        } else {
        	foreach($list as $object) {
                $return[$object->$primaryKey] = $object;
            }
        }
        return $one ? reset($return) : $return;
    }

    /**
     * 根据主键列表获取对象列表
     *
     * @param   string      $where      where 条件
     * @param   mix         $fields     要获取的属性列表
     * @param   string      $table      表名
     * 
     * @throws
     * @return  array
     */
    public function readListByWhere($where = 1, $fields = null, $table = null)
    {
        if (empty($table)) {
        	$table = $this->mainTable;
        	$this->daoHelper->registerEntity($this->mainTable, $this->entity); // 注册实体到数据库操作 
        }
        return $this->daoHelper->fetchAll($table, self::getField($fields), $where);
    }
    
	/**
     * 根据sql读取数据(查)
     *
     * @param   mix         $fields     要获取的属性列表
     * @param   string      $table      表名
     *
     * @throws
     * @return  array | null
     */
    public function readDataBySql($sql, $table = null, $param = array())
    {
        $dataList = $this->daoHelper->fetchBySql($sql, $table, $param);
        return $dataList;
    }

    /**
     * 批量创建对象
     *
     * 注意：批量创建不会更新实体的ID，也不会对实体执行缓存清理操作
     * 如有需要请改为循环调用create方法。
     *
     * @param   array   $objectList  实体对象数组
     * @see create
     *
     * @return bool
     */
    public function createList($objectList)
    {
        // TODO
        
    }

    /**
     * 检查当前是否处于事物中
     *
     * @return bool
     */
    public function inTransaction()
    {
        return $this->daoHelper->transactionNum > 0;
    }

    /**
     * 开始一个MySQL事务
     *
     * @return void
     */
    public function begin()
    {
    	if (!SWITCH_MYSQL_TRANSACTION) {
    		return ;
    	}
        if (!$this->inTransaction()) {
            $this->cache->begin();
            $this->daoHelper->begin();
        }
        $this->daoHelper->transactionNum += 1;
        return;
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
        if ($this->inTransaction()) {
            $this->daoHelper->rollBackNum += 1;
            if (($this->daoHelper->rollBackNum + $this->daoHelper->commitNum) == $this->daoHelper->transactionNum) {
                $this->cache->rollBack();
                $this->daoHelper->rollBack();
                $this->daoHelper->rollBackNum = $this->daoHelper->commitNum = 0;
                $this->daoHelper->transactionNum = 0;
            }
        }
        return;
    }

    /**
     * 提交事务
     *
     * @return void
     */
    public function commit()
    {
    	if (!SWITCH_MYSQL_TRANSACTION) {
    		return ;
    	}
        if ($this->inTransaction()) {
            $this->daoHelper->commitNum += 1;
            if (($this->daoHelper->rollBackNum + $this->daoHelper->commitNum) == $this->daoHelper->transactionNum) {
                if ($this->daoHelper->rollBackNum > 0) { // 发生了交叉事物
                    throw new $this->exception('数据库内部错误');
                }
                $this->daoHelper->rollBackNum = $this->daoHelper->commitNum = $this->daoHelper->transactionNum = 0;
                $this->daoHelper->commit();
                $this->cache->commit();
            }
        }
        return;
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

    /**
     * 根据数据获取数据信息详情
     *
     * @param   object  $data 	数据
     *
     * @return array
     */
    private function getDataInfo($data)
    {
        if (is_object($data)) {
            $cacheByPrimary = true; // 是否根据主键缓存
            $primaryKey = $this->getPrimaryKeyByData($data); // 根据数据获取主键字段
            $where = array();
        	if (!empty($data->$primaryKey)) {
        		$where[] = "`{$primaryKey}` = '{$data->$primaryKey}'";
        	} else {
        		unset($data->$primaryKey);
             	$cacheByPrimary = false;
          	}
            $where = implode(' AND ', $where);
            $entityClass = get_class($data);
            $dataArr = get_object_vars($data);
            $fields = array_keys($dataArr);
            $values = array_values($dataArr);
            $cacheKeys = $this->getCacheKeyByData($data);
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
            'table'             => $entityClass::MAIN_TABLE,            // 表
            'fields'            => $fields,                             // 字段
            'values'            => $values,                             // 值
            'dataArr'           => $dataArr,              				// 字段 => 值
        );
    }

    /**
     * 根据实体获取主键
     *
     * @param  object	$data  	实体对像
     *
     * @return string|null
     */
    private function getPrimaryKeyByData($data)
    {
        $entityClass = get_class($data);
        $primaryKey = $entityClass == 'stdClass' ? 'id' : $entityClass::PRIMARY_KEY;
        return $primaryKey;
    }

    /**
     * 根据数据获取缓存键
     *
     * @param   object  $data   数据对象
     *
     * @return array|bool
     */
    private function getCacheKeyByData($data)
    {
        $entityClass = get_class($data);       
    	if (!method_exists($data, 'getTableInfo')) {
    		return false;
    	}
    	$daoHelper = $this->daoHelper;
        $getTableInfo = $entityClass::getTableInfo($entityClass::MAIN_TABLE, $daoHelper::$dbName);
        $tableKey = '`' . $entityClass::MAIN_TABLE . '`';
        $indexCacheKeyList = array();
        $unAdd = false;
        foreach($getTableInfo['indexArr'] as $indexName => $fields) {
            $indexCacheKey = $tableKey;
            foreach($fields as $field) {
                if (!isset($data->$field)) {
                	$unAdd = true;
                    continue;
                }
                $indexCacheKey .= "[`$field`='{$data->$field}']";
            }
            $unAdd or $indexCacheKeyList[$indexName] = $indexCacheKey;
        }
        return $indexCacheKeyList;
    }

    /**
     * 获取条件信息
     *
     * @param   mix         $condition    		条件
     * @param   string      $table       		 表名
     * @param   array       $primaryKey   		主键
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
     */
    protected function getConditionInfo($condition, $table = null, $primaryKey = null)
    {
        $primary 	= false;	// 是否为主键
        $index 		= false;	// 是否为索引
        $cache 		= false;  	// 是否可缓存
        $keys 		= null;    	// 缓存键数组  格式: `user`[`id`=1][`name`='xiao']
        $fieldMap 	= array();  // 字段 值
        $primaryKey or $primaryKey = array_map('trim', explode(',', $this->primaryKey)); // 主键key
        $table or $table = $this->mainTable; // 表名
        if (empty($table) || !is_string($table)) {
        	return false;
        }
        if (!is_array($condition)) { // 值必须为主键, 而且主键为单一字段
            if (count($primaryKey) == 1) {
                $primaryKey = array_pop($primaryKey);
                $primary = true;
                $cache = true;
                $keys[] = "`{$table}`[`$primaryKey`='$condition']";
                $fieldMap[$primaryKey] = $condition;
            } else {
                return false;
            }
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
            foreach($condition as $key => $value) {
                if (is_numeric($key) && !is_array($value)) {
                    if (count($primaryKey) != 1) {
                        return false;
                    } else {
                        $primary = true;
                        $cache = true;
                        $fieldMap[end($primaryKey)][] = $value;
                    }
                } else {
                    if (is_array($value)) {
                        $cacheKey = "`{$table}`";
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
                if (count($fieldMap) == count($primaryKey) && !array_diff(array_keys($fieldMap), $primaryKey)) {
                    $key = "`{$table}`";
                    foreach($primaryKey as $field) {
                        if (is_array($fieldMap[$field])) {
                            if (count($primaryKey == 1)) {
                                foreach($fieldMap[$field] as $val) {
                                    if (preg_match("/(^\[)(.*)(\])$/", $val, $matches)) {
                                        $conPrimary = false;
                                        break;
                                    }
                                    $keys[] = "`{$table}`[`$field`='$val']";
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
                	$tableStructure = $this->tableMap;
                    ksort($fieldMap);
                    if (!empty($tableStructure['indexStr']) && $indexStr = array_search(implode(',', array_keys($fieldMap)), $tableStructure['indexStr'])) {
                        $cache = true;
                        $index = true;
                        $key = "`{$table}`";
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
        foreach($fieldMap as $field => $value) {
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
                $where[] = "`$field` = '{$value}'";
            }
        }
        if (empty($where) && $conPrimary) {
            $count = count(end($fieldMap));
            for ($i = 0; $i < $count; $i++) {
                $tmp = array();
                foreach($primaryKey as $field) {
                    $tmp[] = "`$field`='{$fieldMap[$field][$i]}'";
                }
                $where[] = '(' . implode(' AND ', array_unique($tmp)) . ')';
            }
            $excision = ' OR ';
        }
        return array(
            'primary'           => $primary,                                    	// 是否为主键
            'index'             => $index,                                      	// 是否为索引
            'cache'             => $cache,                                      	// 是否可缓存
            'keys'              => empty($keys) ? array() : array_unique($keys),	// 缓存的keys
            'where'             => implode($excision, array_unique($where)),    	// sql where  // 主键缓存key
        );
    }

    /**
     * 根据映射关系构造对象改变值数组(已废弃)
     *
     * @param   entity      $object     实体对象
     * @param   array       $map        映射关系数组
     *
     * @return stdClass 返回包含setFields和arrFields属性的stdClass对象
     */
    final protected function getChangeByMap(&$object, $map)
    {
        // 获取对象改变的内容
        $setArr = $object->setArr;
        $addArr = $object->addArr;
        $minArr = $object->minArr;
        $maxArr = $object->maxArr;
        $setFields = array();
        $addFields = array();
        $minFields = array();
        $maxFields = array();
        foreach ($map as $field) {
            if (isset($setArr[$field])) {
                $setFields[$field] = $setArr[$field];
            }
            if (isset($addArr[$field])) {
                $addFields[$field] = $addArr[$field];
            }
            if (isset($minArr[$field])) {
                $minFields[$field] = $minArr[$field];
            }
            if (isset($maxArr[$field])) {
                $maxFields[$field] = $maxArr[$field];
            }
        }
        $change = new \stdClass();
        $change->setFields = $setFields;
        $change->addFields = $addFields;
        $change->minFields = $minFields;
        $change->maxFields = $maxFields;
        return $change;
    }
    
	/**
     * 执行sql语句
     *
     * @param  string  $sql	 sql 
     *
     * @return bool
     */
    public function execBySql($sql)
    {
        return $this->daoHelper->execBySql($sql);
    }
    
	/**
     * 按主键构造带索引的列表
     *
     * @param 	array  	$list 	实体列表
     * @param 	string 	$key  	构造列表的键名（属性名）
     *
     * @return array
     */
    public function refactorListByKey(array $list, $key = null)
    {
        $refactorProp = null === $key ? $this->primaryKey : $key;
        $return = array();
        foreach ($list as $row) {
            $return[$row->$refactorProp] = $row;
        }
        return $return;
    }


    /**
     * 批量插入数据
     *
     * @param array $data
     * @return bool
     */
    public function insertList($data)
    {
        $sqlFields = array_keys($data[0]);
        $sql       = 'insert into ' . $this->mainTable . ' (`' . implode('`,`', $sqlFields) . '`) values ';
        $insertArr = [];
        foreach ($data as $row) {
            $value       = array_values($row);
            $insertArr[] = "('" . implode("','", array_map('addslashes', $value)) . "')";
        }
        $sql .= implode(',', $insertArr);
        return $this->execBySql($sql);
    }

}