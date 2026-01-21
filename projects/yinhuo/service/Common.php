<?php
namespace service;

/**
 * 公共 逻辑类
 *
 * @author 	wangwei
 */
class Common extends ServiceBase
{
    /**
     * 单例
     *
     * @var \service\Common
     */
    private static $instance;
    
    /**
     * 单例模式
     *
     * @return \service\Common
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Common();
        }
        return self::$instance;
    }
    
    
    // 获取文件路径
    public function getFile($fileName, $schoolId, $checkExist = true)
    {
        if (empty($fileName)) {
            return "";
        }
        // 图片文件夹
        $dir = $this->frame->conf['resourceDir'] . $schoolId . DS;
        $file = $dir . $fileName;
        // 检查文件是否存在
        if (!empty($checkExist)) {
            if (!file_exists($dir)) {
                return "";
            }
        }
        return $file;
    }
    
    // 上传文件
    public function uploadFile($fileInfo, $name, $schoolId)
    {
        if (empty($fileInfo) || empty($fileInfo['file']) || empty($fileInfo['extension'])) {
            return '';
        }
        $tmpFile = $fileInfo['file'];
        $suffix = $fileInfo['extension'];
        // 图片文件夹
        $dir = $this->frame->conf['resourceDir']  . $schoolId . DS ;
        if (!file_exists($dir)) {
            makeDir($dir);
        }
        // 新的文件名
        $fileName = $name . '.' . $suffix;
        $file = $dir . $fileName;
        $tries = 3;
        do {
            $ok = @move_uploaded_file($tmpFile, $file); // 将临时地址移动到指定地址
        } while ($ok === false && --$tries > 0);
        
        if (file_exists($file)) {
            return $fileName;
        } else {
            return '';
        }
    }
    
    // 上传图片
    public function uploadImg($imageInfo, $name, $schoolId)
    {
        if (empty($imageInfo) || empty($imageInfo['data']) || empty($imageInfo['suffix'])) {
            return '';
        }
        $content = $imageInfo['data'];
        $suffix = $imageInfo['suffix'];
        // 图片文件夹
        $dir = $this->frame->conf['resourceDir']  . $schoolId . DS ;
        if (!file_exists($dir)) {
            makeDir($dir);
        }
        // 新的文件名
        $fileName = $name . '.' . $suffix;
        $file = $dir . $fileName;
        $tries = 3;
        do {
            if ($handle = fopen($file, 'w')) {
                $ok = fwrite($handle, $content);
            } else {
                $ok = false;
            }
            fclose($handle);
        } while ($ok === false && --$tries > 0);
        if ($ok === false) {
            file_put_contents($file, $content);
        }
        if (file_exists($file)) {
            return $fileName;
        } else {
            return '';
        }
    }
    
    /**
     * 按id排序(升序)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    public function sortById($row1, $row2)
    {
        if ($row1['id'] < $row2['id']) {
            return -1;
        } elseif ($row1['id'] > $row2['id']) {
            return 1;
        } else {
            return $row1['createTime'] < $row2['createTime'] ? -1 : 1;
        }
    }
    
    /**
     * 按id排序(升序)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    public function sortByUserId($row1, $row2)
    {
        if ($row1['userId'] < $row2['userId']) {
            return -1;
        } elseif ($row1['userId'] > $row2['userId']) {
            return 1;
        } else {
            return $row1['createTime'] < $row2['createTime'] ? -1 : 1;
        }
    }
    
    /**
     * 按创建时间排序(降序)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    public function sortByCreateTime($row1, $row2)
    {
    	$row1Arr = is_object($row1) ? (array)$row1 : $row1;
    	$row2Arr = is_object($row2) ? (array)$row2 : $row2;
        if ($row1Arr['createTime'] < $row2Arr['createTime']) {
            return 1;
        } elseif ($row1Arr['createTime'] > $row2Arr['createTime']) {
            return -1;
        } else {
            return !empty($row1Arr['id']) && $row1Arr['id'] < $row2Arr['id'] ? -1 : 1;
        }
    }
    
    /**
     * 按index排序(由小到大)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    public function sortByIndex($row1, $row2)
    {
        $row1Arr = is_object($row1) ? (array)$row1 : $row1;
        $row2Arr = is_object($row2) ? (array)$row2 : $row2;
        if ($row1Arr['index'] < $row2Arr['index']) {
            return -1;
        } elseif ($row1Arr['index'] > $row2Arr['index']) {
            return 1;
        } else {
            return $row1Arr['id'] < $row2Arr['id'] ? 1 : -1;
        }
    }
    
    /**
     * 按id排序(降序)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    private function sortByStatus($row1, $row2)
    {
        if ($row1['status'] > $row2['status']) {
            return -1;
        } elseif ($row1['status'] < $row2['status']) {
            return 1;
        } else {
            return $row1['createTime'] > $row2['createTime'] ? -1 : 1;
        }
    }
    
    /**
     * 按id排序(升序)
     *
     * @param 	array	$row1		元素1
     * @param 	array	$row2	 	元素2
     *
     * @return int
     */
    public function sortByThreshold($row1, $row2)
    {
    	if ($row1['threshold'] < $row2['threshold']) {
    		return -1;
    	} elseif ($row1['threshold'] > $row2['threshold']) {
    		return 1;
    	} else {
    		return 1;
    	}
    }
    
    /**
     * 处理特殊字符
     */
    public function specialCharacte($str)
    {
        $str = trim($str, "\t\n\r");
        $str = str_replace('\r\n', "<br/>", $str);
        $str = str_replace('\n', "<br/>", $str);
        $str = str_replace('\r', "<br/>", $str);
        while (strpos($str, "<br><br>")) {
            $str = str_replace("<br><br>", "<br/>", $str);
        }
        return $str;
    }
    
    /**
     * 替换文本中的特殊字符
     * 保留的标签有:  <br> <br/> <red></red> <dot></dot> <line></line> <tcenter></tcenter> <sub></sub> <sup></sup> <!--[img][/img]-->
     * @return string
     */
    public function filterHtml($text, $formulaTag = false)
    {
        // 处理<u> 标签
        if (preg_match_all('/<u>(.*?)<\/u>/', $text, $matchArr)) {
            foreach ($matchArr['1'] as $key => $row) {
                $text = str_replace($matchArr['0'][$key], '<line>' . $row . '</line>', $text);
            }
        }
        
        $text = preg_replace('/<u>(.*?)<\/u>/', "________", $text); // 粉笔替换u标签
        $text = preg_replace('/<input type="text"(.*?)\/>/', "________", $text); // 粉笔替换input标签
        $text = preg_replace('/&[a-z]{4};/', "", $text); // 剔除&nbsp; 类似富文本
        $text = str_replace('<br/>', "<br>", $text); // 规范换行
        $tagSearch = array('<!--[img]', '[/img]-->', '<formula');
        $tagReplace = array('__IMG__', '__IMG_E_', '__FORMULA__');
        // 替换保留的标签
        $text = str_replace($tagSearch, $tagReplace, $text);
        
        // 处理<p> 标签
        if (preg_match_all('/<p align="center">(.*?)<\/p>/', $text, $matchArr)) {
            foreach ($matchArr['1'] as $key => $row) {
                $text = str_replace($matchArr['0'][$key], '<tcenter>' . $row . '</tcenter>', $text);
            }
        }
        if (preg_match_all('/<b>(.*?)<\/b>/', $text, $matchArr)) {
            foreach ($matchArr['1'] as $key => $row) {
                $text = str_replace($matchArr['0'][$key], '<red>' . $row . '</red>', $text);
            }
        }
        if (preg_match_all('/<p>(.*?)<\/p>/', $text, $matchArr)) {
            foreach ($matchArr['1'] as $key => $row) {
                $text = str_replace($matchArr['0'][$key], '  ' . $row . '<br>', $text);
            }
        }
        $search  = array(
            '<\/red>',
            '<\/line>',
            '<\/dot>',
            '<\/tcenter>',
            '<\/sub>',
            '<\/sup>',
            '&thinsp;',
            '<br><br><br>',
            '<br><br>',
            '<<'
        );
        $replace = array(
            '</red>',
            '</line>',
            '</dot>',
            '</tcenter>',
            '</sub>',
            '</sup>',
            '',
            '<br>',
            '<br>',
            '< <'
        );
        $text = str_replace($search, $replace, $text);
        if (empty($formulaTag)) {
            $text = strip_tags($text, '<br><red><line><dot><tcenter><sub><sup><formula>'); // 剔除html标签，只保留支持的标签
        }
        
        $text = str_replace($tagReplace, $tagSearch, $text);
        $text = preg_replace('/' . preg_quote('<br>', '/') . '$/', '', $text); // 剔除开头的换行符
        $text = preg_replace('/^' . preg_quote('<br>', '/') . '/', '', $text); // 剔除结尾的换行符
        // 替换公式字符串
        if (!empty($formulaTag)) {
            if (preg_match_all('/\${1,3}(.*?)\${1,3}/', $text, $matchArr)) {
                foreach ($matchArr['1'] as $key => $row) {
                    $text = str_replace($matchArr['0'][$key], '<formula>' . $row . '</formula>', $text);
                }
            }
        }
        return $text;
    }
    
    /**
     * 小数点后两位
     *
     * @return string
     */
    public static function decimal($value, $precision = 2)
    {
        //$value = intval($value * 100) * 0.01;
        $valueArr = explode('.', $value);
        if (empty($valueArr['1'])) {
            $valueArr['1'] = '00';
        } elseif (strlen($valueArr['1']) < $precision) {
            $valueArr['1'] = $valueArr['1'] . str_pad('', $precision - strlen($valueArr['1']), '0');
        } elseif (strlen($valueArr['1']) > $precision) {
            $valueArr['1'] = substr($valueArr['1'], 0, $precision);
        }
        $value = $valueArr['0'] . '.' . $valueArr['1'];
        return (string)$value;
    }
    
    /**
     * 小数点转百分比
     *
     * @return string
     */
    public static function floatToPercent($float)
    {
        return empty($float) ? '0.00%' : self::decimal($float * 100) . '%';
    }
    
    /**
     * 组织图片地址
     *
     * @return string
     */
    public static function formartImgUrl($fileName, $subDir = '')
    {
    	$urlBase = self::$instance->frame->conf['urls']['images'];
    	if (!empty($subDir)) {
    		$urlBase .= $subDir . DS;
    	}
    	return $urlBase . $fileName;
    }
    
    /**
     * 替换图片地址
     *
     * @return string
     */
    public static function replaceImgSrc($content, $subDir = 'report')
    {
    	$urlBase = self::$instance->frame->conf['urls']['images'];
    	if (!empty($subDir)) {
    		$urlBase .= $subDir . DS;
    	}
    	// 使用正则表达式查找所有 img 标签中的 src 属性
    	$pattern = '/(<img\s+[^>]*src=")([^"]+)(")/i';
    	// 将 src 属性的值替换为指定的 baseURL 前缀
    	$replacement = '${1}' . $urlBase . '${2}${3}';
    	// 返回替换后的文本
    	return preg_replace($pattern, $replacement, $content);
    }
    
    /**
     * 获取测评需知
     *
     * @return string
     */
    public static function formartNoticeDiv($price, $questionNum = '', $answerTimeLimit = '')
    {
    	$p1 = array(); // 第一句
    	$p1[] = '本测评为' . 	($price > 0 ? '付费' : '免费') . '测试';
    	if (!empty($questionNum)) {
    		$p1[] = '总共' . $questionNum . '道题';
    	}
    	if (!empty($answerTimeLimit) && $answerTimeLimit > 0) {
    		$p1[] = '测试时长约为' . $answerTimeLimit . '分钟';
    	} else {
    		$p1[] = '测试时长不受限制';
    	}
    	$notice = array();
    	$notice[] = implode('，', $p1) . "。";

    	// 第二句
    	$notice[] = '测题的选项间无对错好坏之分，请在心态平和及时间充足的情况下开始答题，选择与您内心最倾向的选项即可。';
    	// 第三句
    	if ($price > 0) {
    		$notice[] = "本测评可免费作答，查看测验报告需付费，测评为虚拟商品服务，购买后概不退款。";
    	}
    	$notice[] = "测评结果永久保存在『我的』-『我的报告』中，进入即可查看报告，点击报告顶部“重测”按钮，可免费重测。";
    	$indexMap = array('①', '②', '③', '④', '⑤', '⑥', '⑦');
    	foreach ($notice as $index => $text) {
    		$notice[$index] = '<p>' . $indexMap[$index] . $text . '</p>';
    	}
    	return implode('', $notice);
    }
    
}