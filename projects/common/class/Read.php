<?php
namespace service;

/**
 * Excel类封装 
 *  
 * @author
 */
class Read
{
    /**
     * 构造函数
     * 
     * @return void
     */
    public function __construct() 
    {    
    	ini_set('memory_limit', '2048M');
    }
    
	/**
     * 获取reader
     * 
     * @return void
     */
    public function getExcelReader() 
    {
		require_once LIB_PATH . "Excel" . DS . "reader.php";
        $reader = new \Spreadsheet_Excel_Reader();
        $reader->setOutputEncoding('UTF-8');
        $reader->setUTFEncoder('mb');
        return $reader;    
    }

	/**
     * 获取树状数据
     * 
     * @param	string		$file  	Exce文件
     * @return array
     */
    public function getTreeDataByExcel($file)
    {
        $excelReader = $this->getExcelReader();
        $excelReader->read($file);
    	if (empty($excelReader->boundsheets) || count($excelReader->boundsheets) <= 0 
    		|| empty($excelReader->boundsheets['0']['name'])) {		
        	return array('status' => false, 'error' => 'Excel解析错误');
        }
        $sheet = 0;  
        if (empty($excelReader->sheets[$sheet])) {
        	return array('status' => false, 'error' => 'Excel解析错误');
        }
        $dataList = $excelReader->sheets[$sheet]['cells'];
        $map = array();
        $id = 0;
        foreach ($dataList as $key => $list) {
        	foreach ($list as $level => $row) {
        		// 查找元素的父级id
        		$map[$level][$id] = array(
        			'name' 		=> $row,
        			'id'		=> ++$id,
        			'level' 	=> $level,
        			'key'		=> $key,
        			'parentId'  => 0,
        		);
        	}
        }
        foreach ($map as $level => $list) {
        	foreach ($list as $id => $row) {
        		if (!empty($map[$level - 1])) {
        			$parentArr = $map[$level - 1]; // 父级列表
        			$parentId = 0;
        			$parentName = '';
        			krsort($parentArr);
        			foreach ($parentArr as $v) {
        				if ($v['key'] <= $row['key']) {
        					$parentId = $v['id'];
        					$parentName = $v['name'];
        					break;
        				}
        			}
        			$map[$level][$id]['parentId'] = $parentId;
//         			$map[$level][$id]['parentName'] = $parentName;
        		}
        	}
        }
        $result = array();
        foreach ($map as $level => $list) {
        	foreach ($list as $id => $row) {
        		$result[$id] = $row;
        	}
        }
        return array(
        	'status' 	=> true,
        	'data' 		=> $map,
        	'table' 	=> $excelReader->boundsheets['0']['name'],
        );
    }
	
    /**
     * 获取数据
     *
     * @param	string		$file  	Exce文件
     * 
     * @return array
     */
    public function getDataByExcel($file)
    {
    	$excelReader = $this->getExcelReader();
    	$excelReader->read($file);
    	if (empty($excelReader->boundsheets) || count($excelReader->boundsheets) <= 0
    		|| empty($excelReader->boundsheets['0']['name'])) {
    		return array('status' => false, 'error' => 'Excel解析错误');
    	}
    	$sheet = 0;
    	if (empty($excelReader->sheets[$sheet])) {
    		return array('status' => false, 'error' => 'Excel解析错误');
    	}
    	$dataList = $excelReader->sheets[$sheet]['cells'];
    	$numRows = $excelReader->sheets[$sheet]['numRows'];
    	$numCols = $excelReader->sheets[$sheet]['numCols'];
    	$fieldMap = array();
    	if (isset($excelReader->sheets[$sheet+1]['cells'])) {
    		$fieldMapData = $excelReader->sheets[$sheet+1]['cells'];
    		if (!empty($fieldMapData)) foreach ($fieldMapData as $row) {
    			if (empty($row[1]) || empty($row[2])) continue;
    			$fieldMap[$row[1]] = $row[2];
    		}
    	}
    	if (empty($fieldMap)) {
    		return array('status' => false, 'error' => 'Excel解析错误');
    	}
    	$dataHeads = array_shift($dataList);
    	$data = array();
    	foreach ($dataList as $row) {
    		$tmp = array();
    		foreach ($dataHeads as $index => $name) {
    			$column = array_search($name, $fieldMap);
    			if (empty($column)) {
    				continue;
    			}
    			$cell = isset($row[$index]) ? $row[$index] : '';
    			if (strtolower(trim($cell)) == 'null') {
    				$cell = '';
    			}
    			$tmp[$column] = $cell;
    		}
    		$data[] = $tmp;
    	}
    	if (empty($data)) {
    		return array('status' => false, 'error' => 'Excel解析错误');
    	}
    	return array(
    		'status' 	=> true,
    		'data' 		=> $data,
    		'fields' 	=> array_keys($data['0']),
    		'table' 	=> $excelReader->boundsheets['1']['name'],
    	);
    }
    
	/**
     * 解析execl文件
     * 
     * @param	string		$file  	Exce文件
     * @return array
     */
    public function getDataByExcelJson($file)
    {
        $excelReader = $this->getExcelReader();
        $excelReader->read($file);
        $sheet = 0;    
        if (empty($excelReader->sheets[$sheet])) {
        	return array('status' => false, 'error' => 'Excel解析错误');
        }    
        $dataList = $excelReader->sheets[$sheet]['cells'];
        $numRows = $excelReader->sheets[$sheet]['numRows'];
        $numCols = $excelReader->sheets[$sheet]['numCols'];
        $dataHeads = array_shift($dataList); 	// 英文表头  
        $headDes = array_shift($dataList); 		// 中文表头
        $data = array();
        foreach ($dataList as $key => $row) {
            $tmp = array();
            foreach ($dataHeads as $index => $name) {
            	$cell = isset($row[$index]) ? $row[$index] : '';     
	          	if (strtolower(trim($cell)) == 'null') {
	               	$cell = '';
	       		}
            	if (is_numeric($cell)) {
	            	if (is_float($cell)) {
	            		$cell = (int)$cell;
	            	} else {
	            		$cell = (float)$cell;	
	            	}
	            }
	         	$tmp[$name] = $cell;
            }
            $data[] = $tmp;
        }
        if (empty($data)) {
        	return array('status' => false, 'error' => 'Excel解析错误');
        }
        return array(
        	'status' 	=> true,
        	'data' 		=> $data,
        	'fields' 	=> $dataHeads,
        	'table' 	=> $excelReader->boundsheets['1']['name'],
        );
    }
    
    /**
     * 获取题目数据
     *
     * @param	string		$file  	Exce文件
     *
     * @return array
     */
    public function getQuestionDataByExcel($file)
    {
    	$excelReader = $this->getExcelReader();
    	$excelReader->read($file);
    	if (empty($excelReader->boundsheets) || count($excelReader->boundsheets) <= 0
    		|| empty($excelReader->boundsheets['0']['name'])) {
    		return array('status' => false, 'error' => 'Excel解析错误');
    	}
    	$sheet = 0;
    	if (empty($excelReader->sheets[$sheet])) {
    		return array('status' => false, 'error' => 'Excel解析错误');
    	}
    	$dataList = $excelReader->sheets[$sheet]['cells'];
    	$dataHeads = array_shift($dataList);
    	$data = array();
		foreach ($dataList as $row) {
			$tmp = array();
			foreach ($dataHeads as $index => $name) {
				$cell = isset($row[$index]) ? $row[$index] : '';
				if (strtolower(trim($cell)) == 'null') {
					$cell = '';
				}
				$tmp[$name] = $cell;
			}
			$data[] = $tmp;
		}
		if (empty($data)) {
			return array('status' => false, 'error' => 'Excel解析错误');
		}
    	return array(
        	'status' 	=> true,
        	'data' 		=> $data,
        	'head' 		=> $dataHeads,
        );
    }
    
    /**
     * ����
     * 
     * @param
     */
    public function import($parameter)
    {
        if (!isset($parameter["tableId"]) || !isset($parameter["content"])
            || !isset(self::$TABLE_MAPPING[$parameter["tableId"]])
        ) return array(0, 'ParameterError');
        $truncate = empty($parameter["truncate"]) ? false : true;
        $tableId = $parameter["tableId"];
        $tableName = self::$TABLE_MAPPING[$parameter["tableId"]];
        $content = base64_decode($parameter["content"]);
        
        $tmpPath = CACHE_PATH . "importData" . DS;
        $filename = "{$tableName}.xls";
        file_put_contents($tmpPath . $filename, $content);

        // ��ȡEXCEL��д����Ӧ�ı�
        $installSv = $this->locator->getService("Installer");
        $installSv->setDir($tmpPath);
        $kvDao = $this->locator->getDao("KeyValue");
        try {
            $installSv->initStaticData($tableName, $filename, $truncate);
            // ���?�����
            if ($tableId == 100) { 
                $commonDao = $this->locator->getDao('Common');
                $createTable = <<<EOT
CREATE TABLE IF NOT EXISTS `game_updateData` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '��ݱ��',
  `ordinal` varchar(256) NOT NULL COMMENT '���к�',
  `data` longtext NOT NULL COMMENT '���',
  PRIMARY KEY (`id`),
  KEY `ordinal` (`ordinal`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='��ݸ�����ʱ��' AUTO_INCREMENT=1 ;
EOT;
                $commonDao->execBySql($createTable); // ������ݱ�
                
                $commonDao->setTable('game_updateData');
                $where = "1 order by `id` asc";
                $resultList = $commonDao->fetchAll('*', $where);
                if (empty($resultList)) {
                    return array(0, 'dataError');
                }
                $ordinal = isset($resultList['0']->ordinal) ? $resultList['0']->ordinal : null; // ���к�
                if (count($resultList) != substr($ordinal, 20)) {
                    return array(0, 'dataError');
                }
                $afterData = null;
                foreach ($resultList as $result) {
                    if (!preg_match('/^{.*}$/', $result->data)) {
                        return array(0, 'dataError');
                    }
                    $afterData .= substr($result->data, 1, -1);
                }           
                // ���� ��ѹ
                $data = bzdecompress(base64_decode($afterData));
                $result = $commonDao->execBySql($data); // ��ݸ���
                if (is_numeric($result) && $result) {
                    $kvDao->set('UPATE_DATA_ID', $ordinal); 
                } else {
                    return array(0, 'dataError');
                }
                // ����������к����ڶԱ�ʹ��
            }   
            // �����web����������
            $key = $this->system['date']['mktm']."clearConstcache";
            $kvDao->set('cache_key', $key);
            // ���memcache����
            $this->cache->flush();
            $cc = new \ConstCache;
            $cc->flush();
        } catch (\Exception $e) {
            return array(0, $e->getMessage());
        }

        return array(1, 'ok');
    }

    /**
     * 导出数据
     * 
     * @return array
     */
    public function exportData($dataList, $columns, $tableCommentName = '', $tableName = '')
    {
    	
    	$content = <<<XMLHEADER
<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook
  xmlns="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:o="urn:schemas-microsoft-com:office:office"
  xmlns:x="urn:schemas-microsoft-com:office:excel"
  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:html="http://www.w3.org/TR/REC-html40">
    
XMLHEADER;
    	$content .= "  <Worksheet ss:Name=\"{$tableCommentName}\">\n";
    	$content .= "    <Table>\n";
    	$content .= "      <Row ss:AutoFitHeight=\"0\">\n";
    	$fields = array();
    	$comments = array();
    	foreach ($columns as $comment => $field) {
    		$comments[$field] = $comment;
    		if (!empty($field)) {
    			$fields[] = $field;
    			$content .= '        <Cell>';
    			$content .= "<Data ss:Type=\"String\">{$comment}</Data>";
    			// $content .= "<Data ss:Type=\"String\">{$column->Field}</Data>";
    			// $content .= "<Comment><ss:Data>{$column->Comment}</ss:Data></Comment>";
    			$content .= "</Cell>\n";
    		}
    	}
    	$content .= "      </Row>\n";
    	foreach ($dataList as $data) {
    		$content .= "      <Row ss:AutoFitHeight=\"0\">\n";
    		foreach ($fields as $field) {
    			$cellData = $data[$field];
    			$dataType = is_numeric($cellData) ? "Number" : "String";
    			$content .= "        <Cell>";
    			$content .= "<Data ss:Type=\"{$dataType}\">{$cellData}</Data>";
    			$content .= "</Cell>\n";
    		}
    		$content .= "      </Row>\n";
    	}
    	$content .= "    </Table>\n";
    	$content .= "  </Worksheet>\n";
    
    	$content .= "  <Worksheet ss:Name=\"{$tableName}\">\n";
    	$content .= "    <Table>\n";

    	foreach ($comments as $comment => $field) {
    		$content .= "      <Row ss:AutoFitHeight=\"0\">\n";
    		$content .= "        <Cell>";
    		$content .= "<Data ss:Type=\"String\">{$comment}</Data>";
    		$content .= "</Cell>\n";
    		$content .= "        <Cell>";
    		$content .= "<Data ss:Type=\"String\">{$field}</Data>";
    		$content .= "</Cell>\n";
    		$content .= "      </Row>\n";
    	}
    	$content .= "    </Table>\n";
    	$content .= "  </Worksheet>\n";
    	$content .= "</Workbook>";
    	$result = array("content" => $content);
    	return $result;
    }

}