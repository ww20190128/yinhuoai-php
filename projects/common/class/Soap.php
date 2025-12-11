<?php
namespace service;
loadFile('nusoap', LIB_PATH . 'Nusoap' . DS); // 引入第三方库

/**
 * Soap 类的 封装
 * 
 * @author
 */
class Soap
{
	private static $namespace = 'soapInterface'; // 空间名
    private static $url;        // url 地址
    private $soapClient; // 客户端
    private $soapServer; // 服务器

    public $response;  // 响应信息

    /**
     * 初始化SOPA服务端
     * 
     * @return void
     */
    public function __construct()
    {
        $this->soapServer = new \soap_server();  
        $this->soapServer->configureWSDL(self::$namespace, "");
        $this->soapServer->headers['content-type'] = "text/html; charset=UTF-8";
        $this->soapServer->xml_encoding = "UTF-8";
        $this->soapServer->wsdl->schemaTargetNamespace = "urn:{self::$namespace}";
        return;
    }

    /**
     * 初始化SOAP客户端
     *
     * @param $url
     * @internal param string $region_url SOPA服务地址
     *
     * @return void
     */
    public function init($url)
    {
        self::$url = $url;
        $this->soapClient = new \soapclient(self::$url . "?WSDL", false);
        return ;
    }

    /**
     * 调用远程方法
     * 
     * @param $operation string 方法名
     * @param $params array 参数列表
     * 
     * @return array
     */
    public function call($operation, $params = array())
    {
        $result = $this->soapClient->call($operation, $params);
        if (empty($result)) {
            $this->response = $this->soapClient->response; // // 获取响应信息
            // 调用 [URL]/?[OPERATION] 失败
            Framework::$Exception->error("e.soap.call [URL]/?[OPERATION] error", array('URL' => self::$url, 'OPERATION' => $operation));
        }
        return $result;
    }

    /**
     * 注册服务端接口
     * 
     * @param string    $func       方法名
     * @param array     $args       参数信息表
     * @param array     $return     结果信息
     * @param string    $desc       接口描述
     * 
     * @return void
     */
    public function register($func, $desc, $args = null, $return = null)
    {
        if (is_null($args)) $args = array("token"=>"xsd:String", "parameter"=>"xsd:Array");
        if (is_null($return)) $return = array("return"=>"xsd:Array");
        $this->soapServer->register($func, $args, $return,
            "urn:{self::$namespace}", "urn:{self::$namespace}#{$func}",
            "rpc", "encoded", $desc);



        /**
         * soap 的接口入口
         */
        define('ROOT_PATH', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR);
        define('DISPATCH_PATH', ROOT_PATH . 'Dispatch' . DIRECTORY_SEPARATOR);
        require_once DISPATCH_PATH . 'dispatch.php';
        $soapSv = Framework::$Locator->getService("Soap");
        $soapSv->register(
            "CustomerCity.cityList",
            "获取服务器城市列表|array('id'=>'城市ID', name'=>'城市名称', 'x'=>'所在城市X坐标')",
            array('name'=>'城市名称', 'page'=>'当前页数', 'perpage'=>'每页显示的数量'), // 参数
            array("return"=>"xsd:Array") // 返回格式
        );
        $soapSv->startService();
    }

    /**
     * 开启SOAP服务
     * 
     * @return void
     */
    public function startService()
    {
        $f = file_get_contents("php://input");
        if (empty($HTTP_RAW_POST_DATA)) $HTTP_RAW_POST_DATA = $f;
        $this->soapServer->service($HTTP_RAW_POST_DATA);
    }
}