<?php
namespace service\reuse;

/**
 * 多维数组与坐标互转    通用类
 *
 * @author wangwei
 */
class TreeBak extends \service\ServiceBase
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return Tree
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Tree();
        }
        return self::$instance;
    }
	
    /**
     * 按左右值排序(升序)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    public function sortBySide($row1, $row2)
    {
    	if ($row1['leftSide'] + $row1['rightSide'] < $row2['leftSide'] + $row2['rightSide']) {
    		return -1;
    	} else {
    		return 1;
    	}
    }
     
    /**
     * 根据左右值组装成树状结构
     *
     * @param  array  $data  二维数组
     *
     * @return array
     */
    public function sideToTree($data)
    {
    	$dataById = array(); // 根据id索引
    	$numMap = array();
    	if (!empty($data)) foreach ($data as $key => $row) {
    		$numMap[] = $row['leftSide'];
    		$numMap[] = $row['rightSide'];
    		$row['sublevels'] = array();
    		$dataById[$row['id']] = $row;
    	}
    	$numMap = array_unique($numMap);
    	sort($numMap);
    	$indexMap = array();
    	$maxLevel = 0; // 最大层级数
    	foreach ($numMap as $index) {
    		foreach ($dataById as $row) {
    			if ($row['leftSide'] <= $index &&  $row['rightSide'] >= $index) {
    				$indexMap[$index][$row['id']] = $row;
    			}
    		}
    		if (count($indexMap[$index]) > $maxLevel) {
    			$maxLevel = count($indexMap[$index]);
    		}
    	}
    	// 获取当前级别的父级列表
    	$getParents = function ($item, $data) {
    		$parentList = array();
    		if (!empty($data)) foreach ($data as $row) {
    			if ($row['id'] != $item['id'] && $item['leftSide'] >= $row['leftSide'] && $item['rightSide'] <= $row['rightSide']) {
    				$parentList[$row['id']] = $row;
    			}
    		}
    		return $parentList;
    	};
    	$levelMap = array(); // 根据层级索引的列表
    	$dataByIdBase = $dataById;
    	for ($level = $maxLevel; $level >= 1;  $level--) { // 从最上层开始查找, 查找完将上层去掉
    		if (!empty($dataById)) foreach ($dataById as $id => $row) {
    			if ($getParents($row, $dataById)) { // 判断是否为子类
    				continue;
    			}
    			$levelMap[$level][$id] = $row;
    		}
    		if (!empty($levelMap[$level])) foreach ($levelMap[$level] as $id => $row) {
    			unset($dataById[$id]);
    		}
    	}
    	// 同等级下进行排序
    	foreach ($levelMap as $key => $list) {
    		uasort($list, array($this, 'sortBySide'));
    		$levelMap[$key] = $list;
    	}
    	for ($level = 1; $level <= $maxLevel;  $level++) { // 从最小层级开始将数据赋值给上一级
    		if (!isset($levelMap[$level + 1])) {
    			if (!empty($levelMap[$level])) foreach ($levelMap[$level] as $key => $value) {
    				unset($levelMap[$level][$key]['leftSide'], $levelMap[$level][$key]['rightSide']);
    			}
    			break;
    		}
    		$parent = array_values($levelMap[$level + 1]); 	// 父级
    		$child 	= empty($levelMap[$level]) ? array() : array_values($levelMap[$level]); // 子级
    		// 将子级的数据赋值给父级
    		foreach ($child as $childKey => $row) {
    			$parentList = $getParents($dataByIdBase[$row['id']], $parent); // 判断是否为子类
    			if (!empty($parentList)) foreach ($parentList as $parentId => $value) {
    				foreach ($parent as $k => $v) {
    					if ($parentId == $v['id']) {
    						unset($row['leftSide'], $row['rightSide']);
    						$parent[$k]['sublevels'][] = $row;
    					}
    				}
    			}
    			unset($child[$childKey]);
    		}
    		$levelMap[$level + 1] = $parent;
    		unset($levelMap[$level]);
    	}
    	$levelMap = reset($levelMap);
    	return empty($levelMap) ? array() : array_values($levelMap);
    }
    
    /**
     * 将元素添加到树中
     * 
     * @return array
     */
    public function addItemToTree($item, $tree, $parentId)
    {
    	foreach ($tree as &$row1) {
    		if ($parentId == $row1['id']) { // 添加到子节点
    			array_push($row1['sublevels'], $item);
    			break;
    		} else {
    			foreach ($row1['sublevels'] as &$row2) {
    				if ($parentId == $row2['id']) { // 添加到子节点
    					array_push($row2['sublevels'], $item);
    					break 2;
    				} else {
    					foreach ($row2['sublevels'] as &$row3) {
    						if ($parentId == $row3['id']) { // 添加到子节点
    							array_push($row3['sublevels'], $item);
    							break 3;
    						} else {
    							foreach ($row3['sublevels'] as &$row4) {
    								if ($parentId == $row4['id']) { // 添加到子节点
    									array_push($row4['sublevels'], $item);
    									break 4;
    								} else {
    									foreach ($row4['sublevels'] as &$row5) {
    										if ($parentId == $row5['id']) { // 添加到子节点
    											array_push($row5['sublevels'], $item);
    											break 5;
    										} else {
    											foreach ($row5['sublevels'] as &$row6) {
    												if ($parentId == $row6['id']) { // 添加到子节点
    													array_push($row6['sublevels'], $item);
    													break 6;
    												} else {
    													foreach ($row6['sublevels'] as &$row7) {
    														if ($parentId == $row7['id']) { // 添加到子节点
    															array_push($row7['sublevels'], $item);
    															break 7;
    														} else {
    															foreach ($row7['sublevels'] as &$row8) {
    																if ($parentId == $row8['id']) { // 添加到子节点
    																	array_push($row8['sublevels'], $item);
    																	break 8;
    																} else {
    																	foreach ($row8['sublevels'] as &$row9) {
    																		if ($parentId == $row9['id']) { // 添加到子节点
    																			array_push($row9['sublevels'], $item);
    																			break 9;
    																		} else {
    																			foreach ($row9['sublevels'] as &$row10) {
    																				if ($parentId == $row10['id']) { // 添加到子节点
    																					array_push($row10['sublevels'], $item);
    																					break 10;
    																				} else {
    																					foreach ($row10['sublevels'] as &$row11) {
    																						if ($parentId == $row11['id']) { // 添加到子节点
    																							array_push($row11['sublevels'], $item);
    																							break 11;
    																						} else {
    																					
    																						}
    																					}
    																				}
    																			}
    																		}
    																	}
    																}
    															}
    														}
    													}	
    												}
    											}	
    										}
    									}	
    								}
    							}
    						}
    					}
    				}
    			}
    		}
    		
    	}
    	return $tree;
    }
    
    
    /**
     * 根据左右值组装成树状结构
     *
     * @param 	array 	$data 	多维数组
     *
     * @return array
     */
    public function treeToSide($data)
    {
    	$result = array();
    	$sideValue = 1;
    	foreach ($data as $key1 => $row1) {
    		$row1['leftSide'] = $sideValue++; // 1级元素赋左值
    		if (!empty($row1['sublevels'])) foreach ($row1['sublevels'] as $row2) {
    			$row2['leftSide'] = $sideValue++; // 2级元素赋左值
    			if (!empty($row2['sublevels'])) foreach ($row2['sublevels'] as $row3) {
    				$row3['leftSide'] = $sideValue++; // 3级元素赋左值
    				if (!empty($row3['sublevels'])) foreach ($row3['sublevels'] as $row4) {
    					$row4['leftSide'] = $sideValue++; // 4级元素赋左值
    					if (!empty($row4['sublevels'])) foreach ($row4['sublevels'] as $row5) {
    						$row5['leftSide'] = $sideValue++; // 5级元素赋左值
    						if (!empty($row5['sublevels'])) foreach ($row5['sublevels'] as $row6) {
    							$row6['leftSide'] = $sideValue++; // 6级元素赋左值
    							if (!empty($row6['sublevels'])) foreach ($row6['sublevels'] as $row7) {
    								$row7['leftSide'] = $sideValue++; // 7级元素赋左值
    								if (!empty($row7['sublevels'])) foreach ($row7['sublevels'] as $row8) {
    									$row8['leftSide'] = $sideValue++; // 8级元素赋左值
    									if (!empty($row8['sublevels'])) foreach ($row8['sublevels'] as $row9) {
    										$row9['leftSide'] = $sideValue++; // 9级元素赋左值
    										if (!empty($row9['sublevels'])) foreach ($row9['sublevels'] as $row10) {
    											$row10['leftSide'] = $sideValue++; // 10级元素赋左值
    											if (!empty($row10['sublevels'])) foreach ($row10['sublevels'] as $row11) {
    												$row11['leftSide'] = $sideValue++; // 11级元素赋左值
    												if (!empty($row11['sublevels'])) foreach ($row11['sublevels'] as $row12) {
    													$row12['leftSide'] = $sideValue++; // 12级元素赋左值
    													if (!empty($row12['sublevels'])) foreach ($row12['sublevels'] as $row13) {
    															
    													}
    													unset($row12['sublevels']);
    													$row12['rightSide'] = $sideValue++; // 赋右值
    													$keyName = array();
    													for ($i = 1; $i <= 12; $i++) {
    														$keyName[] = ${'row'.$i}['name'];
    													}
    													$keyName = implode('-', $keyName);
    													if (!empty($result[$keyName])) {
    														throw new $this->exception("标签树中有重名的标签{$keyName}");
    													} else {
    														$row12['level'] = 12;
    														$row12['parentId'] = $row11['id'];
    														$row12['structureName'] = $keyName;
    														$result[$keyName] = $row12;
    													}
    												}
    												unset($row11['sublevels']);
    												$row11['rightSide'] = $sideValue++; // 赋右值
    												$keyName = array();
    												for ($i = 1; $i <= 11; $i++) {
    													$keyName[] = ${'row'.$i}['name'];
    												}
    												$keyName = implode('-', $keyName);
    												if (!empty($result[$keyName])) {
    													throw new $this->exception("标签树中有重名的标签{$keyName}");
    												} else {
    													$row11['level'] = 11;
    													$row11['parentId'] = $row10['id'];
    													$row11['structureName'] = $keyName;
    													$result[$keyName] = $row11;
    												}
    											}
    											unset($row10['sublevels']);
    											$row10['rightSide'] = $sideValue++; // 赋右值
    											$keyName = array();
    											for ($i = 1; $i <= 10; $i++) {
    												$keyName[] = ${'row'.$i}['name'];
    											}
    											$keyName = implode('-', $keyName);
    											if (!empty($result[$keyName])) {
    												throw new $this->exception("标签树中有重名的标签{$keyName}");
    											} else {
    												$row10['level'] = 10;
    												$row10['parentId'] = $row9['id'];
    												$row10['structureName'] = $keyName;
    												$result[$keyName] = $row10;
    											}
    										}
    										unset($row9['sublevels']);
    										$row9['rightSide'] = $sideValue++; // 赋右值
    										$keyName = array();
    										for ($i = 1; $i <= 9; $i++) {
    											$keyName[] = ${'row'.$i}['name'];
    										}
    										$keyName = implode('-', $keyName);
    										if (!empty($result[$keyName])) {
    											throw new $this->exception("标签树中有重名的标签{$keyName}");
    										} else {
    											$row9['level'] = 9;
    											$row9['parentId'] = $row8['id'];
    											$row9['structureName'] = $keyName;
    											$result[$keyName] = $row9;
    										}
    									}
    									unset($row8['sublevels']);
    									$row8['rightSide'] = $sideValue++; // 赋右值
    									$keyName = array();
    									for ($i = 1; $i <= 8; $i++) {
    										$keyName[] = ${'row'.$i}['name'];
    									}
    									$keyName = implode('-', $keyName);
    									if (!empty($result[$keyName])) {
    										throw new $this->exception("标签树中有重名的标签{$keyName}");
    									} else {
    										$row8['level'] = 8;
    										$row8['parentId'] = $row7['id'];
    										$row8['structureName'] = $keyName;
    										$result[$keyName] = $row8;
    									}
    								}
    								unset($row7['sublevels']);
    								$row7['rightSide'] = $sideValue++; // 赋右值
    								$keyName = array();
    								for ($i = 1; $i <= 7; $i++) {
    									$keyName[] = ${'row'.$i}['name'];
    								}
    								$keyName = implode('-', $keyName);
    								if (!empty($result[$keyName])) {
    									throw new $this->exception("标签树中有重名的标签{$keyName}");
    								} else {
    									$row7['level'] = 7;
    									$row7['parentId'] = $row6['id'];
    									$row7['structureName'] = $keyName;
    									$result[$keyName] = $row7;
    								}
    							}
    							unset($row6['sublevels']);
    							$row6['rightSide'] = $sideValue++; // 赋右值
    							$keyName = array();
    							for ($i = 1; $i <= 6; $i++) {
    								$keyName[] = ${'row'.$i}['name'];
    							}
    							$keyName = implode('-', $keyName);
    							if (!empty($result[$keyName])) {
    								throw new $this->exception("标签树中有重名的标签{$keyName}");
    							} else {
    								$row6['level'] = 6;
    								$row6['parentId'] = $row5['id'];
    								$row6['structureName'] = $keyName;
    								$result[$keyName] = $row6;
    							}
    						}
    						unset($row5['sublevels']);
    						$row5['rightSide'] = $sideValue++; // 赋右值
    						$keyName = array();
    						for ($i = 1; $i <= 5; $i++) {
    							$keyName[] = ${'row'.$i}['name'];
    						}
    						$keyName = implode('-', $keyName);
    						if (!empty($result[$keyName])) {
    							throw new $this->exception("标签树中有重名的标签{$keyName}");
    						} else {
    							$row5['level'] = 5;
    							$row5['parentId'] = $row4['id'];
    							$row5['structureName'] = $keyName;
    							$result[$keyName] = $row5;
    						}
    					}
    					unset($row4['sublevels']);
    					$row4['rightSide'] = $sideValue++; // 赋右值
    					$keyName = array();
    					for ($i = 1; $i <= 4; $i++) {
    						$keyName[] = ${'row'.$i}['name'];
    					}
    					$keyName = implode('-', $keyName);
    					if (!empty($result[$keyName])) {
    						throw new $this->exception("标签树中有重名的标签{$keyName}");
    					} else {
    						$row4['level'] = 4;
    						$row4['parentId'] = $row3['id'];
    						$row4['structureName'] = $keyName;
    						$result[$keyName] = $row4;
    					}
    				}
    				unset($row3['sublevels']);
    				$row3['rightSide'] = $sideValue++; // 赋右值
    				$keyName = array();
    				for ($i = 1; $i <= 3; $i++) {
    					$keyName[] = ${row.$i}['name'];
    				}
    				$keyName = implode('-', $keyName);
    				if (!empty($result[$keyName])) {
    					throw new $this->exception("标签树中有重名的标签{$keyName}");
    				} else {
    					$row3['level'] = 3;
    					$row3['parentId'] = $row2['id'];
    					$row3['structureName'] = $keyName;
    					$result[$keyName] = $row3;
    				}
    			}
    			unset($row2['sublevels']);
    			$row2['rightSide'] = $sideValue++; // 赋右值
    			$keyName = array();
    			for ($i = 1; $i <= 2; $i++) {
    				$keyName[] = ${'row'.$i}['name'];
    			}
    			$keyName = implode('-', $keyName);
    			if (!empty($result[$keyName])) {
    				throw new $this->exception("标签树中有重名的标签{$keyName}");
    			} else {
    				$row2['level'] = 2;
    				$row2['parentId'] = $row1['id'];
    				$row2['structureName'] = $keyName;
    				$result[$keyName] = $row2;
    			}
    		}
    		unset($row1['sublevels']);
    		$row1['rightSide'] = $sideValue++; // 赋右值
    		$keyName = $row1['name'];
    		if (!empty($result[$keyName])) {
    			throw new $this->exception("标签树中有重名的标签{$keyName}");
    		} else {
    			$row1['parentId'] = 0;
    			$row1['level'] = 1;
    			$row1['structureName'] = $keyName;
    			$result[$keyName] = $row1;
    		}
    	}
    	return $result;
    }
}