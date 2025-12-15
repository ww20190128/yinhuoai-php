<?php
namespace service;

/**
 * 分类
 * 
 * @author 
 */
class Classify extends ServiceBase
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
     * @return Classify
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Classify();
        }
        return self::$instance;
    }

    /**
     * 获取分类列表
     * 
     * @param $showHomePage 是否在首页显示
     *
     * @return array
     */
    public function getClassifyList($showHomePage = 0, $getPaperNum = false)
    {
        $classifyDao = \dao\Classify::singleton();
        $classifyEttList = $classifyDao->readListByIndex(array(
            'status' => 0,
        ));

        $modelList = empty($showHomePage) ? array(
            array(
                'id'    => 0,
                'name'  => '全部',
                'icon'  => '',
                'index' => 0,
            )
        ) : array();
        $classifyMap = array();
       	if (!empty($getPaperNum)) { // 获取分类下的测评数量
       		$classifyRelationDao = \dao\ClassifyRelation::singleton();
			$classifyIds = array_column($classifyEttList, 'id');
       		$where = "`classifyId` in ('" . implode("','", $classifyIds) . "');";
    		$classifyRelationEttList = $classifyRelationDao->readListByWhere($where);
    		foreach ($classifyRelationEttList as $classifyRelationEtt) {
    			$classifyMap[$classifyRelationEtt->classifyId][$classifyRelationEtt->testPaperId] = $classifyRelationEtt->testPaperId; 
    		}
       	}
        $commonSv = \service\Common::singleton();
        if (is_iteratable($classifyEttList)) foreach ($classifyEttList as $classifyEtt) {
            $model = $classifyEtt->getModel();
            if (empty($model['index'])) {
                continue;
            }
            if (!empty($showHomePage) && empty($model['showHomePage'])) {
                continue;
            }
            if (!empty($getPaperNum)) { // 获取分类下的测评数量
            	
            	if (empty($model['id']) || $model['id'] == 1) {
            		$model['testPaperNum'] = '';
            	} else {
            		$model['testPaperNum'] = empty($classifyMap[$model['id']]) ? 0 : count($classifyMap[$model['id']]);
            	}
            }
            $modelList[] = $model;
        }
        uasort($modelList, array($commonSv, 'sortByIndex'));
        return $modelList; 
    }
    
    /**
     * 按id排序(降序)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    private function sortById($row1, $row2)
    {
        if ($row1['id'] > $row2['id']) {
            return -1;
        } elseif ($row1['id'] < $row2['id']) {
            return 1;
        } else {
            return $row1['createTime'] > $row2['createTime'] ? -1 : 1;
        }
    }
    
    /**
     * 按price排序(降序)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    private function sortByPrice($row1, $row2)
    {
        if ($row1['price'] < $row2['price']) {
            return -1;
        } elseif ($row1['price'] > $row2['price']) {
            return 1;
        } else {
            return $row1['createTime'] < $row2['createTime'] ? -1 : 1;
        }
    }
    
    /**
     * 按saleNum排序(降序)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    private function sortBySaleNum($row1, $row2)
    {
        if ($row1['saleNum'] < $row2['saleNum']) {
            return -1;
        } elseif ($row1['saleNum'] > $row2['saleNum']) {
            return 1;
        } else {
            return $row1['price'] < $row2['price'] ? -1 : 1;
        }
    }
    
    /**
     * 获取分类列表
     * 
     * @return array
     */
    public function getListByClassify($classifyId, $info, $pageNum = 1, $pageLimit = 20)
    {
    	$testPaperDao = \dao\TestPaper::singleton();
    	// 上线的测评
    	$onlineMap = \constant\TestPaper::onlineMap();
    	$where = "`name` in ('" . implode("','", $onlineMap) . "');";
    	$onlineTestPaperEttList = $testPaperDao->readListByWhere($where);
    	$onlineIds = array_column($onlineTestPaperEttList, 'id');
    	$classifyName = '';
        if (!empty($classifyId)) { // 根据分类ID获取
            $classifyDao = \dao\Classify::singleton();
            $classifyEtt = $classifyDao->readByPrimary($classifyId);
            if (empty($classifyEtt) || $classifyEtt->status == \constant\Common::DATA_DELETE) {
                throw new $this->exception('分类已删除');
            }
            $classifyName = $classifyEtt->name;
            $testPaperIds = array(); // 测评ID
            if (strpos($classifyEtt->name, '免费') !== false) { // 获取免费测评
            	$testPaperEttList = $testPaperDao->readListByIndex(array(
            		'status' => 0,
            	));
            	foreach ($testPaperEttList as $key => $testPaperEtt) {
            		if ($testPaperEtt->price > 0 || $testPaperEtt->status == \constant\Common::DATA_DELETE) {
            			unset($testPaperEttList[$key]);
            			continue;
            		}
            	}
            	
            } else {
            	$classifyRelationDao = \dao\ClassifyRelation::singleton();
            	$classifyRelationEttList = $classifyRelationDao->readListByIndex(array(
            		'classifyId' => $classifyId,
            	));
            	if (empty($classifyRelationEttList)) {
            		return array(
            			'classifyName' => $classifyName,
            			'totalNum' => 0,
            			'list' => array(),
            		);
            	}
            	foreach ($classifyRelationEttList as $classifyRelationEtt) {
            		$testPaperIds[$classifyRelationEtt->testPaperId] = intval($classifyRelationEtt->index);
            	}
            	$testPaperEttList = $testPaperDao->readListByPrimary(array_keys($testPaperIds));
            	if (is_iteratable($testPaperEttList)) foreach ($testPaperEttList as $key => $testPaperEtt) {
            		if ($testPaperEtt->status == \constant\Common::DATA_DELETE) {
            			unset($testPaperEttList[$key]);
            			continue;
            		}
            	}
            }

            
        } else { // 获取全部
            $testPaperEttList = $testPaperDao->readListByIndex(array(
                'status' => 0,
            ));
            $classifyName = '全部';
        }
        $testPaperIds = array_column($testPaperEttList, 'id');

		// 取交集
  $testPaperIds = array_intersect($onlineIds, $testPaperIds);
        $testPaperEttList = $testPaperDao->readListByPrimary($testPaperIds);
        $testShareUrlBase = empty($this->frame->conf['web_url']) ? '' : $this->frame->conf['web_url'];
        $modelList = array();
        foreach ($testPaperEttList as $key => $testPaperEtt) {
            if (!empty($info['testPaperName'])) { // 有搜索关键词
                if (strpos($testPaperEtt->name, $info['testPaperName']) === false) {
                    unset($testPaperEttList[$key]);
                    continue;
                }
            }
            if (!empty($info['testPaperId'])) { // 有搜索关键词
            	if ($testPaperEtt->id != $info['testPaperId']) {
            		unset($testPaperEttList[$key]);
            		continue;
            	}
            }
            $testPaperModel = $testPaperEtt->getModel();
            // 生成分享二维码   测评链接 & 分享信息 加密  (分享用户uid   分享时间， 设备信息   ip地址  网络环境， 秘钥 )
            $url = $testShareUrlBase . "/detail?testPaperId={$testPaperEtt->id}&hasParams=1";
            $testPaperModel['url'] = $testShareUrlBase . "/detail?testPaperId={$testPaperEtt->id}";
            $modelList[$testPaperEtt->id] = $testPaperModel;
        }
        // 根据ID排序
        uasort($modelList, array(self::$instance, 'sortById'));
        if (!empty($info['sortType'])) { // 排序
            if ($info['sortType'] == 2) { // 热点
                uasort($modelList, array(self::$instance, 'sortBySaleNum'));
            } else { // 3 价格
                uasort($modelList, array(self::$instance, 'sortByPrice'));
            }
        }
        // 符合条件的总条数
        $totalNum = count($modelList);
        // 分页显示
        if ($pageNum > 0) {
            $modelList = array_slice($modelList, ($pageNum - 1) * $pageLimit, $pageLimit);
        }
        $testPaperIds = array();
        if (is_iteratable($modelList)) foreach ($modelList as $model) {
            $testPaperIds[] = intval($model['id']);
        }
        return array(
            'totalNum' => intval($totalNum),
            'list' => array_values($modelList),
        	'classifyName' => $classifyName,
        );
    }
    
    /**
     * 将测评加入分类
     *
     * @return array
     */
    public function addTestPapersToClassify($classifyId, $testPaperIds)
    {
    	$classifyDao = \dao\Classify::singleton();
    	$classifyEtt = $classifyDao->readByPrimary($classifyId);
    	if (empty($classifyEtt) || $classifyEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('分类已删除');
    	}
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperEttList = $testPaperDao->readListByPrimary($testPaperIds);
    	if (is_iteratable($testPaperEttList)) foreach ($testPaperEttList as $key => $testPaperEtt) {
    		if ($testPaperEtt->status == \constant\Common::DATA_DELETE) {
    			unset($testPaperEttList[$key]);
    		}
    	}
    	$classifyRelationDao = \dao\ClassifyRelation::singleton();
    	$classifyRelationEttList = $classifyRelationDao->readListByIndex(array(
    		'classifyId' => $classifyId,
    	));
    	$havaMap = array();
    	foreach ($classifyRelationEttList as $classifyRelationEtt) {
    		$havaMap[$classifyRelationEtt->testPaperId] = $classifyRelationEtt->index;
    	}
    	$classifyRelationDao = \dao\ClassifyRelation::singleton();
    	$now = $this->frame->now;
    	$index = empty($havaMap) ? 0: max($havaMap);
    	foreach ($testPaperEttList as $testPaperEtt) {
    		if (!empty($havaMap[$testPaperEtt->id])) {
    			continue;
    		}
    		$classifyRelationEtt = $classifyRelationDao->getNewEntity();
    		$classifyRelationEtt->classifyId = $classifyId;
    		$classifyRelationEtt->testPaperId = $testPaperEtt->id;
    		$classifyRelationEtt->updateTime = $now;
    		$classifyRelationEtt->createTime = $now;
    		$classifyRelationEtt->index = $index;
    		$classifyRelationDao->create($classifyRelationEtt);
    		$index++;
    	}	
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 将测评从分类中移除
     *
     * @return array
     */
    public function removeTestPapersFromClassify($classifyId, $testPaperIds)
    {
    	$classifyDao = \dao\Classify::singleton();
    	$classifyEtt = $classifyDao->readByPrimary($classifyId);
    	if (empty($classifyEtt) || $classifyEtt->status == \constant\Common::DATA_DELETE) {
    		throw new $this->exception('分类已删除');
    	}
    	$classifyRelationDao = \dao\ClassifyRelation::singleton();
    	$classifyRelationEttList = $classifyRelationDao->readListByIndex(array(
    		'classifyId' => $classifyId,
    	));
    	foreach ($classifyRelationEttList as $classifyRelationEtt) {
    		if (!in_array($classifyRelationEtt->testPaperId, $testPaperIds)) {
    			continue;
    		}
    		$classifyRelationDao->remove($classifyRelationEtt);
    	}
    	return array(
    		'result' => 1,
    	);
    }
    
    /**
     * 获取相关推荐
     *
     * @return array
     */
    public function getRecommendList($testOrderId)
    {
        $testPaperDao = \dao\TestPaper::singleton();
        $testPaperEttList = $testPaperDao->readListByIndex(array(
            'status' => 0,
        ));
        return array(
            'list' => array(),
        );
        
    }
    
}