<?php
namespace entity;

/**
 * TestPaper 实体类
 * 
 * @author 
 */
class TestPaper extends ModelBase
{
    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'testPaper';

    /**
     * 主键
     *
     * @var string
     */
    const PRIMARY_KEY = 'id';

    /**
     * 主键id
     *
     * @var int
     */
    public $id;

    /**
     * 测试名称
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 状态
     *
     * @var tinyint
     */
    public $status = 0;

    /**
     * 副标题
     *
     * @var varchar
     */
    public $subhead = '';

    /**
     * 缩略图
     *
     * @var varchar
     */
    public $coverImg = '';

    /**
     *预览图
     *
     * @var varchar
     */
    public $mainImg = '';

    /**
     * 专业报告数量
     *
     * @var int
     */
    public $reportNum = 0;

    /**
     * 测评说明
     *
     * @var varchar
     */
    public $contentTitle = '';

    /**
     * 说明内容
     *
     * @var varchar
     */
    public $content = '';

    /**
     * 测评须知
     *
     * @var varchar
     */
    public $noticeTitle = '';

    /**
     * 须知内容
     *
     * @var varchar
     */
    public $notice = '';

    /**
     * 价格
     *
     * @var decimal(4,2)
     */
    public $price = 0.00;

    /**
     * 原价
     *
     * @var decimal(4,2)
     */
    public $originalPrice = 0.00;

    /**
     * 售出数量
     *
     * @var int
     */
    public $saleNum = 0;

    /**
     * 答题样式类型
     *
     * @var int
     */
    public $questionStypeType = 1;

    /**
     * 客服链接
     *
     * @var varchar
     */
    public $customerUrl = '';

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;
    
    /**
     * 报告制作图片
     *
     * @var varchar
     */
    public $reportProcessImg = '';
   
    
// 表结构end

    /**
     * 获取测评的题目
     * 
     * @return array
     */
    public static function getQuestions($testPaperName)
    {
    	// 读取稳定版本
    	$questionConf = getStaticData($testPaperName, 'question.stable');

    	if (empty($questionConf)) { // 读取心芝版本的题目
    		$questionConf = getStaticData($testPaperName, 'question');
    	}

    	if (count($questionConf) >= 10) { // 就一个版本
    		$questionConf = array(
    			'1' => $questionConf,
    		);
    	}
    	$commonSv = \service\Common::singleton();
    	foreach ($questionConf as $version => $questions) {
    		foreach ($questions as $key => $question) {
    			if (!empty($question['matterImg'])) {
    				$question['matterImg'] = $commonSv::formartImgUrl($question['matterImg'], 'question');
    			}
    			foreach ($question['selections'] as $k => $row) {
    				if (!empty($row['img'])) {
    					$question['selections'][$k]['img'] = $commonSv::formartImgUrl($row['img'], 'question');
    				}
    			}
    			$questionConf[$version][$key] = $question;		
    		}
    	}
    	
    	return $questionConf;
    }
    
    /**
     * 计算答题时间(分钟)
     *
     * @return int
     */
    private static function getAnswerTimeLimit($questionNum)
    {
    	// 计算作答时间
    	$answerTimeLimit = 0;
    	if ($questionNum <= 20) {
    		$answerTimeLimit = 10;
    	} elseif ($questionNum <= 30) {
    		$answerTimeLimit = 15;
    	} elseif ($questionNum <= 60) {
    		$answerTimeLimit = 30;
    	} elseif ($questionNum <= 90) {
    		$answerTimeLimit = 40;
    	} else {
    		$answerTimeLimit = 60;
    	}
    	return $answerTimeLimit;
    }
    
    /**
     * 计算已测数量
     * 
     * @return int
     */
    private static function getSaleNum($questionNum, $price)
    {
    	if ($price <= 0) {
    		$price = 50;
    	}
    	$startTime = strtotime('2024-11-08');
    	$now = time(); // 获取当前时间的时间戳
    	$preSaleNum = (($now - $startTime) / (60 * 60) + $questionNum * 30 + $price * 70) * 5;
    	$preSaleNum = intval($preSaleNum);
    	if ($preSaleNum >= 10000) {
    		$preSaleNum = number_format($preSaleNum * 0.0001, 2) . '万';
    	}
    	return $preSaleNum;
    }
    
    /**
     * 创建模型
     * 
     * @return array
     */
    protected function createModel()
    {
    	$commonSv = \service\Common::singleton();
    	$coverImg = $commonSv::formartImgUrl($this->name . '.png', 'cover'); // 封面图片 450 * 450
    	$mainImg = $commonSv::formartImgUrl($this->name . '.jpg', 'main'); // 主图片 70 * 420
    	
    	$questionConf = self::getQuestions($this->name);
    	
    	$versionConfig = array();
    	$questionNum = 0; // 第一个版本题目数量
    	if (count($questionConf) >= 2) { // 有多个版本		
    		$versionText = '请选择您的性别';
    		if ($this->name == '九型人格测试') {
    			$versionText = '请选择版本';
    			$versionList = array(
    				'专业精华版' => $commonSv::formartImgUrl('版本1.png'),
    				'国际标准版' => $commonSv::formartImgUrl('版本2.png'),
    			);
    		} elseif ($this->name == 'MBTI性格测试2025最新版') {
    			$versionList = array(
    				'男生' => $commonSv::formartImgUrl('版本-男.png'),
    				'女生' => $commonSv::formartImgUrl('版本-女.png'),
    			);
    		} else { // 默认
    			$versionList = array(
    				'男生' => $commonSv::formartImgUrl('版本-男.png'),
    				'女生' => $commonSv::formartImgUrl('版本-女.png'),
    			);
    		}
    		$options = array();
    		$id = 1;
    		foreach ($versionList as $key => $vaule) {
    			$options[] = array(
    				'id'   => $id++,
    				'name' => $key,
    				'img'  => $vaule,
    			);
    		}
    		$versionConfig = array(
    			'text' => $versionText,
    			'options' => $options,
    		);
    	}
    	$questionNum = count(reset($questionConf)); // 题目数量
    	$answerTimeLimit = self::getAnswerTimeLimit($questionNum); // 作答时间
	
    	// 请选择你的年龄
    	$ageSet = array();
    	if (in_array($this->name, array('瑞文智力专业评估', '瑞文国际标准智商测试'))) {
    		$ageSet = array(
    			'title' => '请选择您的年龄',
    			'desc' => '<p><span style="font-size: 14px;">为确保您智商测试的准确性与专业性，请在上方选择您的<span style="font-size: 16px; color: #F6727E;font-weight:bold;">真实年龄。</span></span></p>',
    			'location' => 'end',
    		);
    	}
    	
    	// 是否mbti样式
    	$mbtiStyle = 0;
    	if (in_array($this->name, array('MBTI性格测试2025最新版'))) {
    		$mbtiStyle = 1;
    	}
    	
    	// 盖洛普优势测试

    	if (in_array($this->name, array('盖洛普优势测试'))) {

    	}
    	// 获取通用配置
    	$conf = getStaticData($this->name, 'common');

    	$saleNum = number_format($this->saleNum * 0.0001, 1, '.', '');
    	
    	$introduceConf = getStaticData($this->name, 'Introduce');
 
    	// 组织测评需知
    	$introduceConf['notice'] = $commonSv::formartNoticeDiv($this->price, $questionNum, $answerTimeLimit);
    	
    
    	if (empty($introduceConf['theme'])) {
    		$introduceConf['theme'] = 'blue';
    	}
    	
    	// 替换图片
    	if (!empty($introduceConf['recommend'])) {
    		$introduceConf['recommend'] = $commonSv::replaceImgSrc($introduceConf['recommend'], 'intro');
    	}
    	if (!empty($introduceConf['theory'])) {
    		$introduceConf['theory'] = $commonSv::replaceImgSrc($introduceConf['theory'], 'intro');
    	}
		$saleNum = self::getSaleNum($questionNum, $this->price);
		
	
        return array(
    		'id'                => intval($this->id),
        	'name'          	=> $this->name,
    		'status'            => intval($this->status),
    		'subhead'       	=> $this->subhead,
    		'coverImg'         	=> $coverImg,
        	'mainImg'           => $mainImg,
        	'reportNum'         => intval($this->reportNum),
        	'contentTitle'      => $this->contentTitle,
        	'content'           => $this->content,
        	'noticeTitle'       => $this->noticeTitle,
        	'notice'            => $this->notice,
        	'price'             => $this->price,
        	'originalPrice'     => $this->originalPrice,
        	'saleNum'           => $saleNum,
        	'questionStypeType' => intval($this->questionStypeType),
        	'customerUrl'       => $this->customerUrl,
        	'createTime'        => intval($this->createTime),
            'questionNum'		=> $questionNum, // 题目数量，取第一个版本的题目数量
            'answerTimeLimit'	=> $answerTimeLimit, // 作答时长
        	'extend'			=> empty($conf['extend']) ? array() : $conf['extend'],
        	'versionConfig'     => $versionConfig,
        	'ageSet'            => $ageSet,
        	'mbtiStyle'         => $mbtiStyle,
        	'introduce'			=> $introduceConf,
			'favorableRate'		=> 0,
        );
    }
    
}