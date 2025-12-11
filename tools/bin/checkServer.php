#! /usr/bin/env php
<?php
/**
 * 检查服务器配置及环境
 * 
 * @version 1.0
 * @author wangwei
 * 
 * php checkServer.php -c /data/www/conf/dev.trunk.rxsg.zhanchenggame.com.php
 */
if (PHP_SAPI != 'cli') {
    die('Access policy: HTTP is not allowed.');
}
// -c或者--configuration 配置文件绝对路径 
$opts = getopt('c:', array('configuration:'));
if (isset($opts['c'])) {
    $configuration = $opts['c'];
} elseif (isset($opts['configuration'])) {
    $configuration = $opts['configuration'];
} else {
    $configuration = "/data/www/conf/127.0.0.1.php";
}
if (!is_file($configuration)) {
    fprintf(STDERR, "ERROR: 在目录/data/www/conf/下没有找到配置文件!\n");
    exit(127);
}
$RED = "\x1B[31m";
$NORMAL = "\x1B[0m";
require realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . 'Dispatch' . DIRECTORY_SEPARATOR . 'Bootstrap.php';
//Application::run(Bootstrap::RUN_MODE_NO);
set_time_limit(0);

/**
 * 错误提示
 */
function configure_failed($status) 
{
    echo "\n", "configure failed with exit status ", $status, "\n\n";
    exit($status);
}

/**
 * 读取项目外部配置
 *
 * @param   string  $name    	配置节点名字空间
 * 
 * @throws RuntimeException
 * @return mixed
 */
function configure_cfg($name)
{
    $info = explode('.', $name);
    $module = array_shift($info); // 模块
    if (!empty($path)) {
        if (is_dir($path)) {
            $filename = ltrim($path, CS) . CS . $module . '.conf.php';
        } elseif (is_file($path)) {
            $filename = $path;
        }
    } else {
        $filename = CONFIGS_PATH . $module . '.conf.php';
    }    
    if (!isset($filename) || !is_file($filename)) {
        throw new RuntimeException("配置文件: $filename 没找到");
    }
    $conf = include($filename);
    foreach($info as $slice) {
        if (!isset($conf[$slice])) {
            throw new RuntimeException("配置: $name 没找到");
        }
        $conf = $conf[$slice];
    }
    return $conf;
}
/**
 * 打印日志
 * 
 * @param $format
 * @param $var_args
 */
function configure_log_print($format, $var_args = null) 
{
    $var_args = func_get_args();
    $args = array_slice($var_args, 1);
    $message = vsprintf($format, $args);
    echo($message);
}

function configure_log_debug($format, $var_args = null) 
{
    $var_args = func_get_args();
    $args = array_slice($var_args, 1);
    $message = vsprintf($format, $args);
    if ($message[strlen($message) - 1] != "\n") {
        $message .= "\n";
    }
    echo($message);
}

function configure_log_error($errno, $format, $var_args = null) 
{
    global $php_errormsg;
    $var_args = func_get_args();
    $args = array_slice($var_args, 2);
    $message = vsprintf($format, $args);
    if ($message[strlen($message) - 1] == "\n") {
        $message = substr($message, 0, strlen($message) - 1);
    }
    if ($errno > 0) {
        $message .= sprintf(" (%d: %s)", $errno, posix_strerror($errno));
    } else if (!empty($php_errormsg)) {
        $message .= " ($php_errormsg)";
    }
    if ($message[strlen($message) - 1] != "\n") {
        $message .= "\n";
    }
    return fwrite(STDERR, $message, strlen($message));
}

function configure_log_stderr($format, $var_args = null) 
{
    $var_args = func_get_args();
    $args = array_slice($var_args, 1);
    $message = vsprintf($format, $args);
    if ($message[strlen($message) - 1] != "\n") {
        $message .= "\n";
    }
    return fwrite(STDERR, $message, strlen($message));
}
$reporting = error_reporting(0);
// 检查php版本
echo 'checking for php version... ';
if (version_compare(PHP_VERSION, '5.6') <= 0) {
    echo "失败, 需要 php-5.6及以上版本, 当前为: ", PHP_VERSION, " \n";
    configure_failed(127);
}
echo PHP_VERSION, PHP_EOL;
$phpCli = configure_cfg('server.environ.php_cli');
if (!is_executable($phpCli)) {
    configure_log_stderr('php binary "%s" is not executable.', $phpCli);
    exit(127);
}
// 检查安装的扩展
$extensions = array( // 需要安装的扩展列表
    'curl', 
    'gettext', 
    'json', 
    'mbstring', 
    'msgpack',
    'pcre', 
    'pcntl', 
    'posix', 
    'sockets', 
    'sysvmsg',
	'sysvmsg',
	'EpollServer',
	'zip',
	'zlib',
	'redis',
	'pdo_mysql',
	'Zend OPcache'
);
foreach ($extensions as $extension) {
    echo 'checking for "' . $extension . '" php extension... ';
    if (!extension_loaded($extension)) {
        echo 'failed', PHP_EOL;
        configure_failed(127);
    }
    echo 'ok', PHP_EOL;
}
unset($extensions, $extension);
// 检查目录环境
$runtimeDir = configure_cfg('server.environ.runtime_dir');
configure_log_print('checking runtime directory "%s"... ', $runtimeDir);
$user = configure_cfg('server.environ.user');
$group = configure_cfg('server.environ.group');
if (!file_exists($runtimeDir)) {
    if (mkdir($runtimeDir, 0777, true) === false) {
        configure_log_error(posix_get_last_error(), 'mkdir(%s, 0777, true) failed.', $runtimeDir);
        exit(127);
    }
}
if (!is_dir($runtimeDir)) {
    configure_log_debug('runtime dir must be a directory: "%s".', $runtimeDir);
    exit(127);
} else {
    $uid = fileowner($runtimeDir);
    if ($uid === false) {
        configure_log_stderr('fileowner(%s) failed.', $runtimeDir);
        exit(127);
    }
    $pw = posix_getpwuid($uid);
    if ($pw === false) {
        configure_log_stderr('posix_getpwuid(%d) failed.', $uid);
        exit(127);
    }
    if ($pw['name'] != $user) {
        if (chown($runtimeDir, $user) === false) {
            configure_log_stderr('chown(%s, %s) failed.', $runtimeDir, $user);
            exit(127);
        }
    }
    $gid = filegroup($runtimeDir);
    if ($gid === false) {
        configure_log_stderr("filegroup(%s) failed.", $runtimeDir);
        exit(127);
    }
    $gr = posix_getgrgid($gid);
    if ($gr === false) {
        configure_log_stderr('posix_getgrgis(%d) failed.', $gid);
        exit(127);
    }
    if ($gr['name'] != $group) {
        if (chgrp($runtimeDir, $group) === false) {
            configure_log_stderr('chgrp(%s, %s) failed.', $runtimeDir, $group);
            exit(127);
        }
    }
}
clearstatcache(true);
// 判断目录的权限
$perms = fileperms($runtimeDir);
if (!($perms&0x0100)) {
    configure_log_stderr('runtime dir "%s" has no read perm.', $runtimeDir);
    exit(127);
}
if (!($perms&0x0080)) {
    configure_log_stderr('runtime dir "%s" has no write perm.', $runtimeDir);
    exit(127);
}
if (!(($perms&0x0040) && !($perms&0x0800))) {
    configure_log_stderr('runtime dir "%s" has no enter perm.', $runtimeDir);
    exit(127);
}
unset($perms);
configure_log_debug("ok");
$directories = array(
	'crontab', 
	'epoll', 
	'locks', 
	'logs', 
	'pids', 
	'warData'
);
foreach ($directories as $v) {
    $filename = $runtimeDir . '/' . $v;
    configure_log_print('checking work directory "%s"...', $filename);
    if (!file_exists($filename)) {
        if (mkdir($filename, 0777, true) === false) {
            configure_log_error(posix_get_last_error(), 'mkdir(%s, 0777, true) failed.', $filename);
            exit(127);
        }
    }
    if (!is_dir($filename)) {
        configure_log_debug('runtime dir must be a directory: "%s".', $filename);
        exit(127);
    } else {
        $uid = fileowner($filename);
        if ($uid === false) {
            configure_log_stderr('fileowner(%s) failed.', $filename);
            exit(127);
        }
        $pw = posix_getpwuid($uid);
        if ($pw === false) {
            configure_log_stderr('posix_getpwuid(%d) failed.', $uid);
            exit(127);
        }
        if ($pw['name'] != $user) {
            if (chown($filename, $user) === false) {
                configure_log_stderr('chown(%s, %s) failed.', $filename, $user);
                exit(127);
            }
        }

        $gid = filegroup($filename);
        if ($gid === false) {
            configure_log_stderr("filegroup(%s) failed.", $filename);
            exit(127);
        }
        $gr = posix_getgrgid($gid);
        if ($gr === false) {
            configure_log_stderr('posix_getgrgis(%d) failed.', $gid);
            exit(127);
        }
        if ($gr['name'] != $group) {
            if (chgrp($filename, $group) === false) {
                configure_log_stderr('chgrp(%s, %s) failed.', $filename, $group);
                exit(127);
            }
        }
    }
    configure_log_debug("ok");
}
unset($directories, $user);

# 检查rsyslog服务器
echo 'checking rsyslogd service...';
exec('service rsyslog status', $output, $status);
echo implode(PHP_EOL, $output), PHP_EOL;
if ($status) {
    echo 'failed', PHP_EOL;
    configure_failed(127);
}
unset($output, $status);

// 检查消息队列
configure_log_print('checking message queue capability...queue size:       %dk', 0);
$msg_queue_key = configure_cfg('server.environ.msg_queue_key');
$ready = msg_queue_exists($msg_queue_key);
$msgQueue = msg_get_queue($msg_queue_key, 0666);
$msgtype = 1;
$msgsize = 128;
$message = str_repeat('*', $msgsize);
$ratio = 1024 / $msgsize;
$capability = 0;
$a = msg_send($msgQueue, $msgtype, $message, false, false, $errno);
while (msg_send($msgQueue, $msgtype, $message, false, false, $errno) && $capability < 262144) {
    $capability += 1;
    if (!($capability % $ratio)) {
        configure_log_print("\x1b[8D% 7dk", $capability / $ratio);
    }
}
while (msg_receive($msgQueue, $msgtype, $msgtype, 65536, $message, false, MSG_IPC_NOWAIT, $errno)) {
    /** void */
}
if (!$ready) {
    msg_remove_queue($msgQueue);
}
configure_log_debug("");
unset($msgQueue, $msgsize, $msgtype, $message, $ratio, $capability, $msg_queue_key, $ready);

# 检查后台进程
echo 'checking php Master service...', PHP_EOL;
if (exec("$phpCli " . ROOT_PATH . '/Shell/main.php ProcessManager.main -a restart', $output, $status) === false) {
    configure_log_stderr('Can not restart php master service.');
    exit(127);
}
echo "  ", implode("\n  ", $output), PHP_EOL;
if ($status) {
    configure_failed(127);
}
if (exec("$phpCli " . ROOT_PATH.'/Shell/main.php ProcessManager.main -a stop', $output, $status) === false || $status !== 0) {
    configure_log_stderr('Can not stop php master service.');
    exit(127);
}
unset($output, $status);
# 检查ip 端口 的开启
echo 'checking for caching settings...';
echo PHP_EOL;
echo 'Perfect, all test are ok!', PHP_EOL;
exit(0);