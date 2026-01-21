<?php

/**
 * 调用其他项目的api
 *
 * @param 	string		$projectName 		项目名称
 * @param 	string		$op 				方法名称
 *
 * @return array|bool
 */
function restFul($projectName = 'apiServer', $op, $paramArr = array())
{
    if (empty($projectName) || empty($op)) {
        return false;
    }
    $paramArr['op'] = $op;
    if ($projectName == 'apiServer') {
        $url = "http://192.168.0.172:5555/index.php";
    } elseif ($projectName == 'userCenter') {
        if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '192.168.0.172') { // 内网
            $url = "http://192.168.0.172:5556/index.php";
        } else {
            $url = "http://user.magook.com/uc.php";
        }
    } else {
        return false;
    }
    $response = httpGetContents($url, $paramArr);
    $response = empty($response) ? array() : json_decode($response, true);
    if (empty($response) || !isset($response['status'])) {
        return false;
    }
    return $response;
}

/**
 * 类自动生成
 * 
 * @param   string  $className  类名
 * @param   string  $namespace  空间名首字母
 * @param   bool    $force      存在则覆盖
 * 
 * @return bool
 */
function makeClass($className, $namespace = null, $force = false)
{
    $className = ucfirst($className);
    $namespaces = array_intersect(str_split('csde'), array_unique(str_split(strtolower($namespace))));
    $namespaces or $namespaces = str_split('csde');
    $names = array(
        'c' => 'ctrl',        // 控制器
        's' => 'service',    // 逻辑层
        'd' => 'dao',        // 数据操作层
        'e' => 'entity',    // 实体层
    );
    foreach ($namespaces as $namespace) {
        if ($namespace == 'c') {
            $namespaceDir = CTRL_PATH;
            $classBase = 'CtrlBase';
            $des = '控制器';
        } else if ($namespace == 's') {
            $namespaceDir = SERVICE_PATH;
            $classBase = 'ServiceBase';
            $des = '逻辑';
        } else if ($namespace == 'd') {
            $namespaceDir = DAO_PATH;
            $classBase = 'DaoBase';
            $des = '数据库';
        } else if ($namespace == 'e') {
            $namespaceDir = ENTITY_PATH;
            $classBase = 'EntityBase';
            $des = '实体';
        } else {
            continue;
        }
        $file = $namespaceDir . $className . '.php';
        if (file_exists($file) && empty($force)) {
            continue;
        }
        $content = array();
        $content[] = '<?php';
        $content[] = "namespace {$names[$namespace]};";
        $content[] = '';
        $content[] = '/**';
        $content[] = " * {$className} {$des}类";
        $content[] = ' * ';
        $content[] = " * @author ";
        $content[] = ' */';
        $content[] = $classBase ? "class {$className} extends {$classBase}" : "class {$className}";
        $content[] = '{';
        if ($namespace != 'e') {
            if ($namespace != 'e') {
                $content[] = "    /**";
                $content[] = "     * 单例";
                $content[] = "     *";
                $content[] = "     * @var object";
                $content[] = "     */";
                $content[] = "    private static $" . 'instance;';
                $content[] = "";
                $content[] = "    /**";
                $content[] = "     * 单例模式";
                $content[] = "     *";
                $content[] = "     * @return $className";
                $content[] = "     */";
                $content[] = "    public static function singleton()";
                $content[] = "    {";
                $content[] = "        if (!isset(self::$" . 'instance)) {';
                $content[] = "            self::$" . "instance = new $className();";
                $content[] = "        }";
                $content[] = "        return self::$" . 'instance;';
                $content[] = "    }";
                $content[] = "";
            }
            $content[] = "    /**";
            $content[] = "     * 主方法";
            $content[] = "     *";
            $content[] = "     * @return void";
            $content[] = "     */";
            $content[] = "    public function main()";
            $content[] = "    {";
            $content[] = "        return ;";
            $content[] = "    }";
        }
        $content[] = "";
        $content[] = "}";
        $result = file_put_contents($file, implode("\n", $content));
        $printStr = "created class $names[$namespace]\\\\$className ";
        if (empty($result)) { // 写入失败
            $printStr .= "失败!, 请检查目录[{$namespaceDir}是否有写入权限";
        } else {
            $printStr .= "成功!";
            @chmod($file, 0777);
        }
        $printStr .= "\n";
        echo $printStr;
    }
    return true;
}

/**
 * 获取表结构
 *
 * @param   string  	$table		表名
 * @param	string 		$db	 		数据库名
 *
 * @return array
 */
function getTableStructure($table, $db = '', $forceFromDB = false)
{
    $filename = CODE_PATH . 'configs' . DIRECTORY_SEPARATOR . 'table.conf.php';
    $tableConf = array();
    if (file_exists($filename)) {
        $tableConf = cfg('table');
    }
    if (!empty($tableConf[$db]) && empty($forceFromDB)) {
        if (!empty($tableConf[$db][$table])) {
            return $tableConf[$db][$table];
        } else {
            $tableLower = strtolower($table);
            if (!empty($tableConf[$db][$tableLower])) {
                return $tableConf[$db][$tableLower];
            }
        }
    }
    $daoHelper = &Application::$DaoHelper;
    $db = empty($db) ? $daoHelper::$dbName : $db;
    //$daoHelper->disconnect(); // 断开数据库连接
    $daoHelper::$dbName = $db;
    $indexs = $daoHelper->fetchBySql("show index from `$table`");
    $indexMap = array();
    if (!empty($indexs)) foreach ($indexs as $index) {
        $indexMap[$index->Key_name][] = $index->Column_name;
    }
    $primary = array();     // 主键
    $indexStr = array();     // 索引
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
        foreach ($fields as $field) {
            $fieldIndexInfo[$field][] = $name;
        }
    }
    $columns = $daoHelper->fetchBySql("show full columns from `$table`");
    $columnMap = array();
    $priProp = null;
    $columnComment = array();
    foreach ($columns as $column) {
        $columnMap[$column->Field] = $column->Default;
        $columnComment[$column->Field] = $column->Comment;
    }
    $result = array(
        'primary'           => $primary,        // 主键
        'indexStr'          => $indexStr,       // 索引字符串
        'indexArr'          => $indexMap,       // 索引数组
        'column'            => $columnMap,      // 字段信息
        'comment'           => $columnComment,  // 字段描述
        'fieldIndexInfo'    => $fieldIndexInfo, // 字段索引信息
    );
    return $result;
}

/**
 * 构造实体
 * 
 * @param   string  $table      数据表名
 * @param   string  $className  类表名
 * 
 * @return void
 */
function createEntity($table, $className)
{
    $className = ucfirst($className);
    @unlink(ENTITY_PATH . $className . '.php');
    makeClass($className, 'e', true);
    $file = explode("\n", file_get_contents(ENTITY_PATH . $className . '.php'));
    while (end($file) != '}') {
        array_pop($file);
        break;
    }
    array_pop($file);
    $daoHelper = &Application::$DaoHelper;
    $columns = $daoHelper->fetchBySql("SHOW FULL COLUMNS FROM `{$table}`");
    $fields = array();
    $priProps = array();
    foreach ($columns as $column) {
        $field   = '$' . $column->Field;
        $fields[] = "    /**";
        $fields[] = empty($column->Comment) ? "    *字段" :  "     * {$column->Comment}";
        $fields[] = "     *";
        $fields[] = "     * @var " . preg_replace('(\(\d+\))', '', $column->Type);
        $fields[] = "     */";
        if ($column->Default !== null && $column->Default !== "") {
            $fields[] = "    public {$field} = $column->Default;";
        } elseif ($column->Default === "") {
            $fields[] = "    public {$field} = '';";
        } else {
            $fields[] = "    public {$field};";
        }
        $fields[] = "";
        if ($column->Key == 'PRI') {
            $priProps[] = $column->Field;
        }
    }
    $fields[] = "// 表结构end";
    sort($priProps);
    $head = array();
    $head[] = "    /**";
    $head[] = "     * 主表";
    $head[] = "     *";
    $head[] = "     * @var string";
    $head[] = "     */";
    $head[] = "    const MAIN_TABLE = '{$table}';";
    if ($priProps) {
        $head[] = "";
        $head[] = "    /**";
        $head[] = "     * 主键";
        $head[] = "     *";
        $head[] = "     * @var string";
        $head[] = "     */";
        $head[] = "    const PRIMARY_KEY = '" . implode(', ', $priProps) . "';";
        $head[] = "";
    }
    $file = array_merge($file, $head, $fields);
    $file[] = "}";
    file_put_contents(ENTITY_PATH . $className . '.php', implode("\n", $file));
    echo "created entity\\\\$className \n";
    return;
}

/**
 * 调试
 *
 * @param var   $var        参数
 * @param bool  $isWrite    是否写入
 * @param bool  $isShow     是否显示
 *
 * @return void
 */
function e($var = null, $isWrite = false, $isShow = true)
{
    $path = CACHE_PATH . 'log' . DS . 'ww.log';
    if (is_null($var)) {
        echo "Here~!\n";
    } elseif (is_null($isWrite)) {
        print_r($var);
        echo "\n";
    } else {
        error_log($var, 3, $path);
        if ($isShow) {
            print_r($var);
            echo "\n";
        }
    }
    return;
}

/**
 * 计算目录的大小
 * 
 * @param  $dirPath string 目录路径
 *
 * @return int
 */
function getDirSize($dirPath)
{
    $size = 0;
    $dir = @opendir($dirPath);
    if (!$dir) {
        return -1;
    }
    while (($file = readdir($dir)) !== false) {
        if ($file['0'] == '.') continue;
        if (is_dir($dirPath . $file)) {
            $size += getDirSize($dirPath . $file . DS);
        } else {
            $size += filesize($dirPath . $file);
        }
    }
    @closedir($dir);
    return $size;
}

/**
 * 根据目录获取目录下的文件
 *
 * @param 	string	$dir 	目录
 * @param 	string	$suffix 包涵的后缀名
 *
 * @return array
 */
function getFilesByDir($dir, $suffix = null)
{
    $files = array();
    if (is_dir($dir)) {
        if ($handle = opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if ((is_dir($dir . DS . $file)) && !in_array($file, array('.', '..'))) {
                    $files = array_merge($files, getFilesByDir($dir . DS . $file . DS, $suffix));
                } elseif (!in_array($file, array('.', '..'))) {
                    if ($suffix && !preg_match("/.{$suffix}$/", $file)) {
                        continue;
                    }
                    $files[] = $dir . DS . $file;
                }
            }
        }
        closedir($handle);
    }
    return array_unique($files);
}

/**
 * 读取项目外部配置
 *
 * @param   string  $name    	配置节点名字空间
 * @param   null    $path    	查找路径名
 * @param   bool    $noCache    是否不走缓存
 * 
 * @throws RuntimeException
 * @return mixed
 * 
 * cfg('server.queue.messager');
 */
function cfg($name, $path = null, $noCache = false)
{
    $cache = &Application::$Cache;
    $cacheReturn = $cache->get('cfg:' . $name);
    if ($noCache && $cacheReturn !== false) {
        return $cacheReturn;
    }
    $info = explode('.', $name);
    $module = array_shift($info); // 模块
    if (!empty($path)) {
        if (is_dir($path)) {
            $filename = ltrim($path, CS) . CS . $module . '.conf.php';
        } elseif (is_file($path)) {
            $filename = $path;
        }
    } else {
        $filename = CODE_PATH . 'configs' . DIRECTORY_SEPARATOR . $module . '.conf.php';
    }
    if (!isset($filename) || !is_file($filename)) {
        throw new RuntimeException("配置文件: $filename 没找到");
    }
    $conf = include($filename);
    foreach ($info as $slice) {
        if (!isset($conf[$slice])) {
            throw new RuntimeException("配置: $name 没找到");
        }
        $conf = $conf[$slice];
    }
    $cache->set('cfg:' . $name, $conf);
    return $conf;
}

/**
 * 带超时控制的从远程获取内容
 *
 * @param   string          $url        地址
 * @param   null|string     $data       post信息
 * @param   int             $timeout    超时时间
 * @param   array           $headers    头内容
 * @param   int             $tries      尝试次数
 * @param   int             $errno      错误码
 *
 * @return string
 */
function httpGetContents($url, $data = null, $timeout = 20, $headers = array(
    'charset=UTF-8'
), $tries = 1, &$errno = 0)
{
    if (is_array($data)) {
        $data = http_build_query($data);
    }
    do {
        if (extension_loaded('curl')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 规避SSL验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 跳过HOST验证
            if (isset($data)) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                if (isset($data[1024])) {
                    $headers[] = 'Expect:';
                }
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $body = curl_exec($ch);
            if ($body === false) {
                $errno = curl_errno($ch);
               
                //curl_error($ch);
            }
            curl_close($ch);
        } else {
            $options = array(
                'http' => array(
                    'timeout' => $timeout,
                )
            );
            if (isset($data)) {
                $options['http']['method'] = 'POST';
                $options['http']['header'] = 'Content-type: application/x-www-form-urlencoded';
                $options['http']['content'] = $data;
            }
            $context = stream_context_create($options);
            $body = file_get_contents($url, FILE_BINARY, $context);
        }
    } while ($body === false && --$tries > 0);
    return $body;
}

/**
 * post请求
 * 
 * @param   string  $url    请求地址
 * @param   array   $data   请求参数
 * 
 * @return mixed
 */
function doPost($url, $data, $header = array('charset=UTF-8'), $timeout = 10, $isBuild = true)
{
    if (is_array($data) && $isBuild) {
        $data = http_build_query($data);
    }
    $curlHander = curl_init($url);
    curl_setopt($curlHander, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curlHander, CURLOPT_POST, TRUE);
    curl_setopt($curlHander, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curlHander, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($curlHander, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curlHander, CURLOPT_SSL_VERIFYPEER, false); // 规避SSL验证
    curl_setopt($curlHander, CURLOPT_SSL_VERIFYHOST, false); // 跳过HOST验证
    if (!empty($header)) {
        curl_setopt($curlHander, CURLOPT_HTTPHEADER, $header);
    }
    $response = curl_exec($curlHander);
    curl_close($curlHander);
    return $response;
}

/**
 * 获取进程状态
 * 
 * @param 	string	$pidFile	进程文件
 * 
 * @return int
 */
function procStatus($pidFile)
{
    $status = 4;
    exec("kill -0 `cat $pidFile 2> /dev/null` 2> /dev/null", $output, $status);
    return $status;
}

/**
 * 使用http上传文件
 *
 * @param   string          $url        地址
 * @param   null|string     $data       post信息
 * @param   int             $timeout    超时时间
 * @param   array           $headers    头内容
 * @param   int             $tries      尝试次数
 * @param   int             $errno      错误码
 *
 * @return bool|mixed
 */
function httpPutContents($url, $data = null, $timeout = 4, $headers = array(), $tries = 1, &$errno = 0)
{
    do {
        if (extension_loaded('curl')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // 在发起连接前等待的时间，如果设置为0，则无限等待
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);         //	设置cURL允许执行的最长秒数。
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $body = curl_exec($ch);
            if ($body === false) {
                $errno = curl_errno($ch);
            }
            curl_close($ch);
        } else {
            $body = false;
        }
    } while ($body === false && --$tries > 0);
    return $body;
}

/**
 * 获取变量名
 *
 * @param   string  $var    变量
 *
 * @return string
 */
function getVarName($var)
{
    $trace = debug_backtrace();
    $lineArr = file(__FILE__);
    $curLine = $lineArr[$trace[0]['line'] - 1];
    preg_match("#\\$(\w+)#", $curLine, $match);
    return $match[1];
}

/**
 * 重构数据
 * 
 * @param	array	$data	数据
 * @param	string	$key1	一级key
 * @param	string	$key2	二级key
 * @param	bool	$only	最后级非数组
 * 
 * @return  $data 
 */
function refactor($data, $key1, $key2 = null, $only = false)
{
    $result = array();
    if (is_iteratable($data)) {
        if ($key2) {
            foreach ($data as $row) {
                if (!isset($row->$key1) || !isset($row->$key2)) {
                    return $data;
                }
                if ($only) {
                    $result[$row->$key1][$row->$key2] = $row;
                } else {
                    $result[$row->$key1][$row->$key2][] = $row;
                }
            }
        } else {
            foreach ($data as $row) {
                if (!isset($row->$key1)) {
                    return $data;
                }
                if ($only) {
                    $result[$row->$key1] = $row;
                } else {
                    $result[$row->$key1][] = $row;
                }
            }
        }
    } else {
        return $data;
    }
    return $result;
}

// 私有密匙(用于解密和加密)
define('ENCRYPT_KEY', 'fjsakjlkg&*^%)(89042432sf');

/**
 * 加密函数
 *
 * @param   string  $string     等待加密的原字串
 *
 * @return  string    原字串经过私有密匙加密后的结果
 */
function encrypt($string, $privateKey = '')
{	
	if (is_numeric($string)) {
		$string = (string)$string;
	}
    srand((float)microtime() * 1000000); // 使用随机数发生器产生 0~32000 的值并 MD5()
    $encryptKey = md5(rand(0, 32000));  // 用于加密的key 
    $init = 0; // 初始化变量长度
    $tmp = '';
    $strLen = strlen($string); // 待加密字符串的长度
    $encryptKeyLen = strlen($encryptKey); // 加密key的长度
    for ($index = 0; $index < $strLen; $index++) {
        $init = $init == $encryptKeyLen ? 0 : $init; // 如果 $init = $encryptKey 的长度, 则 $init 清零
        // $tmp 字串在末尾增加两位, 其第一位内容为 $encryptKey 的第 $init 位，
        // 第二位内容为 $string 的第 $index 位与 $encryptKey 的 $init 位取异或。然后 $init = $init + 1
        $tmp .= $encryptKey[$init] . ($string[$index] ^ $encryptKey[$init++]);
    }
    // 返回结果，结果为 passportKey() 函数返回值的 base65 编码结果
    return base64_encode(passportKey($tmp, $privateKey));
}

/**
 * 密匙处理函数
 *
 * @param        string        待加密或待解密的字串
 * @param        string        私有密匙(用于解密和加密)
 *
 * @return    string        处理后的密匙
 */
function passportKey($string, $privateKey = '')
{
	if (empty($privateKey)) {
		$encryptKey = md5(ENCRYPT_KEY); // 加密的key
	} else {
		$encryptKey = md5($privateKey); // 加密的key
	}
    
    $init = 0;
    $tmp = '';
    $len = strlen($string);
    $encryptKeyLen = strlen($encryptKey);
    for ($index = 0; $index < $len; $index++) {
        $init = $init == $encryptKeyLen ? 0 : $init;
        $tmp .= $string[$index] ^ $encryptKey[$init++];
    }
    return $tmp;
}

/**
 * 解密函数
 *
 * @param		string        加密后的字串
 *
 * @return    string        字串经过私有密匙解密后的结果
 */
function decrypt($string, $privateKey = '')
{
    $string = passportKey(base64_decode($string), $privateKey);
    $tmp = '';
    $len = strlen($string);
    for ($index = 0; $index < $len; $index++) {
        if (!isset($string[$index]) || !isset($string[$index + 1])) {
            return false;
        }
        $tmp .= $string[$index] ^ $string[++$index];
    }
    return $tmp;
}

/**
 * 计算公式的值
 *
 * @param   int     $id     公式id
 *
 * @return mixed
 */
function formula($id)
{
    $args = array_slice(func_get_args(), 1);
    return Formula::calculate($id, $args);
}

// 兼容扩展函数没安装的情况
use service\MsgPack as MsgPack;

if (!function_exists('msgpack_serialize')) {
    function msgpack_serialize($message)
    {
        return MsgPack::encode($message);
    }
}

if (!function_exists('msgpack_unserialize')) {
    function msgpack_unserialize($buffer)
    {
        return MsgPack::decode($buffer);
    }
}

if (!function_exists('igbinary_serialize')) {
    function igbinary_serialize()
    {
        return false;
    }
}

if (!function_exists('igbinary_unserialize')) {
    function igbinary_unserialize()
    {
        return false;
    }
}

// 函数版本兼容
if (!function_exists('json_encode')) {
    function json_encode($value)
    {
        static $jsonObject;
        if (!isset($jsonobj)) {
            include_once(LIB_PATH . DS . 'json' . DS . 'JSON.php');
            $jsonObject = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        }
        return $jsonObject->encode($value);
    }
}

if (!function_exists('json_decode')) {
    function json_decode($jsonString)
    {
        static $jsonObject;
        if (!isset($jsonObject)) {
            include_once(LIB_PATH . DS . 'json' . DS . 'JSON.php');
            $jsonObject = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        }
        return $jsonObject->decode($jsonString);
    }
}

if (!function_exists('file_put_contents')) {
    function file_put_contents($file, $string)
    {
        $fp = @fopen($file, 'w');
        if (empty($fp)) {
            return false;
        }
        flock($fp, LOCK_EX);
        $stringlen = @fwrite($fp, $string);
        flock($fp, LOCK_UN);
        @fclose($fp);
        return $stringlen;
    }
}

if (!function_exists('http_build_query')) {
    function http_build_query($formdata, $numericPrefix = null, $argSeparator = null)
    {
        if (!is_array($formdata)) {
            return false;
        }
        if ($argSeparator == null) {
            $argSeparator = '&';
        }
        return http_build_recursive($formdata, $argSeparator);
    }
    function http_build_recursive($formdata, $separator, $key = '', $prefix = '')
    {
        $result = '';
        foreach ($formdata as $key => $value) {
            if (is_array($value)) {
                if ($key)
                    $result .= http_build_recursive($value, $separator, $key . '[' . $key . ']', $prefix);
                else
                    $result .= http_build_recursive($value, $separator, $key, $prefix);
            } else {
                if ($key)
                    $result .= $prefix . $key . '[' . urlencode($key) . ']=' . urldecode($value) . '&';
                else
                    $result .= $prefix . urldecode($key) . '=' . urldecode($value) . '&';
            }
        }
        return $result;
    }
}

/**
 * 
 * 随机小数
 * @param float $min     下限值
 * @param float $max     上限值
 * @param int   $decimal 小数位数
 */
function dotRand($min, $max, $decimal = 0)
{
    $multiple = pow(10, $decimal);
    return round(mt_rand(floor($min * $multiple), floor($max * $multiple)) / $multiple, $decimal);
}

/**
 * 半角-全角互相转换
 * 
 * @param string $str     转换前的字符串
 * @param bool   $reverse 默认false，true为全角转半角
 * 
 * @return string
 */
function SBC2DBC($str, $reverse = false)
{
    // 全角字符集
    $DBC = array(
        '０', '１', '２', '３', '４',
        '５', '６', '７', '８', '９',
        'Ａ', 'Ｂ', 'Ｃ', 'Ｄ', 'Ｅ',
        'Ｆ', 'Ｇ', 'Ｈ', 'Ｉ', 'Ｊ',
        'Ｋ', 'Ｌ', 'Ｍ', 'Ｎ', 'Ｏ',
        'Ｐ', 'Ｑ', 'Ｒ', 'Ｓ', 'Ｔ',
        'Ｕ', 'Ｖ', 'Ｗ', 'Ｘ', 'Ｙ',
        'Ｚ', 'ａ', 'ｂ', 'ｃ', 'ｄ',
        'ｅ', 'ｆ', 'ｇ', 'ｈ', 'ｉ',
        'ｊ', 'ｋ', 'ｌ', 'ｍ', 'ｎ',
        'ｏ', 'ｐ', 'ｑ', 'ｒ', 'ｓ',
        'ｔ', 'ｕ', 'ｖ', 'ｗ', 'ｘ',
        'ｙ', 'ｚ', '－', '　', '：',
        '．', '，', '／', '％', '＃',
        '！', '＠', '＆', '（', '）',
        '＜', '＞', '＂', '＇', '？',
        '［', '］', '｛', '｝', '＼',
        '｜', '＋', '＝', '＿', '＾',
        '￥', '￣', '｀'
    );

    // 半角字符集
    $SBC = array(
        '0', '1', '2', '3', '4',
        '5', '6', '7', '8', '9',
        'A', 'B', 'C', 'D', 'E',
        'F', 'G', 'H', 'I', 'J',
        'K', 'L', 'M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T',
        'U', 'V', 'W', 'X', 'Y',
        'Z', 'a', 'b', 'c', 'd',
        'e', 'f', 'g', 'h', 'i',
        'j', 'k', 'l', 'm', 'n',
        'o', 'p', 'q', 'r', 's',
        't', 'u', 'v', 'w', 'x',
        'y', 'z', '-', ' ', ':',
        '.', ',', '/', '%', '#',
        '!', '@', '&', '(', ')',
        '<', '>', '"', '\'', '?',
        '[', ']', '{', '}', '\\',
        '|', '+', '=', '_', '^',
        '$', '~', '`'
    );
    return empty($reverse) ? str_replace($SBC, $DBC, $str) : str_replace($DBC, $SBC, $str);
}

/**
 * 序列转换数组
 * 
 * @param 	string	$strSerial			字符串序列
 * @param 	char	$strSplitMain		一级分隔符
 * @param 	char	$strSplitSub		二级分隔符
 * @param 	bool	$keyPrimary			是否根据主键索引
 * @param 	bool	$keyPrimaryOnly		是否主键唯一
 * 
 * @param array
 */
function stringToArray(
    $strSerial,
    $strSplitMain = '|',
    $strSplitSub = ':',
    $keyPrimary = true,
    $keyPrimaryOnly = true
) {
    $arrResult = array();
    if ($strSerial) {
        $arrRand = explode($strSplitMain, $strSerial);
        foreach ($arrRand as $key => $item) {
            $arrItem = explode($strSplitSub, $item);
            $arrItem['0'] = str_replace(array("\n", "\r"), '', $arrItem['0']);
            if ($keyPrimary) {
                if ($keyPrimaryOnly) {
                    $arrResult[$arrItem['0']] = $arrItem;
                } else {
                    $arrResult[$arrItem['0']][] = $arrItem;
                }
            } else {
                $arrResult[] = $arrItem;
            }
        }
    }
    return $arrResult;
}

/**
 * 按个数生成随机字符串
 * 
 * @param	int 	$num		字符数量
 * 
 * @return string
 */
function randomString($num = 8)
{
    return substr(str_shuffle('1234567890qwertyuiopasdfghjklzxcvbnm'), 0, $num);
}

/**
 * 语言包转化-根据提示字符串获取错误码
 *
 * @param   string  $string    提示字符串
 * 
 * @return int
 */
function i18n($string)
{
    try {
        $errorCode = cfg('error.' . $string);
    } catch (Exception $e) {
        $errorCode = 0; //  错误码未定义
    }
    return $errorCode;
}

/**
 * urlencode转换
 *
 * @param   array  	$array    		 转换的字符
 * @param   string  $function    	转换的编码
 * @return int
 */
function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
{
    $recursive_counter = 0;
    if (++$recursive_counter > 1000) {
        die('possible deep recursion attack');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayRecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function($value);
        }
        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
    $recursive_counter--;
}

/**
 * 按权重获取类型
 * 
 * @param 	array 	$weights 	权重列表	[type => weight]
 * 
 * @param int|string
 */
function getWeightItem($weights)
{
    asort($weights); // 根据权重由小到大排序	
    $randValue = dotRand(1, array_sum($weights)); // 随机到的权重值
    $limitValue = 0;
    foreach ($weights as $type => $weight) {
        $limitValue += $weight;
        if ($randValue <= $limitValue) {
            return $type;
        }
    }
    return 0;
}

/**
 * 版本比较
 * 
 * @param	string	$version1 	版本1
 * @param	string	$version2 	版本1
 * 
 * @bool
 */
function versionCompare($version1, $version2)
{
    if (strlen($version1) <> strlen($version2)) {
        $version1Tmp = explode('.', $version1);
        $version2Tmp = explode('.', $version2);
        if (strlen($version1Tmp[1]) == 1) {
            $version1 .= '0';
        }
        if (strlen($version2Tmp[1]) == 1) {
            $version2 .= '0';
        }
    }
    return version_compare($version1, $version2);
}

/**
 * 转义引号字符串(支持单个字符与数组)
 * 
 * @param 	string|array 	$var	内容
 * 
 * @return string|array	 返回转义后的字符串或是数组
 */
function istripslashes($var)
{
    if (is_array($var)) {
        foreach ($var as $key => $value) {
            $var[stripslashes($key)] = istripslashes($value);
        }
    } else {
        $var = stripslashes($var);
    }
    return $var;
}

/**
 * 转义字符串的HTML
 * 
 * @param 	string|array 	$var	内容
 * 
 * @return string|array	 返回转义后的字符串或是数组
 */
function ihtmlspecialchars($var)
{
    if (is_array($var)) {
        foreach ($var as $key => $value) {
            $var[htmlspecialchars($key)] = ihtmlspecialchars($value);
        }
    } else {
        $var = str_replace('&amp;', '&', htmlspecialchars($var, ENT_QUOTES));
    }
    return $var;
}

/**
 * 统计内容大小
 * 
 * @param 	int		$size	大小
 * 
 * @return string
 */
function sizeCount($size)
{
    if ($size >= 1073741824) {
        $size = round($size / 1073741824 * 100) / 100 . ' GB';
    } elseif ($size >= 1048576) {
        $size = round($size / 1048576 * 100) / 100 . ' MB';
    } elseif ($size >= 1024) {
        $size = round($size / 1024 * 100) / 100 . ' KB';
    } else {
        $size = $size . ' Bytes';
    }
    return $size;
}

/**
 * 分解ip列表
 *
 * @param array  $ipArr   ip列表
 *
 * @return array
 */
function ipList($ipArr)
{
    $whiteIps = array(); // 服务器白名单ip列表
    if (!empty($ipArr)) foreach ($ipArr as $key => $ip) {
        $pos = strripos($ip, '.');
        $headerIp = substr($ip, 0, $pos + 1);
        $lastIp = substr($ip, $pos + 1, strlen($ip));
        $ipArr = explode('-', $lastIp);
        if (is_array($ipArr) && count($ipArr) > 1) {
            for ($index = $ipArr[0]; $index <= $ipArr[1]; $index++) {
                $whiteIps[] = $headerIp . $index;
            }
        } else {
            $whiteIps[] = $headerIp . $lastIp;
        }
    }
    return $whiteIps;
}

/**
 * 遍历数组
 *
 * @param 	mix  	$needle   	目标
 * @param 	array  	$haystack   数组
 * @param 	int  	$model   	1 获取下一个  2 获取上一个
 * @param 	int  	$step   	长度
 *
 * @return mix
 */
function iterate($needle, $haystack, $model = 1, $step = 1)
{
    $haystack = array_values($haystack);
    $needlePostion = array_search($needle, $haystack);
    if ($needlePostion === false) {
        return null;
    }
    if ($model == 2) {
        $slice = array_slice($haystack, $needlePostion - $step, 1);
    } else {
        $slice = array_slice($haystack, $needlePostion + $step, 1);
    }
    if (empty($slice)) {
        return null;
    }
    return $model == 2 ? reset($slice) : end($slice);
}

/**
 * 选择表
 * 
 * @param 	string 		$key 			key
 * @param 	int 		$tableNum 		表数量
 * 
 * @return int
 */
function selectTable($key, $tableNum)
{
    $table = abs((intval($key) % $tableNum) + 1);
    return $table ? $table : 1;
}

/**
 * 替换文件名特殊字符
 *
 * @param   string  $name    文件名
 *
 * @return string
 */
function replaceFileName($name)
{
    $map = array(
        '1a1' => '/\//',
        '1b2' => '/\\\/',
        '1c1' => '/\:/',
        '1d1' => '/\*/',
        '1e1' => '/\?/',
        '1f1' => '/\"/',
        '1g1' => '/\</',
        '1h1' => '/\>/',
        '1i1' => '/\→/',
        '1j1' => '/\|/',
        '1h1' => '/\\n/',
    );
    $text = preg_replace(array_values($map), array_keys($map), $name);
    return trim($text);
}

function makeDir($dir)
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
        @chmod($dir, 0777);
        @chown($dir, 'nobody');
        @chgrp($dir, 'nobody');
    }
    return true;
}

/**
 * 获取目录信息, 解决pathinfo的bug
 *
 * @param  sting   $filePath   文件
 *
 * @return array
 */
function selfPathInfo($filePath)
{
    $pathInfo = pathinfo($filePath);
    $pathInfo['dirname']     = rtrim(substr($filePath, 0, strrpos($filePath, DS)), DS) . DS;
    $pathInfo['basename']     = ltrim(substr($filePath, strrpos($filePath, DS)), DS);
    $pathInfo['extension']     = substr(strrchr($filePath, '.'), 1);
    $pathInfo['filename']     = ltrim(substr($pathInfo['basename'], 0, strrpos($pathInfo['basename'], '.')), DS);
    return $pathInfo;
}

/**
 * 过滤特殊字符
 *
 * @param   string   $text   文本
 *
 * @return string

 需要特殊处理的字符
 [<⃒] => &nvlt
 [&] => &amp;
 [<] => &lt;
 [>] => &gt;
 */
function filterSpecialCharacter($text)
{
    // 需要替换的特殊字符表
    $specialCharacterMap = array('&backbackprime;', '&nparsl', '&bne', '&nvgt', '&fjlig', '&Uring;', '&ThickSpace', '&nrarrw', '&npart', '&nang', '&caps', '&cups', '&nvsim', '&race', '&acE', '&nesim', '&ape;', '&napid', '&nvap', '&nbump', '&nbumpe', '&nedot', '&bnequiv', '&nvle', '&nvge', '&nlE', '&NotGreaterFullEqual', '&lvertneqq', '&gvertneqq', '&nLtv', '&nLt', '&NotGreaterGreater', '&nGt', '&NotSucceedsTilde', '&vnsub', '&nsupset', '&vsubne', '&vsupne', '&NotSquareSubset', '&NotSquareSuperset', '&sqcaps', '&sqcups', '&nvltrie', '&nvrtrie', '&nLl', '&nGg', '&lesg', '&gesl', '&notindot', '&notinE', '&nrarrc', '&NotLeftTriangleBar', '&NotRightTriangleBar', '&ncongdot', '&napE', '&nles', '&nges', '&NotNestedLessLess', '&NotNestedGreaterGreater', '&smtes', '&lates', '&NotPrecedesEqual', '&NotSucceedsEqual', '&nsubE', '&nsupseteqq', '&vsubnE', '&varsupsetneqq', '&nparslnparsl');
    $htmlTranslationTable = get_html_translation_table(HTML_ENTITIES, ENT_XML1 | ENT_XHTML, 'UTF-8');
    $translationTable = array(); // 需要替换的字符表
    $tmpTranslationTable = array();
    foreach ($htmlTranslationTable as $key => $value) {
        $tmpTranslationTable[$value] = preg_replace('/&/', '＆', $value);
        if (in_array($value, $specialCharacterMap)) {
            $translationTable[$value] = $key;
            unset($htmlTranslationTable[$key]);
        }
    }
    // 处理[<⃒] => &nvlt, 将[<⃒] 替换成 * 将&nvlt 替换成*
    $translationTable['<⃒']     = '*';
    $translationTable['&nvlt']     = '*';
    $unTranslationTable = array('&amp;', '&lt;', '&gt;', '&nvlt');
    foreach ($unTranslationTable as $key => $value) {
        unset($htmlTranslationTable[array_search($value, $htmlTranslationTable)]);
    }
    $noNeedTranslationTable = $htmlTranslationTable; // 不需要替换的字符表
    // 第一步 将可能报错的富文本替换成字符
    $text = str_replace(array_keys($translationTable), array_values($translationTable), $text);
    // 第二步 将&替换成＆
    $text = preg_replace('/&/', '＆', $text);
    // 第三步 将富文本标准化
    $text = str_replace(array_values($tmpTranslationTable), array_keys($tmpTranslationTable), $text);
    return $text;
}

/**
 * 删除目录及目录下所有文件或删除指定文件
 *
 * @param 	string 		$path   		待删除目录路径
 * @param 	bool 		$deleteDir 		是否删除目录, true删除目录, false则只删除文件保留目录（包含子目录）
 *
 * @return bool 返回删除状态
 */
function deleteDirAndFile($path, $deleteDir = false)
{
    if (!is_dir($path)) {
        return false;
    }
    $handle = opendir($path);
    if ($handle) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                is_dir($path . DS . $item) ? deleteDirAndFile($path . DS . $item, $deleteDir) : @unlink($path . DS . $item);
            }
        }
        closedir($handle);
        if ($deleteDir) { // 删除文件夹
            return @rmdir($path);
        }
    } else {
        if (file_exists($path)) {
            return @unlink($path);
        } else {
            return false;
        }
    }
}

/**
 * 解压文件
 *
 * @param      string      $filename       文件路径
 * @param      string      $path           将文件解压到该目录下
 * @param      string      $iconv          是否转义
 *
 * @return array
 */
function unzip($fileName, $path, $iconv = false)
{
    // 将文件名和路径转成windows系统默认的gb2312编码，否则将会读取不到
    if (!empty($iconv)) {
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        $path     = iconv('utf-8', 'gb2312', $path);
    }
    $resource = @zip_open($fileName); // 打开压缩包
    // 遍历读取压缩包里面的每个文件夹
    while (@$dirResource = zip_read($resource)) {
        if (zip_entry_open($resource, $dirResource)) { // 如果能打开则继续
            // 获取当前项目的名称, 即压缩包里面当前对应的文件名
            $tmpFileName = $path . zip_entry_name($dirResource);
            if (!empty($iconv)) {
                $tmpFileName = @iconv('utf-8', 'gb2312', $tmpFileName); // 将解压后的文件名转码
            }
            // 以最后一个'/'分割, 再用字符串截取出路径部分
            $tmpFilePath = substr($tmpFileName, 0, strrpos($tmpFileName, '/'));
            // 如果路径不存在, 则创建一个目录, true表示可以创建多级目录
            if (!is_dir($tmpFilePath)) {
                @mkdir($tmpFilePath, 0777, true);
            }
            // 如果不是目录, 则写入文件
            if (!is_dir($tmpFileName)) {
                // 读取这个文件
                $fileSize = zip_entry_filesize($dirResource);
                // 最大读取6M,如果文件过大,跳过解压,继续下一个
                if ($fileSize < (1024 * 1024 * 6)) {
                    $fileContent = zip_entry_read($dirResource, $fileSize);
                    file_put_contents($tmpFileName, $fileContent);
                } else {
                    // 此文件已被跳过，原因：文件过大
                    return false;
                }
            }
            // 关闭当前
            zip_entry_close($dirResource);
        }
    }
    //关闭压缩包
    @zip_close($resource);
    return true;
}

/**
 * 把二维数组里的一个或者多个元素组成一个二维数组的唯一索引
 * @param $arr                       二维数组
 * @param $index    string|array     二维数组的索引值
 * @param string $separator          数组索引的分隔符
 * @return array                     返回处理好之后的数组结果集
 */
function cast_index_to_key($arr, $index, $separator = '-')
{
    $new_arr  = array();
    $row_type = gettype(current($arr));
    foreach ($arr as $_row) {
        if (is_array($index) && !empty($index)) {
            $arr_index = '';
            foreach ($index as $val) {
                if ($row_type == 'array') {
                    $arr_index .= $_row[$val] . $separator;
                }
                if ($row_type == 'object') {
                    $arr_index .= $_row->$val . $separator;
                }
            }
            $arr_index = (!empty($arr_index) ? substr($arr_index, 0, -1) : $arr_index);
        } else {
            $arr_index = $row_type == 'array' ? $_row[$index] : $_row->$index;
        }
        $new_arr[$arr_index] = $_row;
    }
    return $new_arr;
}

/**
 * 过滤字符串中无法识别的乱码字符
 * @param $ostr
 * @return string
 */
function filter_utf8_char($ostr)
{
    preg_match_all('/[\x{FF00}-\x{FFEF}|\x{0000}-\x{00ff}|\x{4e00}-\x{9fff}|、|“|”|‘|’|①|②|③|④|⑤|⑥|⑦|⑧|⑨|⑩]+/u', $ostr, $matches);
    $str = join('', $matches[0]);
    if ($str == '') {   //含有特殊字符需要逐個處理
        $returnstr  = '';
        $i          = 0;
        $str_length = strlen($ostr);
        while ($i <= $str_length) {
            $temp_str = substr($ostr, $i, 1);
            $ascnum   = Ord($temp_str);
            if ($ascnum >= 224) {
                $returnstr = $returnstr . substr($ostr, $i, 3);
                $i         = $i + 3;
            } elseif ($ascnum >= 192) {
                $returnstr = $returnstr . substr($ostr, $i, 2);
                $i         = $i + 2;
            } elseif ($ascnum >= 65 && $ascnum <= 90) {
                $returnstr = $returnstr . substr($ostr, $i, 1);
                $i         = $i + 1;
            } elseif ($ascnum >= 128 && $ascnum <= 191) { // 特殊字符
                $i = $i + 1;
            } else {
                $returnstr = $returnstr . substr($ostr, $i, 1);
                $i         = $i + 1;
            }
        }
        $str = $returnstr;
        preg_match_all('/[\x{FF00}-\x{FFEF}|\x{0000}-\x{00ff}|\x{4e00}-\x{9fff}|、]+/u', $str, $matches);
        $str = join('', $matches[0]);
    }
    return $str;
}


/**
 * 根据id排序
 * 
 * @return array
 */
function orderById(array $list, array $ids)
{
    $map = array();
    foreach ($list as $row) {
        if (is_object($row)) {
            $map[$row->id] = $row;
        } elseif (is_array($row)) {
            $map[$row['id']] = $row;
        }
    }
    $result = array();
    foreach ($ids as $id) {
        if (!empty($map[$id])) {
            $result[] = $map[$id];
            unset($map[$id]);
        }
    }
    if (!empty($map)) {
        foreach ($map as $row) {
            $result[] = $row;
        }
    }
    return $result;
}
/**
 * 把数字1-1亿换成汉字表述，如：123->一百二十三
 * @param [num] $num [数字]
 * @return [string] [string]
 */
function numToWord($num)
{
    $chiNum = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
    $chiUni = array('', '十', '百', '千', '万', '亿', '十', '百', '千');
    $num_str = (string)$num;
    $count = strlen($num_str);
    $last_flag = true; //上一个 是否为0
    $zero_flag = true; //是否第一个
    $temp_num = null; //临时数字
    $chiStr = ''; //拼接结果
    if ($count == 2) { //两位数
        $temp_num = $num_str[0];
        $chiStr = $temp_num == 1 ? $chiUni[1] : $chiNum[$temp_num] . $chiUni[1];
        $temp_num = $num_str[1];
        $chiStr .= $temp_num == 0 ? '' : $chiNum[$temp_num];
    } else if ($count > 2) {
        $index = 0;
        for ($i = $count - 1; $i >= 0; $i--) {
            $temp_num = $num_str[$i];
            if ($temp_num == 0) {
                if (!$zero_flag && !$last_flag) {
                    $chiStr = $chiNum[$temp_num] . $chiStr;
                    $last_flag = true;
                }
            } else {
                $chiStr = $chiNum[$temp_num] . $chiUni[$index % 9] . $chiStr;
                $zero_flag = false;
                $last_flag = false;
            }
            $index++;
        }
    } else {
        $chiStr = $chiNum[$num_str[0]];
    }
    return $chiStr;
}

/**
 * 把中文的符号替换成英文符号
 * @param $array array 二维数组
 * @param $keyName string key值
 * @return array
 */
function changeSign($array, $keyName)
{
    $cnSign   = ['（', '）', '“', '”', '‘', '’', '，', '。', '？', '！', '：', '；', '【', '】'];
    $enSign   = ['(', ')', '"', '"', "'", "'", ',', '.', '?', '!', ':', ';', '[', ']'];
    $tempItem = current($array);
    $row_type = gettype($tempItem);
    if (is_string($tempItem)) {
        $array[$keyName] = str_replace($cnSign, $enSign, $array[$keyName]);
        return $array;
    }

    if (empty($array) || ($row_type == 'array' && !isset($tempItem[$keyName])) || ($row_type == 'object' && !isset($tempItem->$keyName))) {
        return $array;
    }

    $data     = [];

    foreach ($array as $item) {
        if ($row_type == 'array') {
            $item[$keyName] = str_replace($cnSign, $enSign, $item[$keyName]);
        }
        if ($row_type == 'object') {
            $item->$keyName = str_replace($cnSign, $enSign, $item->$keyName);
        }
        $data[] = $item;
    }
    return $data;
}

/**
 * get请求接口
 * @param string $url
 * @return string
 */
function doGet($url = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 规避SSL验证
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 跳过HOST验证
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

/**
 * json方式post请求
 * @param $url
 * @param array $data
 * @return bool|string
 */
function culrlPost($url, $data = [])
{
    $data = json_encode($data); //设置参数，若有多个参数用&连接
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        )
    );
    curl_setopt($ch, CURLOPT_POST, 1); //启用POST提交
    $file_contents = curl_exec($ch);
    curl_close($ch);
    return $file_contents;
}

/**
 * 说明:找出key val对应的值返回
 * @param array $array 源数据
 * @param string $key 找的键主键
 * @param string $var 找的值
 * @param boolen $multi 是否要合并多维
 * @return array
 */
function find_array_key($array, $key = 'id', $var = '', $multi = false)
{
    $out = array();
    foreach ($array as $value) {
        if ($value[$key] == $var) {
            if ($multi) {
                $out[] = $value;
            } else {
                $out = $value;
                break;
            }
        }
    }
    return empty($out) ? false : $out;
}
/**
 * 说明:读取某数某个字段组合为一维数组
 * @param array $array 须改变的变量
 * @param string $key 使用的主键
 * @return array
 */
function get_array_key($array, $key = 'id')
{
    if (is_array($array)) {
        $narray = array();
        foreach ($array as $value) {
            $narray[] = $value[$key];
        }
        return $narray;
    } else {
        return array();
    }
}
/**
 * 说明:自动索引多级分类,多维数组返回list_by_sort fix:2013-6-25
 * @param string $sort 索引路径如1,2,3
 * @param mixd $class 输入数组的话是经list_to_tree处理的数组，输入字串符的话就是代表表名，或array('db'=>'Area','pid'=>'pid')
 * @param string $mkey 主键
 * @param string $child 节点变量
 * @param string $multi 组合的类型true为多选用其它的单维数组输出
 * @param int $l 设定固定输出的长度
 * @return array
 */
function path_to_list($sort, $class, $mkey = 'id', $child = '_child', $multi = true, $l = 0)
{
    $array = array();
    if (is_string($sort)) {
        $sortarray = explode(',', $sort);
        $sortarray = array_filter($sortarray);
        if (empty($sortarray)) $sortarray = array(0);
    } elseif (!is_array($sort) || empty($sort)) {
        $sortarray = array(0);
    } else {
        $sortarray = $sort;
    }
    //$class数据处理
    if (is_string($class)) {
        $class = array('db' => $class, 'pid' => 'pid');
    }
    if (!empty($class['db']) && is_string($class['db'])) {
        $pid = empty($class['pid']) ? 'pid' : $class['pid'];
        $model = D($class['db']);
        $where = array('pid' => array('in', $sortarray));
        array_pop($where['pid'][1]);
        $model->where($where);
        $where['pid'][1][] = '0';
        //echo $model->where($where)->buildSql();
        $class = list_to_tree($model->where($where)->select(), $mkey, 'pid', $child);
    }
    foreach ($sortarray as $value) {
        $tmparray = array();
        foreach ($class as $key => $data) {
            if ($data[$mkey] == $value) {
                $tclass = $data;
                if (!empty($data[$child])) unset($data[$child]);
                if ($multi)
                    $tmparray[] = array_merge($data, array('se' => 'selected'));
                else
                    $tmparray = $data;
            } else {
                if ($multi) $tmparray[] = array_merge($data, array('se' => ''));
            }
        }
        $array[] = $tmparray;
        if (!empty($tclass[$child])) {
            $class = $tclass[$child];
        } else {
            $class = array();
            break;
        }
    }
    if ($l > 0 && count($array) < $l) {
        !empty($class) && $array[] = $class;
        $array = array_pad($array, $l, array());
    }
    return $array;
}
/**
 * 说明:把key设为指定的变量
 * @param array $array 须改变的变量
 * @param string $key 使用的主键
 * @param string $node 开启节点模式
 * @param bool $mulit 重复主键的多维数组记录方式
 * @param bool $keepk 重复主键的多维数组保留主键
 * @return array
 */
function list_by_key($array, $key = 'id', $node = false, $mulit = false, $keepk = true)
{
    $narray = array();
    foreach ($array as $kv => $value) {
        $keyvar = $value[$key];
        if (false !== $node && !empty($value[$node])) $value[$node] = list_by_key($value[$node], $key, $node);
        if (!$mulit) {
            $narray[$keyvar] = $value;
        } else {
            if ($keepk)
                $narray[$keyvar][$kv] = $value;
            else
                $narray[$keyvar][] = $value;
        }
    }
    return $narray;
}
/**
 * 说明:把返回的数据集转换成Tree
 * @param array $list 原数据
 * @param string $pk 主键
 * @param string $pid 索引键
 * @param string $child 节点键名
 * @param int $root 开始点的pid名
 * @return array
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0, $keepKey = false)
{
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                if ($keepKey) {
                    $tree[$data[$pk]] = &$list[$key];
                } else {
                    $tree[] = &$list[$key];
                }
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    if ($keepKey) {
                        $parent[$child][$data[$pk]] = &$list[$key];
                    } else {
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
    }
    return $tree;
}
/**
 * 对查询结果集进行排序
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型
 * @param bool $keepkey 保留键
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list, $field, $sortby = 'asc', $keepkey = true)
{
    if (is_array($list)) {
        $refer = $resultSet = array();
        foreach ($list as $i => $data)
            $refer[$i] = &$data[$field];
        switch ($sortby) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc': // 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val) {
            if ($keepkey)
                $resultSet[$key] = &$list[$key];
            else
                $resultSet[] = &$list[$key];
        }
        return $resultSet;
    }
    return false;
}
/**
 * 说明:内循环查到key值的内容返回 fix 12.05.25
 * @param array $array 输入的数组
 * @param mix $key 输入的键值
 * @param string $child 节点键名
 * @param string $order 排序，默认asc从一级到N级
 * @return array
 */
function each_find_array($array, $skey, $child = '_child', $order = 'asc')
{
    foreach ($array as $key => $value) {
        if ($key == $skey) {
            if (!empty($value[$child])) unset($value[$child]);
            return array($value);
        } elseif (!empty($value[$child])) {
            $jg = each_find_array($value[$child], $skey, $child, 'desc');
            if ($jg !== false) {
                unset($value[$child]);
                $jg[] = $value;
                return $order == 'asc' ? array_reverse($jg) : $jg;
            }
        }
    }
    return false;
}


/**
 * 说明:剪切字串符
 * @param string $str 输入的字符
 * @param int $length 裁的长度
 * @param string $suffix 补充字符
 * @param int $start 起始字符位
 * @param string $charset 字符格式
 * @return string
 */
function cutstr($str, $length, $suffix = '...', $start = 0, $charset = "utf-8")
{
    $str = strip_tags(htmlspecialchars_decode($str));
    $str = str_replace(array("&nbsp;", " "), '', $str);
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    if ($str === $slice)
        return $slice;
    else
        return $slice . $suffix;
}

function strip_html_tags($tags, $str)
{
    $html = array();
    foreach ($tags as $tag) {
        $html[] = "/(<(?:\/" . $tag . "|" . $tag . ")[^>]*>)/i";
    }
    $data = preg_replace($html, '', $str);
    return $data;
}


/**
 * 按月分表
 *
 * @return array
 */
function splitTableByMonth($prefiTable, $date = '')
{
    if (empty($date)) {
        $month = date('m');
    } else {
        $month = date('m', strtotime($date));
    }
    $table = $prefiTable . '_' . intval($month);
    return $table;
}

/**
 * 时间转化
 * @return string
 */
function timeToStr($ts)
{
    if (!ctype_digit($ts)) {
        $ts = strtotime($ts);
    }
    $diff = time() - $ts;
    if ($diff == 0) {
        return '现在';
    } else if ($diff > 0) {
        $day_diff = floor($diff / 86400);
        if ($day_diff == 0) {
            if ($diff < 60)     return '刚刚';
            if ($diff < 120)     return '1分钟前';
            if ($diff < 3600)     return floor($diff / 60) . '分钟前';
            if ($diff < 7200)     return '1小时前';
            if ($diff < 86400)     return floor($diff / 3600) . '小时前';
        }
        if ($day_diff == 1)     return '昨天';
        if ($day_diff < 7)         return $day_diff . '天前';
        if ($day_diff < 31)     return ceil($day_diff / 7) . '周前';
        if ($day_diff < 60)     return '上个月';
        return date('Y-m-d', $ts);
    } else {
        $diff = abs($diff);
        $day_diff = floor($diff / 86400);
        if ($day_diff == 0) {
            if ($diff < 120)     return '一分钟内';
            if ($diff < 3600)     return floor($diff / 60) . '分钟后';
            if ($diff < 7200)     return '一小时内';
            if ($diff < 86400)     return floor($diff / 3600) . '小时内';
        }
        if ($day_diff == 1)     return '明天';
        if ($day_diff < 4)         return date('l', $ts);
        if ($day_diff < 7 + (7 - date('w')))     return '下周';
        if (ceil($day_diff / 7) < 4)             return ceil($day_diff / 7) . '周后';
        if (date('n', $ts) == date('n') + 1)     return '下个月';
        return date('Y-m-d', $ts);
    }
}

/**
 * 获取ip地址信息
 * @return string
 */
function getIpInfo($ip)
{
    $url        = "https://www.maitube.com/ip/?ip=" . $ip;
    $ipInfo     = httpGetContents($url, '', 2);
    $address    = '';
    if (!empty($ipInfo) && !strpos($ipInfo, '404 Not Found')) {
        $addressArr = explode(':', $ipInfo);
        $address    = end($addressArr);
        
    } else {
        $ip2region = new \service\Ip2Region();
        $ipInfo = $ip2region->btreeSearch($ip);
        if (!empty($ipInfo) && is_array($ipInfo)) {
            $addressArr = explode('|', $ipInfo['region']);
            $address = join('', array_filter($addressArr));
        }
    }
    return $address;
}

/**
 * 组合算法:C(m,n)
 * 支持截取
 * @param $array
 * @param $m
 * @param int $start
 * @param int $limit
 * @return array
 */
function combination($array, $m, $start = 0, $limit = 500)
{
    static $_start, $_level = 0;

    $r = array();
    $n = count($array);
    if ($m <= 0 || $m > $n) {
        return $r;
    }

    if ($_level == 0) $_level = $m;
    if ($_start == 0) $_start = $start;

    for ($i = 0; $i < $n; $i++) {
        $t = array($array[$i]);
        if ($m == 1) {
            $r[] = $t;
        } else {
            $b = array_slice($array, $i + 1);
            $c = combination($b, $m - 1, $start, $limit);

            if ($m == $_level && $_start > 0) {
                // 截取
                $count = count($c);
                if ($count >= $_start) {
                    $c      = array_slice($c, $_start);
                    $_start = -1;
                } else {
                    $_start -= $count;
                    continue;
                }
            }

            foreach ($c as $v) {
                if ($m == $_level && count($r) >= $limit) break;
                $r[] = array_merge($t, $v);
            }
        }
    }
    return $r;
}

/**
 * 写入配置
 *
 * @return array
 */
function setStaticData($dir, $fileName, $model)
{
	$file = CODE_PATH . 'static' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $fileName . '.php';
	$content = "<?php\nreturn " . str_replace("'CURRENT_TIMESTAMP'", "date('Y-m-d H:i:s')", var_export($model, true)).";\n";
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
	$conf = include($file);
	return $conf;
}

/**
 * 获取静态配置
 *
 * @return array
 */
function getStaticData($dir, $fileName, $suffixName = 'php')
{
	$file = CODE_PATH . 'static' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $fileName . '.' . $suffixName;
	if (!file_exists($file)) {
		return false;
	}
	$data = '';
	if ($suffixName == 'php') {
		$data = include($file);
	} else {
		$data = file_get_contents($file);
	}
	return $data;
}