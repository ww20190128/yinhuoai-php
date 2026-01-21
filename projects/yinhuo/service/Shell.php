<?php
namespace service;

/**
 * Shell 逻辑层
 *
 * @package service
 */
class Shell extends ServiceBase
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
     * @return Shell
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Shell();
        }
        return self::$instance;
    }
    
    /**
     * 清空动态缓存
     *
     * @return bool
     */
    public function flushCache()
    {
        $shellDao = \dao\Shell::singleton();
        return $shellDao->flushCache();
    }

    /**
     * 初始化公式
     *
     * @return int
     *
     * @var int
     */
    public function initFormula()
    {
        $shellDao = $this->locator->getDao('Shell');
        $staticDataDao = \dao\StaticData::singleton();
        $formulaEntityList = $staticDataDao->readList('formula');
        $typeMap = array(
            '-2' => array(
                'desc'      => '四舍五入取整',
                'function'  => 'intval(round($str))',
                'type'      => 'int'
            ),
            '-1' => array(
                'desc'      => '向上取整',
                'function'  => 'intval(ceil($str))',
                'type'      => 'int'
            ),
            '0' => array(
                'desc'      => '向下取整',
                'function'  => 'intval(floor($str))',
                'type'      => 'int'
            ),
            // > 0  N, N位小数
        );
        $formulaStrList = array();
        $typeConstList = array();
        ksort($formulaEntityList);
        foreach($formulaEntityList as $formulaEntity) {
            $valueType = $formulaEntity->valueType;
            if (empty($typeMap[$valueType])) { // N,N位小数
                $fun = "round(\$str, $valueType)";
                $valueTypeDesc = 'N位小数';
                $varType = 'float';
            } else {
                $varType = 'int';
                $fun = $typeMap[$valueType]['function'];
                $valueTypeDesc = $typeMap[$valueType]['desc'];
            }
            $typeConstList[$formulaEntity->id] =
<<<BLOCK
    /**
     * 公式类型: $formulaEntity->desc
     *
     * 公式说明: $formulaEntity->explain
     *
     * @var $varType $valueTypeDesc
     */
     const TYPE_$formulaEntity->id = $formulaEntity->id;
BLOCK;
            $valueFormula = str_replace('$str', $formulaEntity->formula, $fun);
            $formulaStrList[$formulaEntity->id] =
<<<BLOCK
            case self::TYPE_$formulaEntity->id : // $formulaEntity->desc
                /** @noinspection PhpUndefinedVariableInspection */
                \$value = $valueFormula;
                break;
BLOCK;
        }
        $content = array();
        $content[] =
<<<BLOCK
<?php
/**
 * 公式
 */
class Formula
{

BLOCK;
        $content[] = implode("\n\n", $typeConstList);
        $content[] =
<<<BLOCK

    /**
     * 公式计算
     *
     * @param   int     \$id             公式id
     * @param   array   \$arguments      参数列表
     *
     * @return bool|mix
     */
    public static function calculate(\$id, \$arguments = array())
    {
        \$params = array();
        foreach(\$arguments as \$key => \$val) {
            \$params['arg' . (\$key + 1)] = \$val;
        }
        extract(\$params);
        switch (\$id) {
BLOCK;
        $content[] = implode("\n", $formulaStrList);
        $content[] =
            <<<BLOCK
            default:
                return false;
        }
        return \$value;
    }

BLOCK;
        $content[] = "}";
        $file = CONFIGS_PATH . 'static.formula.php';
        file_put_contents($file, implode("\n", $content));
        @chmod($file, 0777);
        if (strpos(exec("php -l $file"),"No syntax errors detected in") === false) {
            @unlink($file);
            return false;
        }
        return true;
    }
    
	/**
     * 初始化错误码
     *
     * @return int
     *
     * @var int
     */
    public function initErrorCode()
    {
        $shellDao = $this->locator->getDao('Shell');
        $staticDataDao = \dao\StaticData::singleton();
        $errorCodeEntityList = $staticDataDao->readList('error_code');
        $errorCodeStrList = array();
        ksort($errorCodeEntityList);
        $content = array();
        $content[] =
<<<BLOCK
<?php
/**
 * 错误码
 */
return array(
BLOCK;
        foreach($errorCodeEntityList as $errorCodeEntity) {
        	if (isset($errorCodeStrList[$errorCodeEntity->desc])) {
        		return false;
        	}
        	$content[] = "	'{$errorCodeEntity->desc}' => $errorCodeEntity->id,";
        }
        $content[] =
<<<BLOCK
);
BLOCK;
        $file = CONFIGS_PATH . 'error.conf.php';
        file_put_contents($file, implode("\n", $content));
        @chmod($file, 0777);
        if (strpos(exec("php -l $file"), 'No syntax errors detected in') === false) {
            @unlink($file);
            return false;
        }
        return true;
    }
    
	/**
     * 初始化表结构
     *
     * @return int
     */
    public function initTableStructure()
    {
    	if (posix_geteuid() == 0) {
            $user = cfg('server.environ.user');
            $pw = posix_getpwnam($user);
            if (!posix_setgid($pw['gid'])) {
                $errno = posix_get_last_error();
                fprintf(STDERR, "#%d: %s\n", $errno, posix_strerror($errno));
                exit(127);
            }
            if (!posix_setuid($pw['uid'])) {
                $errno = posix_get_last_error();
                fprintf(STDERR, "#%d: %s\n", $errno, posix_strerror($errno));
                exit(127);
            }
        }
    	$shellDao = \dao\Shell::singleton(); 
    	$databases = $this->Global_serverConf->database; 	
    	$tables = array();
    	foreach ($databases as $type => $database) {var_dump($database);
    		if (!in_array($type, array('desktop', 'public_admin_kgk', 'public-admin'))) {
    			continue;
    		}
    		$tables[$type] = $shellDao->getAllTables($type, $database['db_name']);
    	}
    	$tableInfoList = array();
    	if ($tables) foreach ($tables as $type => $tableList) {
    		if ($tableList) foreach ($tableList as $table) {
    			$tableName = $table->Name;
    			$tableInfoList[$type][$tableName] = getTableStructure($tableName, $type, true);
    		}
    	}
    	$file = CODE_PATH . 'configs' . DIRECTORY_SEPARATOR . 'tmp.table.conf.php';
    	$content = "<?php\nreturn ".str_replace("'CURRENT_TIMESTAMP'", "date('Y-m-d H:i:s')", var_export($tableInfoList, true)).";\n";		
    	$pathName = dirname($file);
        if (!is_dir($pathName)) {
            @mkdir($pathName, 0777, true);
        }
        $fp = fopen($file, 'w+');  
        if ($fp === false) {
    		return false;
		}
    	if (fwrite($fp, $content, strlen($content)) != strlen($content)) {
    		exit(127);
		}	
		fclose($fp);
		if (strpos(exec("php -l $file"), 'No syntax errors detected in') === false) {
            //@unlink($file);
            return true;
        }
    	return true;
    }
    
	/**
     * 同步表结构
     *
     * @return int
     */
    public function synTableStructure()
    {
    	if (posix_geteuid() == 0) {
            $user = cfg('server.environ.user');
            $pw = posix_getpwnam($user);
            if (!posix_setgid($pw['gid'])) {
                $errno = posix_get_last_error();
                fprintf(STDERR, "#%d: %s\n", $errno, posix_strerror($errno));
                exit(127);
            }
            if (!posix_setuid($pw['uid'])) {
                $errno = posix_get_last_error();
                fprintf(STDERR, "#%d: %s\n", $errno, posix_strerror($errno));
                exit(127);
            }
        }
    	$tmpFile = CODE_PATH . 'configs' . DIRECTORY_SEPARATOR . 'tmp.table.conf.php';
    	if (!is_file($tmpFile)) {
    		$this->initTableStructure();
    	}
		$file = CODE_PATH . 'configs' . DIRECTORY_SEPARATOR  . 'table.conf.php';	
		exec("cp -rf $tmpFile $file");
		if (!file_exists($file)) {
            //@unlink($file);
            return false;
        }
    	return true;
    }

	/**
     * 初始化静态数据
     *
     * @return bool
     */
    public function initData()
    {
    	ini_set('memory_limit', '2048M');
    	$dataDir = cfg('dir.static_data');
    	//$files = getFilesByDir($dataDir, '.xls');
    	$files = glob($dataDir . DS . '*.xls');
    	$readSv = new \service\Read();
        $staticDataDao = \dao\StaticData::singleton();
    	if (is_iteratable($files)) foreach ($files as $file) {
    		$result = $readSv->getDataByExcel($file);	
    		if (empty($result['status'])) {
    			echo "错误\n";
    			print_r($file);exit;
    		}
    		// 导入数据到数据库
    		// 清空原有的数据表
    		$staticDataDao->execBySql("TRUNCATE TABLE `{$result['table']}`");
    		$staticDataDao->addData($result['table'], array_map('trim', $result['fields']), $result['data']);
print_r($file);
echo "\n";
    	}
    	exit;
		$shellDao = $this->locator->getDao('Shell');
    	$databases = $this->Global_serverConf->database;
		$tables = $shellDao->getAllTables('static', $databases['static']['db_name']);
	
    	return ;
    }
    
	/**
     * 清空数据库
     *
     * @return bool
     */
    public function clearDb()
    {	
    	$shellDao = \dao\Shell::singleton();
    	$databases = $this->Global_serverConf->database;
    	$tables = array();
    	foreach ($databases as $type => $database) {
    		$tables[$type] = $shellDao->getAllTables($type, $database['db_name']);
    	}

    	unset($tables['static']);
    	unset($tables['log']);
		unset($tables['center']);
    	$commonDao = \dao\Common::singleton();
    	foreach ($tables as $list) {
    		foreach ($list as $row) {
    			if (in_array($row->Name, array('crontab'))) {
    				continue;
    			}
        		$commonDao->execBySql("TRUNCATE TABLE  `{$row->Name}`");
    		}
    	}
    	return true;
    }
    
	/**
     * 重载数据表
     * 
     * @param	string		$table  	数据表
     * @param	string		$entity  	实体名
     * 
     * @return bool
     */
    public function reloadTable($table, $entity)
    {
    	if (posix_geteuid() == 0) {
            $user = cfg('server.environ.user');
            $pw = posix_getpwnam($user);
            if (!posix_setgid($pw['gid'])) {
                $errno = posix_get_last_error();
                fprintf(STDERR, "#%d: %s\n", $errno, posix_strerror($errno));
                exit(127);
            }
            if (!posix_setuid($pw['uid'])) {
                $errno = posix_get_last_error();
                fprintf(STDERR, "#%d: %s\n", $errno, posix_strerror($errno));
                exit(127);
            }
        }
    	$shellDao = $this->locator->getDao('Shell');
    	$tableInfo = getTableStructure($table);
		// 加载实体
		$className = ucfirst($entity);
		if (!file_exists(ENTITY_PATH . $className . '.php')) {
			createEntity($table, $entity);
		}
		$file = explode("\n", file_get_contents(ENTITY_PATH . $className . '.php')); 
		if (!is_file(ENTITY_PATH . $className . '.php') || count($file) <= 3) {
			createEntity($table, $entity);
		}
		createEntity($table, 'tmp');
		$tmpFile = explode("\n", file_get_contents(ENTITY_PATH . 'Tmp.php'));
		$tmpFile['4'] = str_replace('Tmp', $className, $tmpFile['4']);
		$tmpFile['8'] = str_replace('Tmp', $className, $tmpFile['8']);
		array_pop($tmpFile);
		$flag = array_search('    // 表结构end', $file);
		if (empty($flag)) {
			$flag = array_search('// 表结构end', $file);
			if (empty($flag)) {
				return false;
			}
		}
		$selfFile = array_slice($file, $flag + 1);
		$newFile = array_merge($tmpFile, $selfFile);
		// 替换继承类
		$havaCreateModelFunc = false; // 是否有createModel方法
		foreach ($newFile as $row) {
			if (strpos($row, 'createModel')) {
				$havaCreateModelFunc = true;
			}
		}
		if (!empty($havaCreateModelFunc)) {
			foreach ($newFile as $key => $row) {
				if (strpos($row, 'extends EntityBase')) {
					$newFile[$key] = str_replace("EntityBase", "ModelBase", $row);
				}
			}
		}		
    	file_put_contents(ENTITY_PATH . $className . '.php', implode("\n", $newFile));
    	@unlink(ENTITY_PATH . 'Tmp.php');
    	$file = ENTITY_PATH . $className . '.php';
// 		if (strpos(exec("php -l $file"), 'No syntax errors detected in') === false) {
//             @unlink($file);
//             return false;
// 		}
    	if (!file_exists($file)) {
    	   return false;
    	}
		return true;
    }
	
	/**
     * 获取静态表
     *
     * @return int
     */
    public function getStaticTables($type = 'static')
    {
    	$shellDao = $this->locator->getDao('Shell'); 
    	$databases = $this->Global_serverConf->database;
    	$tables = $shellDao->getAllTables($type, $databases[$type]['db_name']);
    	$model = array();
    	$staticDataDao = \dao\StaticData::singleton();
    	foreach ($tables as $tableInfo) {
    		$table = $tableInfo->Name;
    		$tableStructure = getTableStructure($table, $type);
    		if (!empty($tableStructure['primary'][0])) {
    			$id = $tableStructure['primary'][0];
    		} else {
    			$id = 'id';
    		}
    		$sql = "SELECT max({$id}) as maxId, min({$id}) as minId, count(*) as count 
    			FROM `{$table}` WHERE 1;";	
    		$reuslt = $staticDataDao->readDataBySql($sql, $table);
    		if (empty($reuslt)) {
    			continue;
    		}
    		$reuslt = $reuslt[0];
    		$model[$table] = array(
    			'table' 		=> $table,
    			'desc'			=> $tableInfo->Comment,
    			'updateTime'	=> $tableInfo->Update_time,
    			'count'			=> empty($reuslt->count) ? 0 : $reuslt->count,
    			'minId'			=> empty($reuslt->minId) ? 0 : $reuslt->minId,
    			'maxId'			=> empty($reuslt->maxId) ? 0 : $reuslt->maxId,
    		);
    	}
    	return $model;
    }
}