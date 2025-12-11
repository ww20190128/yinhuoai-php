<?php
namespace service\reuse;

/**
 * 屏蔽相关的逻辑
 * 
 * @author wangwei
 */
class MaskWord
{

    /**
     * 获取屏蔽词数据的URL
     *
     * @var string
     */
    private $filterDataUrl;

    /**
     * 屏蔽词文件地址
     *
     * @var string
     */
    private $filterFilePath;

    

    /**
     * 特殊过滤字符
     *
     * @var array
     */
    private static $filterChars = array(
            "'", '"', "[", "]", "<", ">",
            "#", "|", "\t", "\n", "\\",
        );

    /**
     * 过滤正则表达式列表
     *
     * @var array
     */
    private static $filterReg;
    
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return MaskWord
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MaskWord();
        }
        return self::$instance;
    }

    /**
     * 检测是否含有屏蔽词，或者过滤屏蔽词
     *
     * @param	string	$word    未过滤的文字
     * @param 	string 	$replace 替换字符（默认为null时，只进行是否含有屏蔽词的检测）
     *
     * @return mixed 当$replace参数传字符串时进行替换后返回，为null是返回bool值
     */
    public function filter($word, $replace = null)
    {
    	return true;
        if (empty(self::$filterReg)) {
            self::$filterReg = array();
            $filePath = $this->filterFilePath;
            if (is_readable($filePath)) {
                $filterReg = require($filePath);
                if (!is_array($filterReg))
                    $filterReg = array($filterReg);
            } else {
                $filterReg = array();
            }
            self::$filterReg = $filterReg;
        }
        // 特殊字符的处理
        static $specialFR = null;
        if (empty($specialFR) && !empty(self::$filterChars)) {
            $specialFR = '/' . implode('|', array_map('preg_quote', self::$filterChars)) . '|[\\p{C}]+/is';
        }
        if (null === $replace) {
            $result = false;
            if (!empty($specialFR))
                $filterReg[] = $specialFR;
            foreach ($filterReg as $fr) {
                if (!preg_match($fr, $word)) continue;
                $result = true;
                break;
            }
        } else {
            $result = $word;
            foreach ($filterReg as $fr) {
                $result = preg_replace($fr, $replace, $result);
            }
            if (!empty($specialFR)) {
                $result = preg_replace($specialFR, '', $result);
            }
        }
        return $result;

    }

    /**
     * 生成屏蔽词缓存文件
     *
     * @return void
     */
    public function createFilterCache()
    {
        $data = $this->getFilterData();

        // 去重，每1500条拆为一组
        $data = array_unique($data);
        $subDatas = array_chunk($data, 1500);

        // 生成游戏用屏蔽词列表文件
        $filterFile = $this->filterFilePath;
        if (!empty($filterFile)) {
            $filterReg = array();
            foreach ($subDatas as $key => $subData) {
                $filterReg[] = '/' . implode('|', array_map('preg_quote', $subData)) . '/is';
            }
            $fileData = "<" . "?php\nreturn " . var_export($filterReg, true) . ";\n?" . ">";
            file_put_contents($filterFile, $fileData);
        }

        // 生成gameworld用屏蔽词列表
        $gameworldFilterFile = $this->gameworldFilterFilePath;
        if (!empty($gameworldFilterFile)) {
            $fileData = implode("\n", $data);
            file_put_contents($gameworldFilterFile, $fileData);
        }

    }

    /**
     * 获取所有来源的屏蔽词列表
     *
     * @return array
     */
    private function getFilterData()
    {
        // http://www3.kunlun.com/?act=inter.getFilterWords&type=1&kind=0&style=1
        // 类型type 0全部 1 国内 2日本 3马来 4台湾 5香港 6英文
        // 屏蔽字类别kind 0全部 1名字屏蔽 用户名,昵称,角色名 2内容屏蔽 聊天,邮件,论坛
        // 样式style 0为PHP格式 1为文本格式
        $url = $this->filterDataUrl;

        // 读取平台屏蔽词列表
        $text = trim(file_get_contents($url));
        $platformData = explode("\n", $text);

        // TODO 获取游戏内或其他来源的屏蔽词数据，汇总到$data中

        $data = $platformData;
        return $data;
    }

}