<?php
namespace service;

/**
 * 客户端工具
 * 
 * 获取客户端IP、操作系统、浏览器以及HTTP操作等功能
 * 
 * @author wangwei
 */
class Client
{
    /**
     * 跳转网址
     *
     * @param   string  $url    url地址
     * @param   int     $mode   模式
     *
     * @return void
     */
    public static function redirect($url, $mode = 302)
    {
        header("Location: " . $url, $mode);
        header("Connection: close");
        exit;
    }

    /**
     * 发送下载声明
     *
     * @param   string     $mime        文件类型
     * @param   string     $filename    文件名
     *
     * @return void
     */
    public static function download($mime, $filename)
    {
        header("Content-type: $mime");
        header("Content-Disposition: attachment; filename=$filename");
    }

    /**
     * 获取客户端IP
     *
     * @return  string
     */
    public static function getIP()
    { 	
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '';
        }
        return($ip);
    }
    
   /**
     * 获取请求方法
     * 
     * @return string
     */
    public static function requestMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**  
     * 获取客户端浏览器信息 
     * @return  string   
     */
    public static function getBrowser($agent = '')
    {
        if (empty($agent)) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
        }
        $matchRules = [
            'UC'                => '/UCWEB [\d\.\w]*/i', // uc
            'Opera '            => '/Opera\/[\d\.\w]*/i', // Opera
            'Opera'             => '/OPR\/[\d\.\w]*/i', // Opera
            '火狐'              => '/Firefox\/([\d\.\w]*)/i', // Firefox 
            '搜狗'              => '/SE.*MetaSr/i', // sougou
            '360安全'           => '/360SE/i', // 360
            '360极速'           => '/360EE/i', // 360
            'IE'                => '/MSIE [\d\.\w]*/i', // IE 
            'MicroSoft Edge'    => '/Edg\/[\d\.]*/i', // MicroSoft Edge
            '微信'              => '/MicroMessenger\/[\d\.]*/i', // 微信
            'QQ'                => '/QQBrowser\/[\d\.]*/i', // QQ
            '钉钉'              => '/DingTalk/i', // 钉钉
            'UC '               => '/UCBrowser\/[\d\.\w]*/i', // uc
            '百度'              => '/BIDU|baidu/i', // 百度
            '猎豹'              => '/LBBROWSER/i', // 猎豹
            '遨游'              => '/Maxthon/i', // 遨游
            '谷歌'              => '/Chrome\/[\d\.\w]*/i', // Chrome
            'Safari'            => '/Safari\/[\d\.\w]*/i', // Safari
            'IE '               => '/rv\:[\d\.\w]*/i', // IE
        ];
        $browser = 'unknown';
        foreach ($matchRules as $name => $patten) {
            if (preg_match($patten, $agent)) {
                $browser = $name;
                break;
            }
        }
        return $browser . '浏览器';
    }

    /**  
     * 获取客户端操作系统信息
     * @return  string   
     */
    public static function getOS($agent = '')
    {
        if (empty($agent)) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
        }
        if (preg_match('/Android/i', $agent)) {
            $os = 'Android';
        } else if (preg_match('/iPhone/i', $agent) && preg_match('/os/i', $agent)) {
            $os = 'iPhone OS';
        } else if (preg_match('/mac/i', $agent) && preg_match('/os/i', $agent)) {
            $os = 'Mac OS';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 10.0;/i', $agent)) {
            $os = 'Windows 10';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.2;/i', $agent)) {
            $os = 'Windows 8';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.1;/i', $agent)) {
            $os = 'Windows 7';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.0;/i', $agent)) {
            $os = 'Windows Vista';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 5.1;/i', $agent)) {
            $os = 'Windows XP';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt 5;/i', $agent)) {
            $os = 'Windows 2000';
        } else if (preg_match('/win/i', $agent) && preg_match('/nt;/i', $agent)) {
            $os = 'Windows NT';
        } else if (preg_match('/win 9x/i', $agent) && strpos($agent, '4.90;')) {
            $os = 'Windows ME';
        } else if (preg_match('/win/i', $agent) && strpos($agent, '98;')) {
            $os = 'Windows 98';
        } else if (preg_match('/win/i', $agent) && strpos($agent, '95;')) {
            $os = 'Windows 95';
        } else if (preg_match('/win/i', $agent) && strpos($agent, '32;')) {
            $os = 'Windows 32';
        } else if (preg_match('/linux/i', $agent)) {
            $os = 'Linux';
        } else if (preg_match('/unix/i', $agent)) {
            $os = 'Unix';
        } else if (preg_match('/sun/i', $agent) && preg_match('/os/i', $agent)) {
            $os = 'Solaris';
        } else if (preg_match('/FreeBSD/i', $agent)) {
            $os = 'FreeBSD';
        } else if (preg_match('/ibm/i', $agent) && preg_match('/os/i', $agent)) {
            $os = 'IBM OS/2';
        } else if (preg_match('/PowerPC/i', $agent)) {
            $os = 'PowerPC';
        } else if (preg_match('/AIX/i', $agent)) {
            $os = 'AIX';
        } else if (preg_match('/HPUX/i', $agent)) {
            $os = 'HPUX';
        } else if (preg_match('/NetBSD/i', $agent)) {
            $os = 'NetBSD';
        } else if (preg_match('/BSD/i', $agent)) {
            $os = 'BSD';
        } else if (preg_match('/OSF1/i', $agent)) {
            $os = 'OSF1';
        } else if (preg_match('/IRIX/i', $agent)) {
            $os = 'IRIX';
        } else if (preg_match('/teleport/i', $agent)) {
            $os = 'teleport';
        } else if (preg_match('/flashget/i', $agent)) {
            $os = 'flashget';
        } else if (preg_match('/webzip/i', $agent)) {
            $os = 'webzip';
        } else if (preg_match('/offline/i', $agent)) {
            $os = 'offline';
        } else {
            $os = '未知操作系统';
        }
        return $os;
    }

}