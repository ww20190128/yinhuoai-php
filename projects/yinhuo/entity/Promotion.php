<?php
namespace entity;

/**
 * Promotion 实体类
 * 
 * @author 
 */
class Promotion extends ModelBase
{

    /**
     * 主表
     *
     * @var string
     */
    const MAIN_TABLE = 'promotion';

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
     * 推广名称
     *
     * @var varchar
     */
    public $name = '';

    /**
     * 状态
     *
     * @var int
     */
    public $status = 0;

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
     * 简介
     *
     * @var text
     */
    public $desc;

    /**
     * 测评Id
     *
     * @var int
     */
    public $testPaperId = 0;

    /**
     * 背景图片
     *
     * @var varchar
     */
    public $backgroundImage;

    /**
     * 版权
     *
     * @var text
     */
    public $copyright;

    /**
     * 样式-按钮样式
     *
     * @var varchar
     */
    public $ui_btnColor;


    /**
     * 样式类型
     *
     * @var int
     */
    public $styleType = 0;

    /**
     * 答题类型
     *
     * @var text
     */
    public $answerStyleType;

    /**
     * 更新时间
     *
     * @var int
     */
    public $updateTime = 0;

    /**
     * 创建时间
     *
     * @var int
     */
    public $createTime = 0;

// 表结构end

    /**
     * 当前测评
     *
     * @var Object
     */
    protected $testPaper = null;
    
    /**
     * 获取测评
     *
     * @return int
     */
    protected function getTestPaper()
    {
        if (is_null($this->testPaper) && $this->testPaperId) {
            $testPaperDao = \dao\TestPaper::singleton();
            $testPaperEtt = $testPaperDao->readByPrimary($this->testPaperId);
            $this->testPaper = $testPaperEtt;
        }
        return $this->testPaper;
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
    	
    	$testPaperModel = $this->getTestPaper()->getModel();
    	$versionConfig = empty($testPaperModel['versionConfig']) ? array() : $testPaperModel['versionConfig'];
    	if ($this->name == 'MBTI性格测试2025最新版') {
    		$versionConfig['type'] = 1;
    		$versionConfig['text'] = '请选择版本';
    		$versionList = array(
    			'男生版' => $commonSv::formartImgUrl('男.png'),
    			'女生版' => $commonSv::formartImgUrl('女.png'),
    		);
    		$options = array();
    		$id = 1;
    		foreach ($versionList as $key => $vaule) {
    			$options[] = array(
    				'id'   => $id++,
    				'name' => $key,
    				'img'  => $vaule,
    			);
    		}
    		$versionConfig['gains'] = array(
    			'个人MBTI类型测试结果',
				'个人性格优势和劣势描述',
				'个人性格特点及正反面',
				'如何扬长避短',
				'人口占比、类似名人',
				'匹配适合的职业',
				'职业发展指南',
				'恋爱类型匹配、恋爱状态'
    		);
    		$typeGroupMap = array( // groupName
    		 	'分析家' => array(
    				'color' => '#866995',
    				'bgColor' => '#F5F1FF',
    				'list' => array(
    					'INTJ' => array(
    						'name' => '建筑师型',
    						'desc' => '富有想象力和战略性思维，一切皆在计划之中。',
    					),
    					'INTP' => array(
    						'name' => '逻辑家型',
    						'desc' => '具有创造力的发明家，对知识有着不可抑制的渴望。',
    					),
    					'ENTJ' => array(
    						'name' => '指挥官型',
    						'desc' => '大胆、富有想象力且意志强大的领导者，总能找到或创造解决方法。',
    					),
    					'ENTP' => array(
    						'name' => '辩论家型',
    						'desc' => '聪明好奇的思考者，无法抵挡智力挑战的诱惑。 ',
    					),
    				),
    			),
    			'外交家' => array(
    				'color' => '#5CA68B',
    				'bgColor' => '#D6EBE2',
    				'list' => array(
    					'INFJ' => array(
    						'name' => '提倡者型',
    						'desc' => '安静神秘，但非常鼓舞人心且不知疲倦的理想主义者。',
    					),
    					'INFP' => array(
    						'name' => '调停者型',
    						'desc' => '富有诗意、善良且无私的人，总是热衷于帮助正义事业。',
    					),
    					'ENFJ' => array(
    						'name' => '主人公型',
    						'desc' => '具有魅力并能激励人心的领导者，能够让听众为之着迷。',
    					),
    					'ENFP' => array(
    						'name' => '竞选者型',
    						'desc' => '充满活力，富有创意，善于交际的自由之人，总能找到微笑的理由。',
    					),
    				),
    			),
    			'守护者' => array(
    				'color' => '#4F9CBB',
    				'bgColor' => '#D8E9F1',
    				'list' => array(
    					'ISTJ' => array(
    						'name' => '物流师型',
    						'desc' => '务实且注重事实的人，可靠性不容怀疑。',
    					),
    					'ISFJ' => array(
    						'name' => '守卫者型 ',
    						'desc' => '非常专注和热情的保护者，总是随时准备保护他们所爱的人。',
    					),
    					'ESTJ' => array(
    						'name' => '总经理型',
    						'desc' => '出色的管理者，在管理事物或人的方面无与伦比。',
    					),
    					'ESFJ' => array(
    						'name' => '执政官型 ',
    						'desc' => '非常关心他人，善于社交，受人欢迎，总是乐于助人。',
    					),
    				),
    			),
    			'探险家' => array(
    				'color' => '#E7CB98',
    				'bgColor' => '#F8EDD7',
    				'list' => array(
    					'ISTP' => array(
    						'name' => '鉴赏家型',
    						'desc' => '大胆而实际的实验家，擅长使用各种形式的工具。',
    					),
    					'ISFP' => array(
    						'name' => '探险家型',
    						'desc' => '灵活有魅力的艺术家，时刻准备着探索和体验新鲜事物。',
    					),
    					'ESTP' => array(
    						'name' => '企业家型',
    						'desc' => '聪明、充满活力且洞察力极强的人，真正喜欢充满刺激和危险的生活。',
    					),
    					'ESFP' => array(
    						'name' => '表演者型',
    						'desc' => '精力充沛、热情，总是心血来潮，有他们在身边，生活永远不会无聊。',
    					),
    				),
    			),
    		);
    		$typeGroupList = array();
    		foreach ($typeGroupMap as $groupName => $groupRow) {
    			$list = array();
    			foreach ($groupRow['list'] as $type => $row) {
    				$row['img'] = $commonSv::formartImgUrl($type . '.svg', 'report\MBTI\svg');
    				$row['type'] = $type;
    				$list[] = $row;
    			}
    			$groupRow['list'] = $list;
    			$groupRow[$groupName] = $groupName;
    			$typeGroupList[] = $groupRow;
    		}
    		$versionConfig['notice'] = '本测评长期有效，可多次重复测试，在人生的不同阶段，选择也不尽相同，建议不定期重新测试。';
    		$versionConfig['typeGroupList'] = $typeGroupList;
    		$versionConfig['topDesc'] = '目前使用广泛的MBTI测试（2025）';
    		$versionConfig['topImg'] = $commonSv::formartImgUrl('topImg.png', 'promotion');
    		$versionConfig['options'] = $options;
    		$versionConfig['desc'] = '<h5>为什么每个人都要做MBTI测试？</h5><p>MBTI测试是世界500强正在使用的人格评估工具。MBTI从纷繁复杂的个性特征中，归纳提炼出4个关键要素，从而把人区分成16种类型，如ENTJ领导者、ISFP艺术家、INFJ哲学家等。</p><p>很多测试者认为MBTI对自己的恋爱、婚姻、职业和自我认知帮助无可替代，专家建议反复测试。</p>';
    	}
    	
    	// 红包配置   第一次 减10元   第二次 减 20 元
    	$price = $this->price; // 价格
    	$value1 = '10.00'; // 第1次折扣减额
    	$discount1 = ceil(100 * ($price - $value1) / $price) * 0.1; // 第一次折扣
    	$value2 = '20.00'; // 第2次折扣减额
    	$redPacketConfig = array(
    		'value1' => $value1, // 第1次减少的值
    		'discount1' => number_format($discount1, 1), // 第1次折扣
    		'value2' => $value2, // 第2次减少的值
    		'image3' => $commonSv::formartImgUrl('image3.png', 'promotion'),
    	);
    	$desc = empty($this->desc) ? '' : $this->desc;
    	$desc = $commonSv::replaceImgSrc($desc, 'promotion');
    	
    	// 组织测评需知
    	$noticeDiv = $commonSv::formartNoticeDiv($price);
    	 
    	// 添加测评需知
    	$noticeDiv = <<<BLOCK
<div class="card-warper" style="padding: 0px 10px 70px 20px; !important; ">
	<div class="title-underline" ><span>测评需知</span></div>
    <div class="content-warper theory" >$noticeDiv</div>
</div>
BLOCK;
    	$desc = $desc . $noticeDiv;
    	
    
    	return array(
            'id'                => intval($this->id),
            'status'            => intval($this->status),
            'testPaperId'       => intval($this->testPaperId),
            'name'              => $this->name,
            'desc'              => $desc,
            'price'             => $price,
            'originalPrice'     => $this->originalPrice,
    		'backgroundImage'  	=> $this->backgroundImage,
    		'copyright'     	=> $this->copyright,
    		'ui_testBtnText'	=> '立即测试', // 首页测试按钮文本（红色）
    		'ui_btnColor'     	=> 'rgb(255, 0, 0)', // 首页测试按钮颜色（红色）
       		'styleType'     	=> intval($this->styleType), // 推广测评的样式  1  最简单的样式  (标题，题目数量，报告数，测试按钮) 2  desc 测试按钮悬浮  3  4 显示测前序知   5 显示背景图片
    		'answerStyleType'   => intval($this->answerStyleType),
    		'createTime'        => intval($this->createTime),
    		'updateTime'        => intval($this->updateTime),
    		'versionConfig'     => $versionConfig,
    		'redPacketConfig'   => $redPacketConfig,
        );
    }
    
}