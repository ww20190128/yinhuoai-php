<?php
namespace drive\view;

/**
 * 模板视图
 */
class TemplateView extends ViewBase
{
    /**
     * 左标识符
     *
     * @var string
     */
	private static $left_identifier ;
	
    /**
     * 右标识符
     *
     * @var string
     */
	private static $right_identifier ;

    /**
     * 模板文件目录
     *
     * @var String
     */
	private static $templateDir;
	
    /**
     * 模板编译目录
     *
     * @var String
     */
    private static $compileDir;

    /**
     * 模板文件路径
     *
     * @var string
     */
    private static $templateFile;
    
    /**
     * 模板编译文件路径
     *
     * @var string
     */
    private static $compileFile;
    
    /**
     * 模板缓存路径
     *
     * @var string
     */
    private static $cacheDir;

    /**
     * 模板文件名
     *
     * @var string
     */
    private $fileName;
    
    /** 
     * 构造函数   设置参数
     * 
     * @param array $args  参数
     *  
     * @return void
     */
	public function __construct() 
	{
		self::$cacheDir         = SMARTY_CACHE_DIR;
        self::$compileDir       = SMARTY_COMPILE_DIR;
        self::$templateDir      = HTML_PATH;
        self::$left_identifier  = SMARTY_LEFT_DELIMITER;
        self::$right_identifier = SMARTY_RIGHT_DELIMITER;
	}

    /**
     * 展示视图
     *
     * @param  string $fileName    模板名
     * @param  string $model        视图数据
     *
     * @throws \Exception
     * @return string    编译模板
     */
    public function display($fileName = null, $model = null)
    {    	
        header("Content-Type: text/html; charset=utf-8"); 
        $this->fileName = $fileName;
   //     $this->model = $model;
        self::$templateFile = implode(DIRECTORY_SEPARATOR, array(self::$templateDir, $this->fileName)).'.html';
        if (!is_readable(self::$templateFile))
        {
            throw new \Exception('template file can\'t read: ' . self::$templateFile);
        }
        self::$compileFile = implode(DIRECTORY_SEPARATOR, array(self::$compileDir, $this->_getCompileName()));
        if (!file_exists(self::$compileFile))
        {

            if (!is_writable(self::$compileDir))
            {
                throw new \Exception('compile directory can\'t write: ' . self::$compileDir);
            }
            $content = file_get_contents(self::$templateFile);
            $content = $this->_compileContent($content);
            file_put_contents(self::$compileFile, $content);
        }
        require self::$compileFile;
    }
    
	/**
     * 获取模板编译文件名
     *
     * @return string
     */
    private function _getCompileName()
    {	
        $fileItems = explode('.', $this->fileName);
        if (count($fileItems) > 1)
        {
            array_pop($fileItems);
        }
        return implode('.', $fileItems).'.'.filemtime(self::$templateFile).'.php';
    }
    
	/**
     * 编译模板内容
     *
     * @param string $content
     * 
     * @return string
     */
    private function _compileContent($content)
    {
        $matchNum = preg_match_all('/'.preg_quote(self::$left_identifier).'(.*?)'.preg_quote(self::$right_identifier).'/is', $content, $matchs);
        if (!empty($matchNum))
        {
            $pairs = array();
            for ($i = 0; $i < $matchNum; $i++)
            {
                $replace = $matchs[0][$i];
                $replace = $this->_compileVariable($replace);
                $replace = $this->_compileIdent($replace);

                $pairs[$matchs[0][$i]] = $replace;
            }
            if (count($pairs) > 0)
            {
                $content = str_replace(array_keys($pairs), array_values($pairs), $content);
            }
        }
        return $content;
    } 

    /**
     * 编译模板变量
     * @param  string $content  模板内容
     * 
     * @return string 			编译之后的内容
     */
    private function _compileVariable($content)
    {
        $matchNum = preg_match_all('/(\$[\w\[\]\.\$]+)/', $content, $matchs);
        if (!empty($matchNum))
        {
            $pairs = array();
            $replace = array(
                '/\.(\w+)\./'   => "['\$1']",
                '/\.(\w+)\[/'   => "['\$1'][",
                '/\.(\w+)\]/'   => "['\$1']]",
                '/\.(\w+)$/m'  => "['\$1']",
                '/\](\w+)\[/'   => "]['\$1'][",
                '/\](\w+)\]/'   => "]['\$1']]",
                '/\](\w+)$/m'  => "]['\$1']",
                '/\$\[/'   => "\$this->model[",
            );
            for ($i = 0; $i < $matchNum; $i++)
            {
                $pairs[$matchs[0][$i]] = preg_replace(array_keys($replace), array_values($replace), $matchs[0][$i]);
            }
            if (count($pairs) > 0)
            {
                $content = str_replace(array_keys($pairs), array_values($pairs), $content);
            }
        }
        return $content;
    }

    /**
     * 编译模板标识符
     *
     * @param  string $content  模板内容
     * 
     * @return string 			编译之后的内容
     */
    private function _compileIdent($content)
    {
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*=(.*?)\s*'.preg_quote(self::$right_identifier).'$/m', '<?php echo $1; ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*while\s+(.*?)\s*'.preg_quote(self::$right_identifier).'$/m', '<?php while($1) { ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*\/while\s*'.preg_quote(self::$right_identifier).'$/m', '<?php } ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*foreach\s+(.*?)\s*'.preg_quote(self::$right_identifier).'$/m', '<?php foreach($1) { ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*\/foreach\s*'.preg_quote(self::$right_identifier).'$/m', '<?php } ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*for\s+(.*?)\s*'.preg_quote(self::$right_identifier).'$/m', '<?php for($1) { ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*\/for\s*'.preg_quote(self::$right_identifier).'$/m', '<?php } ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*if\s+(.*?)\s*'.preg_quote(self::$right_identifier).'$/m', '<?php if($1) { ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*else\s*'.preg_quote(self::$right_identifier).'$/m', '<?php } else { ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*else\s*if\s*'.preg_quote(self::$right_identifier).'$/m', '<?php } elseif { ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'\s*\/if\s*'.preg_quote(self::$right_identifier).'$/m', '<?php } ?>', $content);
        $content = preg_replace('/^'.preg_quote(self::$left_identifier).'(.*?)'.preg_quote(self::$right_identifier).'$/m', '<?php $1; ?>', $content);
        return $content;
    }

    /**
     * 设置配置信息
     *
     * @param string $templateDir
     * @param string $compileDir
     * 
     * @return void
     */
    public static function setConfig($templateDir, $compileDir)
    {
        self::$templateDir = $templateDir;
        self::$compileDir = $compileDir;
        return ;
    }
}