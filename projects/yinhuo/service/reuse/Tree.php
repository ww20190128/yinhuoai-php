<?php
namespace service\reuse;

/**
 * 多维数组与坐标互转    通用类
 *
 * @author wangwei
 */
class Tree extends \service\ServiceBase
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
     * 根据左右值组装成树状结构
     *
     * @param 	array 	$data 	多维数组
     *
     * @return array
     */
    public function treeToArray(&$data, $resetIndex = false, $resetSublevelsIndex = true, $resetParentId = false)
    {
    	$commonSv = \service\Common::singleton();
    	$result = array();
    	$indexMap = array();
    	foreach ($data as $key1 => &$row1) {
    		if (!empty($row1['sublevels'])) foreach ($row1['sublevels'] as &$row2) {
    			if (!empty($row2['sublevels'])) foreach ($row2['sublevels'] as &$row3) {
    				if (!empty($row3['sublevels'])) foreach ($row3['sublevels'] as &$row4) {
    					if (!empty($row4['sublevels'])) foreach ($row4['sublevels'] as &$row5) {
    						if (!empty($row5['sublevels'])) foreach ($row5['sublevels'] as &$row6) {
    							if (!empty($row6['sublevels'])) foreach ($row6['sublevels'] as &$row7) {
    								if (!empty($row7['sublevels'])) foreach ($row7['sublevels'] as &$row8) {
    									if (!empty($row8['sublevels'])) foreach ($row8['sublevels'] as &$row9) {
    										if (!empty($row9['sublevels'])) foreach ($row9['sublevels'] as &$row10) {
    											if (!empty($row10['sublevels'])) foreach ($row10['sublevels'] as &$row11) {
    												if (!empty($row11['sublevels'])) foreach ($row11['sublevels'] as &$row12) {
    													if (!empty($row12['sublevels'])) foreach ($row12['sublevels'] as &$row13) {
    															
    													}
    												}
    												$result[$row11['id']] = $this->resetIndex($row11, $indexMap, $resetSublevelsIndex);
    											}
    											$result[$row10['id']] = $this->resetIndex($row10, $indexMap, $resetSublevelsIndex);
    										}
    										$result[$row9['id']] = $this->resetIndex($row9, $indexMap, $resetSublevelsIndex);
    									}
    									$result[$row8['id']] = $this->resetIndex($row8, $indexMap, $resetSublevelsIndex);
    								}
    								$result[$row7['id']] = $this->resetIndex($row7, $indexMap, $resetSublevelsIndex);
    							}
    							$result[$row6['id']] = $this->resetIndex($row6, $indexMap, $resetSublevelsIndex);
    						}
    						$result[$row5['id']] = $this->resetIndex($row5, $indexMap, $resetSublevelsIndex);
    					}	
    					$result[$row4['id']] = $this->resetIndex($row4, $indexMap, $resetSublevelsIndex);
    				}	
    				$result[$row3['id']] = $this->resetIndex($row3, $indexMap, $resetSublevelsIndex);
    			}	
    			$result[$row2['id']] = $this->resetIndex($row2, $indexMap, $resetSublevelsIndex);
    		}
    		$result[$row1['id']] = $this->resetIndex($row1, $indexMap, $resetSublevelsIndex);
    	}
    	if (!empty($resetIndex)) {
    		return $indexMap;
    	}
    	return $result;
    }
    
    public function resetIndex(&$item, &$indexMap, $resetSublevelsIndex = true)
    {
    	$list = empty($item['sublevels']) ? array() : $item['sublevels'];
    	if (!empty($resetSublevelsIndex)) {
    		uasort($list, array($this, 'sortByIndex'));
    	}
    	$index = 1;
    	foreach ($list as $key => &$row) {
    		if ($row['index'] != $index || $row['parentId'] != $item['id']) {
    			$row['index'] = $index;
    			$row['parentId'] = $item['id'];
    			$indexMap[$row['id']] = array(
    				'id'		 	=> $row['id'],
    				'index'		 	=> $index,
    				'parentId' 		=> $item['id'],
    			);
    		}
    		$index++;
    	}
    	$item['sublevels'] = array_values($list);
    	$result = $item;
    	unset($result['sublevels']);
    	return $result;
    }
    
    /**
     * 按index排序(升序)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    public function sortByIndex($row1, $row2)
    {
    	if ($row1['index'] < $row2['index']) {
    		return -1;
    	} elseif ($row1['index'] > $row2['index']) {
    		return 1;
    	} else {
    		return $row1['id'] < $row2['id'] ? -1 : 1;
    	}
    }
    
    /**
     * 获取子树
     * 
     * @return array
     */
    public function getSubTree($data, $id)
    {
    	$result = array();
    	foreach ($data as $key1 => $row1) {
    		if ($id == $row1['id']) {
    			return $row1;
    		}
    		if (!empty($row1['sublevels'])) foreach ($row1['sublevels'] as $row2) {
    			if ($id == $row2['id']) {
    				return $row2;
    			}
    			if (!empty($row2['sublevels'])) foreach ($row2['sublevels'] as $row3) {
    				if ($id == $row3['id']) {
    					return $row3;
    				}
    				if (!empty($row3['sublevels'])) foreach ($row3['sublevels'] as $row4) {
    					if ($id == $row4['id']) {
    						return $row4;
    					}
    					if (!empty($row4['sublevels'])) foreach ($row4['sublevels'] as $row5) {
    						if ($id == $row5['id']) {
    							return $row5;
    						}
    						if (!empty($row5['sublevels'])) foreach ($row5['sublevels'] as $row6) {
    							if ($id == $row6['id']) {
    								return $row6;
    							}
    							if (!empty($row6['sublevels'])) foreach ($row6['sublevels'] as $row7) {
    								if ($id == $row7['id']) {
    									return $row7;
    								}
    								if (!empty($row7['sublevels'])) foreach ($row7['sublevels'] as $row8) {
    									if ($id == $row8['id']) {
    										return $row8;
    									}
    									if (!empty($row8['sublevels'])) foreach ($row8['sublevels'] as $row9) {
    										if ($id == $row9['id']) {
    											return $row9;
    										}
    										if (!empty($row9['sublevels'])) foreach ($row9['sublevels'] as $row10) {
    											if ($id == $row10['id']) {
    												return $row10;
    											}
    											if (!empty($row10['sublevels'])) foreach ($row10['sublevels'] as $row11) {
    												if ($id == $row11['id']) {
    													return $row11;
    												}
    												if (!empty($row11['sublevels'])) foreach ($row11['sublevels'] as $row12) {
    													if ($id == $row12['id']) {
    														return $row12;
    													}
    													if (!empty($row12['sublevels'])) foreach ($row12['sublevels'] as $row13) {
    														if ($id == $row13['id']) {
    															return $row13;
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
    	return array();
    }
}