<?php
namespace service;
use constant\Common;
loadFile(array('simple_html_dom'), LIB_PATH . 'Dom' . DS);    // 加载Dom

/**
 * 采集  逻辑类
 *
 * @author
 */
class Grab extends ServiceBase
{
    /**
     * 下载图片
     */
    public function xz_getImgs()
    {
        $dir = "/data/www/assets/";
        $targetDir = "/data/www/assets-new";
        // , array('.png', '.jpg', 'gif')
        $fileList1 = getFilesByDir($dir, '.png');
        $fileList2 = getFilesByDir($dir, '.jpg');
        $fileList3 = getFilesByDir($dir, '.gif');
        
        $fileList = array_merge($fileList1, $fileList2, $fileList3);
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 5 // 超时时间，单位为秒
            )
        )); 
        // 查找关键字
        $keyword1 = 'module.exports = "data:image';
        $keyword2 = 'module.exports = __webpack_public_path__ + "static/img/';
        $pictureFileList = array();
        foreach ($fileList as $file) {
            $fileInfo = selfPathInfo($file);
            $pictureFile = $targetDir . DS . $fileInfo['basename']; // 目标文件
            if (file_exists($pictureFile)) {
                continue;
            }
            echo $file . "\n";continue;
            $fileContent = file_get_contents($file);
            $pictureContent = ''; // 目标文件的内容
            
            // 判断字符串中是否包含关键字
            if (strpos($fileContent, $keyword1) !== false) {
                // 截取最后一个逗号后的内容
                $pictureContent = substr($fileContent, strrpos($fileContent, ',') + 1);
                $pictureContent = base64_decode($pictureContent);
               
            } elseif (strpos($fileContent, $keyword2) !== false) {
                // https://one.1cece.top/h5/static/img/red-packet-blin.8b634a58.png
                // 截取 static/img/ 后的内容
                $filename = substr($fileContent, strpos($fileContent, $keyword2) + strlen($keyword2));
                // 提取图片名称
                $imageName = substr($filename, 0, strpos($filename, '"'));
                $imageUrl = "https://one.1cece.top/h5/static/img/" . $imageName;

                $tries = 3;
                do {
                    $pictureContent = @file_get_contents($imageUrl, 0, $context);
                } while (empty($pictureContent) && --$tries > 0);
            }
            if (empty($pictureContent)) {
                continue;
            }
           
            $tries = 3;
            do {
                if ($handle = fopen($pictureFile, 'w')) {
                    $ok = fwrite($handle, $pictureContent);
                } else {
                    $ok = false;
                }
                fclose($handle);
            } while ($ok === false && --$tries > 0);
            $pictureFileList[] = $pictureFile;
        }
        print_r($pictureFileList);exit;
    }
    
    /**
     * 获取题目数据
     */
    private function xz_detail($goodsInfo, $goods_version_select = 1)
    {
        sleep(1);
        // 创建交易订单
        $putOrderUrl = "https://adapi.monday1.top/v1/goods/put_order";
        $data = array(
            'goods_version_select' => $goods_version_select,
            'browser_version' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'guid' => $goodsInfo['guid'],
            'hasParams' => 1,
            'network' => 'other',
            'phone_model' => '',
            'screen_resolution' => '1280*720',
            'use_env' => 2,
        );
        $putOrderResponse = httpGetContents($putOrderUrl, $data);
        $putOrderResponse = json_decode($putOrderResponse, true);
        // 交易单号
        $out_trade_no = empty($putOrderResponse['data']['out_trade_no']) ? '' : $putOrderResponse['data']['out_trade_no'];
        if (empty($out_trade_no)) {
            $error = "获取商品[{$goodsInfo['goods_name']}]获取交易单号失败！";
            echo $error . "\n";
            return  false;
            throw new $this->exception($error);
        }
        $createUrl = "https://adapi.monday1.top/v1/paper_order/create";
        $data = array(
            'out_trade_no' => $out_trade_no,
        );
        $createOrderResponse = httpGetContents($createUrl, $data);
        $createOrderResponse = json_decode($createOrderResponse, true);
        $paper_order_sn = empty($createOrderResponse['data']['paper_order_sn']) ? '' : $createOrderResponse['data']['paper_order_sn'];
        if (empty($paper_order_sn)) {
            $error = "获取商品[{$goodsInfo['goods_name']}]获取交易码失败！";
            echo $error . "\n";
            return  false;
            throw new $this->exception($error);
        }
        // 获取题目
        $questionUrl = "https://adapi.monday1.top/v1/paper_order/detail?paper_order_sn=" . $paper_order_sn;
     
        $questionResponseResult = httpGetContents($questionUrl);
        $questionResponse = json_decode($questionResponseResult, true);
        return empty($questionResponse['data']) ? '' : $questionResponse['data'];
    }
    
    /**
     * 采集心芝题目
     *
     * @return array
     */
    public function xz()
    {   
        // 获取分类
        $classifyUrl = "https://adapi.monday1.top/v1/goods_category/getList";
        $classifyResponse = httpGetContents($classifyUrl);
        //$classifyResponse = '{"code":1,"message":"success","data":[{"id":0,"category_name":"全部","category_icon_code":""},{"id":22,"category_name":"免费测试","category_icon_code":"fa-feed"},{"id":7,"category_name":"爱情婚姻","category_icon_code":"fa-heartbeat"},{"id":8,"category_name":"心理健康","category_icon_code":"fa-user-md"},{"id":9,"category_name":"人格个性","category_icon_code":"fa-language"},{"id":10,"category_name":"职场人际","category_icon_code":"fa-binoculars"},{"id":11,"category_name":"家庭情感","category_icon_code":"fa-home"},{"id":12,"category_name":"能力潜质","category_icon_code":"fa-graduation-cap"},{"id":14,"category_name":"价值观","category_icon_code":"fa-balance-scale"}]}';
        $classifyResponse = empty($classifyResponse) ? array() : json_decode($classifyResponse, true);
        $classifyList = empty($classifyResponse['data']) ? array() : $classifyResponse['data']; 
        if (empty($classifyList)) {
            throw new $this->exception("获取分类失败");
        }
        $commonDao = \dao\Common::singleton();
        $sql = "select guid from `xz_goods` where 1;";
        $guids = $commonDao->readDataBySql($sql);
        $guidArr = array_column($guids, 'guid');
        $guidArr = array();
        foreach ($classifyList as $classifyData) {
            $goodsListUrl = "https://adapi.monday1.top/v1/goods/getList?goods_category_id={$classifyData['id']}&limit=200&page=1&goods_name=&sort_type=2";
            $goodsListResponse = httpGetContents($goodsListUrl);
            //$goodsListResponse = '{"code":1,"message":"success","data":{"total":110,"per_page":999,"current_page":1,"last_page":1,"data":[{"guid":"65727f6a","goods_name":"MBTI性格测试2024版","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20240423\/7a63571d1829365f4897cb5f8184ac5c.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20240423\/3a07bd3c0348bac9e8b07bf7360749c8.png","goods_subtitle":"测测你真实的MBTI类型到底是哪种？","goods_version":2,"version_text":"<p>为确保测试结果的信度<\/p><p>请先选择性别！<\/p>","left_version_text":"男生","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20231208\/057eed4acab6d12cdd7436471a5ffb76.png","right_version_text":"女生","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20231208\/14526a686304321e219709e27260b0a6.png","goods_vr_sale_count":6345732,"goods_sale_price":"49.90","is_limit_time_free":0,"paper_style_type":1},{"guid":"64aad4a6","goods_name":"盖洛普优势识别测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230711\/4c429e11d596e6926cb093d782840878.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230711\/635ddf1b6d66396a7fbc6bc2eea47a3e.png","goods_subtitle":"34种才干，全面挖掘你基因自带的技能","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":867891,"goods_sale_price":"39.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"62b1754a","goods_name":"人格七宗罪","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220621\/45ad6deee149bca0347b5c2a98f1b938.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220621\/ee67c290a452a9fdecd3db342ed04385.jpg","goods_subtitle":"找到禁锢你的人格原罪","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":530103,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddd089","goods_name":"性吸引力评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/99ec7244aebb5b852c920de5169d37f3.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/4af72d369c8f71f4411c8d40ad9692f5.png","goods_subtitle":"测测你有多“撩人”？","goods_version":2,"version_text":"请选择性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":516930,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9ebe","goods_name":"抑郁测试「专业版」","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221228\/5d2806e2492ac6fcbd5fa1b3ad183ae1.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221228\/5c6271353ce2508d95570705cca0475c.jpg","goods_subtitle":"测测你的抑郁程度有多深？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":489600,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"636bc13e","goods_name":"恋爱心理成熟指数评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221109\/effe7e100d536ad655418082f9ded253.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221109\/9ec9dd2a045153fad04fb6f1bed46599.jpg","goods_subtitle":"你是否拥有成熟的爱情观?","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":452311,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"6373c072","goods_name":"依恋类型专业评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221116\/da6813b0ffe6e4fe8ae3ece35fddf32a.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221116\/46ccf5762790e553f17419f98261a9d4.png","goods_subtitle":"在亲密关系中，你是哪种依恋类型？","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":420251,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddc0ce","goods_name":"MBTI专业爱情测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/7b20cd27747463cfee569d8ca0e41f00.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/c4a246f6509563d9aa538530a93413f7.png","goods_subtitle":"什么样的人真正适合你？","goods_version":2,"version_text":"请选择性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":394500,"goods_sale_price":"39.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"638334e2","goods_name":"潜意识画像测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221127\/3d5986ed3a8ce133ae528acd0c129e9e.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221127\/e0536ec0ce01cffdde2b15cb0b06d540.jpg","goods_subtitle":"测测你内心最真实的一面","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":386015,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddaeba","goods_name":"人格原型测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221130\/bf9e37fa25f88648060a4adb0da37ddc.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221130\/5f61e057c9a119aaa7be44bbf247c6c1.jpg","goods_subtitle":"测测你藏着不为人知的哪一面？","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":381500,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"63b1b2c7","goods_name":"气质类型测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230102\/4eed546ebee16d54295d199acfb731cc.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230102\/fd958043014dd3ce87fe3a2a7b80b791.jpg","goods_subtitle":"在别人眼里，你拥有哪种独特的气质？","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":329056,"goods_sale_price":"29.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"64a79575","goods_name":"你有多容易恋爱脑","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230707\/a3111f366d53fd019f4fa33f6446aad7.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230707\/f578d08389f65df9f09d80a510a863a5.png","goods_subtitle":"测测你的恋爱脑指数","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":315864,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddc856","goods_name":"异性魅力评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/9e3c9e433d0d5a96e8d4ba680a0ba68a.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/5320f750231f884943bb564c3a3f9642.jpg","goods_subtitle":"你的异性魅力值有多高？","goods_version":2,"version_text":"请选择性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":275607,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"6551f3ec","goods_name":"内在动物原型测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20231113\/6f510091b101c5f9db31e4d333687c5d.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20231113\/621674857435d0a0cd2f36272235fc78.jpg","goods_subtitle":"你的内心里，住着哪种动物原型?","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":275361,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddc1d9","goods_name":"伴侣潜在出轨风险评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/074afeb3f627bc1ea283fdc8a169f96b.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/88c90627cfe9a6e355d1a9bc26146e87.png","goods_subtitle":"你的伴侣有哪些潜在的出轨动机？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":269800,"goods_sale_price":"19.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"643fe4ac","goods_name":"暗黑人格魅力测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230419\/ac8e8cb56c4ab971d7039af3dea188fb.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230419\/39c593bc0fb64a6669509d391bc7a0d0.jpg","goods_subtitle":"测测你有哪些暗黑人格特质","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":245625,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddb676","goods_name":"360°情商综合评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230326\/dfed40e5e33136d92db82283c5debdb3.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230326\/1b93e7ce8bc6c01142cc440f24c03530.png","goods_subtitle":"情商段位国际标准测验","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":245600,"goods_sale_price":"39.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"625ffdbb","goods_name":"性格底色测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220421\/10e4e915736dba7a6f93df3be176b36e.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220421\/782576ed8b6aab85932b352a8478e83b.jpg","goods_subtitle":"测测你的性格是什么颜色","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":243000,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"663af55f","goods_name":"产后抑郁测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20240508\/7031b01dfc38df15abddfe5a9793d78e.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20240508\/bff970a3226e3438547b8251104df9ee.png","goods_subtitle":"孕产期\/产后抑郁自测量表(专业版)","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":238651,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddb4dc","goods_name":"九型人格测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230411\/5e0cbea4efc4f4ffbccbde0323aec171.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230411\/c9a1c2fdb7a6d63ac820bfdf7438440a.png","goods_subtitle":"九型人格测出你的人生密码","goods_version":2,"version_text":"请选择版本","left_version_text":"专业精华版","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/4cb48fa82434b9b96ef44349422fbeec.png","right_version_text":"国际标准版","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/0cc2ea3d88d6ea32678d249132a685db.png","goods_vr_sale_count":229800,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd22a7","goods_name":"瑞文智力专业评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/36f175702a7666492b1a1cc20405933b.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/d0eae67e045dc3566c0fc9bd5913bd90.png","goods_subtitle":"测一下你有多聪明？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":216500,"goods_sale_price":"39.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"6425525d","goods_name":"瑞文国际标准智商测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230331\/94697e0ec13b376104cc1c70e35c8642.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230331\/05ea167d7ca1ee118159cc8c8b58279f.jpg","goods_subtitle":"测一下你的真实智商水平","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":216500,"goods_sale_price":"39.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dda402","goods_name":"抑郁指数评估「医用版」","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/7e55d05c382e9e9a1876d322ed046657.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/bad91e4ff0930d562a00b4a3a4631b58.png","goods_subtitle":"给你的心理做一次全面的健康体检","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":212716,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"654e0c1d","goods_name":"动物恋人测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20231110\/df8dfce1f6c8884ef8fa978b29340fc5.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20231110\/72d08c04e57c7b322678d1e2c9bbbe9c.jpg","goods_subtitle":"测一测你是哪种类型的动物恋人？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":209210,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de58c7","goods_name":"潜意识投射测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/2283e7cba91b2b6f8ada4aea29fe8643.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/2630b4ebd317a3eaf85d1296b8180df0.jpg","goods_subtitle":"你的潜意识中，隐藏着什么秘密？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":199350,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dda988","goods_name":"内在小孩类型测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/e8a50e1acd6c13653011e1b112ee40c5.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/2ed5c3b0003c2bebf20a1408caabd329.jpg","goods_subtitle":"你的内心住着怎样的一个小孩？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":195673,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddd4de","goods_name":"霍兰德职业兴趣测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/ae06b75b8b51051a4b35cbb871a51dbf.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/f1ce570e697835fc8461bc5fb27dd0ba.jpg","goods_subtitle":"真正适合你的工作是什么？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":168017,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddaa76","goods_name":"大五人格专业测式","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220609\/7f821cc981a6d9724eb64e8dbd35f57e.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220609\/5a49cdc7e914592be7c9dc78dfabc5fb.png","goods_subtitle":"测测你最吸引人的人格特质？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":128658,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"6321a880","goods_name":"人格障碍类型专业评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221008\/7884db092641bc6e3f83616ace0693b9.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20221122\/0c2254bda4cd02f6fe63b8f6f0be6c27.jpg","goods_subtitle":"我的性格哪里不好了？","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":123608,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd6c7f","goods_name":"多元智力测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/f4f6f09cae4fe219fd53042ac07f3924.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/f78cf432b54e07f7b47f1736ac843a66.jpg","goods_subtitle":"你的多元智力结构是怎么样的？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":121314,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd99b8","goods_name":"心理防御测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/d5fbf9f0d0c58fcc446e21a2db1adc42.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/9d41c0a6b1a994586f7ffbc663fde40e.png","goods_subtitle":"你的心理防线在第几层？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":120191,"goods_sale_price":"14.90","is_limit_time_free":1,"paper_style_type":0},{"guid":"63c6053f","goods_name":"城府类型测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230203\/b9693bed109cebf70d5d361630afa185.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230203\/a88726fcd414037d98c4a0b825f6f8b2.png","goods_subtitle":"你的城府等级有多高？","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":115690,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dda6d9","goods_name":"心智成熟度专业测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/941dee9dec57f98646899471d476162d.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/6395a92a791cf5957f1c560f93398fe7.jpg","goods_subtitle":"你是一个缺“心眼”的人吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":112663,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddd260","goods_name":"深层心理需求测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/5f8d1aa9d1168ad1e6fe75c006f84544.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/9cd149c5f01e4c77e6b5d25c509feaa9.jpg","goods_subtitle":"你内心深处最想要的是什么？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":112307,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddca3d","goods_name":"原生家庭影响评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/35c5dc842202998c28321475bf195a90.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/3947e0506710463629137de0bed8a512.png","goods_subtitle":"你身上带着多少父母的影子","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":108727,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddc359","goods_name":"脱单指数评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/e3e3113bfbdfcd4a1b7c0b31ce186a3f.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/d7d8553dbee419397538c47266960514.png","goods_subtitle":"为什么你脱不了单？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":104406,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dda5b2","goods_name":"敏感程度专业测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230411\/a5d5cc05763d918f35e0adfa6b592430.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230411\/15cd146fbb27e2a1778aefa74c18de9a.png","goods_subtitle":"你是一个多愁善感的人吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":103249,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddc676","goods_name":"伴侣相处能力综合评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/a115a5d4f8a6230f48d44b69b0e59c91.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/fa08e9caae481b81f25bb6f7f1aa0f0e.jpg","goods_subtitle":"你在亲密关系中的表现能打多少分？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":98653,"goods_sale_price":"19.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de5397","goods_name":"人际心理边界评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/5e9f39af167ba7fb5c62f1564106cfe4.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/b207936ccffa00c8d46f1a21eca02fff.png","goods_subtitle":"你和别人相处的最佳距离是什么？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":98072,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9de5","goods_name":"拖延行为风格评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/bec84cb81e76f47a2240296c8a7d8078.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/c7640854edd72fef290588e4af5935a3.png","goods_subtitle":"如何克服自已的拖延行为","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":87127,"goods_sale_price":"2.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de57c4","goods_name":"职业锚类型专业评测","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/52188772eacccb3455294c9c64983185.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/64bfae58fee196c0d63dcdd502d51881.jpg","goods_subtitle":"找到你的职业方向","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":82013,"goods_sale_price":"9.80","is_limit_time_free":1,"paper_style_type":0},{"guid":"61ddb131","goods_name":"人格魅力评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/c87f58378a09c2a640af9490b1089904.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/2651e4174eb487f9c3e2fd13f79d47e2.png","goods_subtitle":"你有哪些独特的性格优势","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":81776,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de54f0","goods_name":"人际交往能力综合评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/b4dbc09ef7784a4f6762853b17511bdd.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/c80c260c1e9be49d7ce64d803c60a9e8.png","goods_subtitle":"为什么你说的话别人听不进去？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":79275,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddc7b1","goods_name":"伴侣沟通能力评测","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/e0e233124fe2a2c908883d93853ef57b.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/2efbeffdb941ce94bbf3fee8ad3b5a49.png","goods_subtitle":"你在亲密关系中的沟通能力如何","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":78276,"goods_sale_price":"14.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddc532","goods_name":"恋爱语言测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/57135fce585d85bf218d5b2fdf505d68.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/7603d046b6ec21236803248d079cbe20.png","goods_subtitle":"爱的5种语言，你最需要的是哪种？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":77313,"goods_sale_price":"14.80","is_limit_time_free":1,"paper_style_type":0},{"guid":"61dda8b6","goods_name":"决策风格专业测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/7cb340121148d1aa7ff2dacaaeb97409.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/cba688b93391bbed9e58661516429207.png","goods_subtitle":"如何优化你的决策力？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":75623,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de56e9","goods_name":"应对方式专业评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/b8741577a08c4ffd40040d19dbe31599.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/5ea1e90f0e8cfe5606b893065d979082.png","goods_subtitle":"面对逆境时你会如何应对？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":71371,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddccda","goods_name":"婚姻质量综合评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/3288082e79a26b62a4221daddf73e065.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/70e264abfeac81b6aed22685b229e888.png","goods_subtitle":"如何提升你们的婚姻满意度？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":71331,"goods_sale_price":"39.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddc723","goods_name":"家庭功能健康评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/04b6a1bcb9dfd83c65ad08167b5f1a6d.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/7370390f84f2f0894424fdf49bb7c198.png","goods_subtitle":"你的家庭健康和谐吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":71017,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddc5c4","goods_name":"爱情观专业测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/922ac5344723a78fc9bee1827c023476.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/2783d4a950cb9e58388318eb93e80243.jpg","goods_subtitle":"你的爱情观，决定了你怎样的爱情宿命？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":70563,"goods_sale_price":"14.90","is_limit_time_free":1,"paper_style_type":0},{"guid":"61ddd463","goods_name":"时间态度评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/a116e3ec17d2cde6b92183c3924dc82b.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/7d6688060cf46907520efe7eabbd42f5.png","goods_subtitle":"你是活在过去，还是未来？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":69132,"goods_sale_price":"9.80","is_limit_time_free":1,"paper_style_type":0},{"guid":"61ddacd7","goods_name":"完美主义心理评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/cbe68043147bd99317ab907ccf683516.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/c5aa8eeb0c65f4efeb9c8e5b94b6dc5a.png","goods_subtitle":"你是一个追求完美的人吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":68378,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddce87","goods_name":"内心真实性别特质评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/2285f8cb0f5b255c00393345724aae59.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/b242f5a2fcb49db340c701cd5ddf4431.png","goods_subtitle":"你是否背上了性别的枷锁？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":66773,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de5850","goods_name":"价值观测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/f2a4c430d7cd1920e539c59a14487ac7.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/9540db89346d00c1ed2a3a9957594bd9.jpg","goods_subtitle":"你的潜意识里真正的欲望是什么？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":66208,"goods_sale_price":"14.80","is_limit_time_free":1,"paper_style_type":0},{"guid":"61ddc289","goods_name":"婚姻准备度专业评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/aee50b9cea2240d39fb06e89553aefff.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/0ba0033da6f4afef42ce0861f7d2d10f.png","goods_subtitle":"婚前测试，你准备好结婚了吗?","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":65861,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"64229ea2","goods_name":"ABO性别角色评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230812\/9b9afae8f6f2f8d1d16ba27347ecdc9a.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230812\/23bc24ad70c8f64a41b59405a95ff070.jpg","goods_subtitle":"在ABO世界中，你是哪种属性？","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":65246,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dda7b9","goods_name":"荣格古典心理原型测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/16661a61b3a296d12ba6a0d8b76ee869.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/c567172761af21d037797a5786f70d0a.jpg","goods_subtitle":"测一下你潜意识心理原型是什么","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":65224,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de55c3","goods_name":"PDP性格测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220705\/3dee8e348dead010b3996bd313f80b6b.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220705\/d9702793fb44fac03cac5e88244d2239.jpg","goods_subtitle":"发现属于自已的职场性格优势","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":65032,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddd815","goods_name":"工作满意度权威评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/7c4bf319ca034094b59f6024e2fc4e1c.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/b20044dfc8d0056eeda4480401902111.png","goods_subtitle":"如何提升工作中的幸福感？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":65027,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddc3d7","goods_name":"真爱鉴定评测","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/5ec88595f78af714f0637b70dd972fc4.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/2a2140559f4cd7ed173f960c57318272.png","goods_subtitle":"你们的爱情是真爱吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":64117,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de547e","goods_name":"人际沟通能力评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/69dc0861bb3a35224d92820ed8cb8aca.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/e62df94b0ce22d05ca3ef4965687b9dd.png","goods_subtitle":"你是一个善于沟通的人吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":63983,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddcfb1","goods_name":"多维性观念私密测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/e146d5f51beb1e2b77e4a7b334337439.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/4e07860e2c2b30777caaa7c838f0296c.jpg","goods_subtitle":"你潜意识是怎样看待“性”的？","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":58316,"goods_sale_price":"29.90","is_limit_time_free":1,"paper_style_type":0},{"guid":"61ddc481","goods_name":"恋爱能力评测","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/a3e9376ab2cf8e1cbd7ef602cb0e2192.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/dbc10cda024c68e43ed78e587b0bbeef.png","goods_subtitle":"面对爱情你是幼稚还是成熟？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":58017,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddbe64","goods_name":"宜人性专业测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/129327742fdaa2a980b08e1d9515b5ac.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/abf955b1d2dc53e0334c279a4bcca1de.jpg","goods_subtitle":"你是一个招人喜欢的人吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":52217,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd3f84","goods_name":"瑞文高级智力评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/511569712b6944fad5e6e9735752a77e.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/11aa65a948e345082d2af6a96b5352c1.png","goods_subtitle":"测一下你到底聪明到什么程度了","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":51274,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddad51","goods_name":"自我觉察能力综合测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/e12e9ccd4447b0f1c99ac36e242eff78.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/43a45d8c9330e75fa05922256ee6ca5a.png","goods_subtitle":"你的自我觉察能力有多强大？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":48106,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de574e","goods_name":"冲突处理模式评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/138b0250f6c3da77267459cd93ed252b.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/2485daf95066f2b862639b73351fb947.png","goods_subtitle":"你善于处理冲突吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":42038,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"66268367","goods_name":"ABM恋爱动物测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20240422\/92f68cbaaa5437af5952202009f98066.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20240423\/29d1344b5215854945ffbcf49bb853ea.png","goods_subtitle":"测一测恋爱中的你属于哪种动物","goods_version":2,"version_text":"请选择您的性别","left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":39811,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd987b","goods_name":"双相情感障碍筛查","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/de288bb0f5c7b32d1f526138cc69468b.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/1bc72bb398178392ea904e11173c97b0.png","goods_subtitle":"你的情绪反复无常是否正常？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":38931,"goods_sale_price":"9.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9205","goods_name":"抑郁焦虑压力综合测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/dffe7c416b27a39113ee4ef004df2570.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/b4676fd35c9c0ecb2bb5b3cef3a8dace.png","goods_subtitle":"区分身上这三种负面情绪","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":35517,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de567b","goods_name":"职业倦怠度评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/c88dd962d6e3c030f5227ac6b65fa9c5.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/442e8155f353a331e0d87549d287b513.png","goods_subtitle":"你厌倦你现在从事的职业吗","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":32612,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dda29d","goods_name":"社交焦虑倾向评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/e54069f73a26f9f454f65fac514e62f8.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/322757804601fbaf6c3e0e3e79842a04.png","goods_subtitle":"你有社交焦虑的倾向吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":32391,"goods_sale_price":"9.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de530a","goods_name":"人际关系支持度评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/b0ced6de9206f13c8e3c01bc4fc8dc1b.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/2b0d8da8f061d5c607e87caf49e74303.jpg","goods_subtitle":"你能从人际关系中获得多少帮助？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":29318,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddaf54","goods_name":"共情商数测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/6fae3e752a68123f56db6178c9188abe.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/420d6bec455df123daa6043361335890.png","goods_subtitle":"测一下你是暖男还是钢铁直男","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":25087,"goods_sale_price":"9.80","is_limit_time_free":1,"paper_style_type":0},{"guid":"61ddbfbb","goods_name":"NLP感官系统测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/201b0f3905828aadc2795e8741ed571e.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/f28077f772d618902d36dff288274987.png","goods_subtitle":"你是哪一种沟通风格？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":23716,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddbdb6","goods_name":"内外向性格专业评定","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/95b3dbda041140a519172c9617b06ea9.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/f9fb0a1aa4d9595d67fd32893a69c321.jpg","goods_subtitle":"你的性格到底是内向还是外向？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":23124,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9c7d","goods_name":"心理弹性专业测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/c28ae526939814a281fd7aa9325f2a33.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/e270afff6bab0c008c3e5eede6bbbb45.jpg","goods_subtitle":"测一下你心态容易“炸”吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":18581,"goods_sale_price":"9.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd8319","goods_name":"自我评价水平评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/93dc58ee84576ba6e9822f7f873131cd.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/f98aa376f70136bd9387e624da7a4c75.png","goods_subtitle":"你内心对自已有怎样的评价？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":18013,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd8219","goods_name":"焦虑测试「专业版」","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/af24894c173a0f0dab916f4c3f206a4d.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/526140c78792d814484d31586519257d.png","goods_subtitle":"为什么你越努力却越焦虑？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":15386,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddb000","goods_name":"自信心水平测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/4c77889e5e7f75f0e22bc86b7da91198.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/de34994a8c0f32dd9ac856e602a0126a.png","goods_subtitle":"测一下你对自已有多自信？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":12602,"goods_sale_price":"2.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddbd16","goods_name":"情绪化程度测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/1f8ce7c38f13449f63a552ca31b85d8e.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/cf362b1e325ca9e751e44054a7344d91.png","goods_subtitle":"测一下你是一个容易情绪化的人吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":12507,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddbeed","goods_name":"DISC个性测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220508\/7e63c198d57a219ecddef7a6c2040ed4.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220508\/033ebd80b06dcf44a34dfe7846c19c55.jpg","goods_subtitle":"你的处事风格是哪种类型？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":12315,"goods_sale_price":"29.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dda645","goods_name":"自我观平衡测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/78553b6f49cdea14ff413f18854ec486.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/3c11a65ba1bcda431b763efd00df6a1b.png","goods_subtitle":"你希望过一个怎样的人生？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":10813,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9601","goods_name":"儿童多动症初步筛查","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/43d36745dc927ba8690022ad0682f2dc.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/bf514aeb0cca693686799d243ee1f87c.png","goods_subtitle":"你的小孩有多动症倾向吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":9878,"goods_sale_price":"2.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddcd82","goods_name":"女性性和谐指数评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/c32f4ac76fd61a1322da9a972f9930a7.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/9f5d50d3f90511f5de5272f4f6de5075.jpg","goods_subtitle":"你知道性和谐对一个女人有多重要吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":9831,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9798","goods_name":"疑病心理倾向评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/060b04ce54bf80c67da11c0f110102b5.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/1315a4bf4ea4cfc18031786683a8b2f8.png","goods_subtitle":"你是否过度担心自已的身体健康？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":9826,"goods_sale_price":"2.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddb382","goods_name":"责任心专业评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/87d2e1e4ef8122b1c9a983d472cdb738.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/4f183aeebe6cb550d051184254b8c69e.jpg","goods_subtitle":"测一下你是一个有责任心的人吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":9813,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dda36e","goods_name":"强迫症程度测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/f099b9f063cec0bd0297e79ddfab074b.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/5c0b3505ac975af97e9c63d850cdefc5.png","goods_subtitle":"你有强迫症倾向吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":8892,"goods_sale_price":"2.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd94dc","goods_name":"自恋程度综合评测","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/d126e7708ae4a7c22b9a072a48d7ce2c.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/28dafafeeb3fa2619f2081f75fe0f6d2.png","goods_subtitle":"你对自已的欣赏程度有多深？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":8814,"goods_sale_price":"9.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9a9a","goods_name":"自我和谐度测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/7acbed1ff75d3800cfb78edf22932202.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/68a72fd1dc1e48e8e4a8afecf3c5b446.png","goods_subtitle":"你的自我安慰能力有多强大？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":8612,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddb2b4","goods_name":"开放性指数评测","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/7ef106c84717a645fd225e969883d68a.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/893e8523fe37540b72b3b123704709de.png","goods_subtitle":"测一下你的个性有多开放？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":8611,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddb097","goods_name":"A型人格倾向评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/cce4375a43c9cf25251a352734daab08.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/7412fc52d710d2913d99495a28eecf89.png","goods_subtitle":"你是个急性子吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":6831,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddb1fe","goods_name":"创造力倾向评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/eabac7ebae09d9fcd773bd467c0332c2.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/6fa673f66c526668a8e95323aa409218.png","goods_subtitle":"测测你是哪方面的隐形天才","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":5835,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddbc6b","goods_name":"情绪管控评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/0dbd1af1067831e7f1f6cc1422715c29.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/2a9275cd5445e99c164ff3e8a6f3cfff.png","goods_subtitle":"你善于随机应变吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":5463,"goods_sale_price":"5.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de52a1","goods_name":"人际敏锐程度评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/f7293051484e74e86e94f69b17ffc241.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/139a4de7aca9f3803a088041e45f660d.png","goods_subtitle":"你在人际交往中有多敏锐？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":5431,"goods_sale_price":"3.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9338","goods_name":"无聊心理状态专业评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/adcab18942389e3f1104fc98a19da8a6.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/0f09e4a530757cdaf3648b9fc2d7f0c1.png","goods_subtitle":"是什么杀死了你的快乐？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":4976,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd93de","goods_name":"易怒程度专业鉴定","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/dee72969dfeb2819c8586c5ae6e05b20.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/0984fbba7770e12c06bb7916a0c1f608.png","goods_subtitle":"你的情绪为何总容易爆炸？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":4636,"goods_sale_price":"3.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddae35","goods_name":"内向者优势测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/96e23739dcc61af0e41dae11c430b1eb.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/5a55f272be0f1bdc1bc3bd143f999934.png","goods_subtitle":"测测你有哪些未被挖掘的优势？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":3991,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd96bd","goods_name":"创伤后应激障碍测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/574335ad3b148daa9c3364a580f03311.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/5df0d5b20c31430f0cd53193c50d5335.png","goods_subtitle":"你的心理创伤愈合了吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":3926,"goods_sale_price":"2.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddadc3","goods_name":"冲动特质测评","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/3c261e174cf9cafc4875147cd458d883.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/aca7c7b8093f72e2be0a42fcdfae2968.png","goods_subtitle":"你的冲动性有多高？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":3857,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd90ec","goods_name":"性格危机测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/797df2195239140dbd38eb76fa68c7f3.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/49b3f0c0499182f35aa9ce4e95fbc0aa.jpg","goods_subtitle":"你的性格中藏有哪些危机因子？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":3189,"goods_sale_price":"14.90","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dda00c","goods_name":"自尊类型测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/c5bb7dba98f3254bb205a82b5ffd807d.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/e40ca28dbb9cee813daf4fc5ab38fa8e.jpg","goods_subtitle":"测测你的自尊心有多强？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":2872,"goods_sale_price":"2.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd8437","goods_name":"正念指数测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/77c7b4762b47f90d049d7216b760ceeb.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/998dc4b7ca33d109c0ec5b5d72d40e21.png","goods_subtitle":"测测你的正念指数","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":2368,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddce06","goods_name":"性少数者自我认同评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/718964a13f67918635877ce4559d476a.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/db4600aa4a7a2666068ce61e08154523.png","goods_subtitle":"你的性倾向自我认同度高吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":2307,"goods_sale_price":"14.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddbbea","goods_name":"控制信念评测","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/e9bc907439b816d7dd3b3f9869602f0f.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/eb87cc260293f322f422f4d52d0d03a8.png","goods_subtitle":"你的人生由谁掌控？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":2279,"goods_sale_price":"9.80","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9bda","goods_name":"睡眠质量专业评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/2d4633b70e7ec0f491760d9cb7fc337e.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/55fef79c0b9eef1ad4adb6fc82ea259a.png","goods_subtitle":"你的睡眠质量还好吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":1232,"goods_sale_price":"5.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9d40","goods_name":"孤独症特质测试","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/1c82ec1a63aaabddc770b2b582dcb2ae.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/564056d67c194e3ca397da38c53b5cf1.png","goods_subtitle":"你有哪些潜在的孤独症特质？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":1223,"goods_sale_price":"2.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61dd9b25","goods_name":"自我效能评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/6a4a42a26f3d4a274be748c3b1bedb10.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220111\/5d2d2e43dc8bc96dc19784f942f7d6ea.png","goods_subtitle":"你能掌控自已的生活吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":1121,"goods_sale_price":"2.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61ddcbfb","goods_name":"母亲依恋关系评测","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/b6ee5dda7cc6e834d9ed499d8a73bbde.jpg","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/5618db9d92867678f5de8e8d45e45ec4.png","goods_subtitle":"你知道你对妈妈有多依恋吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":914,"goods_sale_price":"2.00","is_limit_time_free":0,"paper_style_type":0},{"guid":"61de555d","goods_name":"职场战斗力评估","goods_thumb_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75e97dd7805eaf182f852f2c5365609a.png","goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/f2fa8a9a322d693c34ec57b079513981.png","goods_subtitle":"你真的是一个工作狂吗？","goods_version":1,"version_text":"","left_version_text":"","left_version_image":"https:\/\/oss.1cece.top","right_version_text":"","right_version_image":"https:\/\/oss.1cece.top","goods_vr_sale_count":367,"goods_sale_price":"5.00","is_limit_time_free":0,"paper_style_type":0}]}}';
            
            $goodsListResponse = json_decode($goodsListResponse, true);
            $goodsList = empty($goodsListResponse['data']['data']) ? array() : $goodsListResponse['data']['data'];
            if (empty($goodsList)) {
                throw new $this->exception("获取【{$classifyData['goods_name']}】分类下的商品失败");
            }
            echo "开始同步分类[{$classifyData['category_name']}]\t的数据，累计:" . count($goodsList) . "条\n";
            $goodDataList = array();
            foreach ($goodsList as $goods) {
               	if (in_array($goods['guid'], $guidArr)) {
               		continue;
               	}
               	if ($goods['guid'] != '61dd22a7') {
   //continue;
               	}
                // 获取商品详情
                $goodsUrl = "https://adapi.monday1.top/v1/goods/detail?guid=" . $goods['guid'];
                $goodsResponseResult = httpGetContents($goodsUrl, null, 50);
                $goodsResponse = json_decode($goodsResponseResult, true);
                
                // 商品详情
                $goodsInfo = empty($goodsResponse['data']['goods_info']) ? array() : $goodsResponse['data']['goods_info'];
                if (empty($goodsInfo)) {
                    throw new $this->exception("获取商品[{$goods['goods_name']}]失败");
                }
                $detail1 = '';
                // 加载题目
                $tries = 3;
                do {
                	$detail1 = $this->xz_detail($goodsInfo, 1);
                } while ($detail1 === false && --$tries > 0);
                if (empty($detail1)) {
                	throw new $this->exception("获取商品[{$goods['goods_name']}]题目失败");
                }
     
                $detail2 = '';
                if ($goodsInfo['goods_version'] > 1) { // 有多个版本的题目
                	$tries = 3;
                	do {
                		$detail2 = $this->xz_detail($goodsInfo, 2);
                	} while ($detail2 === false && --$tries > 0);
                	if (empty($detail2)) {
                		throw new $this->exception("获取商品[{$goods['goods_name']}] 版本2题目失败");
                	}
                }
    
                $goods_info = base64_encode(json_encode($goodsInfo, JSON_UNESCAPED_UNICODE));
                $detail1 = empty($detail1) ? '' : base64_encode(json_encode($detail1, JSON_UNESCAPED_UNICODE));
                $detail2 = empty($detail2) ? '' : base64_encode(json_encode($detail2, JSON_UNESCAPED_UNICODE));
        
                $data = array(
                	'guid' => $goodsInfo['guid'],
                	'goods_name' => $goodsInfo['goods_name'],
                	'detail1' => $detail1,
                	'detail2' => $detail2,
                	'goods_info' => $goods_info,
                );
                $fieldStr = '`' . implode('`, `', array_keys($data)) . '`';
                $valueStr = "'" . implode("', '", array_values($data)) . "'";
                $sql = "REPLACE INTO `xz_goods` ({$fieldStr}) VALUES ({$valueStr});";
                $commonDao->execBySql($sql);  
                $guidArr[] = $goodsInfo['guid'];

                echo "{$data['goods_name']} \n";
            }
            echo "【{$classifyData['category_name']}】\n";
        }
        echo "完毕！";
        exit;
    }
    
    /**
     * 初始化题目
     * 
     * @var int
     */
    private function initQuestion($testPaperName, $questionDatas, $testPaperId)
    {
    	$selectionKeyMap = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'. 'L');
    	$questionModels = array();
    	if (!empty($questionDatas)) foreach ($questionDatas as $questionData) {
    		$questionModel = array();
    		if (!empty($questionData['groupName'])) { // 题目分组
    			$questionModel['groupName'] = $questionData['groupName'];
    		}
    		$questionModel['matter'] = $questionData['matter']; // 题干
    		$questionModel['scoreValue'] = ''; // 测评值
    		if (!empty($questionData['matterImg'])) { // 题干图片
    			$questionModel['matterImg'] = $questionData['matterImg'];
    		}
    		$selections = json_decode($questionData['selections'], true);
    		$selectionModels = array();
    		foreach ($selections as $key => $selection) {
    			$selectionModel = array();
    			$selectionModel['name'] = $selection['name']; // 选项描述
    			if (!empty($selection['img'])) { // 选项图片
    				$selectionModel['img'] = $selection['img'];
    			}
    			$selectionModels[$selectionKeyMap[$key]] = $selectionModel;
    		}
    		$questionModel['selections'] = $selectionModels;
    		$questionModels[$questionData['version']][$questionData['index']] = $questionModel;
    	}
    	$content = "<?php\nreturn " . var_export($questionModels, true).";\n";
    	$file = CODE_PATH . 'static' . DIRECTORY_SEPARATOR . $testPaperName . DIRECTORY_SEPARATOR . 'question' . '.php';
    	$pathName = dirname($file);
    	if (!is_dir($pathName)) {
    		@mkdir($pathName, 0777, true);
    	}
    	$fp = fopen($file, 'w+');
    	if ($fp === false) {
    		echo "{$file} 目录没有写入权限\n";exit;
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
    	
    	// 创建题目
    	$testQuestionDao = \dao\TestQuestion::singleton();
    	$testQuestionDao->execBySql("delete from `testQuestion` where `testPaperId`='{$testPaperId}'");
    	if (is_iteratable($questionDatas)) foreach ($questionDatas as $key => $questionData) {
    		$questionDatas[$key]['testPaperId'] = $testPaperId;
    	}
    	$fieldStr = '`' . implode('`, `', array_keys(reset($questionDatas))) . '`';
    	$list = array();
    	foreach ($questionDatas as $key => $value) {
    		$list[$key] = "('" . implode("', '", $value) . "')";
    	}
    	$headSql = "REPLACE INTO `testQuestion` ({$fieldStr}) VALUES ";
    	$sql = $headSql . implode(',', $list) . ';';
    	$testQuestionDao->execBySql($sql);
    	return $conf;
    }
    
    /**
     * 心芝-同步-分类
     *
     * @return array
     */
    public function xz_input_classify()
    {  
    	$testPaperDao = \dao\TestPaper::singleton();
    	$classifyRelationDao = \dao\ClassifyRelation::singleton();
    	// 清空已有的数据
    	
    	$classifyRelationDao->execBySql("TRUNCATE TABLE `classifyRelation`");
    	
        $testPaperEttList = $testPaperDao->readListByWhere();
        $testPaperEttList = array_column($testPaperEttList, null, 'name');   
        $classifyDao = \dao\Classify::singleton();
        $classifyEttList = $classifyDao->readListByWhere();
        $classifyEttList = array_column($classifyEttList, null, 'name');
        
        $now = $this->frame->now;
        $classifyRelationEttList = array();
        $map = array(
            '热卖爆款' => 1,
            '最新首发' => 2,
            '精选推荐' => 3,
        );
        foreach ($map as $categoryName => $type) {
            $classifyId = empty($classifyEttList[$categoryName]) ? 0 : $classifyEttList[$categoryName]->id;
            if (empty($classifyId)) {
                continue;
            }
            $goodsUrl = "https://adapi.monday1.top/v1/goods/getIndexGoods?page=1&limit=300&type={$type}";
            $goodsResponse = httpGetContents($goodsUrl);
            $goodsResponse = empty($goodsResponse) ? array() : json_decode($goodsResponse, true);
            $goodsList = empty($goodsResponse['data']['data']) ? array() : $goodsResponse['data']['data'];
            $index = 1;
            foreach ($goodsList as $data) {
                // 找到试卷ID
                $testPaperId = empty($testPaperEttList[$data['goods_name']]) ? 0 : $testPaperEttList[$data['goods_name']]->id;
                if (empty($testPaperId)) {
                    continue;
                }
                $classifyRelationEtt = $classifyRelationDao->getNewEntity();
                $classifyRelationEtt->classifyId = $classifyId;
                $classifyRelationEtt->testPaperId = $testPaperId;
                $classifyRelationEtt->index = $index++;
                $classifyRelationEtt->updateTime = $now;
                $classifyRelationEtt->createTime = $now;
                $classifyRelationEttList[] = $classifyRelationEtt;
            }
        }
        
        $classifyUrl = "https://adapi.monday1.top/v1/goods_category/getList";
        $classifyResponse = httpGetContents($classifyUrl);
        $classifyResponse = empty($classifyResponse) ? array() : json_decode($classifyResponse, true);
        $classifyList = empty($classifyResponse['data']) ? array() : $classifyResponse['data'];
        if (is_iteratable($classifyList)) foreach ($classifyList as $classify) {
            if (empty($classify['id'])) {
                continue;
            }
            $classifyId = empty($classifyEttList[$classify['category_name']]) ? 0 : $classifyEttList[$classify['category_name']]->id;
            if (empty($classifyId)) {
                continue;
            }
            // 获取分类下的数据
            $goodsUrl = "https://adapi.monday1.top/v1/goods/getList?goods_category_id={$classify['id']}&limit=5000&page=1&goods_name=&sort_type=2";
            $goodsResponse = httpGetContents($goodsUrl);
            $goodsResponse = empty($goodsResponse) ? array() : json_decode($goodsResponse, true);
            $goodsList = empty($goodsResponse['data']['data']) ? array() : $goodsResponse['data']['data'];
            $index = 1;
            foreach ($goodsList as $data) {
                // 找到试卷ID
                $testPaperId = empty($testPaperEttList[$data['goods_name']]) ? 0 : $testPaperEttList[$data['goods_name']]->id;
                if (empty($testPaperId)) {
                    continue;
                }
                $classifyRelationEtt = $classifyRelationDao->getNewEntity();
                $classifyRelationEtt->classifyId = $classifyId;
                $classifyRelationEtt->testPaperId = $testPaperId;
                $classifyRelationEtt->index = $index++;
                $classifyRelationEtt->updateTime = $now;
                $classifyRelationEtt->createTime = $now;
                $classifyRelationEttList[] = $classifyRelationEtt;
            }
        }
        if (is_iteratable($classifyRelationEttList)) foreach ($classifyRelationEttList as $classifyRelationEtt) {
            $classifyRelationDao->create($classifyRelationEtt);
        }
        echo "分类同步完毕！";exit;
    }
    
    /**
     * 录入心芝数据
     * 
     * @return array
     */
    public function xz_input($guid = 0)
    {
    	$testPaperDao = \dao\TestPaper::singleton();
$name = 'MBTI性格测试2024版'; // MBTI专业爱情测试   MBTI性格测试专业版     MBTI性格测试2024版
        $commonDao = \dao\Common::singleton();
        if (!empty($guid)) {
            $sql = "SELECT * FROM `xz_goods` WHERE `guid`='{$guid}' order by `guid` asc;";
        } elseif (!empty($name)) {
            $sql = "SELECT * FROM `xz_goods` WHERE `goods_name` like '%{$name}%' order by `guid` asc;";
        } else {
            $sql = "SELECT * FROM `xz_goods` WHERE 1 order by `guid` asc;";
            $testPaperDao->execBySql("TRUNCATE TABLE `testPaper`");
            $testPaperDao->execBySql("TRUNCATE TABLE `testQuestion`");
        }
        $now = $this->frame->now;
        $goodsList = $commonDao->readDataBySql($sql);
        $promotionDao = \dao\Promotion::singleton();
        $commonSv = \service\Common::singleton();
        $testQuestionDao = \dao\TestQuestion::singleton();
        $reportProcessDao = \dao\ReportProcess::singleton();
        $commonDao = \dao\Common::singleton();
        if (is_iteratable($goodsList)) foreach ($goodsList as $data) {
        	$goods_name = $data->goods_name;
        	if (!in_array($data->goods_name, array('MBTI性格测试2024版'))) {
        		// continue;
        	}
        	$goods_info = empty($data->goods_info) ? array() : json_decode(base64_decode($data->goods_info), true);
        	// 版本1 题目
        	$detail1 = empty($data->detail1) ? array() : json_decode(base64_decode($data->detail1), true);
        	// 版本2 题目
        	$detail2 = empty($data->detail2) ? array() : json_decode(base64_decode($data->detail2), true);
        	if (empty($detail1) || empty($detail1['paper_order_detail'])) {
        		echo "ERROR：" . $goods_name . "\t detail1 \n";
        	}
        	
        	// 加载题目
        	$paper_order_detail1 = empty($detail1['paper_order_detail']) ? array() : $detail1['paper_order_detail'];
        	$paper_order_detail2 = empty($detail2['paper_order_detail']) ? array() : $detail2['paper_order_detail'];

        	$questionGroup1 = empty($detail1['questionGroup']) ? array() : $detail1['questionGroup'];
        	$questionGroup2 = empty($detail2['questionGroup']) ? array() : $detail2['questionGroup'];
        	$questionGroupMap1 = array();
        	if (!empty($questionGroup1)) foreach ($questionGroup1 as $row) {
        		for ($_index = $row['start']; $_index <= $row['end']; $_index++) {
        			$questionGroupMap1[$_index] = $row['group_title'];
        		}
        	}
        	$questionGroupMap2 = array();
        	if (!empty($questionGroup2)) foreach ($questionGroup2 as $row) {
        		for ($_index = $row['start']; $_index <= $row['end']; $_index++) {
        			$questionGroupMap2[$_index] = $row['group_title'];
        		}
        	}
        	// 题目
        	$questionDatas = array();
        	$index = 1;
        	foreach ($paper_order_detail1 as $row) {
        		$questionData = array(
        			'testPaperId'   => 0,
        			'groupName'     => '',
        			'version'       => 1,
        			'index'         => $index++,
        			'matter'        => $row['subject'],
        			'matterImg'     => $row['subject_image'],
        			'selections'    => json_encode($row['option'], JSON_UNESCAPED_UNICODE),
        			'createTime'    => $now,
        		);
        		if (!empty($questionGroupMap1[$questionData['index']])) {
        			$questionData['groupName'] = $questionGroupMap1[$questionData['index']];
        		}
        		$questionDatas[] = $questionData;
        	}
        	$index = 1;
        	if (is_iteratable($paper_order_detail2)) foreach ($paper_order_detail2 as $row) {
        		$questionData = array(
        			'testPaperId'   => 0,
        			'groupName'     => '',
        			'version'       => 2,
        			'index'         => $index++,
        			'matter'        => $row['subject'],
        			'matterImg'     => $row['subject_image'],
        			'selections'    => json_encode($row['option'], JSON_UNESCAPED_UNICODE),
        			'createTime'    => $now,
        		);
        		if (!empty($questionGroupMap2[$questionData['index']])) {
        			$questionData['groupName'] = $questionGroupMap2[$questionData['index']];
        		}
        		$questionDatas[] = $questionData;
        	}
        	// 选项配置
        	$testPaperVersionConfig = array(); // 测评版本配置
        	if (!empty($goods_info['right_version_text']) && $goods_info['goods_version'] >= 2) {
        		$testPaperVersionConfig['text'] = $goods_info['version_text'];
        		$testPaperVersionConfig['list'] = array(
        			$goods_info['left_version_text'] => $goods_info['left_version_image'],
        			$goods_info['right_version_text'] => $goods_info['right_version_image'],
        		);
        	}
        	$testPaperEtt = $testPaperDao->getNewEntity();
        	$testPaperEtt->name = $goods_info['goods_name']; // 测评名称
        	$testPaperEtt->subhead = $goods_info['goods_subtitle']; // 副标题
        	$testPaperEtt->coverImg = empty($goods_info['goods_cover_image']) ? '' : $goods_info['goods_cover_image']; // 列表小图
        	$testPaperEtt->mainImg = empty($goods_info['goods_thumb_image']) ? '' : $goods_info['goods_thumb_image']; // 详情页图片
        	$testPaperEtt->reportNum = empty($goods_info['report_page_count']) ? 0 : $goods_info['report_page_count'];
        	$testPaperEtt->contentTitle = empty($goods_info['goods_content_title']) ? '' : $goods_info['goods_content_title'];
        	$testPaperEtt->content = empty($goods_info['goods_content']) ? '' : $goods_info['goods_content'];
        	$testPaperEtt->noticeTitle = empty($goods_info['goods_notice_title']) ? '' : $goods_info['goods_notice_title'];
        	$testPaperEtt->notice = empty($goods_info['goods_notice']) ? '' : $goods_info['goods_notice'];
        	$testPaperEtt->price = empty($goods_info['goods_sale_price']) ? 0 : $goods_info['goods_sale_price'];
        	$testPaperEtt->originalPrice = empty($goods_info['goods_original_price']) ? 0 : $goods_info['goods_original_price'];
        	$testPaperEtt->saleNum = empty($goods_info['goods_vr_sale_count']) ? 0 : $goods_info['goods_vr_sale_count'];
        	
        	$testPaperEtt->mbtiStyle = 0;
        	if ($goods_name =='MBTI性格测试2024版' || $goods_name == 'MBTI性格测试专业版') { // paper_style_type 值只有 0, 1  只MBTI性格测试2024版为1
        		$testPaperEtt->mbtiStyle = 1;
        	}
        	$testPaperExtend = array();
        	$paperSet = $detail1['paperSet'];
        	$paper_tips = $detail1['paper_tips'];
        	$paper_info = $detail1['paper_info'];
        	$goods_extend = empty($detail1['goods_info']['goods_extend']) ? array() : $detail1['goods_info']['goods_extend'];
        	if (!empty($goods_extend)) { // 盖洛普优势识别测试
        		if (!empty($goods_extend['sub_title'])) {
        			$testPaperExtend['headText'] = $goods_extend['sub_title'];
        		}
        		if (!empty($goods_extend['xcy'])) {
        			$testPaperExtend['centerText'] = $goods_extend['xcy'];
        		}
        		if (!empty($goods_extend['jjhd'])) {
        			$testPaperExtend['bottomText'] = $goods_extend['jjhd'];
        		}
        	}
        	if (!empty($paper_info)) { // paper_info
        		if (!empty($paper_info['style_type'])) {
        			$testPaperExtend['styleType'] = $paper_info['style_type'];
        		}
        		if (!empty($paper_info['bottom_Info'])) {
        			$testPaperExtend['bottomInfo'] = $paper_info['bottom_Info']; // 答题页面底部信息
        		}
        	}
        	$ageSet = array();
        	if (!empty($paperSet)) { // 需要选择年龄
        		if (!empty($paperSet['setPageInfo'])) {
        			$ageSet = array(
        				'title' => '请选择你的年龄',
        				'desc' => $paperSet['setPageInfo']['shuoming'],
        				'location' => 'end', // 所处的位置
        			);
        		}
        	}
        	// 通过报告数据进行补充
        	$sql = "SELECT * FROM `xz_report` WHERE `goods_name` = '{$goods_name}';";
        	$reportList = $commonDao->readDataBySql($sql);
        	if (is_iteratable($reportList)) foreach ($reportList as $reportData) {
        		$report = empty($data->report) ? array() : json_decode(base64_decode($reportData->report), true);
        		$pay = empty($data->pay) ? array() : json_decode(base64_decode($reportData->pay), true);
        		$order = empty($data->order) ? array() : json_decode(base64_decode($reportData->order), true);
        		$create_report = empty($pay['create_report']) ? array() : $pay['create_report'];
        		if (empty($report)) {
        			continue;
        		}
        		$goods_version_select = empty($order['goodsOrder']['goods_version_select']) ? 1 : $order['goodsOrder']['goods_version_select'];
        		if (empty($testPaperEtt->reportProcessImg)) { // 补充制作流程图片
        			$testPaperEtt->reportProcessImg = $create_report['setting']['touming_icon'];
        		}
        		if (!empty($pay['goods_info'])) {
        			if (empty($testPaperEtt->coverImg)) {
        				$testPaperEtt->coverImg = $pay['goods_info']['goods_cover_image'];
        			}
        			if (empty($testPaperEtt->reportNum)) {
        				$testPaperEtt->reportNum = $pay['goods_info']['report_page_count'];
        			}
        			if (empty($testPaperEtt->saleNum)) {
        				$testPaperEtt->saleNum = $pay['goods_info']['goods_vr_sale_count'];
        			}
        			if (empty($testPaperEtt->notice)) {
        				$testPaperEtt->notice = $pay['goods_info']['goods_notice'];
        			}
        			if (empty($testPaperEtt->noticeTitle)) {
        				$testPaperEtt->noticeTitle = $pay['goods_info']['goods_notice_title'];
        			}
        			if (empty($testPaperEtt->customerUrl)) {
        				$testPaperEtt->customerUrl = $pay['goods_info']['kflink'];
        			}
        		}
        		if (!empty($pay['pay_page'])) {
        			if (!empty($pay['pay_page']['pay_style_type'])) {
        				$testPaperExtend['payStyleType'] = $pay['pay_page']['pay_style_type'];
        			}
        		}
        	}
        	
        	// 开始创建数据
        	$testPaperEtt->ageSet = empty($ageSet) ? '' : json_encode($ageSet, JSON_UNESCAPED_UNICODE);
        	//$testPaperEtt->extend = empty($testPaperExtend) ? '' : json_encode($testPaperExtend, JSON_UNESCAPED_UNICODE);
        	$testPaperEtt->versionConfig = empty($testPaperVersionConfig) ? '' : json_encode($testPaperVersionConfig, JSON_UNESCAPED_UNICODE);
        	$testPaperEtt->createTime = $now;
        	$testPaperEtt->questionStypeType = isset($paper_info['type']) ? $paper_info['type'] : 0;
        	$testPaperEtt->createTime = $now;
        	$haveData = $testPaperDao->readListByWhere("`name`='{$testPaperEtt->name}'");
        	if (!empty($haveData)) {
        		$haveData = (array)reset($haveData);
        		$keys = array_keys($haveData);
        		$diffArr = array();
        		foreach ($keys as $key) {
        			if ($key == '*modelCache' || $key == 'createTime') {
        				continue;
        			}
        			if (isset($testPaperEtt->$key) && $haveData[$key] != $testPaperEtt->$key) {
        				$diffArr[$key] = empty($testPaperEtt->$key) ? $haveData[$key] : $testPaperEtt->$key;
        			}
        			foreach ($diffArr as $key => $vaule) {
        				if ($vaule != $testPaperEtt->$key) {
        					$testPaperEtt->$key = $vaule;
        				}
        			}
        		}
        		$testPaperEtt->id = $haveData['id'];
        		$testPaperDao->execBySql("delete from `testPaper` where `id`='{$testPaperEtt->id}'");
        		$testPaperDao->execBySql("delete from `testQuestion` where `testPaperId`='{$testPaperEtt->id}'");
        	}
        	$testPaperId = $testPaperDao->create($testPaperEtt);
        	$this->initQuestion($goods_name, $questionDatas, $testPaperId);
        	echo "同步测评：" . $goods_name . "\n";
        }
        echo "完成同步"; exit;

        // =========================
        if (is_iteratable($goodsList)) foreach ($goodsList as $data) {
        	$goods_name = $data->goods_name;
            if (!in_array($data->goods_name, array('MBTI性格测试2024版'))) {
                // continue;
            }
            $goods_info = empty($data->goods_info) ? array() : json_decode(base64_decode($data->goods_info), true);
            
            

            
            
            
            $publicityGoods = empty($data->publicityGoods) ? array() : json_decode(base64_decode($data->publicityGoods), true);
            $order = empty($data->order) ? array() : json_decode(base64_decode($data->order), true);
            $report = empty($data->report) ? array() : json_decode(base64_decode($data->report), true);

            $pay = empty($data->pay) ? array() : json_decode(base64_decode($data->pay), true);
            $promotionEtt = null;
            $promotionVersionConfig = array(); // 推广版本配置
            $testPaperVersionConfig = array(); // 测评版本配置
            $testPaperExtend = array();
            $detail1 = array();
            $detail2 = array();
            $testPaperEtt = null;
            $reportProcessEttList = array();
            if (!empty($publicityGoods)) { // 推广测评
            	if ($goods_name = 'MBTI性格测试专业版') {
            		$pay = '{"code":1,"message":"success","data":{"pay_page":{"report_name":"专业测评报告","activity_name":"限时特惠价","pay_thumb_image":"https:\/\/oss.1cece.top","goods_sale_price":"29.9","goods_original_price":"188","has_wenxin_tishi":"0","wenxin_tishi":"","btn_text":"微信支付解锁你的报告","float_btn_color":"rgba(82,140,240,1)","float_btn_text":"微信支付解锁你的报告","float_btn_linke_type":"1","has_head_script_code":"0","head_script_code":"","has_body_script_code":"0","body_script_code":"","pc_pay_jianjie":"<p><span style=\"font-size: 18px;\"><strong>你将获得：<\/strong><\/span><\/p><p><strong>一，专业的分析报告<\/strong><\/p><p>评测报告全文超5000字，含MBTI人格类型评估与分析、恋爱性格分析、爱情婚姻走向、职场性格分析、职业天赋评估、专属心理学建议等20项专业模块，98%的用户好评。<\/p><p><strong>二，3次免费重测权益<\/strong><\/p><p>付费后可享受三次免费重测的权益，4次报告均会保存。<\/p><p><strong>三，报告永久保存<\/strong><\/p><p>支付完成后，报告可永久保存，报告结果可供您随时查看。<\/p>","pc_pay_content":"<p><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/ac90beecb1a86f234759290ec9f47c3f.jpg\" title=\"\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/ac90beecb1a86f234759290ec9f47c3f.jpg\" alt=\"\"\/><\/p><p><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/6eb9508a3d47e47e51b110095f14a3f2.jpg\" title=\"\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/6eb9508a3d47e47e51b110095f14a3f2.jpg\" alt=\"\"\/><\/p><p><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/f68b9bbb9ca32adeadf0b01a44da756a.jpg\" title=\"\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/f68b9bbb9ca32adeadf0b01a44da756a.jpg\" alt=\"\"\/><\/p>","mobile_pay_content":"<p><span style=\"color:#4d6fb0;\">MBTI人格测试在全球盛行了一个多世纪，是最正统的<span style=\"color: #F6727E;\">心理学人格测评系统<\/span>。本测评以<span style=\"color: #F6727E;\">迈尔斯布里格斯类型指标 （MBTI）<\/span> 和现代心理学创始人荣格的<span style=\"color: #F6727E;\">《心理类型》<\/span>理论为基础，并基于中国文化背景研发，更符合中国人测评，用于了解自己的<span style=\"color: #F6727E;\">性格、爱情观、择业观<\/span>。<\/span><\/p>","btn_jianjie":"<p>你的测评报告全文超过<span style=\"color:#F6727E;\">5000<\/span>字！含MBTI人格类型评估与分析、恋爱性格分析、爱情婚姻走向、职场性格分析、职业天赋评估、专属心理学建议等<span style=\"color:#F6727E;\">20项<\/span>专业模块。<\/p><p>支付后可查看报告，并赠送不限时免费重测三次。<\/p>"},"goodsOrderInfo":{"pay_status":0,"get_red_packet_time":0,"red_packet_money":"0.00","goodsOrderVersionText":"女"},"paperOrderInfo":{"create_time":1716727863,"complete_time":1716728814,"age":0,"answerCount":102},"paper_info":{"type":3,"countdown":45,"subtitle":"MBTI人格测评升级版","style_type":0,"bottom_Info":""},"paperSet":{"needSet":0,"setType":0,"setPageInfo":[]},"create_report":{"setting":{"create_type":"1","report_shuoming":"你的MBTI人格测试专业报告正在生成中","touming_icon":"https:\/\/oss.1cece.top\/storage\/Paper\/20220608\/9542b3ee23e3172d8150b165dba3cada.png"},"data":[{"id":585,"title":"性格类型","childList":[{"id":588,"title":"正在分析性格内外向、直觉、情感","title_color":"#f08760","exec_time":"2"},{"id":589,"title":"正在分析你的天赋能力、行为风格","title_color":"#f08760","exec_time":"2"},{"id":590,"title":"正在分析你的性格优势与性格劣势","title_color":"#f08760","exec_time":"1.5"},{"id":591,"title":"正在综合评估你的MBTI人格类型","title_color":"#f08760","exec_time":"1.5"}]},{"id":586,"title":"爱情分析","childList":[{"id":592,"title":"正在评估你的恋爱特质和爱情基因","title_color":"#f08760","exec_time":"2"},{"id":593,"title":"正在分析你喜欢的男生性格类型","title_color":"#f08760","exec_time":"2"},{"id":594,"title":"正在根据性格判断你未来的爱情婚姻走向","title_color":"#f08760","exec_time":"2"},{"id":595,"title":"正在生成专属你的异性魅力锦囊","title_color":"#f08760","exec_time":"1.5"}]},{"id":587,"title":"职业性格","childList":[{"id":596,"title":"正在分析你的职场性格优势与劣势","title_color":"#f08760","exec_time":"2"},{"id":597,"title":"正在分析你的性格对人际关系的影响","title_color":"#f08760","exec_time":"2"},{"id":598,"title":"正在生成与你天赋相匹配的岗位和职业","title_color":"#f08760","exec_time":"1.5"},{"id":599,"title":"正在生成你的求职规划与晋升建议","title_color":"#f08760","exec_time":"1"}]}]},"publicity_goods":{"publicity_goods_name":"你的MBTI人格是哪一型？"},"goods_info":{"goods_name":"MBTI性格测试专业版","has_goods_notice":0,"goods_notice_title":"","goods_notice":"","timu_count":102,"goods_vr_sale_count":225600,"report_page_count":15,"goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230315\/2e5a4380aeb9d54de02bec3c0f39ec39.jpg","good_comment_percent":"98%","kfshow":0,"kflink":""},"userCouponList":[],"userCouponInfo":[]}}';
            		$pay = '{"code":1,"message":"success","data":{"pay_page":{"pay_style_type":"1","report_name":"","activity_name":"","pay_thumb_image":"https:\/\/oss.1cece.top","goods_sale_price":"29.9","goods_original_price":"58","goods_sale_price2":"33.9","goods_original_price2":"98","goods_sale_price3":"39.9","goods_original_price3":"298","price2_send_goods_id":"","price3_send_goods_id":"","price3_send2_goods_id":"","has_wenxin_tishi":"0","wenxin_tishi":"","btn_text":"微信支付解锁你的报告","float_btn_color":"#F6727E","float_btn_text":"微信支付解锁你的报告","float_btn_linke_type":"1","has_head_script_code":"0","head_script_code":"","has_body_script_code":"0","body_script_code":"","pc_pay_jianjie":"<p><span style=\"font-size: 18px;\"><strong>你将获得：<\/strong><\/span><\/p><p><strong>一，专业的分析报告<\/strong><\/p><p>评测报告全文超5000字，含MBTI人格类型评估与分析、恋爱性格分析、爱情婚姻走向、职场性格分析、职业天赋评估、专属心理学建议等20项专业模块，98%的用户好评。<\/p><p><strong>二，3次免费重测权益<\/strong><\/p><p>付费后可享受三次免费重测的权益，4次报告均会保存。<\/p><p><strong>三，报告永久保存<\/strong><\/p><p><span style=\"text-wrap: wrap;\">支付完成后，报告可永久保存，报告结果可供您随时查看。<\/span><\/p>","pc_pay_content":"<p><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/ac90beecb1a86f234759290ec9f47c3f.jpg\" title=\"\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/ac90beecb1a86f234759290ec9f47c3f.jpg\" alt=\"\"\/><\/p><p><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/6eb9508a3d47e47e51b110095f14a3f2.jpg\" title=\"\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/6eb9508a3d47e47e51b110095f14a3f2.jpg\" alt=\"\"\/><\/p><p><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/f68b9bbb9ca32adeadf0b01a44da756a.jpg\" title=\"\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230316\/f68b9bbb9ca32adeadf0b01a44da756a.jpg\" alt=\"\"\/><\/p>","mobile_pay_content":"<p><span style=\"color: rgb(77, 111, 176); text-wrap: wrap;\">MBTI人格测试在全球盛行了一个多世纪，是最正统的<\/span><span style=\"text-wrap: wrap; color: rgb(246, 114, 126);\">心理学人格测评系统<\/span><span style=\"color: rgb(77, 111, 176); text-wrap: wrap;\">。本测评以<\/span><span style=\"text-wrap: wrap; color: rgb(246, 114, 126);\">迈尔斯布里格斯类型指标 （MBTI）<\/span><span style=\"color: rgb(77, 111, 176); text-wrap: wrap;\">&nbsp;和现代心理学创始人荣格的<\/span><span style=\"text-wrap: wrap; color: rgb(246, 114, 126);\">《心理类型》<\/span><span style=\"color: rgb(77, 111, 176); text-wrap: wrap;\">理论为基础，并基于中国文化背景研发，更符合中国人测评，用于了解自己的<\/span><span style=\"text-wrap: wrap; color: rgb(246, 114, 126);\">性格、爱情观、择业观<\/span><span style=\"color: rgb(77, 111, 176); text-wrap: wrap;\">。<\/span><\/p>","btn_jianjie":"<p style=\"text-wrap: wrap;\">你的测评报告全文超过<span style=\"color: rgb(246, 114, 126);\">5000<\/span>字！含MBTI人格类型评估与分析、恋爱性格分析、爱情婚姻走向、职场性格分析、职业天赋评估、专属心理学建议等<span style=\"color: rgb(246, 114, 126);\">20项<\/span>专业模块。<\/p><p style=\"text-wrap: wrap;\">支付后可查看报告，并赠送不限时免费重测三次。<\/p>"},"goodsOrderInfo":{"pay_status":0,"get_red_packet_time":0,"red_packet_money":"0.00","goodsOrderVersionText":"女生"},"paperOrderInfo":{"create_time":1716794806,"complete_time":1716794909,"age":0,"answerCount":105},"paper_info":{"type":3,"countdown":30,"subtitle":"MBTI性格测试专业版","style_type":1,"bottom_Info":"<p style=\"text-align: center;\"><b>完成测试后，您将获得<\/b><\/p><ul class=\" list-paddingleft-2\" style=\"list-style-type: disc;\"><li><p>获取您的4字母类型测试结果<\/p><\/li><li><p>发现适合于您性格类型的职业<\/p><\/li><li><p>知悉您的偏好优势和类型描述<\/p><\/li><li><p>评估您与恋人的长期相处情况<\/p><\/li><li><p>了解您的沟通风格和学习风格<\/p><\/li><li><p>查看与您分享同一性格的名人<\/p><\/li><\/ul><p style=\"text-align: center;\">所有内容基于卡尔·荣格 (Carl Jung) 和伊莎贝尔·布里格斯·迈尔斯 (lsabel Briggs Myers)的MBTI理论实证<\/p><p><br\/><\/p>"},"paperSet":{"needSet":0,"setType":0,"setPageInfo":[]},"create_report":{"setting":{"create_type":"1","report_shuoming":"你的MBTI人格测试专业报告正在生成中","touming_icon":"https:\/\/oss.1cece.top\/storage\/Paper\/20220608\/9542b3ee23e3172d8150b165dba3cada.png"},"data":[{"id":813,"title":"性格类型","childList":[{"id":816,"title":"正在分析性格内外向、直觉、情感","title_color":"#f08760","exec_time":"2"},{"id":817,"title":"正在分析你的天赋能力、行为风格","title_color":"#f08760","exec_time":"2"},{"id":818,"title":"正在分析你的性格优势与性格劣势","title_color":"#f08760","exec_time":"1.5"},{"id":819,"title":"正在综合评估你的MBTI人格类型","title_color":"#f08760","exec_time":"1.5"}]},{"id":814,"title":"爱情分析","childList":[{"id":820,"title":"正在评估你的恋爱特质和爱情基因","title_color":"#f08760","exec_time":"2"},{"id":821,"title":"正在分析你喜欢的男生性格类型","title_color":"#f08760","exec_time":"2"},{"id":822,"title":"正在根据性格判断你未来的爱情婚姻走向","title_color":"#f08760","exec_time":"2"},{"id":823,"title":"正在生成专属你的异性魅力锦囊","title_color":"#f08760","exec_time":"1.5"}]},{"id":815,"title":"职业性格","childList":[{"id":824,"title":"正在分析你的职场性格优势与劣势","title_color":"#f08760","exec_time":"2"},{"id":825,"title":"正在分析你的性格对人际关系的影响","title_color":"#f08760","exec_time":"2"},{"id":826,"title":"正在生成与你天赋相匹配的岗位和职业","title_color":"#f08760","exec_time":"1.5"},{"id":827,"title":"正在生成你的求职规划与晋升建议","title_color":"#f08760","exec_time":"1"}]}]},"publicity_goods":{"publicity_goods_name":"MBTI性格测试专业版"},"goods_info":{"goods_name":"MBTI性格测试2024版","has_goods_notice":0,"goods_notice_title":"","goods_notice":"","timu_count":104,"goods_vr_sale_count":6345732,"report_page_count":20,"goods_cover_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20240423\/3a07bd3c0348bac9e8b07bf7360749c8.png","good_comment_percent":"98%","kfshow":0,"kflink":""},"userCouponList":[],"userCouponInfo":[]}}';
            		$pay = json_decode($pay, true);
            		
            		$pay = $pay['data'];
            		$red_packet = '{"code":1,"message":"success","data":{"red_packet_money":"10.00","red_packet_text":"6.7","red_packet2_money":"20.00","red_packet2_text":"9.9","has_red_packet3":1,"red_packet3_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20230417\/c70bc683ea5cec7c1a420e9cee81a65e.png"}}';
            		$red_packet = json_decode($red_packet, true);
            		$red_packet = $red_packet['data'];
            	}
        
            	// 未处理的字段 top_image  version_page_style.file
            	$promotionEtt = $promotionDao->getNewEntity();

            	$promotionEtt->name = $publicityGoods['publicityGoods']['publicity_goods_name']; // 名称
            	$promotionEtt->desc = $publicityGoods['publicityGoods']['jianjie']; // 简介

            	$promotionEtt->backgroundImage = $publicityGoods['publicityGoods']['bg_image']; // 背景图片
     			$promotionEtt->copyright = $publicityGoods['publicityGoods']['copyright']; // 版权信息
            	$promotionEtt->styleType = $publicityGoods['publicityGoods']['publicity_goods_style'];
            	$promotionEtt->answerStyleType = $publicityGoods['publicityGoods']['order_answer_method']; // 答题样式
            	$promotionEtt->createTime = $now;
            	$promotionVersionConfig = array(); // 选项配置
            	if (!empty($publicityGoods['publicityGoods']['version_page_style'])) {
            		$promotionVersionConfig = array(
            			'type' => $publicityGoods['publicityGoods']['version_page_style']['style_type'], // 默认为0 样式类型 1 2 
            			//'bgColor' => $publicityGoods['publicityGoods']['version_page_style']['version_text'], // 背景样式
            			//'copyright' => $publicityGoods['publicityGoods']['version_page_style']['version_text'], // 版权信息
            			//'bottomDesc' => $publicityGoods['publicityGoods']['version_page_style']['version_text'], // 底部信息
            			'topDesc' => $publicityGoods['publicityGoods']['version_page_style']['top_desc'],
            			'topImg' => $publicityGoods['publicityGoods']['version_page_style']['top_image'],
            			'text' => $publicityGoods['publicityGoods']['version_page_style']['version_text'],
            			'desc' => $publicityGoods['publicityGoods']['version_page_style']['version_desc'],
            		);
            		if (!empty($publicityGoods['publicityGoods']['version_page_style']['version_text'])) {
            			$promotionVersionConfig['list'] = array(
            				$publicityGoods['publicityGoods']['version_page_style']['left_version_text'] => $publicityGoods['publicityGoods']['version_page_style']['left_version_image'],
            				$publicityGoods['publicityGoods']['version_page_style']['right_version_text'] => $publicityGoods['publicityGoods']['version_page_style']['right_version_image'],
            			);
            		}
            	}
            	
            	/** 未处理的字段
                    [paper_style_type] => 1
            	 */
            	$goods = $publicityGoods['publicityGoods']['goods'];
            	if ($goods['goods_version'] >= 2) {
            		$testPaperVersionConfig = array(
            			'text' => $goods['version_text'],
            			'list' => array(
            				$goods['left_version_text'] => $goods['left_version_image'],
            				$goods['right_version_text'] => $goods['right_version_image'],
            			),
            		);
            	}
            	$testPaperEtt = $testPaperDao->getNewEntity();
	            $testPaperEtt->name = $goods['goods_name'];
	            $testPaperEtt->subhead = $goods['goods_subtitle'];
	           
	            $testPaperEtt->saleNum = empty($goods['goods_vr_sale_count']) ? 0 : $goods['goods_vr_sale_count'];
	            $testPaperEtt->customerUrl = $publicityGoods['publicityGoods']['kflink']; //  给测评
            	if (!empty($publicityGoods['paper_info']['bottom_Info'])) {
            		$testPaperExtend['bottomHtml'] = $publicityGoods['paper_info']['bottom_Info'];
            	}
          		$detail1 = empty($publicityGoods['detail1']) ? array() : $publicityGoods['detail1'];
          		$detail2 = empty($publicityGoods['detail2']) ? array() : $publicityGoods['detail2'];
            } 
        
            
          
            // 选项配置
            if (!empty($goods_info['right_version_text']) && $goods_info['goods_version'] >= 2) {
            	$testPaperVersionConfig['text'] = $goods_info['version_text'];
            	$testPaperVersionConfig['list'] = array(
            		$goods_info['left_version_text'] => $goods_info['left_version_image'],
            		$goods_info['right_version_text'] => $goods_info['right_version_image'],
            	);
            }
           
            
          
            $paperSet = $detail1['paperSet'];
            $paper_tips = $detail1['paper_tips'];
            $paper_info = $detail1['paper_info'];
            
            if (empty($paper_info) && !empty($pay['paper_info'])) {
            	$paper_info = $pay['paper_info'];
            }
            if (empty($paperSet) && !empty($pay['paperSet'])) {
            	$paperSet = $pay['paperSet'];
            }
            $goods_extend = empty($detail1['goods_info']['goods_extend']) ? array() : $detail1['goods_info']['goods_extend'];
            if (!empty($goods_extend)) { // 盖洛普优势识别测试
            	if (!empty($goods_extend['sub_title'])) {
            		$testPaperExtend['headText'] = $goods_extend['sub_title'];
            	}
            	if (!empty($goods_extend['xcy'])) {
            		$testPaperExtend['centerText'] = $goods_extend['xcy'];
            	}
            	if (!empty($goods_extend['jjhd'])) {
            		$testPaperExtend['bottomText'] = $goods_extend['jjhd'];
            	}
            }
            //continue;
            
            $ageSet = array();
            if (!empty($paperSet)) { // 需要选择年龄
            	if (!empty($paperSet['setPageInfo'])) {
            		$ageSet = array(
            			'title' => '请选择你的年龄',
            			'desc' => $paperSet['setPageInfo']['shuoming'],
            			'location' => 'end', // 所处的位置
            		);
            	}
            }
            
            if (!empty($paper_info)) { // paper_info
           
            	if (!empty($paper_info['style_type'])) {
            		$testPaperExtend['styleType'] = $paper_info['style_type'];
            	}
            	if (!empty($paper_info['bottom_Info'])) {
					$testPaperExtend['bottomInfo'] = $paper_info['bottom_Info']; // 答题页面底部信息
            	}
            }
 
          
            
            if (!empty($promotionEtt)) {
            	$promotionEtt->testPaperId = $testPaperId;
            	$promotionEtt->updateTime = $now;
            	$haveData = $promotionDao->readListByWhere("`name`='{$promotionEtt->name}'");
            	if (!empty($haveData)) {
            		$promotionEtt->id = reset($haveData)->id;
            		$promotionDao->execBySql("delete from `promotion` where `id`='{$promotionEtt->id}'");
            	}
            	$promotionDao->create($promotionEtt);
            }
            
   print_r($questionDatas);exit;

        }
        echo "完成同步"; exit;
    }
    
    /**
     * 写入报告
     *
     * @return array
     *
     * aibilinkj@126.com
     */
    private function xz_report_write($guid, $row, $headers, $goods_name)
    {
    	$paper_order_sn = $row['paper_order_sn'];
    	$order = base64_encode(json_encode($row, JSON_UNESCAPED_UNICODE));
    	// 用户报告
    	$reportUrl = 'https://adapi.monday1.top/v1/paper/result?paper_order_sn=' . $paper_order_sn;
    	$reportResponse = httpGetContents($reportUrl, null, 20, $headers);
    	$reportResponse = empty($reportResponse) ? array() : json_decode($reportResponse, true);
    	$report = empty($reportResponse['data']) ? array() : $reportResponse['data'];
    	$report = empty($report) ? '' : base64_encode(json_encode($report, JSON_UNESCAPED_UNICODE));
    	$commonDao = \dao\Common::singleton();
    	if (!empty($report)) {
//     		$updateSql = "update `xz_goods` set `report`='{$report}', `order`='{$order}', `paper_order_sn` = '{$paper_order_sn}' where `guid` = '{$guid}';";
//     		$commonDao->execBySql($updateSql);
    	}
    	$payUrl = 'https://adapi.monday1.top/v1/goods_order/pay_page?paper_order_sn=' . $paper_order_sn;
    	$payResponse = httpGetContents($payUrl, null, 20, $headers);
    	$payResponse = empty($payResponse) ? array() : json_decode($payResponse, true);
    	$pay = empty($payResponse['data']) ? array() : $payResponse['data'];
    	$pay = empty($pay) ? '' : base64_encode(json_encode($pay, JSON_UNESCAPED_UNICODE));
    	if (!empty($pay)) {
//     		$updateSql = "update `xz_goods` set `pay`='{$pay}', `order`='{$order}', `paper_order_sn` = '{$paper_order_sn}' where `guid` = '{$guid}';";
//     		$commonDao->execBySql($updateSql);
    	}
    	
    	// 管理员-用户报告
    	$adminReportUrl = 'https://adapi.monday1.top/v1/paper/backendCheck?paper_order_sn=' . $paper_order_sn;
    	$adminReportResponse = httpGetContents($adminReportUrl, null, 20, $headers);
    	 
    	$adminReportResponse = empty($adminReportResponse) ? array() : json_decode($adminReportResponse, true);
    	$adminReport = empty($adminReportResponse['data']) ? array() : $adminReportResponse['data'];
    	$adminReport = empty($adminReport) ? '' : base64_encode(json_encode($adminReport, JSON_UNESCAPED_UNICODE));
    	 
    	$data = array(
    		'paper_order_sn' => $paper_order_sn,
    		'goods_name' => $goods_name,
    		'guid' => $guid,
    		'pay' => $pay,
    		'report' => $report,
    		'adminReport' => $adminReport,
    		'order' => $order,
    	);
    	$fieldStr = '`' . implode('`, `', array_keys($data)) . '`';
    	$valueStr = "'" . implode("', '", array_values($data)) . "'";
    	$sql = "REPLACE INTO `xz_report` ({$fieldStr}) VALUES ({$valueStr});";
    	$commonDao->execBySql($sql);
    	echo $paper_order_sn . "\t" . $goods_name . "\n";
    	return true;
    }
    
    /**
     * 采集心芝题目
     *
     * @return array
     * 
     * aibilinkj@126.com
     */
    public function xz_report()
    {
$authorizationArr = array();
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcxNzMxNjQ5MywiaWF0IjoxNzE3MjMwMDkzLCJpc3MiOiIiLCJqdGkiOiJiYmMxMWE2YTgyYTYwZGU2OTQxMDRlMWU4MjFmNzNhMyIsIm5iZiI6MTcxNzIzMDA5Mywic3ViIjoiIn0.jgkHWmtWYrreIRqOgcwfOYRZzjKDwt-vOJ50fIHYs6M';    	
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY4MDU4MDgsImF1ZCI6IiIsImV4cCI6MTcxNzI5OTcyMSwiaWF0IjoxNzE3MjEzMzIxLCJpc3MiOiIiLCJqdGkiOiIzOTc2N2JjZDZjM2FlYmUyN2YwOTNkOTM3ZGEyMDI1NCIsIm5iZiI6MTcxNzIxMzMyMSwic3ViIjoiIn0.NyeMOLGLpW1jqNfe-GQv4uuSNEOU5im63P-z3-wP0mU';
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcxNzU5ODEwOCwiaWF0IjoxNzE3NTExNzA4LCJpc3MiOiIiLCJqdGkiOiI0MjVlODg4ZGE4ODNlYjc3OWY5YTkxYjM3NDgwMDQ0YyIsIm5iZiI6MTcxNzUxMTcwOCwic3ViIjoiIn0.skb6LNgFA8kp4A5UNwAE6ylT0cmjQxGfUdvXDB8vjvw';
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY4MDU4MDgsImF1ZCI6IiIsImV4cCI6MTcxNzU5NDI2MywiaWF0IjoxNzE3NTA3ODYzLCJpc3MiOiIiLCJqdGkiOiJkZjM1OTQ1NDFiNWVkMjMwMGM1YzI5ZWVkOWJmOGRlOSIsIm5iZiI6MTcxNzUwNzg2Mywic3ViIjoiIn0.7LugeR9FrCE-ktAbQEqKLlzhZxNyT5ttLD6L4eAs2P8';
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcxNzcyNDYzMywiaWF0IjoxNzE3NjM4MjMzLCJpc3MiOiIiLCJqdGkiOiJmOGYyNWE1NjEwMTRlZDliMGJiYjEwNDdmYTY3MjAzMyIsIm5iZiI6MTcxNzYzODIzMywic3ViIjoiIn0.eIi-iUCfX3OVEIW-PqefXd1Jm3AiZMwbGSBvf2A9Ty4';
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcxODExNzI1MCwiaWF0IjoxNzE4MDMwODUwLCJpc3MiOiIiLCJqdGkiOiIxZGNlOTk3ZjZjMWUxYTBjYWQ3ODVmMzc3MDMzOWE5MSIsIm5iZiI6MTcxODAzMDg1MCwic3ViIjoiIn0.XSeHxwe842GKC-SE5DJAhWmtRGIRYlm_or0zVXrd7e4';
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcxODg3NDAzMSwiaWF0IjoxNzE4Nzg3NjMxLCJpc3MiOiIiLCJqdGkiOiIxMWViZGU5ZGEyYTFlZTY0NzkzYzdmYzU0YmJkM2M5MSIsIm5iZiI6MTcxODc4NzYzMSwic3ViIjoiIn0.N49SwQNirelkbVVyrNpOi20RMhWqZDVcH0EKAe870rc';
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY4MDU4MDgsImF1ZCI6IiIsImV4cCI6MTcxODg4MTMwNCwiaWF0IjoxNzE4Nzk0OTA0LCJpc3MiOiIiLCJqdGkiOiI1YzA3ZWQwN2YzOGNjZmQ2ZGIxOWJkNDM3MjFjMTJlMiIsIm5iZiI6MTcxODc5NDkwNCwic3ViIjoiIn0.UiwpnLJRN1Yd6sWiD_-WKnzvet7cDBqciAVSkT5wlCw';
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcxODg3NDAzMSwiaWF0IjoxNzE4Nzg3NjMxLCJpc3MiOiIiLCJqdGkiOiIxMWViZGU5ZGEyYTFlZTY0NzkzYzdmYzU0YmJkM2M5MSIsIm5iZiI6MTcxODc4NzYzMSwic3ViIjoiIn0.N49SwQNirelkbVVyrNpOi20RMhWqZDVcH0EKAe870rc';
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY4MDU4MDgsImF1ZCI6IiIsImV4cCI6MTcxODg4MTMwNCwiaWF0IjoxNzE4Nzk0OTA0LCJpc3MiOiIiLCJqdGkiOiI1YzA3ZWQwN2YzOGNjZmQ2ZGIxOWJkNDM3MjFjMTJlMiIsIm5iZiI6MTcxODc5NDkwNCwic3ViIjoiIn0.UiwpnLJRN1Yd6sWiD_-WKnzvet7cDBqciAVSkT5wlCw';
$authorizationArr[] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcxOTI5ODg2MiwiaWF0IjoxNzE5MjEyNDYyLCJpc3MiOiIiLCJqdGkiOiI0YWYwZTQxYjE2OTI3NGQ0NzM2OGQ0MjMxMzg1Y2VkMiIsIm5iZiI6MTcxOTIxMjQ2Miwic3ViIjoiIn0.8Gy5-up3Id_zDvEt6lRPM0l_CaIPpk7OtFMngf3msjE';
$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY4MDU4MDgsImF1ZCI6IiIsImV4cCI6MTcxOTQ3MzQ5NywiaWF0IjoxNzE5Mzg3MDk3LCJpc3MiOiIiLCJqdGkiOiJhZGZiY2Q5NGE1OWQwZWJkMDJmMTJiNzlmNWQzODMwMCIsIm5iZiI6MTcxOTM4NzA5Nywic3ViIjoiIn0.Ef513MCLQr4FQqRy164WN1jzELUpKR9kmbKu4n4XZ4g';

$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcyMDE3Mjk4NiwiaWF0IjoxNzIwMDg2NTg2LCJpc3MiOiIiLCJqdGkiOiJjZTNhYjdiZDE3YzA3NTY3ZGIzY2I0NGVkMDg3MjI3YyIsIm5iZiI6MTcyMDA4NjU4Niwic3ViIjoiIn0.9LcwXIBudUzzYT5Cfi-lqq7mhulzSxuu4iEsbfXZk2k';
//$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcxOTU1MTc2MiwiaWF0IjoxNzE5NDY1MzYyLCJpc3MiOiIiLCJqdGkiOiJjMTEyODdkYmM0OGI0M2JiNzk3ZmY2YmRjZmZkOTViYyIsIm5iZiI6MTcxOTQ2NTM2Miwic3ViIjoiIn0.8i11emONk0o4LEX6dmNmr0mFLZ2uIjOv9JpdT_QgihQ';
$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY4MDU4MDgsImF1ZCI6IiIsImV4cCI6MTcyMDIzNTkxOSwiaWF0IjoxNzIwMTQ5NTE5LCJpc3MiOiIiLCJqdGkiOiIzNjljNjQ2ZmIwN2JhNDdiNDlmMDM0ZTkzYThmZGU3NCIsIm5iZiI6MTcyMDE0OTUxOSwic3ViIjoiIn0.RxV-Hx-R8fI6fdTITPajqPkave810KAsxHEeEZrEqgM';

$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY4MDU4MDgsImF1ZCI6IiIsImV4cCI6MTcyMDUwODQ3MCwiaWF0IjoxNzIwNDIyMDcwLCJpc3MiOiIiLCJqdGkiOiJkZmU5ZmQxZjdlYjc4OTU4OTIwZTA0MzAzNGVlZTUzNiIsIm5iZiI6MTcyMDQyMjA3MCwic3ViIjoiIn0.zmbA8aOKfApBziFUdz_pI3cHy67B166PHOY2iHei30U';

$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcyMDc1Njg1MiwiaWF0IjoxNzIwNjcwNDUyLCJpc3MiOiIiLCJqdGkiOiI1OTllNDViMWZjMjQ5NTVjZDk1ZTE3NTc2ZjJmY2Y5ZiIsIm5iZiI6MTcyMDY3MDQ1Miwic3ViIjoiIn0.bu6Ho2qLlNIHosO-u0t9STQ2ubVD9GofMMXKwe65z9c';

$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcyMDgzOTY1NSwiaWF0IjoxNzIwNzUzMjU1LCJpc3MiOiIiLCJqdGkiOiJjOWVlYTQ5OWZlZmZjMzJiNzY4YzYyYjFhMmE1NjAzZiIsIm5iZiI6MTcyMDc1MzI1NSwic3ViIjoiIn0.gOVpOtacKhu_7uULOQwimY8t_jEaiTYbzBIbrNiUcbg';
//$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcyMDQ1MTcxOSwiaWF0IjoxNzIwMzY1MzE5LCJpc3MiOiIiLCJqdGkiOiI2ZmIxODdlZjBiYTNmNWU4YTAxNTg0NjZhNzJlZmY2YiIsIm5iZiI6MTcyMDM2NTMxOSwic3ViIjoiIn0.vVGFcmoHN1BjT27L336lvT69mBU87EGOdz9CiYrKqok';
//$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY4MDU4MDgsImF1ZCI6IiIsImV4cCI6MTcxOTI5ODkzNywiaWF0IjoxNzE5MjEyNTM3LCJpc3MiOiIiLCJqdGkiOiI2YjI5MTc1M2UzZjE4ZDc5OWNmNDVlNjU2NTMwODllNyIsIm5iZiI6MTcxOTIxMjUzNywic3ViIjoiIn0.wiNjSu-Xg2ZasEJ3NP9pbwm3-ZSbGFL2eEU9zSgnFXQ';
    	// echo $authorization . "\n";
//     	$authorizationArr = explode('.', $authorization);
$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcyMTEwOTk1NywiaWF0IjoxNzIxMDIzNTU3LCJpc3MiOiIiLCJqdGkiOiJlMmE2YjQyNTI1ODBiOTMwNDExNjljNDhjMzU3MGNhMyIsIm5iZiI6MTcyMTAyMzU1Nywic3ViIjoiIn0.WqtybJjaiwvIcJ6tQMjkkT7s00nPm8lUsQUr7jad9cQ';    	

$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY1MDY4MzcsImF1ZCI6IiIsImV4cCI6MTcyNjgxMTkyMCwiaWF0IjoxNzI2NzI1NTIwLCJpc3MiOiIiLCJqdGkiOiI3OWFkZDBmNDMwMjU3MjJlZGNjNjMzODJkNGI4YTlhMCIsIm5iZiI6MTcyNjcyNTUyMCwic3ViIjoiIn0.mRgoIJNnbc_HkF7lu53VlKLY7xSBK3WODwP5Gt8_22M';
$authorization = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjY4MDU4MDgsImF1ZCI6IiIsImV4cCI6MTcyNjgzNzA3NSwiaWF0IjoxNzI2NzUwNjc1LCJpc3MiOiIiLCJqdGkiOiI4Y2FlZDhkNmJkOThmY2Y2ODg3NGFiYzliZTBkODA5NCIsIm5iZiI6MTcyNjc1MDY3NSwic3ViIjoiIn0.yIu6Oj2t0AwklHlBx0fwYDXpCFR_muehHcIC5yLl6C0';

//     	$authorizationInfo = empty($authorizationArr['1']) ? array() : json_decode(base64_decode($authorizationArr['1']), true);
    	
//     	// 
//     	$a = 'qoP_dbT4-lO5fBI75SW2Z1f4OYBzLsXyL0TZ_jGsnds';
    	
//     	$a = md5($authorizationInfo['nbf']); 
//     	echo $a . "\n";
//     	echo "exp:" . date('Y-m-d H:i:s', $authorizationInfo['exp']) . "\n";
//     	echo "iat:" . date('Y-m-d H:i:s', $authorizationInfo['iat']) . "\n";
//     	echo "nbf:" . date('Y-m-d H:i:s', $authorizationInfo['nbf']) . "\n";

//   		$subStr = base64_encode(json_encode($authorizationInfo));
//   		$subStr = trim($subStr, '=');
  		
// 		$authorization = $authorizationArr['0'] . '.' . $subStr . '.' . $authorizationArr['2'];
    	
// 		echo $authorization . "\n";

        
        $headers = <<<EOT
:authority: adapi.monday1.top
:method: GET
:path: /v1/user/getPaperOrderList?page=1&limit=10&status=1&pay_status=1
:scheme: https
Accept: application/json, text/plain, */*
Accept-Language: zh-CN,zh;q=0.9,en-US;q=0.8,en;q=0.7
Authorization: $authorization
Origin: https://one.1cece.top
Referer: https://one.1cece.top/
Sec-Fetch-Dest: empty
Sec-Fetch-Mode: cors
Sec-Fetch-Site: cross-site
User-Agent: Mozilla/5.0 (Linux; Android 14; 2201122C Build/UKQ1.230917.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/116.0.0.0 Mobile Safari/537.36 XWEB/1160117 MMWEBSDK/20240404 MMWEBID/7135 MicroMessenger/8.0.49.2600(0x2800313D) WeChat/arm64 Weixin NetType/WIFI Language/zh_CN ABI/arm64
X-Requested-With: com.tencent.mm
EOT;
        $headersArr = empty($headers) ? 0 : explode("\n", $headers); // 头信息
        $headers = array();
        foreach ($headersArr as $row) {
            $row = trim($row);
            if (empty($row)) {
                continue;
            }
            $headers[] = $row;
        }
        

        $orderListUrl = "https://adapi.monday1.top/v1/user/getPaperOrderList?page=1&limit=300&status=1&pay_status=1";
        $orderListResponse = httpGetContents($orderListUrl, null, 20, $headers);
 
        //$orderListResponse = '';
        $orderListResponse = empty($orderListResponse) ? array() : json_decode($orderListResponse, true);
		$orderList = empty($orderListResponse['data']['data']) ? array() : $orderListResponse['data']['data'];
        
        $commonDao = \dao\Common::singleton();
//         $sql = "SELECT guid, goods_name FROM `xz_goods` WHERE `report` ='' order by `guid` asc;";
//         $sql = "SELECT * FROM `xz_goods` WHERE 1 order by `guid` asc;";
//         $goodsList = $commonDao->readDataBySql($sql);
//         $goodsList = $commonDao->refactorListByKey($goodsList, 'guid');
 
        
        $sql = "SELECT `paper_order_sn` FROM `xz_report` WHERE 1";
        $havaReports = $commonDao->readDataBySql($sql);
        $havaReportIds = array_column($havaReports, 'paper_order_sn');

        if (is_iteratable($orderList)) foreach ($orderList as $row) {
            $goods_name = $row['goodsOrder']['goods_name'];
            
            if ($goods_name != '瑞文国际标准智商测试') {
            	//continue;
            }
            $guid = $row['goodsOrder']['guid'];
            $paper_order_sn = $row['paper_order_sn'];
            if (in_array($paper_order_sn, $havaReportIds)) { // 已采集
            	// 获取重测列表
            	$resetUrl = 'https://adapi.monday1.top/v1/paper_order/resetOrderList?paper_order_sn=' . $paper_order_sn;
            	$resetResponse = httpGetContents($resetUrl, null, 20, $headers);
            	$resetResponse = empty($resetResponse) ? array() : json_decode($resetResponse, true);
            	$resetList = empty($resetResponse['data']) ? array() : $resetResponse['data'];
            
            	if (!empty($resetList)) {
            		foreach ($resetList as $resetRow) {
            			if (in_array($resetRow['paper_order_sn'], $havaReportIds)) { // 已采集
            				continue;
            			}
            			$this->xz_report_write($guid, $resetRow, $headers, $goods_name);
            		}
            	}
            	continue;
            }
            $this->xz_report_write($guid, $row, $headers, $goods_name);
            // 获取重测列表
            $resetUrl = 'https://adapi.monday1.top/v1/paper_order/resetOrderList?paper_order_sn=' . $paper_order_sn;
            $resetResponse = httpGetContents($resetUrl, null, 20, $headers);
            $resetResponse = empty($resetResponse) ? array() : json_decode($resetResponse, true);
            $resetList = empty($resetResponse['data']) ? array() : $resetResponse['data'];
            if (!empty($resetList)) {
            	foreach ($resetList as $resetRow) {
            		$this->xz_report_write($guid, $resetRow, $headers, $goods_name);
            	}
            }
            
        }
        echo "同步完成";
        exit;
    }
    
    /**
     * 心芝-同步推广数据
     *
     * @return array
     */
    public function xz_promotion($pid = 0)
    {
//  	$this->xz_input('63fb0da9');exit;
        $now = $this->frame->now;
        // 获取商品详情
        $goodsUrl = "https://adapi.monday1.top/v1/publicity/detail?puid={$pid}&t={$now}";
	 	$publicityResponseResult = httpGetContents($goodsUrl, null, 50);
 		//$publicityResponseResult = '{"code":1,"message":"success","data":{"publicityGoods":{"puid":"64132671","publicity_goods_name":"你的MBTI人格是哪一型？","publicity_goods_style":2,"timu_count":0,"report_page_count":0,"need_time":0,"btn_color":"#ff0000","btn_text":"立即测试","jianjie":"<p><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230624\/24ab33c37d4ab071a22281a593de1594.png\" title=\"\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230624\/24ab33c37d4ab071a22281a593de1594.png\" alt=\"\"\/><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230624\/b6befba4a585fb9df82972203324d3fd.png\" title=\"\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230624\/b6befba4a585fb9df82972203324d3fd.png\" alt=\"\"\/><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230624\/f382949421735437c00d1654db340251.png\" title=\"\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230624\/f382949421735437c00d1654db340251.png\" alt=\"\"\/><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230624\/bf46b629abf2a0ee5b2eeaa0c383c3a0.png\" title=\"\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230624\/bf46b629abf2a0ee5b2eeaa0c383c3a0.png\" alt=\"\"\/><img src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230609\/1e3604a934446f873f59e3b34006320b.png\" _src=\"https:\/\/oss.1cece.top\/storage\/uploads\/20230609\/1e3604a934446f873f59e3b34006320b.png\" alt=\"1e3604a934446f873f59e3b34006320b.png\"\/><\/p>","has_head_script_code":0,"head_script_code":"","has_body_script_code":0,"body_script_code":"","copyright":"","top_image":"https:\/\/oss.1cece.top","mobile_content":"","pc_content":"","bg_image":"https:\/\/oss.1cece.top","order_answer_method":0,"version_page_style":[],"kfshow":0,"kflink":"","has_ls_order_btn":0,"is_show_exam_btn":0,"goods":{"guid":"63fb0da9","goods_name":"MBTI性格测试专业版","goods_subtitle":"你的性格，适合怎样的工作、爱情与婚姻？","paper_id":0,"goods_version":2,"version_text":"请选择您的性别","left_paper_id":246,"left_version_text":"男","left_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/30581edbcb68c38818518d0be7afeaf3.png","right_paper_id":249,"right_version_text":"女","right_version_image":"https:\/\/oss.1cece.top\/storage\/Goods\/20220112\/75ad937e7145237d1261db79bfb0149b.png","goods_vr_sale_count":225600,"paper_style_type":0}},"goodsOrderInfo":[],"paperOrderInfo":[]}}';
        $publicityResponse = json_decode($publicityResponseResult, true);
        $publicityGoods = empty($publicityResponse['data']) ? array() : $publicityResponse['data'];
        if (empty($publicityGoods)) {
        	throw new $this->exception('获取推广数据失败');
        }
        $goods = empty($publicityGoods['publicityGoods']['goods']) ? array() : $publicityGoods['publicityGoods']['goods'];
 print_r($publicityGoods);exit;

        $commonDao = \dao\Common::singleton();
        // 加载题目
        $detail1 = '';
        $detail2 = '';
        for ($select = 1; $select <= $goods['goods_version']; $select ++) {
            $tries = 3;
            do {
                $detailResult = $this->xz_detail($goods, $select);
            } while ($detailResult === false && --$tries > 0);
            if (empty($detailResult)) {
                $error = "获取【{$goods['goods_name']}】, 选项为：[{$select}]下题目失败！";
                echo $error . "\n";
                continue;
            }
            if ($select == 1) {
                $detail1 = $detailResult;
            } else {
                $detail2 = $detailResult;
            }
        }
        // 查找是否存在
        $haveSql = "select * from `xz_goods` where `guid`='{$goods['guid']}';";
        $haveDatas = $commonDao->readDataBySql($haveSql);
    
        if (!empty($haveDatas)) {
//         	$publicityGoods['detail1'] = $detail1;
//         	$publicityGoods['detail2'] = $detail1;
        	$publicityGoods = base64_encode(json_encode($publicityGoods, JSON_UNESCAPED_UNICODE));
        	$updateSql = "update `xz_goods` set `publicityGoods`='{$publicityGoods}'  where `guid` = '{$goods['guid']}';";
        	$commonDao->execBySql($updateSql);
        } else {
     
        	$goods_info = base64_encode(json_encode($goods, JSON_UNESCAPED_UNICODE));
        	$publicityGoods = base64_encode(json_encode($publicityGoods, JSON_UNESCAPED_UNICODE));
        	
        	
      
        	$detail1 = empty($detail1) ? '' : base64_encode(json_encode($detail1, JSON_UNESCAPED_UNICODE));
        	$detail2 = empty($detail2) ? '' : base64_encode(json_encode($detail2, JSON_UNESCAPED_UNICODE));
        	if (empty($detail1)) {
        		throw new $this->exception("获取【{$goods['goods_name']}】失败");
        	}
        	


        	$data = array(
        		'guid' => $goods['guid'],
        		'goods_name' => $goods['goods_name'],
        		'detail1' => $detail1,
        		'detail2' => $detail2,
        		'goods_info' => $goods_info,
        	//	'publicityGoods' => $publicityGoods,
        	);
        	$fieldStr = '`' . implode('`, `', array_keys($data)) . '`';
        	$valueStr = "'" . implode("', '", array_values($data)) . "'";
        	$sql = "REPLACE INTO `xz_goods` ({$fieldStr}) VALUES ({$valueStr});";
        	$commonDao->execBySql($sql);
        }        
        echo "{$goods['goods_name']} \n";
    }
    
    /**
     * 心芝-数据分析
     * 
     * 多版本的
     * @return array
     * 
     * order_answer_method  answerStyleType
     */
    public function xz_analyse($name = '')
    {
$name = 'MBTI性格测试2024版';
    	$commonDao = \dao\Common::singleton();
    	$where = "1";
    	if (!empty($name)) {
    	    $where = "`goods_name` like '%{$name}%'";
    	}
    	$sql = "SELECT * FROM `xz_goods` WHERE {$where} order by `guid` asc;";
    	$goodsList = $commonDao->readDataBySql($sql);
    	$goodsList = $commonDao->refactorListByKey($goodsList, 'guid');
    	$goodsArr = array();
    	if (is_iteratable($goodsList)) foreach ($goodsList as $data) {
    		$goods_name = $data->goods_name;
    		
    		$detail1 = empty($data->detail1) ? array() : json_decode(base64_decode($data->detail1), true);
    		$detail2 = empty($data->detail2) ? array() : json_decode(base64_decode($data->detail2), true);
    		$publicityGoods = empty($data->publicityGoods) ? array() : json_decode(base64_decode($data->publicityGoods), true);
    		$report = empty($data->report) ? array() : json_decode(base64_decode($data->report), true);
    		$pay = empty($data->pay) ? array() : json_decode(base64_decode($data->pay), true);
    		$create_report = empty($pay['create_report']) ? array() : $pay['create_report'];
    		$goods_info = empty($data->goods_info) ? array() : json_decode(base64_decode($data->goods_info), true);
    		
    		
    		print_r($report);exit;
    		if (!empty($pay['paperOrderInfo']['age'])) {
    			//print_r($report);
    		}
    		if ( !empty($goods_info['left_paper_id']) || !empty($goods_info['right_paper_id'])) {
    		    print_r($goods_info);
    		}
    		
    		
    		continue;
    		
    		if ($data->goods_version >= 2) { // 多个版本
    			//$goodsArr[] = $goods_name;
    		}
    		if (!empty($goods_info['kflink'])) {
    			print_r($goods_info);exit;
    		}
    		
    		
    		$paper_style_type = $data->paper_style_type;
    		if ($paper_style_type != 0) { // 多个版本
    			//$goodsArr[$paper_style_type] = $goods_name;
    		}
    		if (!empty($publicityGoods)) {
    			// print_r($pay);exit;
    		}
    		if (!empty($create_report)) {
    			$goodsArr[] = $goods_name;
    		}
    		
    		if (!empty($report['goods_sale_price_type'])) {
    			print_r($report);exit;
    		}
    		
    		
    	}
    	exit;
    	print_r($goodsArr);exit;
    	exit;
    	
    }
    
    /**
     * 加载盖洛普报告

    [p_sort] => 4
    [p_extend] => Array
        (
            [weidu_color] => #01945d
            [sort_icon] => https://oss.1cece.top/storage/Goods/20230706/4256b8238864698d2ec02235629cc738.png
        )

    
     * @return
     */
    private function loadGallupReport($paperOrderResult)
    {
if ( $paperOrderResult['create_time'] != '2024-07-05 10:55:52') {
//	return false;
}
    	if (empty($paperOrderResult['duoweidu']['duoweiduList'])) {
    		return false;
    	}
    	$childWeiduList = $paperOrderResult['duoweidu']['childWeiduList'];
    	$childWeiduList = array_column($childWeiduList, 'weidu_name');
 
    	$duoweiduList = $paperOrderResult['duoweidu']['duoweiduList'];
    	$childConfList = array(); // 元素
    	$duoweiduConfs = array();
        if (is_iteratable($duoweiduList)) foreach ($duoweiduList as $duoweidu) {
        	// 维度配置
        	$duoweiduConfs[$duoweidu['weidu_name']] = array(

        		'描述' => $duoweidu['jianjie'],
        		'图标图片' => $duoweidu['extend']['weidu_icon'],
        		'图标颜色' => $duoweidu['extend']['weidu_color'],
        		'标题颜色' => $duoweidu['extend']['weidu_title_color'],
        		'开始图标' => $duoweidu['extend']['sort_icon'],
        	); 
        	
            $childList = empty($duoweidu['childList']) ? array() : $duoweidu['childList'];
            foreach ($childList as $child) {
            	if ($child['weidu_name'] == '专注') {
            		
            		$child['extend']['content1'] = '<p>在“专注”天赋的指引下，你内心始终都需要明确的目的地。没有它，你很可能会对自己的生活和工作一筹莫展。目标如同罗盘，帮助你确定重点，并进行必要的修正，以保持航向。</p><p>你的“专注”天赋十分强大，它会让你本能地判断某个行动是否有助于你达到目标，无助于此的便放弃，帮助你提高效率。这种模式的另一面很可能会难以忍受拖延或障碍，这使你成为一名可贵的团队成员。</p><p><br/></p><h4>一、“专注”的优势</h4><ul class=" list-paddingleft-2" style="list-style-type: disc;"><li><p>1、目标感强，有着高效的处事方式</p></li></ul><p>你喜欢朝着一个具体的目标前进，因为如果没有一个具体的目标，你会觉得失去了方向感。所以你习惯给自己的大小事制定一个又一个的目标，作为你的日常指导。由于你定下了明确的方向，而你又有较强的目标达成动机，因此你有着一套高效的处事方式。你喜欢拆解任务，制定大小适合的目标，事情到你手上，大部分都能够在规定时间内完成任务。</p><ul class=" list-paddingleft-2" style="list-style-type: disc;"><li><p>2、目标感清晰明确，始终保持有效的输出</p></li></ul><p>在执行任务的时候，很多人难免会在过程中迷失了方向，或是感觉目标变得模糊。而你却对目标有着清晰的认知，明白自己每个行动都是为目标服务。所以你很较少会偏离方向，每一步都让自己离目标更进一步。如果你在团队中，你可以担当团队中的指路者，带领成员往正确的方向努力。当发现团队方向有所偏离的时候，你可以与伙伴一起修正，保护大队的航向，确保力量用到实处。</p><ul class=" list-paddingleft-2" style="list-style-type: disc;"><li><p>3、高度的自我控制能力</p></li></ul><p>由于你难以忍受放置指定的目标而不顾，当你定下了一个目标之后，你的重心就是思考如何尽快达成。你的成就感也来自于一个个完成的计划。因此你在处理事务时，会比平时更加的专注和投入。你能抵挡很多的诱惑，专心地完成手头上的事情。这是一种高自控力的表现，能确保你在多种场景下都能维持相对稳定的状态，有效地保证了工作的时效和质量。</p><p><br/></p><h4>二、专注的盲点</h4><ul class=" list-paddingleft-2" style="list-style-type: disc;"><li><p>1、对环境的敏感性不够高</p></li></ul><p>由于你在处事的时候对目标高度专注，这可能会影响到你对外部环境的观察。随着外部的变化，可能你的目标和行动也需要进行调整和改变。但你比较容易忽略这一点，有时会只向着目标奋斗。因此，在面对突发状况的时候，你可能难以马上反应过来，并迅速找到有效的应对方法。在这些时候，会让你显得比较被动。</p><ul class=" list-paddingleft-2" style="list-style-type: disc;"><li><p>2、降低你对身边人的关注</p></li></ul><p>你做事时高度专注，一心一意，当你把全部精力都放到目标时，你会降低对其他事物的关注，这或多或少会让你忽视了对身边人际关系的维系。可能会让别人感觉你在情感上比较冷漠，对他人的关心不够，或者主动减少社交，久而久之，你会逐渐与身边的重要他人变得疏远，不利于你建立良好稳定的亲密关系。</p><p><br/></p><h4>三、如何更好地发挥优势</h4><ul class=" list-paddingleft-2" style="list-style-type: disc;"><li><p>面对自己</p></li></ul><p>1、科学地量化目标</p><p>你的动力来自于对目标的达成，因此设立一个科学合理的目标尤其重要。根据目标的难度、时间周期，你可以设立各种大小不一的目标，并给予相应的时间和衡量标准，持续跟进进度。这样不仅会有具体清晰的达成标准，还能帮助你对一些较难的目标进行拆解，降低完成的心理障碍。同时小而频繁的目标达成，能为你及时提供激励，让你时刻保持充足的动力。</p><p>2、确保自己有能够全神贯注投入的事情</p><p>为了避免陷入过于专注的盲点，你可以把这段投入规定在某个时间段内进行。比如说在这段时间里，你可以允许自己不再关注外界发生的事情，关掉手机和社交软件，让自己在此时全身心投入。因为高强度的专注容易让人精神疲累，影响判断力。因此记得这个时间不宜设置太长，让自己有适当放松，以及关注外界的机会。</p><p>3、目标应该是灵活多变，而不是固定的</p><p>你不必要太过于执着于具体的目标，而是根据自己当前的状态以及目标的达成情况来综合评估。我们应该是让目标使我们感到价值，而不是以目标来定义我们。理性看待目标，才能更好地控制和管理自己的生活。</p><p>4、把你的目标延伸到工作之外的领域</p><p>如果你发现自己太专注于工作目标，则可设定个人生活目标。这些目标将重点针对你的个人喜好，你可以获得生活与工作上的平衡。</p><ul class=" list-paddingleft-2" style="list-style-type: disc;"><li><p>面对他人</p></li></ul><p>在工作中，你可以与自己的上级领导说出自己的中期目标和短期目标，这可能会让你的领导有信心和方向为你提供所需的发展空间。</p><p>作为团队成员之一，你的最大价值可能是帮助他人设立目标。在会议结束时，你可以负责总结会议通过的决定，说明这些决定的实施时间，并设定下一次开会的日期。</p><ul class=" list-paddingleft-2" style="list-style-type: disc;"><li><p>面对环境</p></li></ul><p>寻找可单独行使职责的角色和环境，因为你具有优秀的“专注”天赋，可以在无需监督的情况下坚持完成工作。</p><p><br/></p><h4>四、如何领导具有“专注”天赋的人</h4><ul class=" list-paddingleft-2" style="list-style-type: disc;"><li><p>请他们参与有严格期限的项目。这样的下属会本能地遵守期限。一旦接手一个有明确期限的项目，他们就会倾注全力直到完成。如果让这样的下属控制工作进度，效率会更高。</p></li><li><p>定期与他们沟通。定期沟通会使他们振奋，因为他们喜欢讨论目标、为实现目标而取得的进步。</p></li><li><p>他们不适应变动无常的局面。为处理好这个问题，可以使用Ta更好理解的语言解释变化。例如，使用“新的目标”或“新的成功标准”等字眼来讨论变化。这类字眼会使变化显得既有章法，又有目的。这是他们天生的思维方式。</p></li><li><p>让他们参加时间管理培训。他们天生不擅长于此，但鉴于他们“专注”天赋会推动他们尽快向目标迈进，会看中时间管理给他们带来的高效率。</p></li></ul><p><br/></p><h4>五、职业推荐</h4><ul class=" list-paddingleft-2" style="list-style-type: disc;"><li><p>科研人员、运动员</p></li></ul>';
            	} else {
            	//	continue;
            	}
            	if (empty($child['extend']['content1'])) {
            		continue;
            	}
            	$contentArr = explode('<p><br/></p>', $child['extend']['content1']);
         
            	if (count($contentArr) != 6) { // 6 个模块
            		continue;
            	}
            	
            	
            	$conf = array(
            		'所属类型' => $child['p_weidu_name'],
            		'颜色' => $child['extend']['weidu_color'],
            		'简介' => $child['jianjie'],
            		'平均分' => $child['avg_score'],
            		'描述' => [],
            		'优势' => [],
            		'盲点' => [],
            		'发挥优势' => array(),
            		'领导方法' => array(),
            		'职业推荐' => '',
            	);
            	preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $contentArr['0'], $matches);
            	$conf['描述'] = $matches['1'];
            	// 优势
            	$pattern = '/<h4>(.*?)<\/h4>|<li>\s*<p>\d+\.(.*?)<\/p>\s*<\/li>|<p>(.*?)<\/p>/';
            	preg_match_all($pattern, $contentArr['1'], $matches, PREG_SET_ORDER);
            	$current_key = '';
            	foreach ($matches as $match) {
            		if (!empty($match[2])) {
            			$current_key = trim(strip_tags($match[2]));
            		} elseif (!empty($match[3])) {
            			$conf['优势'][$current_key] = trim(strip_tags($match[3]));
            		}
            	}
            	// 盲点
            	$pattern = '/<h4>(.*?)<\/h4>|<li>\s*<p>\d+\.(.*?)<\/p>\s*<\/li>|<p>(.*?)<\/p>/';
            	preg_match_all($pattern, $contentArr['2'], $matches, PREG_SET_ORDER);
            	$current_key = '';
            	foreach ($matches as $match) {
            		if (!empty($match[2])) {
            			$current_key = trim(strip_tags($match[2]));
            		} elseif (!empty($match[3])) {
            			$conf['盲点'][$current_key] = trim(strip_tags($match[3]));
            		}
            	}
            	// 发挥优势
				$contentArr['3']  =  str_replace(array('1、', '2、', '3、', '4、', '寻求能发挥你的想像力的工作，例如:市场营销、广告业新闻业、设计或新产品开发。'), array('1.', '2.', '3.', '4.', '1.寻求能发挥你的想像力的工作，例如:市场营销、广告业新闻业、设计或新产品开发。'), $contentArr['3']);
            	$pattern = '/<li>\s*<p>(.*?)<\/p>\s*<\/li>|<p>(\d+[、\.]?\s*.*?)<\/p>|<p>(?!\d+[、\.]?\s*)(.*?)<\/p>/';
				preg_match_all($pattern, $contentArr['3'], $matches, PREG_SET_ORDER);
				
				$tmp = [];
				$current_section = '';
				$current_subsection = '';
				foreach ($matches as $match) {
				    if (!empty($match[1])) {
				        $current_section = trim(strip_tags($match[1]));
				        $tmp[$current_section] = [];
				    } elseif (!empty($match[2])) {
				        // 去掉序号
				       $current_subsection = mb_ereg_replace('^\d+[、\.]?\s*', '', trim(strip_tags($match[2])));
      
				       $tmp[$current_section][$current_subsection] = array();
				    } elseif (!empty($match[3])) {
				    	$tmp[$current_section][$current_subsection][] = trim(strip_tags($match[3]));
				    }
				}
//3.
				$conf['发挥优势'] = $tmp;
				// 领导方法
				$pattern = '/<li>\s*<p>(.*?)<\/p>\s*<\/li>/';
				preg_match_all($pattern, $contentArr['4'], $matches);
				$tmp = [];
				foreach ($matches[1] as $match) {
					$tmp[] = trim(strip_tags($match));
				}
				$conf['领导方法'] = $tmp;
				preg_match('/<p[^>]*>(.*?)<\/p>/s', $contentArr['5'], $matches);
				$conf['职业推荐'] = $matches['1'];

				$childConfList[$child['weidu_name']] = $conf;
            }
        }
var_export($duoweiduConfs);exit;
       	return $childConfList;
    }

    
    /**
     * ABO性别角色评估
     * makeClass('reportJung', 'd');
     createEntity('report_jung', 'reportJung');
     * @return
     */
    private function loadABOReport($paperOrderResult)
    {

    	$reportABODao = \dao\ReportABO::singleton();
    	$reportABOEtt = $reportABODao->getNewEntity();
    	if (empty($paperOrderResult['total_result_scoring']['content']['result_explain'])) {
    		return false;
    	}
    	$result_explain = $paperOrderResult['total_result_scoring']['content']['result_explain'];


    	
    	// 使用正则表达式匹配<p>标签中的内容
    	preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $result_explain, $matches);

    	preg_match('/src="([^"]+)"/s', $matches['1']['0'], $submatches);
    	$reportABOEtt->mainImg = $submatches['1'];

    	
    	if (preg_match('/<span style="color:[^"]+;"><b>ABO性别：<\/b><\/span>([^<]+)/', $result_explain, $submatches)) {
    		$reportABOEtt->name = trim($submatches[1]);
    	
    	}
    	
    	$reportABOEtt->sexCharacter = str_replace('<b>性别特征：</b>', '', $matches['1']['3']);
    	$reportABOEtt->pheromoneTag = str_replace('<b>信息素：</b>', '', $matches['1']['5']);

    	
    
    	$tmpArr = array_slice($matches['1'], 6);
    	
    	
    	$workKey = 0;
    	$interpersonalKey = 0;
    	$emotionKey = 0;
    	$personageKey = 0;
    	foreach ($tmpArr as $key => $row) {
    		if (preg_match('/<b>代表人物<\/b>/s', $row, $submatches)) {
    			$personageKey = $key;
    		} elseif ($row == '<b>工作：</b>') {
    			$workKey = $key;
    		} elseif ($row == '<b>人际：</b>') {
    			$interpersonalKey = $key;
    		} elseif ($row == '<b>情感：</b>') {
    			$emotionKey = $key;
    		}
    	}
    	// 信息素
    	$pheromoneDescArr = array();
    	for ($index = 0; $index < $personageKey; $index++) {
    		if ($tmpArr[$index] == '<br/>') {
    			continue;
    		}
    		$pheromoneDescArr[] = '<p>' . $tmpArr[$index] . '</p>';
    	}
    	$reportABOEtt->pheromoneDesc = implode('', $pheromoneDescArr);
    	// 代表人物
    	preg_match('/src="([^"]+)"/s', $tmpArr[$personageKey + 1], $submatches);
    	$reportABOEtt->personageImg = $submatches['1'];
    	
    	if (preg_match('/<p style="text-align: center;">([^<]+)<\/p>/', $result_explain, $tmpSub)) {
    		$reportABOEtt->personageTitle = trim($tmpSub[1]);
    		
    	}
    	


    	$reportABOEtt->personageTitle = $tmpArr['5'];

    	$personageDescArr = array();
    	for ($index = $personageKey + 3; $index < $workKey; $index++) {
    		if ($tmpArr[$index] == '<br/>') {
    			continue;
    		}
    		$personageDescArr[] = '<p>' . $tmpArr[$index] . '</p>';
    	}
    	$reportABOEtt->personageDesc = implode('', $personageDescArr);
		// 工作
    	$workArr = array();
    	for ($index = $workKey + 1; $index < $interpersonalKey; $index++) {
    		if ($tmpArr[$index] == '<br/>') {
    			continue;
    		}
    		$workArr[] = '<p>' . $tmpArr[$index] . '</p>';
    	}
    	$reportABOEtt->work = implode('', $workArr);
    	// 人际
    	$interpersonalArr = array();
    	for ($index = $interpersonalKey + 1; $index < $emotionKey; $index++) {
    		if ($tmpArr[$index] == '<br/>') {
    			continue;
    		}
    		$interpersonalArr[] = '<p>' . $tmpArr[$index] . '</p>';
    	}
    	$reportABOEtt->interpersonal = implode('', $interpersonalArr);
    	// 情感
    	$emotionArr = array_slice($tmpArr, $emotionKey + 1);
    	$reportABOEtt->emotion = '<p>' . implode('<p></p>', $emotionArr) . '</p>';
    	$now = $this->frame->now;
    	$reportABOEtt->createTime = $now;
    	
echo $reportABOEtt->name . "\n";    	

// echo $reportABOEtt->personageTitle . "\n";
print_r($paperOrderResult['fanone']['content']['result_explain']);

echo "\n";
return true;
    	$haveEtt = $reportABODao->readByPrimary($reportABOEtt->name);
    	if (empty($haveEtt)) {
    		$reportABODao->create($reportABOEtt);
    	}
    	return true;
    }
    
    /**
     * 荣格古典
     * makeClass('reportJung', 'd');
    	createEntity('report_jung', 'reportJung');
     * @return
     */
    private function loadJungReport($paperOrderResult)
    {
    	$reportJungDao = \dao\ReportJung::singleton();
    	$jifenPailieList = $paperOrderResult['jifen_pl']['jifenPailieList'];
    	$now = $this->frame->now;
    	foreach ($jifenPailieList as $row) {
    		$reportJungEtt = $reportJungDao->getNewEntity();
    	
    		$reportJungEtt->name = $row['weidu_name'];
    		$reportJungEtt->title = $row['jianjie'];
    
    		preg_match_all('/<div class="cb-a1"><i class="fa fa-snapchat-ghost"><\/i><span>(.*?)<\/span><\/div>/s', $row['xiangxi'], $matches1);
    		
    		preg_match_all('/<p><span style="font-size: .46rem; color: #b9a755;">(.*?)<\/span><\/p>/s', $row['xiangxi'], $matches);
    		
    		// 使用正则表达式匹配<p>标签中的内容
    		preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $row['xiangxi'], $matches);
    		
    		if (!empty($matches['1'])) {
    			preg_match('/<span [^>]*>(.*?)<\/span>/s', $matches['1']['0'], $submatches);
    			$reportJungEtt->desc = $submatches['1'];
    			preg_match('/src="([^"]+)"/s', $matches['1']['1'], $submatches);
    			$reportJungEtt->bgImg = $submatches['1'];
    			preg_match('/<span [^>]*>(.*?)<\/span>/s', $matches['1']['2'], $submatches);
    			$reportJungEtt->archetypeTags = $submatches['1']; // 原型标签
    			$reportJungEtt->archetypeDesc = $matches['1']['3']; // 原型介绍
    			preg_match('/<span [^>]*>(.*?)<\/span>/s', $matches['1']['4'], $submatches);
    			$reportJungEtt->positiveTags = $submatches['1'];
    			$reportJungEtt->positiveDesc = $matches['1']['5'];
    			preg_match('/<span [^>]*>(.*?)<\/span>/s', $matches['1']['6'], $submatches);
    			$reportJungEtt->negativeTags = $submatches['1'];
    			$reportJungEtt->negativeDesc = $matches['1']['7'];
    			
    			$reportJungEtt->influence = "<p>" . $matches['1']['8'] . "</p><p>" . $matches['1']['9'] . '</p>';
    			//preg_match_all('/<span>共处的方法<\/span><\/div>(.*?)/s', $matches['1']['10'], $submatches);
    			
    			$tmpArr = array_slice($matches['1'], 10);
    			$coexistMap = array();
    			$index = 1;
    			foreach ($tmpArr as $key => $val) {
    				if (preg_match('/<span [^>]*>(.*?)<\/span>/s', $val, $submatches)) {
    					$coexistMap[$index] = array(
    						'title' => $submatches['1'],
    						'key' => $key,
    					);
    					$index++;
    				}
    			}
    			foreach ($coexistMap as $index => $row) {
    				$coexistTitlePro = 'coexistTitle' . $index;
    				$coexistContentPro = 'coexistContent' . $index;
    				$reportJungEtt->$coexistTitlePro = $row['title'];
    				$coexistContentArr = array();
    				$keyLimit = isset($coexistMap[$index + 1]) ? $coexistMap[$index + 1]['key'] : count($tmpArr);
    				for ($i = $row['key'] + 1; $i < $keyLimit; $i++) {
    					$coexistContentArr[] = "<p>" . $tmpArr[$i] . '</p>';
    				}
    				$reportJungEtt->$coexistContentPro = implode('', $coexistContentArr);
    			}
    		}
    		$haveReportJungEtt = $reportJungDao->readByPrimary($reportJungEtt->name);
    		if (empty($haveReportJungEtt)) {
    			$reportJungEtt->createTime = $now;
    			$reportJungDao->create($reportJungEtt);
    		}
    	}
    	return true;
    }
    
    /**
     * 瑞文国际标准
     *
     * @return
     */
    private function loadRavenReport($paperOrderResult, $version, $paper_order_sn, $goods_name)
    {
    	if (empty($paperOrderResult['fantwo']) || empty($paperOrderResult['fanthree'])) {
    		return false;
    	}
    	$fantwo = $paperOrderResult['fantwo'];
    	$fanthree = $paperOrderResult['fanthree'];
    	$list = array();
    	if (is_iteratable($fanthree['fanthreeList'])) foreach ($fanthree['fanthreeList'] as $row) {
    		$list[$row['weidu_name']] = array(
    			'name' => $row['weidu_name'],
    			'desc' => $row['weidu_result']['jianyi'],
    			'explain' => $row['weidu_result']['result_explain'],
    		);
    	}
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperEtt = $testPaperDao->readListByWhere("`name`='{$goods_name}'");
    	if (!empty($testPaperEtt)) {
    		$testPaperId = reset($testPaperEtt)->id;
    		// 获取题目
    		$testQuestionDao = \dao\TestQuestion::singleton();
    		$testQuestionEttList = $testQuestionDao->readListByIndex(array(
    			'testPaperId' => $testPaperId,
    		));
    		$testQuestionMap = array();
    		foreach ($testQuestionEttList as $key => $testQuestionEtt) {
    			if (!empty($testQuestionEtt->analysis)) {
    				unset($testQuestionEttList[$key]);
    				continue;
    			}
    			$testQuestionMap[$testQuestionEtt->matterImg] = $testQuestionEtt;
    		}
    		// https://adapi.monday1.top/v1/cuotijiexi/getCuotiList?paper_order_sn=279c8668fb876e8b2d4f641bcc735758
    		$errorQuestionUrl = "https://adapi.monday1.top/v1/cuotijiexi/getCuotiList?paper_order_sn=" . $paper_order_sn;
    		$errorQuestionResponse = httpGetContents($errorQuestionUrl, null, 50);
    		$errorQuestionResponse = json_decode($errorQuestionResponse, true);
    		$errorQuestionData = empty($errorQuestionResponse['data']) ? array() : $errorQuestionResponse['data'];
    		$arrUserCuotiJiexiList = empty($errorQuestionData['arrUserCuotiJiexiList']) ? array() : $errorQuestionData['arrUserCuotiJiexiList'];
    		$analysisMap = array();
    		if (is_iteratable($arrUserCuotiJiexiList)) foreach ($arrUserCuotiJiexiList as $row) {
    			$cuoti_jiexi = $row['cuoti_jiexi'];
    			$analysisMap[$row['list_order']] = $cuoti_jiexi;
    			$subject_image = $row['paperDetail']['subject_image'];
    			if (!empty($testQuestionMap[$subject_image])) {
    				$testQuestionEtt = $testQuestionMap[$subject_image];
    				if (empty($testQuestionEtt->analysis)) {
    					$testQuestionEtt->set('scoreValue', $cuoti_jiexi['true_answer']);
    					$testQuestionEtt->set('analysis', $cuoti_jiexi['jiexi']);
    					$testQuestionDao->update($testQuestionEtt);
    				}
    			}
    		}
    	}
    	echo count($testQuestionMap) . "\n";
    	return true;
    }
    
    private static function formatMBTILoveTypeData($html) 
    { 
    	$result = [];

    // 1. 用 <div class="xl-line"></div> 将内容分割成几个部分
    $sections = preg_split('/<div class="xl-line"><\/div>/', $html);

    foreach ($sections as $section) {
        // 2. 匹配每部分的标题
        if (preg_match('/<div class="cb-a"><i class="fa (.*?)"><\/i><span>(.*?)<\/span><\/div>/', $section, $matches)) {
            $iconClass = $matches[1];
            $title = $matches[2];

            switch ($title) {
                case '你的MBTI恋爱类型':
                    if (preg_match('/<img src="(.*?)"[^>]*>/', $section, $imgMatches)) {
                        $result[$title] = $imgMatches[1];
                    }
                    break;
                case '让你着迷的特质':
                    $result[$title] = [];
                    if (preg_match_all('/<p><b>(.*?)<\/b>(.*?)<\/p>/', $section, $submatches, PREG_SET_ORDER)) {
                        foreach ($submatches as $submatch) {
                            $subTitle = trim(str_replace('：', '', $submatch[1]));
                            $content = trim($submatch[2]);
                            $result[$title][$subTitle] = $content;
                        }
                    }
                    break;
                case '和你互补的特质':
                    $result[$title] = [];
                    if (preg_match('/<p><b>(.*?)<\/b>(.*?)<\/p>/', $section, $submatches)) {
                        $subTitle = trim(str_replace('：', '', $submatches[1]));
                        $content = trim($submatches[2]);
                        $result[$title][$subTitle] = [$content];
                    }
                    break;
                case '相遇':
                case '相知':
                case '相爱':
                case '相惜':
                case '相守':
                    $result[$title] = [
                        '图片' => '',
                        '描述' => []
                    ];
                    if (preg_match('/<img src="(.*?)"[^>]*>/', $section, $imgMatches)) {
                        $result[$title]['图片'] = $imgMatches[1];
                    }
                    if (preg_match_all('/<p>(.*?)<\/p>/', $section, $descMatches)) {
                        $result[$title]['描述'] = array_filter(array_map('strip_tags', $descMatches[1]), function($value) {
                            return !empty(trim($value));
                        });
                    }
                    break;
                case '你的天生情人':
                    $result[$title] = [];
                    if (preg_match('/<span class="on">(.*?)<\/span>/', $section, $typeMatches)) {
                        $result[$title]['类型'] = $typeMatches[1];
                    }
                    if (preg_match('/<div class="pipei-yuanyin">.*?<p>(.*?)<\/p>/', $section, $reasonMatches)) {
                        $result[$title]['匹配原因'] = strip_tags($reasonMatches[1]);
                    }
                    if (preg_match('/<p style="text-align: center;"><span style="color: #95a5a6;">(.*?)<\/span><\/p>/', $section, $tagMatches)) {
                        $result[$title]['标签'] = strip_tags($tagMatches[1]);
                    }
                    if (preg_match('/<img src="(.*?)"[^>]*>/', $section, $imgMatches)) {
                        $result[$title]['图片'] = $imgMatches[1];
                    }
                    // 解析能量方向、体验倾向、决定倾向、组织倾向等
                    $attributes = ['能量方向', '体验倾向', '决定倾向', '组织倾向'];
                    foreach ($attributes as $attribute) {
                        if (preg_match('/<span style="color: #ff5d7d;">' . $attribute . '<\/span><\/p><p>(.*?)<\/p>/', $section, $attrMatches)) {
                            $result[$title][$attribute] = [strip_tags($attrMatches[1])];
                        }
                    }
                    // 解析个性特点
                    if (preg_match('/<span style="font-size: .*?;">TA的个性特点<\/span><\/p><ul class=" list-paddingleft-2">(.*?)<\/ul>/', $section, $personalityMatches)) {
                        $result[$title]['TA的个性特点'] = array_map('strip_tags', explode('</li><li>', trim($personalityMatches[1], '<li>')));
                    }
                    // 解析与TA偶遇
                    if (preg_match('/<span style="color: #ff5d7d;">与TA偶遇<\/span><\/p><p>(.*?)<\/p><ul class=" list-paddingleft-2">(.*?)<\/ul>/', $section, $meetMatches)) {
                        $result[$title]['与TA偶遇'] = [
                            '描述' => strip_tags($meetMatches[1]),
                            '地点' => implode(',', array_map('strip_tags', explode('</li><li>', trim($meetMatches[2], '<li>'))))
                        ];
                    }
                    // 解析爱之初体验
                    if (preg_match('/<span style="color: #ff5d7d;">爱之初体验<\/span><\/p>(.*?)<div class="xl-b9" style="margin: 0 0 20px 0;">/', $section, $loveMatches)) {
                        $result[$title]['爱之初体验'] = [
                            '描述' => array_filter(array_map('strip_tags', explode('<p>', trim($loveMatches[1], '<p>'))), function($value) {
                                return !empty(trim($value));
                            })
                        ];
                    }
                    if (preg_match('/<dt><i class="fa fa-lightbulb-o"><\/i>(.*?)<\/dt><dd><p>(.*?)<\/p><\/dd>/', $section, $datingTipsMatches)) {
                        $tipTitle = strip_tags($datingTipsMatches[1]);
                        $tipContent = strip_tags($datingTipsMatches[2]);
                        $result[$title]['爱之初体验']['约会锦囊'] = [
                            $tipTitle => [$tipContent]
                        ];
                    }
                    // 解析捕心行动
                    if (preg_match('/<span style="color: #ff5d7d; font-size: .46rem;">捕心行动<\/span><\/p>(.*?)<div class="xl-b9" style="margin: 0 0 20px 0;">/', $section, $actionMatches)) {
                        $result[$title]['捕心行动'] = [
                            '描述' => array_filter(array_map('strip_tags', explode('<p>', trim($actionMatches[1], '<p>'))), function($value) {
                                return !empty(trim($value));
                            })
                        ];
                    }
                    if (preg_match('/<dt><i class="fa fa-lightbulb-o"><\/i>(.*?)<\/dt><dd><p>(.*?)<\/p><\/dd>/', $section, $datingTipsMatches)) {
                        $tipTitle = strip_tags($datingTipsMatches[1]);
                        $tipContent = strip_tags($datingTipsMatches[2]);
                        $result[$title]['捕心行动']['约会锦囊'] = [
                            $tipTitle => [$tipContent]
                        ];
                    }
                    // 解析拥有完美的性爱
                    if (preg_match('/<span style="color: #ff5d7d; font-size: .46rem;">拥有完美的性爱<\/span><\/p><p>(.*?)<\/p>/', $section, $sexMatches)) {
                        $result[$title]['拥有完美的性爱'] = [strip_tags($sexMatches[1])];
                    }
                    // 解析让爱天长地久
                    if (preg_match('/<span style="color: #ff5d7d; font-size: .46rem;">让爱天长地久<\/span><\/p>(.*?)<\/div>/', $section, $longLoveMatches)) {
                        $result[$title]['让爱天长地久'] = array_filter(array_map('strip_tags', explode('<p>', trim($longLoveMatches[1], '<p>'))), function($value) {
                            return !empty(trim($value));
                        });
                    }
                    break;
            }
        }
    }

    return $result;
    }
    

    /**
     * mbti专业爱情测试
     * 
     * @return
     */
    private function loadMBTI2Report($paperOrderResult, $version)
    { 
    	
    	$reportMbtiLoveDao = \dao\ReportMbtiLove::singleton();
    	$total_result_explain = $paperOrderResult['mbti_pl']['pl_list']['total_result_explain'];
    	
    	$type = $paperOrderResult['mbti_pl']['pl_list']['extend']['score'];
    
    if ($type != 'ISFP') {
//     	echo $type . '_' . $version . ".php" . "\n";
    	 
//     	print_r($total_result_explain);
    	
//     	echo  "\n";

    	return ;
    }
    
    
    
   
print_r($total_result_explain);  exit;  	

    	//$a = self::formatMBTILoveTypeData($total_result_explain);
    	//print_r($a);exit;
    	// 使用正则表达式匹配<p>标签中的内容
    	preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $total_result_explain, $matches);
    	$reportMbtiLoveEtt = $reportMbtiLoveDao->getNewEntity();
    	$reportMbtiLoveEtt->id = $paperOrderResult['mbti_pl']['pl_list']['id'];
    	$reportMbtiLoveEtt->name = $paperOrderResult['mbti_pl']['pl_list']['weidu_name'];
    	$reportMbtiLoveEtt->type = $paperOrderResult['mbti_pl']['pl_list']['extend']['score'];
    	$reportMbtiLoveEtt->version = $version;
    	// 图片
    	if (preg_match('/src="([^"]+)"/', $matches['1']['0'], $subMatches)) {
//     		$reportMbtiLoveEtt->mainImg = $subMatches['1'];
    	}
    	$img = '';
    	$fascinationIndex = 0;
    	$complementaryIndex = 0;
    	$fascinationArr = array();
    	$meetArr = array();
    	$knowArr = array();
    	$loveArr = array();
    	$cherishArr = array();
    	$togetherArr = array();
    	$matchingArr = array();

    	
    	print_r($matches['1']);
    	foreach ($matches['1'] as $key => $row) {
	    	if (preg_match('/<b>让你着迷的特质：(.*?)<\/b>/s', $row, $subMatches)) {
	    		$reportMbtiLoveEtt->fascination = $subMatches['1'];
	    		$fascinationIndex = $key;
	    	}
	    	if (preg_match('/<b>和你互补的特质：(.*?)<\/b>/s', $row, $subMatches)) {
	    		$complementaryIndex = $key;
	    		$reportMbtiLoveEtt->complementary = $subMatches['1'];
	    		if (preg_match('/src="([^"]+)"/s', $matches['1'][$key - 1], $subMatches)) {
	    			$img = $subMatches['1'];
	    		}
	    		for ($i = $fascinationIndex + 1; $i < $key - 1; $i++) {
	    			$fascinationArr[] = $matches['1'][$i];
	    		}
	    		// 互补的气质
	    		$fascinationArr[] = $matches['1'][$key + 1];
	    	}
	    	

	    	if (!empty($complementaryIndex) && $key > $complementaryIndex) {
	    		if (preg_match('/src="([^"]+)"/s', $row, $subMatches)) {
	    			$img = $subMatches['1'];
	    			if (empty($meetArr['img'])) {
	    				$meetArr['img'] = $img;
	    				$meetArr['index'] = $key;
	    			} elseif (empty($knowArr['img'])) {
	    				$knowArr['img'] = $img;
	    				$knowArr['index'] = $key;
	    				for ($i = $meetArr['index'] + 1; $i < $key; $i++) {
	    					$meetArr['desc'][] = $matches['1'][$i];
	    				}
	    			} elseif (empty($loveArr['img'])) {
	    				$loveArr['img'] = $img;
	    				$loveArr['index'] = $key;
	    				for ($i = $knowArr['index'] + 1; $i < $key; $i++) {
	    					$knowArr['desc'][] = $matches['1'][$i];
	    				}
	    			} elseif (empty($cherishArr['img'])) {
	    				$cherishArr['img'] = $img;
	    				$cherishArr['index'] = $key;
	    				for ($i = $loveArr['index'] + 1; $i < $key; $i++) {
	    					$loveArr['desc'][] = $matches['1'][$i];
	    				}
	    			} elseif (empty($togetherArr['img'])) {
	    				$togetherArr['img'] = $img;
	    				$togetherArr['index'] = $key;
	    				for ($i = $cherishArr['index'] + 1; $i < $key; $i++) {
	    					$cherishArr['desc'][] = $matches['1'][$i];
	    				}
	    			}
	    		}
	    		if ($row == '以下人格类型者作为伴侣与你的契合度最高') {
	    			for ($i = $togetherArr['index'] + 1; $i < $key; $i++) {
	    				$togetherArr['desc'][] = $matches['1'][$i];
	    			}
	    		}
	    	}
    	}

    	$fascination = empty($reportMbtiLoveEtt->fascination) ? array() : explode('、', $reportMbtiLoveEtt->fascination);
    	$fascinationList = array();
    	foreach ($fascination as $key => $tag) {
    		$fascinationList[$tag] = str_replace("<b>{$tag}：</b>", '', $matches['1'][$fascinationIndex + $key + 1]);
    	}
    	$complementary = empty($reportMbtiLoveEtt->complementary) ? array() : explode('、', $reportMbtiLoveEtt->complementary);
    	foreach ($complementary as $key => $tag) {
    		$fascinationList[$tag] = str_replace("<b>{$tag}：</b>", '', $matches['1'][$complementaryIndex + $key + 1]);
    	}
    	$reportMbtiLoveTemperamentDao = \dao\ReportMbtiLoveTemperament::singleton();
    	$now = $this->frame->now;
    	foreach ($fascinationList as $name => $desc) {
    		$reportMbtiLoveTemperamentEtt = $reportMbtiLoveTemperamentDao->readByPrimary($name);
    		if (empty($reportMbtiLoveTemperamentEtt)) {
    			$reportMbtiLoveTemperamentEtt = $reportMbtiLoveTemperamentDao->getNewEntity();
    			$reportMbtiLoveTemperamentEtt->name = $name;
    			$reportMbtiLoveTemperamentEtt->desc = $desc;
    			$reportMbtiLoveTemperamentEtt->createTime = $now;
    			$reportMbtiLoveTemperamentDao->create($reportMbtiLoveTemperamentEtt);
    		}
    	}
    	if (!empty($meetArr)) {
    		
    		$descArr = array();
    		foreach ($meetArr['desc'] as $v) {
    			$descArr[] = '<p>' . $v . '</p>';
    		}
    		$reportMbtiLoveEtt->meetDesc = implode('', $descArr);
    	}
    	if (!empty($knowArr)) {
    	
    		$descArr = array();
    		foreach ($knowArr['desc'] as $v) {
    			$descArr[] = '<p>' . $v . '</p>';
    		}
    		$reportMbtiLoveEtt->knowDesc = implode('', $descArr);
    	}
    	if (!empty($loveArr)) {
    		
    		$descArr = array();
    		foreach ($loveArr['desc'] as $v) {
    			$descArr[] = '<p>' . $v . '</p>';
    		}
    		$reportMbtiLoveEtt->loveDesc = implode('', $descArr);
    	}
    	if (!empty($cherishArr)) {
    		
    		$descArr = array();
    		foreach ($cherishArr['desc'] as $v) {
    			$descArr[] = '<p>' . $v . '</p>';
    		}
    		$reportMbtiLoveEtt->cherishDesc = implode('', $descArr);
    	}
    	if (!empty($togetherArr)) {
 
    		$descArr = array();
    		foreach ($togetherArr['desc'] as $v) {
    			$descArr[] = '<p>' . $v . '</p>';
    		}
    		$reportMbtiLoveEtt->togetherDesc = implode('', $descArr);
    	}
    	preg_match('/<div class="xl-b15" style="margin: .5rem 0;">(.*?)<\/div>/i', $total_result_explain, $matches);
    	if (!empty($matches)) {
    		preg_match_all('/<span[^>]*>(.*?)<\/span>/i', $matches['1'], $submatches);
    		$reportMbtiLoveEtt->matching = implode(',', $submatches['1']);

    	}
    	$matching = empty($reportMbtiLoveEtt->matching) ? array() : explode(',', $reportMbtiLoveEtt->matching);
    	// 匹配原因
    	preg_match_all('/<div class="pipei-yuanyin">(.*?)<\/span><\/strong><\/p>/s', $total_result_explain, $matches);
    	$matchingReasonArr = array();
    	if (!empty($matches)) {
    		foreach ($matches['1'] as $key => $row) {
    			// <h5><i class="fa fa-venus-mars"></i>匹配原因</h5><p>她可以将你带出自我的保护壳，同时用她的温暖来激发你,所以你们的结合是互补又是相互吸引的。</p></div><p style="text-align: center;"><strong><span style="font-size: .6rem; color: #ff5d7d;">教导者
    			preg_match('/<p>(.*?)<\/p>/s', $row, $submatches);
    			$matchingReasonArr[$matching[$key]] = $submatches['1'];
    		}
    	}

    	$now = $this->frame->now;
    	$reportMbtiLoveEtt->updateTime = $now;
    	$reportMbtiLoveEtt->createTime = $now;
    	
    	

    	print_r($reportMbtiLoveEtt);exit;
    	$oldReportMbtiLoveEtt = $reportMbtiLoveDao->readByPrimary($reportMbtiLoveEtt->id);
    	if (empty($oldReportMbtiLoveEtt)) {
    		$reportMbtiLoveDao->create($reportMbtiLoveEtt);
    	}

    	return ;
    	// 文章
    	$extend_read = empty($paperOrderResult['extend_read']) ? array() : $paperOrderResult['extend_read'];	
    	$content = $extend_read['setting']['content'];
    	preg_match_all('/\/article\?id=(\d+)/', $content, $matches);
    	$articleIds = array();
    	if (!empty($matches)) {
    		$articleIds = array_unique($matches['1']);
    	}
    	$reportMbtiLoveTypeDao = \dao\ReportMbtiLoveType::singleton();
    	$reportMbtiLoveTypeEttList = array();
    	$oldReportMbtiLoveTypeEttList = $reportMbtiLoveTypeDao->readListByPrimary(array_keys($reportMbtiLoveTypeEttList));
    	$oldReportMbtiLoveTypeEttList = $reportMbtiLoveTypeDao->refactorListByKey($oldReportMbtiLoveTypeEttList);
    	// 加载所有的文章
    	foreach ($articleIds as $articleId) {
    		if (!empty($oldReportMbtiLoveTypeEttList[$articleId])) {
    			$oldReportMbtiLoveTypeEtt = $oldReportMbtiLoveTypeEttList[$articleId];
	    		if (empty($oldReportMbtiLoveTypeEtt->matchingReason) 
	    			&& $reportMbtiLoveEtt->version != $oldReportMbtiLoveTypeEtt->version 
	    			&& !empty($matchingReasonArr[$oldReportMbtiLoveTypeEtt->name])) 
	    		{
	    			$oldReportMbtiLoveTypeEtt->set('matchingReason', $matchingReasonArr[$oldReportMbtiLoveTypeEtt->name]);
	    			$reportMbtiLoveTypeDao->update($oldReportMbtiLoveTypeEtt);
	    		}
    			continue;
    		}
    		$articleUrl = "https://adapi.monday1.top/v1/article/detail?id=" . $articleId;
    		$articleResponse = httpGetContents($articleUrl, null, 50);
    		$articleResponse = json_decode($articleResponse, true);
    		$articleData = empty($articleResponse['data']) ? array() : $articleResponse['data'];
    		$reportMbtiLoveTypeEtt = $reportMbtiLoveTypeDao->getNewEntity();	
    		$reportMbtiLoveTypeEtt->id = $articleData['id'];
    		$reportMbtiLoveTypeEtt->version = $reportMbtiLoveEtt->version == 1 ? 2 : 1;
    		if (strpos($articleData['title'], '（男）')) { //教导者（女）
    			$reportMbtiLoveTypeEtt->version = 1;
    			$reportMbtiLoveTypeEtt->name = str_replace('（男）', '', $articleData['title']);
    		}
    		if (strpos($articleData['title'], '（女）')) {
    			$reportMbtiLoveTypeEtt->version = 2;
    			$reportMbtiLoveTypeEtt->name = str_replace('（女）', '', $articleData['title']);
    		}
    		if ($reportMbtiLoveEtt->version != $reportMbtiLoveTypeEtt->version
    			&& !empty($matchingReasonArr[$reportMbtiLoveTypeEtt->name])) {
    			$reportMbtiLoveTypeEtt->matchingReason = $matchingReasonArr[$reportMbtiLoveTypeEtt->name];
    		}
    		$articleContent = $articleData['content'];
    		// 使用正则表达式匹配<p>标签中的内容
    		preg_match_all('/<ul class=" list-paddingleft-2">(.*?)<\/ul>/s', $articleContent, $matches);
    		if (!empty($matches)) {
    			$reportMbtiLoveTypeEtt->peculiarity = $matches['1']['0']; // TA的个性特点
    			$reportMbtiLoveTypeEtt->meetWithPlace = $matches['1']['1']; // TA的个性特点
    		}
    		// <dt><i class="fa fa-lightbulb-o"></i>恋爱锦囊：遵守你们的约定。</dt>
    		preg_match_all('/<dt><i class="fa fa-lightbulb-o"><\/i>(.*?)锦囊：(.*?)<\/dt><dd>(.*?)<\/dd>/s', $articleContent, $matches);
    		if (!empty($matches)) {
    			$reportMbtiLoveTypeEtt->firstLoveTitle = $matches['2']['0']; // 恋爱锦囊
    			if (isset($matches['2']['1'])) {
    				$reportMbtiLoveTypeEtt->actionTitle = $matches['2']['1']; // 捕心行动-锦囊
    			}
    			$reportMbtiLoveTypeEtt->firstLoveContent = $matches['3']['0']; // 恋爱锦囊
    			if (isset($matches['3']['1'])) {
    				$reportMbtiLoveTypeEtt->actionContent = $matches['3']['1']; // 捕心行动-锦囊
    			}
    		}

    		// 使用正则表达式匹配<p>标签中的内容
    		preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $articleContent, $matches);
    		if (!empty($matches['1'])) {
    			// 标签
    			preg_match('/<span [^>]*>(.*?)<\/span>/s', $matches['1']['1'], $subMatches);
    			$reportMbtiLoveTypeEtt->tags = $subMatches['1'];
    			// 主图
    			if (preg_match('/src="([^"]+)"/', $matches['1']['2'], $subMatches)) {
    				$reportMbtiLoveTypeEtt->mainImg = $subMatches['1'];
    			}
    			$reportMbtiLoveTypeEtt->energyDirection = $matches['1']['4']; // 能量方向
    			$reportMbtiLoveTypeEtt->experienceTend = $matches['1']['6']; // 体验倾向
    			$reportMbtiLoveTypeEtt->determiningTend = $matches['1']['8']; // 决定倾向
    			$reportMbtiLoveTypeEtt->organizationalTend = $matches['1']['10']; // 组织倾向
    			$reportMbtiLoveTypeEtt->firstLoveDesc = $matches['1']['15']; // 爱之初体验
    			$reportMbtiLoveTypeEtt->actionDesc = $matches['1']['18']; // 捕心行动
    			
    			$tmpArr = array_slice($matches['1'], 20);
    			$perfectSexIndex = 0;
    			$longerIndex = 0;
    			foreach ($tmpArr as $key => $row) {
    				if (strpos($row, '">完美的性爱</span>')) {
    					$perfectSexIndex = $key;
    				}
    				if (strpos($row, '">让爱天长地久</span>')) {
    					$longerIndex = $key;
    				}
    			}
    			$perfectSexArr = array();
    			$longerArr = array();
    			foreach ($tmpArr as $key => $row) {
    				if ($key > $perfectSexIndex && $key < $longerIndex) {
    					$perfectSexArr[] = $row;
    				}
    				if ($key > $longerIndex) {
    					$longerArr[] = $row;
    				}
    			}
    			$reportMbtiLoveTypeEtt->perfectSex = implode('<br>', $perfectSexArr); // 拥有完美的性爱
    			$reportMbtiLoveTypeEtt->longer = implode('<br>', $longerArr); // 让爱天长地久
    		}
    		$reportMbtiLoveTypeEttList[$reportMbtiLoveTypeEtt->id] = $reportMbtiLoveTypeEtt;
    	}
    	if (!empty($reportMbtiLoveTypeEttList)) {
    		$oldReportMbtiLoveTypeEttList = $reportMbtiLoveTypeDao->readListByPrimary(array_keys($reportMbtiLoveTypeEttList));
    		$oldReportMbtiLoveTypeEttList = $reportMbtiLoveTypeDao->refactorListByKey($oldReportMbtiLoveTypeEttList);
    		foreach ($reportMbtiLoveTypeEttList as $reportMbtiLoveTypeEtt) {
    			if (empty($oldReportMbtiLoveTypeEttList[$reportMbtiLoveTypeEtt->id])) {
    				$reportMbtiLoveTypeDao->create($reportMbtiLoveTypeEtt);
    			}
    		}
    	}
    	return true;
    }
    
    /**
     * 职业锚
     * 
     makeClass('reportCareerAnchor', 'd');
     createEntity('report_careerAnchor', 'reportCareerAnchor');
     * @return
     */
    private function loadCareerAnchor($paperOrderResult)
    {
    	$reportCareerAnchorDao = \dao\ReportCareerAnchor::singleton();
    	$now = $this->frame->now;
    	$jifenPailieList = empty($paperOrderResult['jifen_pl']['jifenPailieList']) ? array() : $paperOrderResult['jifen_pl']['jifenPailieList'];
    	if (is_iteratable($jifenPailieList)) foreach ($jifenPailieList as $row) {
    		if ($row['weidu_name'] == '管理型（GM)') {
    			$row['weidu_name'] = '管理型（GM）';
    		}
    		// 管理型（GM)
    		preg_match('/(.*?)（(.*?)）/s', $row['weidu_name'], $matches);

    		$reportCareerAnchorEtt = $reportCareerAnchorDao->getNewEntity();
    		$reportCareerAnchorEtt->type = $matches['2'];
    		$reportCareerAnchorEtt->name = $row['weidu_name'];
    		$reportCareerAnchorEtt->icon = $row['weidu_icon'];
    		$reportCareerAnchorEtt->iconColor = $row['weidu_icon_color'];
    		$reportCareerAnchorEtt->desc = $row['jianjie'];
    		$total_result_remark = $row['total_result_remark'];
    		// 使用正则表达式匹配<p>标签中的内容
    		preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $total_result_remark, $matches);
    		preg_match('/src="([^"]+)"/s', $matches['1']['0'], $submatches);
    		$reportCareerAnchorEtt->mainImg = $submatches['1'];
    		//preg_match('/<span style="color: #f6727e;"><strong>(.*?)<\/strong><\/span>(.*?)/s', $matches['1']['1'], $submatches);
    		$featureDesc = preg_replace('/<span[^>]*>(.*?)<\/span>的人/', '', $matches['1']['1']);
    		$featureDesc = str_replace('<span style="color: #f6727e;"><strong>安全型职业锚又称稳定型职业锚</strong></span>，安全/稳定型的人', '', $featureDesc);
			$featureDesc = str_replace('<strong><span style="color: #f6727e;">技术型（也叫职能型）</span></strong>的人', '', $featureDesc);
    		$reportCareerAnchorEtt->featureDesc = $featureDesc;
    		preg_match_all('/<p><i class="fa vaaii fa-angle-double-right "><\/i>(.*?)<\/p>/s', $total_result_remark, $submatches);
    		$reportCareerAnchorEtt->featureContent = implode('<br>', $submatches['1']);
    		$xiangxi = $row['xiangxi'];
    		preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $xiangxi, $matches);
    		$workKey = 0;
    		$allowanceKey = 0;
    		$promotionKey = 0;
    		$approvalKey = 0;
    		if (!empty($matches['1'])) foreach ($matches['1'] as $key => $val) {
    			if ($val == '<span style="font-size: .46rem; color: #f6727e;">工作类型</span>') {
    				$workKey = $key;
    			} elseif ($val == '<span style="color: #f6727e; font-size: .46rem;">工作类型</span>') {
    				$workKey = $key;
    			} elseif ($val == '<span style="font-size: .46rem; color: #f6727e;">薪酬补贴</span>') {
    				$allowanceKey = $key;
    			} elseif ($val == '<span style="font-size: .46rem; color: #f6727e;">工作晋升</span>') {
    				$promotionKey = $key;
    			} elseif ($val == '<span style="font-size: .46rem; color: #f6727e;">认可方式</span>') {
    				$approvalKey = $key;
    			}
    		}
    		$typeDescArr = array_slice($matches['1'], 0, $workKey);
    		$workArr = array_slice($matches['1'], $workKey + 1, $allowanceKey - $workKey - 1);
    		$allowanceArr = array_slice($matches['1'], $allowanceKey + 1, $promotionKey - $allowanceKey - 1);
    		$promotionArr = array_slice($matches['1'], $promotionKey + 1, $approvalKey - $promotionKey - 1);
    		$approvalArr = array_slice($matches['1'], $approvalKey + 1);
    		$typeDescList = array();
    		foreach ($typeDescArr as $key => $val) {
    			$val = preg_replace('/<span[^>]*>(.*?)<\/span>/', '', $val);
    			$val = str_replace('<i class="fa vaaii fa-hand-o-right"></i>', '', $val);
    			if (empty($val)) {
    				continue;
    			}
    			$typeDescList[$key] = $val;
    		}

    		$workList = array();
    		foreach ($workArr as $key => $val) {
    			$val = preg_replace('/<span[^>]*>(.*?)<\/span>/', '', $val);
    			$val = str_replace('<i class="fa vaaii fa-hand-o-right"></i>', '', $val);
    			if (empty($val)) {
    				continue;
    			}
    			$workList[$key] = $val;
    		}
    	
    		$allowanceList = array();
    		foreach ($allowanceArr as $key => $val) {
    			$val = preg_replace('/<span[^>]*>(.*?)<\/span>/', '', $val);
    			$val = str_replace('<i class="fa vaaii fa-hand-o-right"></i>', '', $val);
    			if (empty($val)) {
    				continue;
    			}
    			$allowanceList[$key] = $val;
    		}
    		$promotionList = array();
    		foreach ($promotionArr as $key => $val) {
    			$val = preg_replace('/<span[^>]*>(.*?)<\/span>/', '', $val);
    			$val = str_replace('<i class="fa vaaii fa-hand-o-right"></i>', '', $val);
    			if (empty($val)) {
    				continue;
    			}
    			$promotionList[$key] = $val;
    		}

    		$approvalList = array();
    		foreach ($approvalArr as $key => $val) {
    			$approvalList[$key] = preg_replace('/<i[^>]*>(.*?)<\/i>/', '', $val);
    		} 
	
    		if ($row['weidu_name'] != '挑战型（CH）') {
    			$approvalList = array(
    				'如何激励与发展挑战型职业锚的人是一个非常复杂的管理问题，他们有强烈的自我发展动机，对带给他们挑战机会的雇主非常忠诚，很多时候，他们是不太需要激励和认可的一类员工。尽管如此，企业仍然需要认可他们的冲劲和解决困难的能力，给予更高更有难度的工作和一定的物质奖励。',
    				'始终不肯放弃的是去解决看上去无法解决的问题、战胜强硬的对手或克服面临的困难。对你而言，职业的意义在于允许你战胜不可能的事情。'
    			);
    		}
    		$reportCareerAnchorEtt->typeDesc = implode('<br>', $typeDescList);
    		$reportCareerAnchorEtt->work = implode('<br>', $workList);
    		$reportCareerAnchorEtt->allowance = implode('<br>', $allowanceList);
    		$reportCareerAnchorEtt->promotion = implode('<br>', $promotionList);
    		$reportCareerAnchorEtt->approval = implode('<br>', $approvalList);
    		$haveEtt = $reportCareerAnchorDao->readByPrimary($reportCareerAnchorEtt->type);
    		if (empty($haveEtt)) {
    			$reportCareerAnchorEtt->createTime = $now;
    			$reportCareerAnchorDao->create($reportCareerAnchorEtt);
    		}
    	}
    	return true;
    }
    
    /**
     * 加载MBTI报告
     * 
     * @return
     */
    private function loadMBTIReport($mbti_pl_data)
    {
		// 你的MBTI恋爱类型
    	$mbti_pl = empty($mbti_pl_data['mbti_pl']) ? array() : $mbti_pl_data['mbti_pl'];
    	if (empty($mbti_pl)) {
    		return false;
    	}
    	$now = $this->frame->now;
    	$total_result_explain = $mbti_pl['total_result_explain']; // 分析数据
    	$juti_result_explain = $mbti_pl['juti_result_explain']; // 推荐
    	$extend = $mbti_pl['extend']; // 拓展
    	$weidu_name = $mbti_pl['weidu_name']; // 名称
    	$type = $extend['score']; // 类型
    	$reportMbtiDao = \dao\ReportMbti::singleton();
    	$reportMbtiEtt = $reportMbtiDao->getNewEntity();
    	$reportMbtiElementDao = \dao\ReportMbtiElement::singleton();
    	$reportMbtiEtt->id = $type;
    	$reportMbtiEtt->updateTime = $now;
    	$reportMbtiEtt->createTime = $now;
    	$reportMbtiEtt->name = $mbti_pl['weidu_name'];
    	$reportMbtiEtt->temperament = $mbti_pl_data['temperament']['1']; // 气质类型
    
    	// 最擅长检查性管理的类型
    	preg_match('/<div class="xinmbti-zong"><h6>最擅长(.*?)的类型<\/h6>(.*?)<\/div>/s', $total_result_explain, $matches);
    	if (!empty($matches)) {
    		$reportMbtiEtt->adeptType = $matches['1'];
    		$reportMbtiEtt->adeptDesc = $matches['2'];
    	} else {
    		echo "ERROR: 最擅长xxx的类型\n";
    		return false;
    	}
    	preg_match_all('/<div class="xinmbti-text">.*?<div>(.*?)%<\/div><\/div>/s', $total_result_explain, $matches);
    	if (!empty($matches)) {
    		$reportMbtiEtt->totalRate = str_replace('占总人口</div><div>', '', $matches['1']['0']);
    		$reportMbtiEtt->manRate = str_replace('占男性</div><div>', '', $matches['1']['1']);
    		$reportMbtiEtt->womanRate = str_replace('占女性</div><div>', '', $matches['1']['2']);
    	} else {
    		echo "ERROR: 占总人口\n";
    		return false;
    	}
    	
    	preg_match_all('/<p><strong>(.*?)<\/strong><span>(.*?)<\/span>(.*?)<\/p>/s', $total_result_explain, $matches);    	
    	if (!empty($matches)) {
    	    $type = implode('', $matches['1']);
    	    if ($type != $reportMbtiEtt->id) {
    	        echo "ERROR: 类型\n";
    	        return false;
    	    }
    	    $reportMbtiElementDao = \dao\ReportMbtiElement::singleton();
    
    	    $now = $this->frame->now;
    	    foreach ($matches['1'] as $key => $value) {
    	        $reportMbtiElementEtt = $reportMbtiElementDao->readByPrimary($value);
    	        if (empty($reportMbtiElementEtt)) {
    	            $reportMbtiElementEtt = $reportMbtiElementDao->getNewEntity();
    	            $reportMbtiElementEtt->id = $value;
    	            $reportMbtiElementEtt->name = $matches['2'][$key];
    	            $reportMbtiElementEtt->desc = $matches['3'][$key];
    	            $reportMbtiElementEtt->createTime = $now;
    	            $reportMbtiElementDao->create($reportMbtiElementEtt);
    	        }
    	    }
    	}

    	// 解析名人
    	preg_match('/<div class="xinmbti-mingren">(.*?)<\/div>/s', $total_result_explain, $matches);
    	if (!empty($matches)) {
    		$str = $matches['1'];
    		$str = str_replace('<!--这里需要修改下面的人格字母-->', '', $matches['1']);
    		preg_match_all('/<p>(.*?)<\/p>/s', $str, $subMatches);
    		if (empty($subMatches)) {
    			echo "ERROR: 名人\n";
    			return false;
    		}
    		$url = str_replace('<p><img src="', '', $subMatches['0']['0']);
    		$reportMbtiEtt->famousPeopleImg = str_replace('"/></p>', '', $url);
    		preg_match_all('/<span>(.*?)<\/span>/s', $subMatches['0']['1'], $subMatches3);
    		$reportMbtiEtt->famousPeople = implode(',', $subMatches3['1']);
    	} else {
    		echo "ERROR: 名人\n";
    		return false;
    	}
    	
    	// 动机和价值观
    	preg_match('/<div class="xinmbti-jzg">(.*?)<\/div>/s', $juti_result_explain, $matches);
    	if (!empty($matches)) {
    		$reportMbtiEtt->valueDesc = $matches['1'];
    	} else {
    		echo "ERROR: 动机和价值观\n";
    		return false;
    	}
    	// 优势 劣势
    	preg_match_all('/<div class="xinmbti-xg-wz">(.*?)<\/div>/s', $juti_result_explain, $matches);
   		$characterArr = array();
    	if (!empty($matches)) {
    		foreach ($matches['1'] as $row) {
    			preg_match_all('/<h6>(.*?)<\/h6><p>(.*?)<\/p>/s', $row, $_subMatches);
    			if (!empty($_subMatches)) {
    				foreach ($_subMatches['1'] as $_k => $_v) {
    					$characterArr[$_v] = $_subMatches['2'][$_k];
    				}
    			}
    			preg_match_all('/<h5>(.*?)<\/h5><p>(.*?)<\/p>/s', $row, $_subMatches);
    			if (!empty($_subMatches)) {
    				foreach ($_subMatches['1'] as $_k => $_v) {
    					$characterArr[$_v] = $_subMatches['2'][$_k];
    				}
    			}		
    		}
    	} else {
    		echo "ERROR: 优势 劣势\n";
    		return false;
    	}
    	$tagArr = array_keys($characterArr);
    	$reportMbtiEtt->characterAdvantage = implode(',', array_slice($tagArr, 0, 4));
    	$reportMbtiEtt->characterDisadvantage = implode(',', array_slice($tagArr, 4, 4));
    	$reportMbtiCharacterDao = \dao\ReportMbtiCharacter::singleton();
    	
    	$now = $this->frame->now;
    	foreach ($characterArr as $key => $value) {
    		$reportMbtiCharacterEtt = $reportMbtiCharacterDao->readByPrimary($key);
    		if (empty($reportMbtiCharacterEtt)) {
    			$reportMbtiCharacterEtt = $reportMbtiCharacterDao->getNewEntity();
    			$reportMbtiCharacterEtt->name = $key;
    			$reportMbtiCharacterEtt->desc = $value;
    			$reportMbtiCharacterEtt->createTime = $now;
    			$reportMbtiCharacterDao->create($reportMbtiCharacterEtt);
    		}
    	}

    	// 成长建议
    	$suggestArr = array();
    	preg_match_all('/<div class="xinmbti-jy">(.*?)<\/div>/s', $juti_result_explain, $matches);
    	if (!empty($matches)) {
    		foreach ($matches['1'] as $row) {
    			preg_match_all('/<h6 class="xinmbti-jy-color1">(.*?)<\/h6><p>(.*?)<\/p>/s', $row, $_subMatches);
    			if (!empty($_subMatches)) {
    				foreach ($_subMatches['1'] as $_k => $_v) {
    					$suggestArr[$_v] = $_subMatches['2'][$_k];
    				}
    	
    			}
    			preg_match_all('/<h6 class="xinmbti-jy-color2">(.*?)<\/h6><p>(.*?)<\/p>/s', $row, $_subMatches);
    			if (!empty($_subMatches)) {
    				foreach ($_subMatches['1'] as $_k => $_v) {
    					$suggestArr[$_v] = $_subMatches['2'][$_k];
    				}
    			} 
    		}
    	} else {
    		echo "ERROR: 成长建议\n";
    		return false;
    	}
    	$reportMbtiEtt->suggest = implode(',', array_keys($suggestArr));

    	$reportMbtiSuggestDao = \dao\ReportMbtiSuggest::singleton();
    	foreach ($suggestArr as $key => $value) {
    		$reportMbtiSuggestEtt = $reportMbtiSuggestDao->readByPrimary($key);
    		if (empty($reportMbtiSuggestEtt)) {
    			$reportMbtiSuggestEtt = $reportMbtiSuggestDao->getNewEntity();
    			$reportMbtiSuggestEtt->name = $key;
    			$reportMbtiSuggestEtt->desc = $value;
    			$reportMbtiSuggestEtt->createTime = $now;
    			$reportMbtiSuggestDao->create($reportMbtiSuggestEtt);
    		}
    	}

    	$errorMap = array(
    		'内向思维(Ti)' => '内向思考(Ti)',
    		'外向直觉 (Ne)' => '外向直觉(Ne)',
    		'内向情感(Fi)' => '内向感觉(Fi)',
    	);
    	// 荣格八维解读性格优劣势
    	$rougeArr = array();
    	preg_match_all('/<div class="xinmbti-rgbw">(.*?)<\/div>/s', $juti_result_explain, $matches);
    	if (!empty($matches)) {
    		foreach ($matches['1'] as $row) {
    			preg_match_all('/<h5>(.*?)<\/h5><h6>(.*?)<\/h6>(.*?)/i', $row, $_subMatches);
    			if (!empty($_subMatches)) {
    				foreach ($_subMatches['1'] as $_k => $_v) {
    					$name =  isset($errorMap[$_v]) ? $errorMap[$_v] : $_v;
    					$title =  $_subMatches['2'][$_k];
    					$desc = substr($row, strpos($row, '</h6>') + strlen('</h6>'));
    					$rougeArr[$name] = array(
    						'title' => $title,
    						'desc' => $desc,
    					);
    				} 
    			}
    		}
    	} else {
    		echo "ERROR: 荣格八维解读性格优劣势\n";
    		return false;
    	}

    	$reportMbtiEtt->rouge = implode(',', array_keys($rougeArr));
    	$reportMbtiRougeDao = \dao\ReportMbtiRouge::singleton();
    	foreach ($rougeArr as $key => $value) {
    		$reportMbtiRougeEtt = $reportMbtiRougeDao->readByPrimary($key);
    		if (empty($reportMbtiRougeEtt)) {
    			$reportMbtiRougeEtt = $reportMbtiRougeDao->getNewEntity();
    			$reportMbtiRougeEtt->name = $key;
    			$reportMbtiRougeEtt->title = $value['title'];
    			$reportMbtiRougeEtt->desc = $value['desc'];
    			$reportMbtiRougeEtt->createTime = $now;
    			$reportMbtiRougeDao->create($reportMbtiRougeEtt);
    		}
    	}

    	if (!empty($extend['love'])) {
    		// 恋爱状态
    		preg_match('/<div class="xinmbti-love-1">(.*?)<\/div>/s', $extend['love'], $matches);
			// 恋爱中
    		if (!empty($matches)) { 
    			$reportMbtiEtt->loving = $matches['1'];
    		}
    		$loveList = array();
    		// 恋爱前中期
    		preg_match_all('/<div class="xinmbti-love-2">(.*?)<\/div>/s', $extend['love'], $matches);
    		if (!empty($matches)) {
    			$loveSingle = $matches['1']['0'];
    			$loveSingle = str_replace('<h6>单身时期</h6><div class="xinmbti-love-2-2">', '', $loveSingle);
    			
    			$lovePremetaphase = $matches['1']['1'];
    			$lovePremetaphase = str_replace('<h6>恋爱前中期</h6><div class="xinmbti-love-2-2">', '', $lovePremetaphase);
    			$loveLate = $matches['1']['2'];
    			$loveLate = str_replace('<h6>恋爱后期</h6><div class="xinmbti-love-2-2">', '', $loveLate);

    			$reportMbtiEtt->loveSingle = $loveSingle;
    			$reportMbtiEtt->lovePremetaphase = $lovePremetaphase;
    			$reportMbtiEtt->loveLate = $loveLate;
    		}
    		// 最佳恋爱匹配类型
    		preg_match('/<div class="xinmbti-love-pipei">(.*?)<\/div>/s', $extend['love'], $matches);
    		if (!empty($matches)) {
    			preg_match('/<img src="(.*?)" alt=""\/>/s', $matches['1'], $submatches1);
    			$reportMbtiEtt->loveMatchingImg = $submatches1['1'];
    			preg_match_all('/<li>(.*?)<\/li>/s', $matches['1'], $submatches2);
    			$reportMbtiEtt->loveMatching = implode(',', $submatches2[1]);
    		} else {
	    		echo "ERROR: 最佳恋爱匹配类型\n";
	    		return false;
    		}
    	}
    	if (!empty($extend['zhichang'])) {
    		// 工作中的ISFP
    		preg_match('/<div class="xinmbti-jzg">(.*?)<strong>团队中的(.*?)<\/strong>(.*?)<strong>作为领导的(.*?)<\/strong>(.*?)<\/div>/s', $extend['zhichang'], $matches);
    		if (!empty($matches)) {
    			$reportMbtiEtt->workIng = $matches['1'];
    			$reportMbtiEtt->workTeam = $matches['3'];
    			$reportMbtiEtt->workLead = $matches['5'];
    		} else {
	    		echo "ERROR: 工作中的\n";
	    		return false;
    		}
    		
    		// 工作中的核心满足感
    		preg_match('/<div class="xinmbti-work-1">(.*?)<\/div>/s', $extend['zhichang'], $matches);
    		if (!empty($matches)) {
    			$reportMbtiEtt->wrokSatisfaction = $matches['1'];
    		} else {
	    		echo "ERROR: 工作中的核心满足感\n";
	    		return false;
    		}
    	
    		// 最佳工作环境
    		preg_match('/<div class="xinmbti-work-2">(.*?)<\/div>/s', $extend['zhichang'], $matches);
    		if (!empty($matches)) {
    			$reportMbtiEtt->workEnvironmentbest = str_replace('<h6>最佳工作环境<span>BEST</span></h6>', '', $matches['1']);
    		} else {
	    		echo "ERROR: 最佳工作环境\n";
	    		return false;
    		}

    		// 最差工作环境
    		preg_match('/<div class="xinmbti-work-3">(.*?)<\/div>/s', $extend['zhichang'], $matches);
    		if (!empty($matches)) {
    			$reportMbtiEtt->workEnvironmentWorst = str_replace('<h6>最差工作环境<span>WORST</span></h6>', '', $matches['1']);
    		} else {
	    		echo "ERROR: 最差工作环境\n";
	    		return false;
    		}
    
    		// 职业参考宝典
    		$professionArr = array();
    		preg_match_all('/<h6 class="xinmbti-left">(.*?)<\/h6>(.*?)<div>(.*?)<\/div>/s', $extend['zhichang'], $matches);

    		if (!empty($matches)) {
    			foreach ($matches['1'] as $_k => $_v) {
    				$desc = $matches['2'][$_k];
    				$desc = str_replace('<!--注意这里的span标签之间是不能空格的，删除多余的标签:<span>文字</span>整体删除-->', '', $desc);
    				$example = $matches['3'][$_k];
    				preg_match_all('/<span>(.*?)<\/span/s', $example, $subMatches);
    				$example = implode(',', $subMatches['1']);
    				$example = str_replace('/span&gt;<span>', ',', $example);
    				$professionArr[$_v] = array(
    					'desc' => $desc,
    					'example' => $example,
    				);
    			}
    		}
    		$reportMbtiEtt->careerRecommend = implode(',', array_keys($professionArr));
    		$reportMbtiProfessionDao = \dao\ReportMbtiProfession::singleton();
    		foreach ($professionArr as $key => $value) {
    			$reportMbtiProfessionEtt = $reportMbtiProfessionDao->readByPrimary($key);
    			if (empty($reportMbtiProfessionEtt)) {
    				$reportMbtiProfessionEtt = $reportMbtiProfessionDao->getNewEntity();
    				$reportMbtiProfessionEtt->role = $key;
    				$reportMbtiProfessionEtt->desc = $value['desc'];
    				$reportMbtiProfessionEtt->example = $value['example'];
    				$reportMbtiProfessionEtt->createTime = $now;
    				$reportMbtiProfessionDao->create($reportMbtiProfessionEtt);
    			}
    		}
    		
    		// 职场避雷锦囊
    		preg_match_all('/<div class="xinmbti-jzg">(.*?)<\/div>/s', $extend['zhichang'], $matches);
    		if (!empty($matches)) {
    			$reportMbtiEtt->careerEvadeDesc = $matches['1']['1'];
    		} else {
	    		echo "ERROR: 职场避雷锦囊\n";
	    		return false;
    		}
    	
    		// 建议
    		preg_match_all('/<div class="xinmbti-zhichang-bilei">(.*?)<\/div><\/div>/s', $extend['zhichang'], $matches);
    		if (!empty($matches)) {
    			for ($index = 1; $index <= 6; $index++) {
    				$titlePro = 'careerEvadeSuggestTitle' . $index;
    				$contentPro = 'careerEvadeSuggestContent' . $index;
    				$reportMbtiEtt->$titlePro = '';
    				$reportMbtiEtt->$contentPro = '';
    			}
    		}

    		if (!empty($matches)) foreach ($matches['1'] as $key => $row) {
    			preg_match('/<h6>(.*?)<\/h6>.*?/s', $row, $subMatches);	
    			if (!empty($subMatches)) {
    				$index = $key + 1;
    			
    				$titlePro = 'careerEvadeSuggestTitle' . $index;
    				$contentPro = 'careerEvadeSuggestContent' . $index;
    				$title = str_replace("<i>0{$index}</i>", '', $subMatches['1']);
    				$search = "<h6><i>0{$index}</i>" . $title . '</h6><div class="xinmbti-zhichang-bilei-1">';
    				$content = str_replace($search, '', $row);
    				$content = str_replace('</div><div class="xinmbti-zhichang-bilei-1">', '', $content);
    				
    				$reportMbtiEtt->$titlePro = $title;
    				$reportMbtiEtt->$contentPro = $content;
    			}
    		
    		} else {
	    		echo "ERROR: 职场避雷锦囊-建议\n";
	    		return false;
    		}
    	}
		return $reportMbtiEtt;
    }
    
    /**
     * 九型人格
     *
     makeClass('reportCareerAnchor', 'd');
     createEntity('report_careerAnchor', 'reportCareerAnchor');
     * @return
     */
    private function loadEnneagram($paperOrderResult)
    {
    	$reportCareerAnchorDao = \dao\ReportCareerAnchor::singleton();
    	$now = $this->frame->now;
    	$pl_list = empty($paperOrderResult['mbti_pl']['pl_list']) ? array() : $paperOrderResult['mbti_pl']['pl_list'];
 		$result = array();
    	if (is_iteratable($pl_list)) foreach ($pl_list as $row) {
    		$model = array(
    			'名称' => $row['weidu_name'], // 名称
    			'分享图片' => $row['share_image'], // 分享图片
    			'关键词' => $row['extend']['shuoming'], // 关键词
    			'图标颜色' => $row['extend']['tubiao_color'], // 图标颜色
    		);
    		
    		
    	print_r($row);exit;
    		
    		$total_result_explain = $row['total_result_explain'];
    		preg_match('/src="([^"]+)"/s', $total_result_explain, $submatches);
    		$model['主图'] = $submatches['1'];
    		
    		preg_match('/<p style="text-align: center;"><span style="color: #f6727e;">(.*?)<\/span><\/p>/s', $total_result_explain, $submatches);
    		$model['标签'] = $submatches['1'];
    		preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $total_result_explain, $submatches);
    		$model['描述'] = end($submatches['1']);
    		
    		$juti_result_explain = $row['juti_result_explain'];
    		
    		$explainArr = explode('<div class="xl-line"></div>', $juti_result_explain);
    		$other = array();
    		if (!empty($explainArr['0'])) {
    			preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $explainArr['0'], $matches);
    			$other['内容'] = $matches['1']['0'];
    			preg_match('/src="([^"]+)"/s', $matches['1']['2'], $submatches);
    			$other['代表人物图片'] = $submatches['1'];
    			$other['欲望特质'] = $matches['1']['4'];
    			$other['基本困思'] = $matches['1']['6'];
    			$other['主要特征'] = $matches['1']['8'];
    			$other['主要特质'] = $matches['1']['10'];
    			$other['生活风格'] = $matches['1']['12'];
    			$other['人际关系'] = $matches['1']['14'];
    			// 顺境 标签
    			preg_match('/<span style="font-size: .46rem; color: #f6727e;">顺境（(.*?)）<\/span>/s', $matches['1']['15'], $submatches);
    			if (!isset($submatches['1'])) {
    				preg_match('/<span style="font-size: .46rem; color: #f6727e;">顺境时（(.*?)）<\/span>/s', $matches['1']['15'], $submatches);
    			}
    			$other['顺境'] = array($submatches['1'] => $matches['1']['16']);
    			// 逆境 标签
    			preg_match('/<span style="font-size: .46rem; color: #f6727e;">逆境（(.*?)）<\/span>/s', $matches['1']['17'], $submatches);
    			if (!isset($submatches['1'])) {
    				preg_match('/<span style="font-size: .46rem; color: #f6727e;">逆境时（(.*?)）<\/span>/s', $matches['1']['17'], $submatches);
    			}
    			$other['逆境'] = array($submatches['1'] => $matches['1']['18']);
    			$other['代表颜色'] = $matches['1']['20'];
    			$other['生命课题'] = $matches['1']['22'];
    		}
    
    		$model['other'] = $other;
    		$desireAndFear = array( // 欲望与恐惧
    			'图片' => '',
    			'欲望' => array(
    				'标题' => '',
    				'内容' => array(),
    			),
    			'恐惧' => array(
    				'标题' => '',
    				'内容' => array(),
    			),
    		);
    		if (!empty($explainArr['1'])) {
    			preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $explainArr['1'], $matches);
    			preg_match('/src="([^"]+)"/s', $matches['1']['0'], $submatches);
    			$desireAndFear['图片'] = $submatches['1'];
    			preg_match('/<span [^>]*>欲望：(.*?)<\/span>/s', $matches['1']['1'], $submatches);
    			$desireAndFear['欲望']['标题'] = $submatches['1'];    		
    			foreach ($matches['1'] as $key => $val) {
    				if (preg_match('/<span style="font-size: .46rem; color: #f6727e;">恐惧：(.*?)<\/span>/s', $val, $submatches)) {
    					$desireAndFear['恐惧']['标题'] = $submatches['1'];
    					$desireAndFear['欲望']['内容'] = array_slice($matches['1'], 2, $key - 2);
    					$desireAndFear['恐惧']['内容'] = array_slice($matches['1'], $key + 1);
    				}
    			}
    		}
    		$model['欲望与恐惧'] = $desireAndFear;
    		$sinAndVirtue = array( // 原罪与美德
    			'图片' => '',
    			'描述' => '',
    			'原罪' => array(
    				'标签' => '',
    				'标题' => '',
    				'内容' => array(),
    			),
    			'美德' => array(
    				'标签' => '',
    				'标题' => '',
    				'内容' => array(),
    			),
    		);
    		if (!empty($explainArr['2'])) {
    			preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $explainArr['2'], $matches);
    			preg_match('/src="([^"]+)"/s', $matches['1']['0'], $submatches);
    			$sinAndVirtue['图片'] = $submatches['1'];
    			$sinAndVirtue['描述'] = $matches['1']['1'];
    			
    			preg_match('/<span style="font-size: .46rem; color: #f6727e;">原罪：(.*?)<\/span>/s', $matches['1']['2'], $submatches);
    			$sinAndVirtue['原罪']['标签'] = $submatches['1'];
    		
    			foreach ($matches['1'] as $key => $val) {
    				if (preg_match('/<span style="font-size: .46rem; color: #f6727e;">美德：(.*?)<\/span>/s', $val, $submatches)) {
    					$sinAndVirtue['美德']['标签'] = $submatches['1'];
    					$sinAndVirtue['原罪']['标题'] = $matches['1']['3'];
    					$sinAndVirtue['原罪']['内容'] = array_slice($matches['1'], 4, $key - 4);
    					$sinAndVirtue['美德']['标题'] = $matches['1'][$key + 1];
    					$sinAndVirtue['美德']['内容'] = array_slice($matches['1'], $key + 2, -1);
    				}
    			}
    		}
    		$model['原罪与美德'] = $sinAndVirtue;
    		// 性格成因
    		$character = array(
    			'图片' => '',
    			'内容' => '',
    		);
    		if (!empty($explainArr['3'])) {
    			preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $explainArr['3'], $matches);
    			preg_match('/src="([^"]+)"/s', $matches['1']['0'], $submatches);
    			$character['图片'] = $submatches['1'];
    			unset($matches['1']['0']);
    			$indexMap = array();
    			foreach ($matches['1'] as $key => $val) {
    				if (preg_match('/<span style="font-size: .46rem; color: #f6727e;">(\d).(.*?)<\/span>/s', $val, $submatches)) {
    					$indexMap[$submatches['1']] = array(
    						'name' => $submatches['2'],
    						'key' => $key,
    					);
    				} 
    			}
    			$contentArr = array();
    			foreach ($indexMap as $index => $_row) {
    				$key = $_row['key'];
    				if (isset($indexMap[$index + 1])) {
    					$subContentArr = array_slice($matches['1'], $key, $indexMap[$index + 1]['key'] - $key - 1);
    				} else {
    					$subContentArr = array_slice($matches['1'], $key);
    				}
    				foreach ($subContentArr as $k => $v) {
    					$subContentArr[$k] = str_replace('<i class="fa vaaii fa-angle-double-right "></i>', '', $v);
    				}
    				$contentArr[$_row['name']] = $subContentArr;
    			}
    			
    			$character['内容'] = $contentArr;
    		}
    		$model['性格成因'] = $character;
    		// 工作方面
    		$work = array(
    			'图片' => '',
    			'描述' => '',
    			'适合的环境' => '',
    			'不适合的环境' => '',
    			'适合的工作' => '',
    			'理想的合作伙伴' => '',
    			'提升指导' => array(
    				'描述' => '',
    				'建议' => array(),
    			),	
    		);
    		if (!empty($explainArr['4'])) {
    			preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $explainArr['4'], $matches);
    			preg_match('/src="([^"]+)"/s', $matches['1']['0'], $submatches);
    			$work['图片'] = $submatches['1'];
    			
    			$work['描述'] = $matches['1']['1'];
    			$work['适合的环境'] = $matches['1']['3'];
    			$work['不适合的环境'] = $matches['1']['5'];
    			$work['适合的工作'] = $matches['1']['7'];
    			
    			
    			$work['理想的合作伙伴'] = $matches['1']['9'];
    			$work['提升指导']['描述'] = $matches['1']['10'];
    			$tmp = array_slice($matches['1'], 12);
    			foreach ($tmp as $k => $v) {
    				$tmp[$k] = str_replace('<i class="fa vaaeq fa-angle-double-right"></i>', '', $v);
    			}
    			$work['提升指导']['建议'] = $tmp;     			 
    		}
    		$model['工作方面'] = $work;
    		// 感情方面
    		$emotion = array(
    			'图片' => '',
    			'描述' => '',
    		);
    		if (!empty($explainArr['5'])) {
    			preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $explainArr['5'], $matches);
    			preg_match('/src="([^"]+)"/s', $matches['1']['0'], $submatches);
    			$emotion['图片'] = $submatches['1'];
    			unset($matches['1']['0']);
    			$emotion['描述'] = array_values($matches['1']);
    		}
    		$model['感情方面'] = $emotion;
    		// 建议
    		$suggest = array(
    			'标题' => '',
    			'内容' => '',
    		);
    	
    		if (!empty($explainArr['6'])) {
    			preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $explainArr['6'], $matches);
    			preg_match('/<span style="color: #f6727e; font-size: .46rem;">(.*?)<\/span>/s', $matches['1']['0'], $submatches);
    			$suggest['标题'] = $submatches['1'];
    			$indexMap = array();
    			foreach ($matches['1'] as $key => $val) {
    				if (preg_match('/<span style="font-size: .46rem; color: #f6727e;">(\d).(.*?)<\/span>/s', $val, $submatches)) {
    					$indexMap[$submatches['1']] = array(
    						'name' => $submatches['2'],
    						'key' => $key,
    					);
    				}
    			}
    			$contentArr = array();
    			foreach ($indexMap as $index => $_row) {
    				$key = $_row['key'];
    				if (isset($indexMap[$index + 1])) {
    					$subContentArr = array_slice($matches['1'], $key + 1, $indexMap[$index + 1]['key'] - $key - 1);
    				} else {
    					$subContentArr = array_slice($matches['1'], $key + 1);
    				}
    				foreach ($subContentArr as $k => $v) {
    					$subContentArr[$k] = str_replace('<i class="fa vaaii fa-angle-double-right "></i>', '', $v);
    				}
    				$contentArr[$_row['name']] = $subContentArr;
    			}
    			$suggest['内容'] = $contentArr;
    		}
    		$model['给你的建议'] = $suggest;
    		setStaticData('九型人格', $model['名称'], $model);
    	}
    	return true;
    }

    /**
     * 同步报告制作流程
     *
     * @return
     */
    public function xz_report_process($name = '')
    {
    	$where = "1";
    	if (!empty($name)) {
    		$where = "`goods_name` like '%{$name}%'";
    	}
    	$commonDao = \dao\Common::singleton();
    	$sql = "SELECT * FROM `xz_report` WHERE {$where};";
    	$reportList = $commonDao->readDataBySql($sql);
    	$now = $this->frame->now;
    	$reportArr = array();
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperSv = \service\TestPaper::singleton();
    	$testPaperEttList = $testPaperDao->readListByWhere();
    	$testPaperEttList = array_column($testPaperEttList, null, 'name');
    	$reportProcessDao = \dao\ReportProcess::singleton();
    	// 删除已有的数据
    	$commonDao->execBySql("TRUNCATE TABLE `reportProcess`;");
    	$reportProcessEttList = array();

    	if (is_iteratable($reportList)) foreach ($reportList as $data) {
    		$goods_name = $data->goods_name;
    		$report = empty($data->report) ? array() : json_decode(base64_decode($data->report), true);
    		$pay = empty($data->pay) ? array() : json_decode(base64_decode($data->pay), true);
    		$order = empty($data->order) ? array() : json_decode(base64_decode($data->order), true);
    		$create_report = empty($pay['create_report']) ? array() : $pay['create_report'];

    		if (empty($order) || empty($create_report) || empty($testPaperEttList[$goods_name])) {
    			continue;
    		}
    		$goods_version_select = empty($order['goodsOrder']['goods_version_select']) ? 1 : $order['goodsOrder']['goods_version_select'];
    		$testPaperEtt = $testPaperEttList[$goods_name];
    		if (empty($testPaperEtt->reportProcessImg)) {
    			$testPaperEtt->set('reportProcessImg', $create_report['setting']['touming_icon']);
    			$testPaperDao->update($testPaperEtt);
    		}
    		if (!empty($reportProcessEttList[$testPaperEtt->id][$goods_version_select])) {
    			continue; 
    		}
    		
    		$index = 1;
    		foreach ($create_report['data'] as $row) {
    			foreach ($row['childList'] as $child) {
    				$reportProcessEtt = $reportProcessDao->getNewEntity();
    				$reportProcessEtt->title = $child['title'];
    				$reportProcessEtt->titleColor = $child['title_color'];
    				$reportProcessEtt->groupName = $row['title'];
    				$reportProcessEtt->executeTime = $child['exec_time'];
    				$reportProcessEtt->testPaperId = $testPaperEtt->id;
    				$reportProcessEtt->version = $goods_version_select;
    				$reportProcessEtt->index = $index++;
    				$reportProcessEtt->updateTime = $now;
    				$reportProcessEtt->createTime = $now;
    				$reportProcessDao->create($reportProcessEtt);
    				$reportProcessEttList[$testPaperEtt->id][$goods_version_select][] = $reportProcessEtt;
    			}
    		}
    	}
    	echo "报告制作流程同步完毕\n";
    	return true;
    }
   
    /**
     * 同步报告数据
     * 
     * @return
     */
    public function xz_report_mbti($name = '')
    {
		$commonDao = \dao\Common::singleton();
//         $commonDao->execBySql("TRUNCATE TABLE `report_mbti`;");
//         $commonDao->execBySql("TRUNCATE TABLE `report_mbti_character`;");
//         $commonDao->execBySql("TRUNCATE TABLE `report_mbti_profession`;");
//         $commonDao->execBySql("TRUNCATE TABLE `report_mbti_rouge`;");
//         $commonDao->execBySql("TRUNCATE TABLE `report_mbti_suggest`;");
//         $commonDao->execBySql("TRUNCATE TABLE `report_mbti_love`;");
//         $commonDao->execBySql("TRUNCATE TABLE `report_mbti_love_type`;");
//         $commonDao->execBySql("TRUNCATE TABLE `report_mbti_love_temperament`;");
        $where = "`goods_name` like '%MBTI专业爱情%'";
        $sql = "SELECT * FROM `xz_report` WHERE {$where};";
        $reportList = $commonDao->readDataBySql($sql);

        $now = $this->frame->now;
        $reportArr = array();
        $testPaperDao = \dao\TestPaper::singleton();
        $testPaperSv = \service\TestPaper::singleton();
        $testPaperEttList = $testPaperDao->readListByWhere();
        $testPaperEttList = array_column($testPaperEttList, null, 'name');
        $reportMbtiDao = \dao\ReportMbti::singleton();

        if (is_iteratable($reportList)) foreach ($reportList as $data) {
        	
        	
            $goods_name = $data->goods_name;
            $report = empty($data->report) ? array() : json_decode(base64_decode($data->report), true);
            $pay = empty($data->pay) ? array() : json_decode(base64_decode($data->pay), true);
            $order = empty($data->order) ? array() : json_decode(base64_decode($data->order), true);
            $create_report = empty($pay['create_report']) ? array() : $pay['create_report'];
            $goods_version_select = empty($order['goodsOrder']['goods_version_select']) ? 1 : $order['goodsOrder']['goods_version_select'];
            $paperOrderResult = empty($report['paperOrderResult']) ? array() : $report['paperOrderResult'];
            if ($goods_name == 'MBTI性格测试专业版') {
            	$mbti_pl = empty($report['paperOrderResult']['mbti_pl']) ? array() : $report['paperOrderResult']['mbti_pl'];
            	$reportMbtiEtt = $this->loadMBTIReport($mbti_pl);
            	if (!empty($reportMbtiEtt)) {
            		$oldReportMbtiEtt = $reportMbtiDao->readByPrimary($reportMbtiEtt->id);
            		if (empty($oldReportMbtiEtt)) {
            			$reportMbtiDao->create($reportMbtiEtt);
            		}
            	}
            } elseif ($goods_name == 'MBTI专业爱情测试') {

            	$this->loadMBTI2Report($paperOrderResult, $goods_version_select);
      
            }
            continue;
            // 盖洛普
            $duoweiduList = empty($report['paperOrderResult']['duoweidu']['duoweiduList']) ? array() : $report['paperOrderResult']['duoweidu']['duoweiduList'];
            if (!empty($duoweiduList)) { 
                $reportGallupEttList = $this->loadGallupReport($duoweiduList);
            }
            
            // 瑞文国际标准智商
            $extend_read = empty($report['paperOrderResult']['extend_read']) ? array() : $report['paperOrderResult']['extend_read'];
            $ruiwen_ord = empty($report['paperOrderResult']['duoweidu']['ruiwen_ord']) ? array() : $report['paperOrderResult']['duoweidu']['ruiwen_ord'];
            if (!empty($ruiwen_ord)) {
            	$reportGallupEttList = $this->loadRavenIQReport($report);
            }
            // 荣格古典
            
            $jifen_pl = empty($report['paperOrderResult']['jifen_pl']) ? array() : $report['paperOrderResult']['jifen_pl'];
  
            if (!empty($jifen_pl)) {
            	$reportMbtiEtt = $this->loadJungReport($jifen_pl);
            }

        }
        echo "MBTI报告数据同步完毕\n";
        exit;
    }
    
    /**
     * 同步报告数据
     * report_mbti_profession
     *    	createEntity('report_mbti_profession', 'reportMbtiProfession'); exit;
     * @return
     */
    public function xz_report_common($name = '')
    {
    	$commonDao = \dao\Common::singleton();
//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti`;");
//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_character`;");
//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_profession`;");
//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_rouge`;");
//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_suggest`;");
//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_love`;");
//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_love_type`;");
//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_love_temperament`;");
    	$where = "`goods_name` like '%MBTI性格测试专业版%'";
//$where = 1;
    	$sql = "SELECT * FROM `xz_report` WHERE {$where};";
    	$reportList = $commonDao->readDataBySql($sql);
    
    	$now = $this->frame->now;
    	$reportArr = array();
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperSv = \service\TestPaper::singleton();
    	$testPaperEttList = $testPaperDao->readListByWhere();
    	$testPaperEttList = array_column($testPaperEttList, null, 'name');
    	$reportMbtiDao = \dao\ReportMbti::singleton();

    	


$b = array();
		$ids = array();
		$goods_names = array();
    	if (is_iteratable($reportList)) foreach ($reportList as $data) {

    		$goods_name = $data->goods_name;

    		$report = empty($data->report) ? array() : json_decode(base64_decode($data->report), true);
    		$pay = empty($data->pay) ? array() : json_decode(base64_decode($data->pay), true);
    		$order = empty($data->order) ? array() : json_decode(base64_decode($data->order), true);
    		$create_report = empty($pay['create_report']) ? array() : $pay['create_report'];
    		$goods_version_select = empty($order['goodsOrder']['goods_version_select']) ? 1 : $order['goodsOrder']['goods_version_select'];
    		$paperOrderResult = empty($report['paperOrderResult']) ? array() : $report['paperOrderResult'];
			$paper_order_sn = $data = $data->paper_order_sn;
			
if ($report['paperOrderResult']['mbti_pl']['mbti_pl']['weidu_name'] !='艺术家') {
	continue;
}
			print_r($report);exit;
			if (isset($paperOrderResult['total_result_scoring']['jifen_guize']) && empty($paperOrderResult['danweidu']) && empty($paperOrderResult['duoweidu'])) {
				if ($paperOrderResult['total_result_scoring']['jifen_guize'] == 1) {
					echo $goods_name . "\n";
					if (in_array($goods_name, $goods_names)) {
						continue;
					}
					
					$goods_names[$goods_name] = $goods_name;
					
					print_r($paperOrderResult['total_result_scoring']);
				}
				
			} else {
				//continue;
			}
			//continue;
			
		/**
		 * 'total_result_scoring' => array(
    				'jifen_guize' => 1,
    				'paper_tile' => '你的易怒指数有多高',
    				'last_percent' => $answerResult['percent'],
    				'content' => array(
    					'id' => 1,
    					'name' => $answerResult['levelConf']['等级'],
    					'result_explain' => $answerResult['levelConf']['解说'],
    					'zhuyi' => empty($answerResult['levelConf']['建议']) ? '' : $answerResult['levelConf']['建议'],
    				),
    				'setting' => array(
    					'jifen_guize' => 1,
    					'title' => '你的易怒程度是',
    					'title_icon_image' => 'fa-free-code-camp',
    					'title_icon_color' => '#f6727e',
    					'icon2_touming' => '#FEDDDD',
    					'title_icon_type' => 1,
    				),		 
    			),
		 */
			
			
			//print_r($report);exit;
			
			
			
			
			
			
			
// 			$weiduList = $paperOrderResult['danweidu']['weiduList'];
// 			$content = $paperOrderResult['total_result_scoring']['content'];
// 			if ($weiduList['1']['weidu_result']['name']  != '较高') {
// 				//continue;
// 			}
			
			
		
			// A型人格   B型倾向人格
			//echo $content['name'];
// print_r($weiduList['1']['weidu_result']);
// echo "\n";
// echo "\n";
// continue;
    		if ($goods_name == '瑞文国际标准智商测试') {
    			$this->loadRavenReport($paperOrderResult, $goods_version_select, $paper_order_sn, $goods_name);
    		} elseif ($goods_name == '荣格古典心理原型测评') {
    			$this->loadJungReport($paperOrderResult);
    		} elseif ($goods_name == 'ABO性别角色评估') {
    
    			$this->loadABOReport($paperOrderResult);
    			
    		continue;
    		} elseif ($goods_name == '盖洛普优势识别测试') {
    			$a = $this->loadGallupReport($paperOrderResult);
    			
    			foreach ($a as $k => $v) {
    				$b[$k] = $v;
    			}
    		
    		} elseif ($goods_name == 'PDP性格测试') {
    			$this->loadCareerAnchor($paperOrderResult);
    		} elseif ($goods_name == '九型人格测试') {
    			$this->loadEnneagram($paperOrderResult);
    		} elseif ($goods_name == '九型人格测试') {
    			$this->loadEnneagram($paperOrderResult);
    		}
    	}
    	print_r($goods_names);exit;
    	
var_export($b);exit;
    	echo "MBTI报告数据同步完毕\n";
    	exit;
    }
    
    /**
     * 同步题目到配置文件
     *  [id] => 7061
   
            [index] => 26
            [matter] => 在与人相处或工作时，你是否小心谨慎，怕出错？
            [matterImg] => 
            [selections] => [{"name":"非常符合"}, {"name":"比较符合"}, {"name":"不确定"}, {"name":"不太符合"}, {"name":"完全不符合"}]
            [version] => 1
            [scoreValue] => 猫头鹰
            [analysis] => 
            [source] => gpt
            [createTime] => 0
            
            1 => 
  array (
    1 => 
    array (
      'matter' => '当你和他人发生冲突时，你会',
      'scoreValue' => '',
      'selections' => 
      array (
        'A' => 
        array (
          'name' => '退缩回避',
        ),
        'B' => 
        array (
          'name' => '坚持自己原来的态度',
        ),
      ),
    ),
     */
    private function sysMysqlQuestionToConf($testPaperEtt, $testQuestionEttList) 
    {
    	$file = CODE_PATH . 'static' . DIRECTORY_SEPARATOR . $testPaperEtt->name . DIRECTORY_SEPARATOR . 'question.stable' . '.php';
    	$conf = getStaticData($testPaperEtt->name, 'question.stable');
    	if (empty($conf)) {
    		$conf = array();
    	}
    	if (is_iteratable($testQuestionEttList)) foreach ($testQuestionEttList as $testQuestionEtt) {
    		$conf[$testQuestionEtt->version][$testQuestionEtt->index] = array(
    			'groupName' => $testQuestionEtt->groupName,
    			'matter' => $testQuestionEtt->matter, // 题干
    			'matterImg' => $testQuestionEtt->matterImg,
    			'scoreValue' => $testQuestionEtt->scoreValue,
    			'selections' => json_decode($testQuestionEtt->selections, true),
    			'analysis' => $testQuestionEtt->analysis,
    		);
    	}
    	setStaticData($testPaperEtt->name, 'question.stable', $conf);
    	return true;
    }
    
    
    
    /**
     * 修复题目
     * 
// 题目
        	$questionDatas = array();
        	$index = 1;
        	foreach ($paper_order_detail1 as $row) {
        		$questionData = array(
        			'testPaperId'   => 0,
        			'groupName'     => '',
        			'version'       => 1,
        			'index'         => $index++,
        			'matter'        => $row['subject'],
        			'matterImg'     => $row['subject_image'],
        			'selections'    => json_encode($row['option'], JSON_UNESCAPED_UNICODE),
        			'createTime'    => $now,
        		);
        		if (!empty($questionGroupMap1[$questionData['index']])) {
        			$questionData['groupName'] = $questionGroupMap1[$questionData['index']];
        		}
        		$questionDatas[] = $questionData;
        	}
     * @return
     */
    public function xz_question()
    {
    	$commonDao = \dao\Common::singleton();
    	$sql = "SELECT * FROM `testQuestionBak` WHERE `source`!='';";
    	$dataList = $commonDao->readDataBySql($sql);
    	$map = array();
    	foreach ($dataList as $data) {
    		$map[$data->testPaperId][$data->version][$data->index] = $data;
    	}
    	$sql = "SELECT * FROM `testPaperBak` WHERE `id` in (" . implode(',', array_keys($map)) . ');';
    	$testPaperList = $commonDao->readDataBySql($sql);
    	$testPaperList = $commonDao->refactorListByKey($testPaperList,'id');
    	foreach ($map as $testPaperId => $list) {
    		if (empty($testPaperList[$testPaperId])) {
    			echo "adx";
    			continue;
    		}
    		$testPaperEtt = $testPaperList[$testPaperId];
    		$testQuestionEttList = array();
    		foreach ($list as $version => $_list) {
    			$testQuestionEttList = array_merge($testQuestionEttList, $_list);
    		}
    		$this->sysMysqlQuestionToConf($testPaperEtt, $testQuestionEttList);
    	}
    	print_r($testPaperList);exit;
    	
    	
    	//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti`;");
    	//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_character`;");
    	//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_profession`;");
    	//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_rouge`;");
    	//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_suggest`;");
    	//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_love`;");
    	//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_love_type`;");
    	//     	$commonDao->execBySql("TRUNCATE TABLE `report_mbti_love_temperament`;");
    	$where = "`goods_name` like '%瑞文%'";
    	//	$where = 1;
    	$sql = "SELECT * FROM `xz_report` WHERE {$where};";
    	$reportList = $commonDao->readDataBySql($sql);
    	$now = $this->frame->now;
    	$reportArr = array();
    	$testPaperDao = \dao\TestPaper::singleton();
    	$testPaperSv = \service\TestPaper::singleton();
    	$testPaperEttList = $testPaperDao->readListByWhere();
    	$testPaperEttList = array_column($testPaperEttList, null, 'name');
    	$reportMbtiDao = \dao\ReportMbti::singleton();
    }
    
}