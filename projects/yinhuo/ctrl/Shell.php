<?php
namespace ctrl;

/**
 * shell主调度
 * 
 * @author wangwei
 */
class Shell extends CtrlBase
{
    /**
     * 前置操作
     *
     * @return bool
     */
    public function beforeFilter(){
        return true;
    }

    /**
     * 主函数
     *
     * @return void
     */
	public function main()
    {
    	$this->help();
	}

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct(__CLASS__);
        define('SHELL_NEWLINE', $this->frame->runType == \Bootstrap::RUN_MODE_SHELL ? "\n" : "<br>");
        $this->phpCgi = cfg('server.environ.php_cli');
    }
	
	/**
	 * 帮助信息
	 * 
	 * @var array
	 */
    private static $HELP_INFO = <<<EOT
Shell.help                                  帮助信息
Shell.flushCache                            清空缓存
ProcessManager.msgQueue                   	查看消息队列
Shell.checkCode         [f]                 检查语法错误  f 文件或目录
ProcessManager.main            [a]          服务器管理   a 操作(start|stop|status|reload|restart)
Shell.crontab         	                	执行计划任务(基于linux cron的crontab, 需要在linux下配置crontab)
Shell.dataCache                       		静态数据初始化
EpollManager.main                       	epoll服务器管理  a 操作(Gateway|Chat|Scene|Crontab|all)
EpollManager.epollStart                     epoll服务器启动  a 操作(Gateway|Chat|Scene|Crontab|all)
Synchronization.main                     	生成统计日志  time 操作(20160707-20160719)
EOT;

    /**
     * 帮助信息
     * 
     * @return void
     */
    public function help()
    {
        if ($this->frame->runType == \Bootstrap::RUN_MODE_SHELL) {
            echo str_repeat("-", 70) . SHELL_NEWLINE;
            echo "Usage: $this->phpCgi " . ROOT_PATH . "Shell/main.php -h [host] [cmd] [args]" . SHELL_NEWLINE . SHELL_NEWLINE;
            echo "[cmd] options:"  . SHELL_NEWLINE . SHELL_NEWLINE;
            echo self::$HELP_INFO . "\n";
            echo SHELL_NEWLINE. "Example: php Shell/main.php -h dev.com Shell.flushCache" . SHELL_NEWLINE;
          	//  echo SHELL_NEWLINE. "Example: php Shell/main.php -h dev.com Shell.queue -n Master -a start" . SHELL_NEWLINE;
            echo str_repeat("-", 70). SHELL_NEWLINE;
        } else {
        	// 检查ip限制
			$whiteList = empty($this->Global_serverConf->white_list) 
				? array() : $this->Global_serverConf->white_list;
			$whiteIps = ipList($whiteList); // 服务器白名单ip列表		
			$selfIp = \service\Client::getIP();		
			$mark = $this->frame->mark;
			if (!in_array($selfIp, $whiteIps) 
				&& !in_array($mark, array('dev', 'dev_ww', 'dev_ydp', 'tw_t1'))) {
//        		exit;
     		}
        	$shellDao = $this->locator->getDao('Shell');
    		$tables = $shellDao->getAllTables();    		
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>

<style>
li{
    margin-top:12px;
}
</style>
<div style="width:400px;height:500px;margin-left:auto;margin-right:auto;margin-top:30px;">
<ul>
    <li><a href="./index.php?op=Shell.checkEnvironment" target="_blank">检测php环境(phpinfo)</a></li>
    <li><a href="./index.php?op=Shell.flushCache" target="_blank">清空缓存(redis)</a></li>
    <li><a href="./index.php?op=Shell.flushRank" target="_blank">刷新排行榜</a></li>
    <li><a href="./index.php?op=ProcessManager.msgQueue" target="_blank">查看消息队列(msgQueue)
            <a href="./index.php?op=ProcessManager.msgQueue&do=1" target="_blank"><span style="color:red;">&nbsp;&nbsp;&nbsp;&nbsp;清空</span></a></a></li>
    <li><a href="./index.php?op=Shell.initFormula" target="_blank">初始化公式(static.formula.php)</a></li>
    <li><a href="./index.php?op=Shell.initErrorCode" target="_blank">初始化错误码(error.conf.php)</a></li>
    <li><a href="./index.php?op=Shell.initMaskWord" target="_blank">初始化屏蔽字</a></li>
    <li><a href="./index.php?op=Shell.dataTool" target="_blank">静态数据导入工具</a></li>
    <li><a href="./index.php?op=Shell.initData" target="_blank">初始化静态数据</a></li>
    <li><a href="./index.php?op=Shell.dataCache" target="_blank">初始化静态数据到缓存</a></li>
    <li><a href="./index.php?op=Shell.initTableStructure" target="_blank">初始化表结构(tmp.table.conf.php)
    	<a href="./index.php?op=Shell.synTableStructure" target="_blank"><span style="color:red;">
    		</br>同步(慎重！)(table.conf.php)</span></a></a></li>
    <li><a href="./index.php?op=Shell.initAll" target="_blank">全部初始化</a></li>
    <li><a href="./index.php?op=Shell.clearDb" target="_blank" onclick="return confirm('确定要清空数据库吗？');">清空数据库</a></li>
    <li><a href="./index.php?op=Shell.censusCode" target="_blank">查看xhprof性能分析日志</a></li>
    <form id="form_search" class="form" action = "./index.php?op=Shell.reloadTable" method="POST">
                    <select name='modifier'>
                        <?php
                        foreach($tables as $table) {
                            echo "<option selected value='$table->Name'>($table->Name)$table->Comment</option>";
                        }
                        ?>
                    </select><br>
                    <td>实体名:<td><input type="text" name="entity" />
		<input type="submit" value="重载数据表" />
	</form>
</ul>
<span style="color:red;">(注意：以上为工具接口，正式上线需要屏蔽)</span>
</div>
</body>
</html>
<?php
        }
        exit;
    }

    /**
     * 提示信息
     *
     * @param   string   $format    信息
     * @return void
     */
    private function prompt($format)
    {
    	$format = vsprintf($format, array_slice(func_get_args(), 1));
    	if ($this->frame->runType == \Bootstrap::RUN_MODE_SHELL) {
    		echo "\x1B[32m". $format . "\x1B[0m", PHP_EOL;
    	} else {
    		print_r($format);
    		echo "<pre>";
    	}
    }
    
    /**
     * 检测php环境
     *
     * @return void
     */
    public function checkEnvironment()
    {
    	phpinfo();
        exit;
    }

    /**
     * 清空动态缓存
     *
     * @return void
     */
    public function flushCache()
    {
        $shellSv = \service\Shell::singleton();
        $result = $shellSv->flushCache();
        $this->prompt("%s 缓存清空%s!", $this->serverMark, $result ? '成功' : '失败');
        return $result;
    }

    /**
     * 检查语法错误
     *
     * @return int
     */
    public function checkCode()
    {
        $file = empty($this->params->f) ? null : $this->params->f;
        $shellSv = $this->locator->getService('Shell');
        $result = $shellSv->checkCode($file);
        $this->prompt("%s 动态缓存清空%s!", $this->serverMark, $result ? '成功' : '失败');
        return;
    }

    /**
     * 检查语法错误
     *
     * @return int
     */
    public function checkCodeE()
    {
        $serverConfs = $this->Global_serverConfs;
        foreach($serverConfs as $serverId => $conf) {
            $this->halo->init($serverId);
        }
        return;
    }

    /**
     * 绿色通道
     *
     * @return mixed
     */
    public function greenChannel()
    {
    	$params = $this->params;
    	$threadSv = \service\Thread::singleton();
        $num = isset($params->num) ? $params->num : 0;
        return $threadSv->execute($num);
    }

    /**
     * 初始化公式
     *
     * @return void
     */
    public function initFormula()
    {
        $shellSv = $this->locator->getService('Shell');
        $result = $shellSv->initFormula();
        $this->prompt("%s 初始化公式%s!", $this->serverMark, $result ? '成功' : '失败');
        return $result;
    }
    
	/**
     * 初始化错误码
     *
     * @return void
     */
    public function initErrorCode()
    {
        $shellSv = $this->locator->getService('Shell');
        $result = $shellSv->initErrorCode();
        $this->prompt("%s 初始化错误码%s!", $this->serverMark, $result ? '成功' : '失败');
        return $result;
    }
    
	/**
     * 初始化表结构
     *
     * @return
     */
    public function initTableStructure()
    {
        $shellSv = \service\Shell::singleton();
        $result = $shellSv->initTableStructure();
        $this->prompt("%s 初始化表结构%s!", $this->serverMark, $result ? '成功' : '失败');
        return $result;
    }
    
	/**
     * 同步表结构
     *
     * @return
     */
    public function synTableStructure()
    {
        $shellSv = \service\Shell::singleton();
        $result = $shellSv->synTableStructure();
        $this->prompt("%s 表结构同步%s!", $this->serverMark, $result ? '成功' : '失败');
        return $result;
    }
    
	/**
     * 初始化静态数据
     *
     * @return void
     */
    public function initData()
    {
        $shellSv = \service\Shell::singleton();
        $result = $shellSv->initData();
        $this->prompt("%s 初始化静态数据%s!", $this->serverMark, $result ? '成功' : '失败');
        return $result;
    }
    
	/**
     * 全部初始化
     *
     * @return
     */
    public function initAll()
    {
    	$this->flushCache();
        $this->initTableStructure();
        $this->initFormula();
        $this->initErrorCode();
        $this->initData();
        exit;
    }
    
	/**
     * 清空所有缓存
     *
     * @return
     */
    public function flushAllCache()
    {    	
    	$this->flushCache();
        exit;
    }
    
	/**
     * 清空数据库
     *
     * @return
     */
    public function clearDb()
    {
    	//return ;
    	$shellSv = \service\Shell::singleton();
        $result = $shellSv->clearDb();
        $this->prompt("%s 清空数据库%s!", $this->serverMark, $result ? '成功' : '失败');
        return $result;
    }
    
	/**
     * 重载数据表
     *
     * @return
     */
    public function reloadTable()
    {
    	$params = $this->params;
    	$table = $params->modifier;
    	$entity = empty($params->entity) ? $table : $params->entity;
    	$shellSv = \service\Shell::singleton();
    	$result = $shellSv->reloadTable($table, $entity);
        $this->prompt("%s 重载数据表%s!", $this->serverMark, $result ? '成功' : '失败');
        return $result;
    }
    
	/**
     * 后台队列功能入口
     *
     * @return bool
     */
    public function execQueue()
    {
    	$queueSv = $this->locator->getService('Queue');
    	$runtimeDir = rtrim(cfg('server.environ.runtime_dir'), DS) . DS;
        if (substr($runtimeDir, 0, 2) == '.' . DS) {
        	$runtimeDir = ROOT_PATH . ltrim($runtimeDir, '.');
        }
    	$queueSv->setPort(1088)
    			->setLogPathPrefix($runtimeDir . 'queue' . DS . 'log' . DS . 'queue_')
            	->addTask('processor', array(Queue::singleton(), 'processor')) //队列处理器
            	->execute();
    }
    
	/**
     * 后台队列功能入口
     *
     * @return bool
     */
    public function startQueue()
    {
        // 启动进程
    	$handle = popen("nohup $this->phpCgi ". ROOT_PATH . 'Shell'. DS . "main.php Shell.execQueue >/dev/null 2>&1 &", 'r');
        pclose($handle);
        usleep(200000);
    }
    
    /**
     * 后台计划任务（crontab）功能入口
     * 需要在linux crontab机制中设置每分钟执行一次的计划任务
     * /bin/php /data/www/developers/wangwei/dev/Shell/main.php -h wangwei.dev.com Shell.crontab
     * @return bool
     * 
     * 第1列表示分钟1～59 每分钟用*或者
 	 * 第2列表示小时1～23（0表示0点）
     * 第3列表示日期1～31
     * 第4列表示月份1～12
 	 * 第5列标识号星期0～6（0表示星期天）
 	 * 
 	 * 要错开 避免一起执行
     */
    public function crontab()
    {
        $schedulerSv = new \service\Scheduler();
    	$runtimeDir = rtrim(cfg('server.environ.runtime_dir'), DS) . DS;
        if (substr($runtimeDir, 0, 2) == '.' . DS) {
        	$runtimeDir = ROOT_PATH . ltrim($runtimeDir, '.');
        }
        $crontabCtrl = \ctrl\Crontab::singleton(); 
        $schedulerSv->setLogPathPrefix($runtimeDir . 'crontab' . DS . 'log' . DS . 'queue_')
	        // 每3分钟执行
	        ->addTask('executePre3', "*/3 * * * *", array($crontabCtrl, 'executePre3'))
	        // 每5分钟执行
	        ->addTask('executePre5', "*/5 * * * *", array($crontabCtrl, 'executePre5'))
            // 每10分钟执行
            ->addTask('executePre10', "*/10 * * * *", array($crontabCtrl, 'executePre10'))
            // 每小时执行一次(有问题)
			//->addTask('executePre3600', "00 */1 * * *", array($crontabCtrl, 'executePre3600')) 
            // 每分钟执行一次
			//->addTask('executePre60', "*/1 * * * *", array($crontabCtrl, 'executePre60'))
            ->execute();
        return true;
    }
    
	/**
     * 执行20s一次的程序
     *
     * @return bool
     */
    public function looper()
    {
    	$arenaTeamSv = \service\ArenaTeam::singleton();
        $arenaTeamSv->autoDismissTeam();
        $arenaGuildTeamSv = \service\ArenaGuildTeam::singleton();
       	$arenaGuildTeamSv->matchTeam();
        return true;
    }
    
	/**
     * 将静态数据初始化到缓存中
     *
     * @return bool
     */
    public function dataCache()
    {
    	$staticDataSv = new \service\StaticData();
    	$staticDataSv->dataCache();
        return true;
    }
     
	/**
     * 静态数据导入工具
     * 
     * @return bool
     */
    public function dataTool()
    {        
       $shellSv = \service\Shell::singleton();
       $tables = $shellSv->getStaticTables();
?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>《HERO》静态数据导入导出工具</title>
</head>
<body>
<table id="ptable" class="tableboder" cellpadding="0" cellspacing="1" style="margin:10px; width:778px;">
    <tr>
       <td colspan="2" class="header"> 
			<h2>《HERO》静态数据导入工具</h2>
       </td>
       <td colspan="2" class="header"> 
       	<form method='post' action='./index.php?op=Shell.importData' enctype='multipart/form-data'><br/>
    	<input type='file' name='file' id='file'>
    	<input type='submit' value='导入'>
    	</form>
   	   </td>
   </tr>
    
<form id="form1" name="form1" method="get">
<table width="1500" border="1">
  <tr>
    <td width="100">数据表名</td>
    <td width="300">数据表描述</td>
    <td width="80">总数据量</td>
    <td width="80">最小id</td>
    <td width="80">最大id</td>
    <td width="80">最后修改时间</td>
    <td width="80">是否清空数据表</td>
  </tr>
<?php
	foreach($tables as $key => $table)
	{
	    echo <<<EOT
	    <tr>
	      <td>{$table['table']}</td>
	      <td>{$table['desc']}</td>
	      <td>{$table['count']}</td>
	      <td>{$table['minId']}</td>
	      <td>{$table['maxId']}</td>
	      <td>{$table['updateTime']}</td>
	      <td width="80"><a href="./index.php?op=Shell.clearTalbe&table={$key}" target="_blank">清空</a></td>
	    </tr>
EOT;
}
?>
</table>
</form>
</table>
 <?php   
    }
    
	/**
     * 清空数据表
     *
     * @return
     */
    public function clearTalbe()
    {
    	$params = $this->params;
    	$shellSv = \service\Shell::singleton();
    	$table = $params->table;
		$staticDataDao = \dao\StaticData::singleton();
       	$result = $staticDataDao->execBySql("TRUNCATE TABLE `{$table}`"); 
       	$result = 1;
        $this->prompt("%s 清空数据表 (%s)%s!", $this->serverMark, $table, $result ? '成功' : '失败');
        return $result;
    }
    
	/**
     * 刷新排行榜
     *
     * @return
     */
    public function flushRank()
    {
return ;    	
    	$rank = new \ctrl\Rank();
    	$rank->createRank();
        $this->prompt("刷新成功");
        $this->flushAllCache();
        return array();
    }
    
	/**
     * 导入数据表
     *
     * @return
     */
    public function importData()
    {
    	$filename = strstr($_FILES['file']['name'], '-', true).'.xls';
    	copy($_FILES['file']['tmp_name'], '/tmp/' . $filename);
    	$shellSv = \service\Shell::singleton();
    	$file = '/tmp/' . $filename;
    	$readSv = new \service\Read();
        $staticDataDao = \dao\StaticData::singleton();
print_r($file);        
    	$result = $readSv->getDataByExcel($file);
    	if (empty($result['status'])) {
    		echo "错误\n";
    		print_r($file);exit;
    	}
    	// 导入数据到数据库
    	// 清空原有的数据表
    	$staticDataDao->execBySql("TRUNCATE TABLE `{$result['table']}`");
    	$staticDataDao->addData($result['table'], $result['fields'], $result['data']);
print_r($file);
		//$this->flushAllCache();
		$cache = $this->cache;
		$allDataKeys = $cache->getKeys('data:*'); 
    	$allEntityKeys = $cache->getKeys('entity:*'); 
    	$allKeys = array_merge($allDataKeys, $allEntityKeys);
    	if (!empty($allKeys)) {
			$cache->execDelete($allKeys);
		}
    	exit;
    }
    
}