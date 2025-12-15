<?php

namespace service\reuse;

use dao\Knowledge;
use http\Params;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\Hyperlink;
use PhpOffice\PhpPresentation\Slide\Note;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpWord\PhpWord;

loadFile(array('autoload'), ROOT_PATH . 'Lib' . DS . 'phpword' . DS);    // 加载第三方word入口文件
loadFile(array('autoload'), ROOT_PATH . 'Lib' . DS . 'phpppt' . DS);     // 加载第三方ppt入口文件
loadFile(array('autoload'), ROOT_PATH . 'Lib' . DS . 'phpoffice' . DS);   // 加载第三方excel入口文件

/**
 * Office 通用类
 *
 * @author wangwei
 */
class Office extends \service\ServiceBase
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;
    private $imgArr = [];

    /**
     * 单例模式
     *
     * @return Office
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Office();
        }
        return self::$instance;
    }


    /**
     * 导出word
     * @param $questions  试卷相关数据
     * @param array $examPaperType 试卷导出选项(1.知识点 2.答案 3.解析)   例：[1,2,3]
     * @param string $fileName word文档名称
     */
    public function exportWord($questions, $examPaperType = [], $fileName = 'questionWord')
    {
    	$questions = $this->processQuestions($questions);
        $phpWord = new PhpWord();
        //设置默认样式
        $phpWord->setDefaultFontName('宋体'); //字体
        $phpWord->setDefaultFontSize('五号'); //字号
        //链接全局基础样式
        $linkStyle = ['color' => '0000FF', 'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE];
        //标题全局基础样式
        $titleStyle = ['align' => 'center'];
        //试卷名字全局基础样式
        $examPaperNameStyle = ['color' => '006699', 'size' => 22, 'bold' => true];
        //题型全局基础样式
        $questionTypeStyle = ['color' => '006699', 'size' => 14, 'bold' => true];
        //答案标题样式
        $answerTitleStyle = ['size' => 14, 'bold' => true];
        //导入文字样式和段落样式.
        $phpWord->addLinkStyle('myLinkStyle', $linkStyle);
        $phpWord->addParagraphStyle('titleStyle', $titleStyle);
        $phpWord->addFontStyle('examPaperNameStyle', $examPaperNameStyle);
        $phpWord->addFontStyle('questionTypeStyle', $questionTypeStyle);
        $phpWord->addFontStyle('answerTitleStyle', $answerTitleStyle);
        // 文本标红
        $phpWord->addFontStyle('colorRedText', array(
            'color' => 'red'
        ));
        //添加页面
        $section     = $phpWord->addSection();
        $projectType = empty($this->frame->conf['type']) ? 0 : $this->frame->conf['type'];
        if ($projectType == 0) {
            throw new $this->exception('项目类型错误，请检查配置文件！');
        }
        $sortKeyArr = array_column($questions, 'index');
        array_multisort($sortKeyArr, SORT_ASC, $questions);
        if ($projectType == 2) {
            $questionsData = [];
            foreach ($questions as $item) {
                if (!empty($item['category'])) {
                    $questionsData[$item['category']][] = $item;
                } else {
                    $questionsData['其他'][] = $item;
                }
            }
        }

        $j = $i = 0;
        //清除路径'/'信息
        $fileName = str_replace('/', '', $fileName);
        $section->addText($fileName, 'examPaperNameStyle', 'titleStyle');

        $keyNum        = 0;
        $isPostpositio = in_array(4, $examPaperType);
        $classfyData   = $projectType == 2 ? array_keys($questionsData) : [];
        $allIds        = array_keys($questions);
        $poArr         = [];
        do {
            if ($projectType == 2 && empty($questionsData[$classfyData[$j]])) {
                $j++;
                continue;
            }
            if ($projectType == 2) {
                $number = numToWord($i + 1);
                $section->addText('第' . $number . '部分   ' . $classfyData[$j], 'answerTitleStyle', 'titleStyle');
            }
            $tempData = $usedIds = $materialArr = [];

            $exportData = $projectType == 2 ? $questionsData[$classfyData[$j]] : array_values($questions);

            foreach ($exportData as $item) {
                if (in_array($item['id'], $usedIds)) {
                    continue;
                }
                $tempData[] = $item;
                $usedIds[]  = $item['id'];
                if (!empty($item['materialInfo'])) {
                    foreach ($item['materialInfo']['questionIds'] as $questionId) {
                        if (in_array($questionId, $usedIds) || !in_array($questionId, $allIds)) {
                            continue;
                        }
                        $tempData[] = isset($questions[$questionId]) ? $questions[$questionId] : [];
                        $usedIds[]  = $questionId;
                    }
                }
            }

            $poArr = array_merge($poArr, $tempData);
            foreach ($tempData as $question) {
                //1.知识点
                if (!$isPostpositio && in_array(1, $examPaperType)) {
                    $knowledgeStr = '【知识点】 ';
                    $knowledgeStr .= isset($question['knowledgeInfo']['name']) ? $question['knowledgeInfo']['name'] : '';
                    $section->addText($knowledgeStr);
                }
                //2.题目材料
                if (!empty($question['materialInfo']) && !in_array($question['materialInfo']['id'], $materialArr)) {
                    $materialArr[] = $question['materialInfo']['id'];
                    $start         = $keyNum + 1;
                    $end           = $keyNum + count($question['materialInfo']['questionIds']);
                    $materialStr   = $start == $end ? '【材料】 根据以下材料回答第' . $start . '题<br>' : '【材料】 根据以下材料回答第' . $start . '-' . $end . '题<br>';
                    $materialStr   .= $question['materialInfo']['material_img'];
                    $section       = $this->addWordMsg($section, $materialStr);
                }
                //3.题目名称
                $tempIndex = $keyNum + 1;
                $section   = $this->addWordMsg($section, $tempIndex . '. ' . $question['matter_img']);
                //4.题目选项
                if (!empty($question['selections'])) {
                    foreach ($question['selections'] as $item) {
                        $section = $this->addWordMsg($section, $item['mark'] . '. ' . $item['value_img']);
                    }
                }
                $section->addTextBreak(1);
                //5.答案
                if (!$isPostpositio && $question['answer_img'] != '' && in_array(2, $examPaperType)) {
                    $answerStr = '【答案】 ' . $question['answer_img'];
                    $section   = $this->addWordMsg($section, $answerStr);
                }
                //6.解析
                if (!$isPostpositio && $question['analyze_img'] != '' && in_array(3, $examPaperType)) {
                    $analyzeStr = '【解析】 ' . $question['analyze_img'];
                    $section    = $this->addWordMsg($section, $analyzeStr);
                }
                //7.来源
                if (!$isPostpositio && !empty($question['relevanceExamPapers']) && in_array(5, $examPaperType)) {
                    $tempSourceArr = [];
                    foreach ($question['relevanceExamPapers'] as $sourceMsg) {
                        $tempSourceArr[] = $sourceMsg['name'] . '第' . $sourceMsg['index'] . '题';
                    }
                    $sourceStr = '【来源】 ' . implode(',', $tempSourceArr);
                    $section   = $this->addWordMsg($section, $sourceStr);
                }
                $section->addTextBreak(1);
                $keyNum++;
            }
            $i++;
            $j++;
        } while ($j < count($classfyData));

        if ($isPostpositio && count($examPaperType) > 2) {
            $postpositioStr = '';
            if (in_array(2, $examPaperType)) {
                $postpositioStr = !in_array(3, $examPaperType) ? '参考答案' : '参考答案与解析';
            }
            if (!empty($postpositioStr) && $projectType == 2) {
                $section->addText($postpositioStr, 'answerTitleStyle', 'titleStyle');
            }
            foreach ($poArr as $k => $question) {
                $tempIndex = $k + 1;
                $titleName = '第' . $tempIndex . '题';
                $section->addText($titleName);
                //1.后置知识点
                if (in_array(1, $examPaperType)) {
                    $knowledgeStr = '【知识点】 ';
                    $knowledgeStr .= isset($question['knowledgeInfo']['name']) ? $question['knowledgeInfo']['name'] : '';
                    $section->addText($knowledgeStr);
                }
                //2.后置答案
                if ($question['answer_img'] != '' && in_array(2, $examPaperType)) {
                    $answerStr = '【答案】 ' . $question['answer_img'];
                    $section   = $this->addWordMsg($section, $answerStr);
                }
                //3.后置解析
                if ($question['analyze_img'] != '' && in_array(3, $examPaperType)) {
                    $analyzeStr = '【解析】 ' . $question['analyze_img'];
                    $section    = $this->addWordMsg($section, $analyzeStr);
                }
                //4.后置来源
                if (!empty($question['relevanceExamPapers']) && in_array(5, $examPaperType)) {
                    $tempSourceArr = [];
                    foreach ($question['relevanceExamPapers'] as $sourceMsg) {
                        $tempSourceArr[] = $sourceMsg['name'] . '第' . $sourceMsg['index'] . '题';
                    }
                    $sourceStr = '【来源】 ' . implode(',', $tempSourceArr);
                    $section   = $this->addWordMsg($section, $sourceStr);
                }
                $section->addTextBreak(1);
            }
        }
        //file路径不存在则创建路径
        if (!is_dir($this->frame->conf['fileDir'])) {
            mkdir($this->frame->conf['fileDir'], 0777);
        }
        //系统文件路径
        $systemPath = $this->frame->conf['fileDir'] . $fileName . '_' . date('YmdH_i') . '.docx';
        //链接访问路径
        $urlPath = $this->frame->conf['urls']['file'] . $fileName . '_' . date('YmdH_i') . '.docx';
        $phpWord->save($systemPath, 'Word2007', false);
        if (!empty($this->imgArr)) {
            foreach ($this->imgArr as $itemPath) {
                @unlink($itemPath);
            }
        }
        return ['systemPath' => $systemPath, 'urlPath' => $urlPath];
    }

    /**
     * 加工题目赋值img及材料题包含的题目id
     * 
     * @return array
     */
    private function processQuestions($questions)
    {
		$proMap = array('matter', 'answer', 'analyzeExtra', 'analyze');
		$questionSv = \service\Question::singleton();
		$materialIds = array();
		if (is_iteratable($questions)) foreach ($questions as $key => $question) {
			foreach ($proMap as $pro) {
				if (isset($question[$pro])) {
					$question[$pro . '_img'] = $questionSv->showImg($question[$pro]);
				}
			}
			if (!empty($question['selections'])) foreach ($question['selections'] as $k => $selection) {
				$question['selections'][$k]['value_img'] = $questionSv->showImg($selection['value']);
			}
			if (!empty($question['materialInfo'])) {
				$question['materialInfo']['material_img'] = $questionSv->showImg($question['materialInfo']['material']);
				$materialIds[] = $question['materialInfo']['id'];
			}
			$questions[$key] = $question;
		}
		$materialIds = array_unique($materialIds);
		if (!empty($materialIds)) { // 需要获取材料下的题目
			$questionMaterialDao = \dao\QuestionMaterial::singleton();
			$questionMap = $questionMaterialDao->getQuestionsByMaterialIds($materialIds);
			if (is_iteratable($questions)) foreach ($questions as $key => $question) {
				if (!empty($question['materialInfo'])) {
					$questionIds = empty($questionMap[$question['materialInfo']['id']])
						? array() : $questionMap[$question['materialInfo']['id']];
					asort($questionIds);
					$questions[$key]['materialInfo']['questionIds'] = array_values($questionIds);
				}
			}
		}
		$questionSv->loadRelevanceExamPapers($questions); // 加载关联的试卷信息
		return $questions;
    }
    
    /**
     * 导出word
     * @param $questions  试卷相关数据
     * @param array $examPaperType 试卷导出选项(1.知识点 2.答案 3.解析)   例：[1,2,3]
     * @param string $fileName word文档名称
     */
    public function exportQuestionTypeWord($questions, $examPaperType = [], $fileName = 'questionWord')
    {
    	$questions = $this->processQuestions($questions);

        //如果有一道题没有题型，就按照原有的word逻辑进行导出
        $checkQuestionType = true;
        foreach ($questions as $item) {
            if (empty($item['typeInfo'])) {
                $checkQuestionType = false;
                break;
            }
        }
        if (!$checkQuestionType) {
            return $this->exportWord($questions, $examPaperType, $fileName);
        }
        $phpWord = new PhpWord();
        //设置默认样式
        $phpWord->setDefaultFontName('微软雅黑'); //字体
        $phpWord->setDefaultFontSize('五号'); //字号
        //链接全局基础样式
        $linkStyle = ['color' => '0000FF', 'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE];
        //标题全局基础样式
        $titleStyle = ['align' => 'center'];
        //试卷名字全局基础样式
        $examPaperNameStyle = ['color' => '000', 'size' => 16, 'bold' => true];
        //题型全局基础样式
        $questionTypeStyle = ['color' => '000', 'size' => 14, 'bold' => true];
        //答案标题样式
        $answerTitleStyle = ['size' => 12, 'bold' => true];
        //导入文字样式和段落样式.
        $phpWord->addLinkStyle('myLinkStyle', $linkStyle);
        $phpWord->addParagraphStyle('titleStyle', $titleStyle);
        $phpWord->addFontStyle('examPaperNameStyle', $examPaperNameStyle);
        $phpWord->addFontStyle('questionTypeStyle', $questionTypeStyle);
        $phpWord->addFontStyle('answerTitleStyle', $answerTitleStyle);
        // 文本标红
        $phpWord->addFontStyle('colorRedText', array(
            'color' => 'red'
        ));
        //添加页面
        $section = $phpWord->addSection();
        //清除路径'/'信息
        $fileName = str_replace('/', '', $fileName);
        $section->addText($fileName, 'examPaperNameStyle', 'titleStyle');
        $sortKeyArr = array_column($questions, 'index');
        array_multisort($sortKeyArr, SORT_ASC, $questions);
        $tempQuestionData = [];
        //数据组装：1.把主观题和客观题拆开
        $haveScore        = true;
        $materialGroupArr = $specialArr = [];
        foreach ($questions as $item) {
            if ($haveScore && (empty($item['score']) || $item['score'] == '0')) {
                $haveScore = false;
            }
            if (!empty($item['materialId'])) {
                $materialGroupArr[$item['materialId']][] = $item['id'];
                if (count($materialGroupArr) > 1) {
                    $specialArr = array_merge($specialArr, $materialGroupArr[$item['materialId']]);
                }
            }
            $tempQuestionData[$item['typeInfo']['subjective']][] = $item;
        }
        //强制把客观题放到主观题的前面
        $zgQuestionData = isset($tempQuestionData['主观题']) ? $tempQuestionData['主观题'] : [];
        $kgQuestionData = isset($tempQuestionData['客观题']) ? $tempQuestionData['客观题'] : [];
        $tempQuestionData = array_filter(['客观题' => $kgQuestionData, '主观题' => $zgQuestionData]);
        //由于材料题的分值不好预判，相关联的材料题目不参加分值排序
        $specialArr = array_unique($specialArr);
        //数据组装：2.把主观题和客观题按照题目类型拆开
        $questionTypeGroupData = [];
        $projectType           = $this->frame->conf['type'];
        $exceptArr             = ['案例分析题'];
        foreach ($tempQuestionData as $key => $item) {
            $tempItem = [];
            foreach ($item as $itum) {
                $tempItem[$itum['typeInfo']['id']]['name']   = $itum['typeInfo']['name'];
                $tempItem[$itum['typeInfo']['id']]['list'][] = $itum;
            }
            if ($haveScore) {
                foreach ($tempItem as $itemkey => $datum) {
                    $commonArr = array_intersect($specialArr, array_column($datum['list'], 'id'));
                    if (empty($commonArr)) {
                        $sortKeyArr = array_column($datum['list'], 'score');
                        if (count(array_unique($sortKeyArr)) != 1 && $projectType == 1 && !in_array($datum['name'], $exceptArr)) {
                            array_multisort($sortKeyArr, SORT_ASC, $datum['list']);
                        }
                    }
                    $questionStr = "（本大题共" . count($datum['list']) . "小题，";
                    if (count($datum['list']) == 1) {
                        $tempArr     = current($datum['list']);
                        $questionStr .= "每题" . $tempArr['score'] . "分，共计" . $tempArr['score'] . "分）";
                    } else {
                        $scoreArr   = array_column($datum['list'], 'score');
                        $totalScore = array_sum($scoreArr);
                        if (count(array_unique($scoreArr)) == 1) {
                            $questionStr .= "每题" . current($scoreArr) . "分，共计" . $totalScore . "分）";
                        } else {
                            $currentScore   = current($datum['list'])['score'];
                            $subQuestionStr = "";
                            $initIndex      = $startIndex = 1;
                            foreach ($datum['list'] as $tempKey => $item) {
                                if ($tempKey == (count($datum['list']) - 1)) {
                                    if ($startIndex == $initIndex) {
                                        $subQuestionStr .= "第" . $startIndex . "题" . $currentScore . "分，";
                                    } else {
                                        if ($startIndex == 1 && $initIndex == 2) {
                                            $subQuestionStr .= "第" . $startIndex . "题" . $currentScore . "分，第" . $initIndex . "题" . $item['score'] . "分，";
                                        } else {
                                            if ($currentScore != $item['score']) {
                                                if ($startIndex == ($initIndex - 1)) {
                                                    $subQuestionStr .= "第" . $startIndex . "题" . $currentScore . "分，第" . $initIndex . "题" . $item['score'] . "分，";
                                                } else {
                                                    $subQuestionStr .= "第" . $startIndex . "-" . ($initIndex - 1) . "题" . $currentScore . "分，第" . $initIndex . "题" . $item['score'] . "分，";
                                                }
                                            } else {
                                                $subQuestionStr .= "第" . $startIndex . "-" . $initIndex . "题" . $currentScore . "分，";
                                            }
                                        }
                                    }
                                    break;
                                }
                                if ($currentScore != $item['score']) {
                                    if ($startIndex == ($initIndex - 1)) {
                                        $subQuestionStr .= "第" . $startIndex . "题" . $currentScore . "分，";
                                    } else {
                                        $subQuestionStr .= "第" . $startIndex . "-" . ($initIndex - 1) . "题" . $currentScore . "分，";
                                    }
                                    $startIndex   = $initIndex;
                                    $currentScore = $item['score'];
                                }
                                $initIndex++;
                            }
                            $subQuestionStr .= "共计" . $totalScore . "分）";
                            $questionStr    .= $subQuestionStr;
                        }
                    }
                    $tempItem[$itemkey]['description'] = $questionStr;
                    $tempItem[$itemkey]['list']        = $datum['list'];
                }
            }
            $questionTypeGroupData[$key] = $tempItem;
        }

        $partArr       = ['一', '二', '三', '四', '五', '六', '七', '八', '九', '十'];
        $partKey       = 0;
        $typeKey       = 0;
        $materialArr   = [];
        $isPostpositio = in_array(4, $examPaperType);
        foreach ($questionTypeGroupData as $subjectiveName => $item) {
            $partStr        = '第' . $partArr[$partKey] . '部分  ' . $subjectiveName;
            $section->addText($partStr, 'answerTitleStyle', 'titleStyle');
            foreach ($item as $datum) {
                $questionTypeTitle = $partArr[$typeKey] . '、' . $datum['name'];
                $questionTypeTitle .= isset($datum['description']) ? $datum['description'] : '';
                $section->addText($questionTypeTitle, 'answerTitleStyle');
                foreach ($datum['list'] as $keyNum => $question) {
                    //1.知识点
                    if (!$isPostpositio && in_array(1, $examPaperType)) {
                        $knowledgeStr = '【知识点】 ';
                        $knowledgeStr .= isset($question['knowledgeInfo']['name']) ? $question['knowledgeInfo']['name'] : '';
                        $section->addText($knowledgeStr);
                    }
                    //2.题目材料
                    if (!empty($question['materialInfo']) && !in_array($question['materialInfo']['id'], $materialArr)) {
                        $materialArr[] = $question['materialInfo']['id'];
                        $section       = $this->addWordMsg($section, $question['materialInfo']['material_img']);
                    }
                    //3.题目名称
                    $tempIndex = $keyNum + 1;
                    $section   = $this->addWordMsg($section, $tempIndex . '. ' . $question['matter_img']);
                    //4.题目选项
                    if (!empty($question['selections'])) {
                        foreach ($question['selections'] as $item) {
                            $section = $this->addWordMsg($section, $item['mark'] . '. ' . $item['value_img']);
                        }
                    }
                    $commonArr = array_intersect([2, 3, 5], $examPaperType);
                    if (!$isPostpositio && !empty($commonArr)) {
                        $section->addTextBreak(1);
                    }
                    //5.答案
                    if (!$isPostpositio && $question['answer_img'] != '' && in_array(2, $examPaperType)) {
                        $answerStr = '【答案】 ' . $question['answer_img'];
                        $section   = $this->addWordMsg($section, $answerStr);
                    }
                    //6.解析
                    if (!$isPostpositio && $question['analyze_img'] != '' && in_array(3, $examPaperType)) {
                        $analyzeStr = '【解析】 ' . $question['analyze_img'];
                        $section    = $this->addWordMsg($section, $analyzeStr);
                    }
                    //7.来源
                    if (!$isPostpositio && !empty($question['relevanceExamPapers']) && in_array(5, $examPaperType)) {
                        $tempSourceArr = [];
                        foreach ($question['relevanceExamPapers'] as $sourceMsg) {
                            $tempSourceArr[] = $sourceMsg['name'] . '第' . $sourceMsg['index'] . '题';
                        }
                        $sourceStr = '【来源】 ' . implode(',', $tempSourceArr);
                        $section   = $this->addWordMsg($section, $sourceStr);
                    }
                    $section->addTextBreak(1);
                }
                $typeKey++;
            }
            $partKey++;
        }
        if ($isPostpositio && count($examPaperType) > 2) {
            $postpositioStr = '参考答案与解析';
            $section->addText($postpositioStr, 'answerTitleStyle', 'titleStyle');
            $partKey = 0;
            $typeKey = 0;
            foreach ($questionTypeGroupData as $subjectiveName => $item) {
                $partStr = '第' . $partArr[$partKey] . '部分  ' . $subjectiveName;
                $section->addText($partStr, 'answerTitleStyle', 'titleStyle');
                foreach ($item as $datum) {
                    $questionTypeTitle = $partArr[$typeKey] . '、' . $datum['name'];
                    $section->addText($questionTypeTitle, 'answerTitleStyle');
                    foreach ($datum['list'] as $k => $question) {
                        $tempIndex = $k + 1;
                        $titleName = '第' . $tempIndex . '题';
                        $section->addText($titleName);
                        //1.后置知识点
                        if (in_array(1, $examPaperType)) {
                            $knowledgeStr = '【知识点】 ';
                            $knowledgeStr .= isset($question['knowledgeInfo']['name']) ? $question['knowledgeInfo']['name'] : '';
                            $section->addText($knowledgeStr);
                        }
                        //2.后置答案
                        if ($question['answer_img'] != '' && in_array(2, $examPaperType)) {
                            $answerStr = '【答案】 ' . $question['answer_img'];
                            $section   = $this->addWordMsg($section, $answerStr);
                        }
                        //3.后置解析
                        if ($question['analyze_img'] != '' && in_array(3, $examPaperType)) {
                            $analyzeStr = '【解析】 ' . $question['analyze_img'];
                            $section    = $this->addWordMsg($section, $analyzeStr);
                        }
                        //4.后置来源
                        if (!empty($question['relevanceExamPapers']) && in_array(5, $examPaperType)) {
                            $tempSourceArr = [];
                            foreach ($question['relevanceExamPapers'] as $sourceMsg) {
                                $tempSourceArr[] = $sourceMsg['name'] . '第' . $sourceMsg['index'] . '题';
                            }
                            $sourceStr = '【来源】 ' . implode(',', $tempSourceArr);
                            $section   = $this->addWordMsg($section, $sourceStr);
                        }
                        $section->addTextBreak(1);
                    }
                    $typeKey++;
                }
                $partKey++;
            }
        }
        //file路径不存在则创建路径
        if (!is_dir($this->frame->conf['fileDir'])) {
            mkdir($this->frame->conf['fileDir'], 0777);
        }
        //系统文件路径
        $systemPath = $this->frame->conf['fileDir'] . $fileName . '_' . date('YmdH_i') . '.docx';
        //链接访问路径
        $urlPath = $this->frame->conf['urls']['file'] . $fileName . '_' . date('YmdH_i') . '.docx';
        $phpWord->save($systemPath, 'Word2007', false);
        if (!empty($this->imgArr)) {
            foreach ($this->imgArr as $itemPath) {
                @unlink($itemPath);
            }
        }
        return ['systemPath' => $systemPath, 'urlPath' => $urlPath];
    }

    /**
     * 题组导出word
     * 
     * @param $questions
     * @param array $groupType
     * @param string $fileName
     */
    public function exportGroupWord($questions, $groupType = [], $fileName = 'questionGroupWord')
    {
    	$questions = $this->processQuestions($questions);
        $phpWord = new PhpWord();
        //设置默认样式
        $phpWord->setDefaultFontName('宋体'); //字体
        $phpWord->setDefaultFontSize('五号'); //字号
        //链接全局基础样式
        $linkStyle = ['color' => '0000FF', 'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE];
        //标题全局基础样式
        $titleStyle = ['align' => 'center'];
        //试卷名字全局基础样式
        $examPaperNameStyle = ['color' => '006699', 'size' => 22, 'bold' => true];
        //题型全局基础样式
        $questionTypeStyle = ['color' => '006699', 'size' => 14, 'bold' => true];
        //答案标题样式
        $answerTitleStyle = ['size' => 14, 'bold' => true];
        //导入文字样式和段落样式.
        $phpWord->addLinkStyle('myLinkStyle', $linkStyle);
        $phpWord->addParagraphStyle('titleStyle', $titleStyle);
        $phpWord->addFontStyle('examPaperNameStyle', $examPaperNameStyle);
        $phpWord->addFontStyle('questionTypeStyle', $questionTypeStyle);
        $phpWord->addFontStyle('answerTitleStyle', $answerTitleStyle);
        //添加页面
        $section     = $phpWord->addSection();
        $projectType = empty($this->frame->conf['type']) ? 0 : $this->frame->conf['type'];
        if ($projectType == 0) {
            throw new $this->exception('项目类型错误，请检查配置文件！');
        }

        $j             = $i = 0;
        $classfyData[] = '其他';
        //清除路径'/'信息
        $fileName = str_replace('/', '', $fileName);
        $section->addText($fileName, 'examPaperNameStyle', 'titleStyle');

        $keyNum        = 0;
        $isPostpositio = in_array(4, $groupType);

        $allIds = array_keys($questions);
        $poArr  = [];
        do {
            $tempData = $usedIds = $materialArr = [];

            $exportData = array_values($questions);

            foreach ($questions as $item) {
                if (in_array($item['id'], $usedIds)) {
                    continue;
                }
                $tempData[] = $item;
                $usedIds[]  = $item['id'];
                if (!empty($item['materialInfo'])) {
                    $tmpIds = array();
                    if (!empty($item['materialInfo']['questionIds'])) foreach ($allIds as $questionId) {
                        if (in_array($questionId, $item['materialInfo']['questionIds'])) {
                            if (in_array($questionId, $usedIds)) {
                                continue;
                            }
                            $tempData[] = isset($questions[$questionId]) ? $questions[$questionId] : [];
                            $usedIds[]  = $questionId;
                        }
                    }
                }
            }
            $poArr = array_merge($poArr, $tempData);
            foreach ($tempData as $question) {
                //1.知识点
                if (!$isPostpositio && in_array(1, $groupType)) {
                    $knowledgeStr = '【知识点】 ';
                    $knowledgeStr .= isset($question['knowledgeInfo']['name']) ? $question['knowledgeInfo']['name'] : '';
                    $section->addText($knowledgeStr);
                }
                //2.题目材料
                if (!empty($question['materialInfo']) && !in_array($question['materialInfo']['id'], $materialArr)) {
                    $materialArr[] = $question['materialInfo']['id'];
                    $start         = $keyNum + 1;
                    $end           = $keyNum + count($question['materialInfo']['questionIds']);
                    $materialStr   = $start == $end ? '【材料】 根据以下材料回答第' . $start . '题<br>' : '【材料】 根据以下材料回答第' . $start . '-' . $end . '题<br>';
                    $materialStr   .= $question['materialInfo']['material_img'];
                    $section       = $this->addWordMsg($section, $materialStr);
                }
                //3.题目名称
                $tempIndex = $keyNum + 1;
                $section   = $this->addWordMsg($section, $tempIndex . '. ' . $question['matter_img']);
                //4.题目选项
                if (!empty($question['selections'])) {
                    foreach ($question['selections'] as $item) {
                        $section = $this->addWordMsg($section, $item['mark'] . '. ' . $item['value_img']);
                    }
                }
                $section->addTextBreak(1);
                //5.答案
                if (!$isPostpositio && $question['answer_img'] != '' && in_array(2, $groupType)) {
                    $answerStr = '【答案】 ' . $question['answer_img'];
                    $section   = $this->addWordMsg($section, $answerStr);
                }
                //6.解析
                if (!$isPostpositio && $question['analyze_img'] != '' && in_array(3, $groupType)) {
                    $analyzeStr = '【解析】 ' . $question['analyze_img'];
                    $section    = $this->addWordMsg($section, $analyzeStr);
                }
                //7.来源
                if (!$isPostpositio && !empty($question['relevanceExamPapers']) && in_array(5, $groupType)) {
                    $tempSourceArr = [];
                    foreach ($question['relevanceExamPapers'] as $sourceMsg) {
                        $tempSourceArr[] = $sourceMsg['name'] . '第' . $sourceMsg['index'] . '题';
                    }
                    $sourceStr = '【来源】 ' . implode(',', $tempSourceArr);
                    $section   = $this->addWordMsg($section, $sourceStr);
                }
                $section->addTextBreak(1);
                $keyNum++;
            }
            $i++;
            $j++;
        } while ($j < count($classfyData));

        if ($isPostpositio && count($groupType) > 2) {
            $postpositioStr = '';
            if (in_array(2, $groupType)) {
                $postpositioStr = !in_array(3, $groupType) ? '参考答案' : '参考答案与解析';
            }
            if (!empty($postpositioStr) && $projectType == 2) {
                $section->addText($postpositioStr, 'answerTitleStyle', 'titleStyle');
            }
            foreach ($poArr as $k => $question) {
                $tempIndex = $k + 1;
                $titleName = '第' . $tempIndex . '题';
                $section->addText($titleName);
                //1.后置知识点
                if (in_array(1, $groupType)) {
                    $knowledgeStr = '【知识点】 ';
                    $knowledgeStr .= isset($question['knowledgeInfo']['name']) ? $question['knowledgeInfo']['name'] : '';
                    $section->addText($knowledgeStr);
                }
                //2.后置答案
                if ($question['answer_img'] != '' && in_array(2, $groupType)) {
                    $answerStr = '【答案】 ' . $question['answer_img'];
                    $section   = $this->addWordMsg($section, $answerStr);
                }
                //3.后置解析
                if ($question['analyze_img'] != '' && in_array(3, $groupType)) {
                    $analyzeStr = '【解析】 ' . $question['analyze_img'];
                    $section    = $this->addWordMsg($section, $analyzeStr);
                }
                //4.后置来源
                if (!empty($question['relevanceExamPapers']) && in_array(5, $groupType)) {
                    $tempSourceArr = [];
                    foreach ($question['relevanceExamPapers'] as $sourceMsg) {
                        $tempSourceArr[] = $sourceMsg['name'] . '第' . $sourceMsg['index'] . '题';
                    }
                    $sourceStr = '【来源】 ' . implode(',', $tempSourceArr);
                    $section   = $this->addWordMsg($section, $sourceStr);
                }
                $section->addTextBreak(1);
            }
        }
        //file路径不存在则创建路径
        if (!is_dir($this->frame->conf['fileDir'])) {
            mkdir($this->frame->conf['fileDir'], 0777);
        }
        //系统文件路径
        $systemPath = $this->frame->conf['fileDir'] . $fileName . '_' . date('YmdH_i') . '.docx';
        //链接访问路径
        $urlPath = $this->frame->conf['urls']['file'] . $fileName . '_' . date('YmdH_i') . '.docx';
        $phpWord->save($systemPath, 'Word2007', false);
        if (!empty($this->imgArr)) {
            foreach ($this->imgArr as $itemPath) {
                @unlink($itemPath);
            }
        }
        return ['systemPath' => $systemPath, 'urlPath' => $urlPath];
    }

    /**
     * 题组导出把材料拆开word导出
     * @param $questions
     * @param array $groupType
     * @param string $fileName
     */
    public function exportMaterialWord($questions, $groupType = [], $fileName = 'questionGroupWord')
    {
    	$questions = $this->processQuestions($questions);
        $phpWord = new PhpWord();
        //设置默认样式
        $phpWord->setDefaultFontName('宋体'); //字体
        $phpWord->setDefaultFontSize('五号'); //字号
        //链接全局基础样式
        $linkStyle = ['color' => '0000FF', 'underline' => \PhpOffice\PhpWord\Style\Font::UNDERLINE_SINGLE];
        //标题全局基础样式
        $titleStyle = ['align' => 'center'];
        //试卷名字全局基础样式
        $examPaperNameStyle = ['color' => '006699', 'size' => 22, 'bold' => true];
        //题型全局基础样式
        $questionTypeStyle = ['color' => '006699', 'size' => 14, 'bold' => true];
        //答案标题样式
        $answerTitleStyle = ['size' => 14, 'bold' => true];
        //导入文字样式和段落样式.
        $phpWord->addLinkStyle('myLinkStyle', $linkStyle);
        $phpWord->addParagraphStyle('titleStyle', $titleStyle);
        $phpWord->addFontStyle('examPaperNameStyle', $examPaperNameStyle);
        $phpWord->addFontStyle('questionTypeStyle', $questionTypeStyle);
        $phpWord->addFontStyle('answerTitleStyle', $answerTitleStyle);
        // 文本标红
        $phpWord->addFontStyle('colorRedText', array(
            'color' => 'red'
        ));
        //添加页面
        $section     = $phpWord->addSection();
        $projectType = empty($this->frame->conf['type']) ? 0 : $this->frame->conf['type'];
        if ($projectType == 0) {
            throw new $this->exception('项目类型错误，请检查配置文件！');
        }
        //清除路径'/'信息
        $fileName = str_replace('/', '', $fileName);
        $section->addText($fileName, 'examPaperNameStyle', 'titleStyle');
        $isPostpositio = in_array(4, $groupType);
        $questions     = array_values($questions);
        foreach ($questions as $keyNum => $question) {
            //1.知识点
            if (!$isPostpositio && in_array(1, $groupType)) {
                $knowledgeStr = '【知识点】 ';
                $knowledgeStr .= isset($question['knowledgeInfo']['name']) ? $question['knowledgeInfo']['name'] : '';
                $section->addText($knowledgeStr);
            }

            //2.题目材料
            if (!empty($question['materialInfo'])) {
                $materialStr = '【材料】 ';
                $section->addText($materialStr);
                $materialStr = $question['materialInfo']['material_img'];
                $section     = $this->addWordMsg($section, $materialStr);
            }

            //3.题目名称
            $tempIndex = $keyNum + 1;

            $section   = $this->addWordMsg($section, $tempIndex . '. ' . $question['matter_img']);
            //4.题目选项
            if (!empty($question['selections'])) {
                foreach ($question['selections'] as $item) {
                    $section = $this->addWordMsg($section, $item['mark'] . '. ' . $item['value_img']);
                }
            }
            $section->addTextBreak(1);
            //5.答案
            if (!$isPostpositio && $question['answer_img'] != '' && in_array(2, $groupType)) {
                $answerStr = '【答案】 ' . $question['answer_img'];
                $section   = $this->addWordMsg($section, $answerStr);
            }
            //6.解析
            if (!$isPostpositio && $question['analyze_img'] != '' && in_array(3, $groupType)) {
                $analyzeStr = '【解析】 ' . $question['analyze_img'];
                $section    = $this->addWordMsg($section, $analyzeStr);
            }
            //7.来源
            if (!$isPostpositio && !empty($question['relevanceExamPapers']) && in_array(5, $groupType)) {
                $tempSourceArr = [];
                foreach ($question['relevanceExamPapers'] as $sourceMsg) {
                    $tempSourceArr[] = $sourceMsg['name'] . '第' . $sourceMsg['index'] . '题';
                }
                $sourceStr = '【来源】 ' . implode(',', $tempSourceArr);
                $section   = $this->addWordMsg($section, $sourceStr);
            }
            $section->addTextBreak(1);
        }

        if ($isPostpositio && count($groupType) > 2) {
            $postpositioStr = '';
            if (in_array(2, $groupType)) {
                $postpositioStr = !in_array(3, $groupType) ? '参考答案' : '参考答案与解析';
            }
            if (!empty($postpositioStr) && $projectType == 2) {
                $section->addText($postpositioStr, 'answerTitleStyle', 'titleStyle');
            }
            foreach ($questions as $k => $question) {
                $tempIndex = $k + 1;
                $titleName = '第' . $tempIndex . '题';
                $section->addText($titleName);
                //1.后置知识点
                if (in_array(1, $groupType)) {
                    $knowledgeStr = '【知识点】 ';
                    $knowledgeStr .= isset($question['knowledgeInfo']['name']) ? $question['knowledgeInfo']['name'] : '';
                    $section->addText($knowledgeStr);
                }
                //2.后置答案
                if ($question['answer_img'] != '' && in_array(2, $groupType)) {
                    $answerStr = '【答案】 ' . $question['answer_img'];
                    $section   = $this->addWordMsg($section, $answerStr);
                }
                //3.后置解析
                if ($question['analyze_img'] != '' && in_array(3, $groupType)) {
                    $analyzeStr = '【解析】 ' . $question['analyze_img'];
                    $section    = $this->addWordMsg($section, $analyzeStr);
                }
                //4.后置来源
                if (!empty($question['relevanceExamPapers']) && in_array(5, $groupType)) {
                    $tempSourceArr = [];
                    foreach ($question['relevanceExamPapers'] as $sourceMsg) {
                        $tempSourceArr[] = $sourceMsg['name'] . '第' . $sourceMsg['index'] . '题';
                    }
                    $sourceStr = '【来源】 ' . implode(',', $tempSourceArr);
                    $section   = $this->addWordMsg($section, $sourceStr);
                }
                $section->addTextBreak(1);
            }
        }
        //file路径不存在则创建路径
        if (!is_dir($this->frame->conf['fileDir'])) {
            mkdir($this->frame->conf['fileDir'], 0777);
        }
        //系统文件路径
        $systemPath = $this->frame->conf['fileDir'] . $fileName . '_' . date('YmdH_i') . '.docx';
        //链接访问路径
        $urlPath = $this->frame->conf['urls']['file'] . $fileName . '_' . date('YmdH_i') . '.docx';
        $phpWord->save($systemPath, 'Word2007', false);
        if (!empty($this->imgArr)) {
            foreach ($this->imgArr as $itemPath) {
                @unlink($itemPath);
            }
        }
        return ['systemPath' => $systemPath, 'urlPath' => $urlPath];
    }

    /**
     * 文本标红
     * 
     * @return mixed
     */
    private function addRed($text, $textRun)
    {
        static $officeSv;
        if (!$officeSv) {
            $officeSv = \service\Office::singleton();
        }
        $valueArr = $officeSv->replaceByTag($text, 'red'); // 根据红色标签分隔文本
        $text = $valueArr['text']; // 文本
        $reds = $valueArr['tags']; // 标红的文本
        if (empty($text)) {
            return $textRun;
        }
        $redTempArr = explode($officeSv::TEXT_TAG_RED, $text);
        if (empty(end($redTempArr))) {
            array_pop($redTempArr);
        }
        if (is_iteratable($redTempArr)) foreach ($redTempArr as $redTemp) {
            $redTemp = strip_tags($this->stripSpecialCode($redTemp));
            if (!empty($redTemp)) {
                $textRun->addText($redTemp);
            }
            if (!empty($reds)) {
                $redText = array_shift($reds);
                if (!empty($redText)) {
                    $redText = strip_tags($this->stripSpecialCode($redText));
                    $textRun->addText($redText, 'colorRedText');
                }
            }
        }
        return $textRun;
    }

    /**
     * Word内容文本转换
     * @param $section
     * @param $content
     * @return mixed
     */
    public function addWordMsg($section, $content)
    {
        //1.替换html语法换行或者空格等内容
        //$content = preg_replace('/&[a-z]{4};/', "", $content);
        $content = str_replace(['&emsp;', '&ensp;', '&nbsp;', '&thinsp;', '<br/>'], ['', '', '', '', "<br>"], $content);
        //2.word导出内容更新
        $textArr = explode('<br>', $content); // 后面未添加换行
/*        $reg     = '/<img.*?src="(.*?)".*?>?|<img.*?src=\'(.*?)\'.*?>?/is';*/
        $reg = '/<img\s+src=([\'"])(.*)\1/U';
        //设置最大word图片宽度
        $maxWidth = 350;

        foreach ($textArr as $text) {
            $textRun = $section->createTextRun('pStyle');
            preg_match_all($reg, $text, $matches);
            if (!empty($matches[2])) {
                //匹配出图片的宽高
                preg_match_all('/<img.*?width="(.*?)".*?>/', $text, $widthArr);
                preg_match_all('/<img.*?height="(.*?)".*?>/', $text, $heightArr);
                //获取所有的图片链接信息
                $imgUrlArr = array_values(array_filter($matches[2]));
                //文字信息拆分成数组       '/<img.*?src="(.*?)".*>?|<img.*?src=\'(.*?)\'.*>?/is';
                $replaceReg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>(<\/img>)?/i';
                $text       = preg_replace($replaceReg, '__IMAGE__', $text);
                $tempArr    = explode('__IMAGE__', $text);
                foreach ($tempArr as $k => $value) {
                    //添加word文字信息
                    if (!empty($value)) {
                        $textRun = $this->addRed($value, $textRun);
                    }
                    // 添加图片信息
                    if (!isset($imgUrlArr[$k])) {
                        continue;
                    }
                    //远端或者本地图片不存在跳出循环
                    $img = $imgUrlArr[$k];
                    @$urlContent = file_get_contents($img);
                    //远端图片不存在或者远端图片内容为空，则不进行图片处理
                    if ($urlContent === false || $urlContent === '') {
                        continue;
                    }
                    //图片文件存储路径
                    if (!is_dir($this->frame->conf['epubTmpDir'])) {
                        mkdir($this->frame->conf['epubTmpDir'], 0777);
                    }
                    $img      = strpos($img, '?') !== false ? substr($img, 0, strpos($img, '?')) : $img;
                    $pathInfo = pathinfo($img);
                    if ($pathInfo['extension'] == 'docx' || $pathInfo['extension'] == 'doc') {
                        continue;
                    }
                    $imgPath = $this->frame->conf['epubTmpDir'] . $pathInfo['filename'] . mt_rand(0, 9999999999) . '.' . $pathInfo['extension'];
                    file_put_contents($imgPath, $urlContent);
                    if (!is_file($imgPath)) {
                        throw new $this->exception('图片保存失败！');
                    }
                    //按照原图片样式的宽高比例，设置word图片的宽高
                    $imgWidth  = isset($widthArr[1][$k]) ? $widthArr[1][$k] : getimagesize($imgPath)[0];
                    $imgHeight = isset($heightArr[1][$k]) ? $heightArr[1][$k] : getimagesize($imgPath)[1];

                    $imgStyle = [
                        'width'  => $imgWidth >= $maxWidth ? $maxWidth : $imgWidth,
                        'height' => $imgWidth >= $maxWidth ? $imgHeight * $maxWidth / $imgWidth : $imgHeight,
                        //'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::END, // 控制是否居中
                    ];
                    $textRun->addImage($imgPath, $imgStyle);
                    $this->imgArr[] = $imgPath;
                }
            } else {
                $textRun = $this->addRed($text, $textRun);
            }
            //  $textRun->addTextBreak(1); // 换行
        }
        return $section;
    }

    /**
     * 导出ppt
     *
     * @param unknown $questions
     * @param string $paperName
     */
    public function exportPpt($questions, $paperName = '')
    {
    	$questions = $this->processQuestions($questions);
        //1.创建ppt对象
        $objPHPPowerPoint = new PhpPresentation();
        //2.设置属性
        $objPHPPowerPoint->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9, true);
        $objPHPPowerPoint->getDocumentProperties()->setCreator('PHPOffice')
            ->setLastModifiedBy('PHPPresentation Team')
            ->setTitle('Sample 02 Title')
            ->setSubject('Sample 02 Subject')
            ->setDescription('Sample 02 Description')
            ->setKeywords('office 2007 openxml libreoffice odt php')
            ->setCategory('Sample Category');

        //3.删除第一页(多页最好删除)
        $objPHPPowerPoint->removeSlideByIndex(0);

        //4.对题目重新进行排序
        $indexArr = array_column($questions, 'index');
        array_multisort($indexArr, SORT_ASC, $questions);
        $questions = array_values($questions);
        //5.创建幻灯片
        $pageSize = count($questions);
        //根据需求 调整for循环
        $imageArr = [];
        for ($i = 1; $i <= $pageSize; $i++) {
            $question = $questions[$i - 1];
            //创建幻灯片并添加到这个演示中
            $slide = $objPHPPowerPoint->createSlide();
            $shape = $slide->createRichTextShape()
                ->setHeight(400)
                ->setWidth(850)
                ->setOffsetX(85)
                ->setOffsetY(90);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            //1.题目来源
            if (!empty($question['relevanceExamPapers'])) {
                $tempSourceArr = [];
                foreach ($question['relevanceExamPapers'] as $sourceMsg) {
                    $tempSourceArr[] = $sourceMsg['name'] . '第' . $sourceMsg['index'] . '题';
                }
                $sourceStr = '【来源】 ' . implode(',', $tempSourceArr);
                $this->addPptMsg($slide, $shape, $sourceStr);
            }
            //题目材料
            if (!empty($question['materialInfo'])) {
                $materialResult = $this->addPptMsg($slide, $shape, '【材料】 ' . $question['materialInfo']['material_img']);
                $imageArr       = array_merge($imageArr, $materialResult['img']);
            }
            //2.题目名称
            $titleResult = $this->addPptMsg($slide, $shape, $i . '. ' . $question['matter_img']);
            $imageArr    = array_merge($imageArr, $titleResult['img']);
            //3.选项导出
            if (!empty($question['selections'])) {
                foreach ($question['selections'] as $selection) {
                    $selectionResult = $this->addPptMsg($slide, $shape, $selection['mark'] . '. ' . $selection['value_img']);
                    $imageArr        = array_merge($imageArr, $selectionResult['img']);
                }
            }
            //将知识点、标签、答案、解析写进备注当中
            $note          = new Note();
            $knowledgeName = (isset($question['knowledgeInfo']['name']) && $question['knowledgeInfo']['name'] != '') ? $question['knowledgeInfo']['name'] : '';
            //4.知识点导出
            $note->setParent($slide)->createRichTextShape()->createText('【知识点】' . $knowledgeName);
            //5.标签导出
            $tagStr = implode('、', array_column($question['tags'], 'name'));
            $note->setParent($slide)->createRichTextShape()->createText('【标签】' . $tagStr);
            //6.答案导出
            $answer       = isset($question['answer_img']) && $question['answer_img'] != '' ? $question['answer_img'] : '';
            $answerResult = $this->addPptNote($slide, $note, '【答案】' . $answer);
            $imageArr     = array_merge($imageArr, $answerResult['img']);
            //7.解析导出
            $analyze       = isset($question['analyze_img']) && $question['analyze_img'] != '' ? $question['analyze_img'] : '';
            $analyzeResult = $this->addPptNote($slide, $note, '【解析】' . $analyze);
            $imageArr      = array_merge($imageArr, $analyzeResult['img']);
            //设置备注
            $slide->setNote($note);
        }
        $oWriterPPTX = \PhpOffice\PhpPresentation\IOFactory::createWriter($objPHPPowerPoint, 'PowerPoint2007');
        //检查导出路径是否创建
        if (!is_dir($this->frame->conf['fileDir'])) {
            mkdir($this->frame->conf['fileDir'], '0777');
        }
        $fileName = $paperName . '_' . date('YmdH_i') . '.pptx';
        //清除路径'/'信息
        $fileName = str_replace('/', '', $fileName);
        //系统文件路径
        $systemPath = $this->frame->conf['fileDir'] . $fileName;
        //链接访问路径
        $urlPath = $this->frame->conf['urls']['file'] . $fileName;
        $oWriterPPTX->save($systemPath);
        if (!empty($imageArr)) {
            foreach ($imageArr as $img) {
                @unlink($img);
            }
        }
        return ['systemPath' => $systemPath, 'urlPath' => $urlPath];
    }

    /**
     * 题组导出PPT
     * @param $questions
     * @param string $groupName
     * @return array
     * @throws \Exception
     */
    public function exportGroupPpt($questions, $groupName = 'questionGroup')
    {
    	$questions = $this->processQuestions($questions);
        //1.创建ppt对象
        $objPHPPowerPoint = new PhpPresentation();
        //2.设置属性
        $objPHPPowerPoint->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9, true);
        $objPHPPowerPoint->getDocumentProperties()->setCreator('PHPOffice')
            ->setLastModifiedBy('PHPPresentation Team')
            ->setTitle('Sample 02 Title')
            ->setSubject('Sample 02 Subject')
            ->setDescription('Sample 02 Description')
            ->setKeywords('office 2007 openxml libreoffice odt php')
            ->setCategory('Sample Category');

        //3.删除第一页(多页最好删除)
        $objPHPPowerPoint->removeSlideByIndex(0);

        //4.对题目重新进行排序
        $indexArr = array_column($questions, 'index');
        array_multisort($indexArr, SORT_ASC, $questions);
        $questions = array_values($questions);
        //5.创建幻灯片
        $pageSize = count($questions);
        //根据需求 调整for循环
        $imageArr = [];
        for ($i = 1; $i <= $pageSize; $i++) {
            $question = $questions[$i - 1];
            //创建幻灯片并添加到这个演示中
            $slide = $objPHPPowerPoint->createSlide();
            $shape = $slide->createRichTextShape()
                ->setHeight(400)
                ->setWidth(850)
                ->setOffsetX(85)
                ->setOffsetY(90);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            //1.题目来源
            if (!empty($question['relevanceExamPapers'])) {
                $tempSourceArr = [];
                foreach ($question['relevanceExamPapers'] as $sourceMsg) {
                    $tempSourceArr[] = $sourceMsg['name'] . '第' . $sourceMsg['index'] . '题';
                }
                $sourceStr = '【来源】 ' . implode(',', $tempSourceArr);
                $this->addPptMsg($slide, $shape, $sourceStr);
            }
            //题目材料
            if (!empty($question['materialInfo'])) {
                $materialResult = $this->addPptMsg($slide, $shape, '【材料】 ' . $question['materialInfo']['material_img']);
                $imageArr       = array_merge($imageArr, $materialResult['img']);
            }
            //2.题目名称
            $titleResult = $this->addPptMsg($slide, $shape, $question['index'] . '. ' . $question['matter_img']);
            $imageArr    = array_merge($imageArr, $titleResult['img']);
            //3.选项导出
            if (!empty($question['selections'])) {
                foreach ($question['selections'] as $selection) {
                    $selectionResult = $this->addPptMsg($slide, $shape, $selection['mark'] . '. ' . $selection['value_img']);
                    $imageArr        = array_merge($imageArr, $selectionResult['img']);
                }
            }
            //将知识点、标签、答案、解析写进备注当中
            $note          = new Note();
            $knowledgeName = (isset($question['knowledgeInfo']['name']) && $question['knowledgeInfo']['name'] != '') ? $question['knowledgeInfo']['name'] : '';
            //4.知识点导出
            $note->setParent($slide)->createRichTextShape()->createText('【知识点】' . $knowledgeName);
            //5.标签导出
            $tagStr = implode('、', array_column($question['tags'], 'name'));
            $note->setParent($slide)->createRichTextShape()->createText('【标签】' . $tagStr);
            //6.答案导出
            $answer       = isset($question['answer_img']) && $question['answer_img'] != '' ? $question['answer_img'] : '';
            $answerResult = $this->addPptNote($slide, $note, '【答案】' . $answer);
            $imageArr     = array_merge($imageArr, $answerResult['img']);
            //7.解析导出
            $analyze       = isset($question['analyze_img']) && $question['analyze_img'] != '' ? $question['analyze_img'] : '';
            $analyzeResult = $this->addPptNote($slide, $note, '【解析】' . $analyze);
            $imageArr      = array_merge($imageArr, $analyzeResult['img']);
            //设置备注
            $slide->setNote($note);
        }
        $oWriterPPTX = \PhpOffice\PhpPresentation\IOFactory::createWriter($objPHPPowerPoint, 'PowerPoint2007');
        //检查导出路径是否创建
        if (!is_dir($this->frame->conf['fileDir'])) {
            mkdir($this->frame->conf['fileDir'], '0777');
        }
        $fileName = $groupName . '_' . date('YmdH_i') . '.pptx';
        //清除路径'/'信息
        $fileName = str_replace('/', '', $fileName);
        //系统文件路径
        $systemPath = $this->frame->conf['fileDir'] . $fileName;
        //链接访问路径
        $urlPath = $this->frame->conf['urls']['file'] . $fileName;
        $oWriterPPTX->save($systemPath);
        if (!empty($imageArr)) {
            foreach ($imageArr as $img) {
                @unlink($img);
            }
        }
        return ['systemPath' => $systemPath, 'urlPath' => $urlPath];
    }

    /**
     * 试卷模板导出
     * @param $questions
     * @param string $paperName
     * @return array
     * @throws \Exception
     */
    public function templatePpt($questions, $paperName = '')
    {
    	$questions = $this->processQuestions($questions);
        //1.创建ppt对象
        $objPHPPowerPoint = new PhpPresentation();
        //2.设置属性
        $objPHPPowerPoint->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9, true);
        $objPHPPowerPoint->getDocumentProperties()->setCreator('PHPOffice')
            ->setLastModifiedBy('PHPPresentation Team')
            ->setTitle('Sample 02 Title')
            ->setSubject('Sample 02 Subject')
            ->setDescription('Sample 02 Description')
            ->setKeywords('office 2007 openxml libreoffice odt php')
            ->setCategory('Sample Category');

        //3.删除第一页(多页最好删除)
        $objPHPPowerPoint->removeSlideByIndex(0);

        //4.对题目重新进行排序
        $indexArr = array_column($questions, 'index');
        array_multisort($indexArr, SORT_ASC, $questions);
        $questions = array_values($questions);
        //5.创建幻灯片
        $pageSize = count($questions) + 1;
        //根据需求 调整for循环
        $imageArr = [];
        for ($i = 0; $i <= $pageSize; $i++) {
            //创建幻灯片并添加到这个演示中
            $slide = $objPHPPowerPoint->createSlide();
            //log展示
            $shape = $slide->createDrawingShape();
            $shape->setName('PHPPresentation logo')
                ->setDescription('PHPPresentation logo')
                ->setPath('../cache/17logo.png')
                ->setHeight(45)
                ->setOffsetX(30)
                ->setOffsetY(30);
            $shape->getShadow()->setVisible(true)
                ->setDirection(45)
                ->setDistance(10);
            //处理试卷名称和主讲人
            if ($i == 0) {
                //ppt的第一页展示试卷名称和主讲
                $shape = $slide->createRichTextShape()
                    ->setHeight(160)
                    ->setWidth(800)
                    ->setOffsetX(85)
                    ->setOffsetY(90);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fontStyle = [
                    'style' => '思源黑体 CN Medium', //字体
                    'color' => '#000', //颜色
                    'size'  => '49', //字的大小
                    'bold'  => false, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, $paperName, $fontStyle);
                //ppt的第一页展示试卷名称和主讲
                $shape             = $slide->createRichTextShape()
                    ->setHeight(80)
                    ->setWidth(600)
                    ->setOffsetX(85)
                    ->setOffsetY(300);
                $fontStyle['size'] = 14;
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $this->addPptMsg($slide, $shape, '主讲人：', $fontStyle);
                continue;
            }
            //处理末页内容
            if ($i == $pageSize) {
                //ppt的第一页展示试卷名称和主讲
                $shape = $slide->createRichTextShape()
                    ->setHeight(60)
                    ->setWidth(500)
                    ->setOffsetX(220)
                    ->setOffsetY(110);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fontStyle = [
                    'style' => 'Heiti SC Medium', //字体
                    'color' => '#000', //颜色
                    'size'  => '40', //字的大小
                    'bold'  => false, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, '学习就业，17搞定', $fontStyle);
                $shape = $slide->createRichTextShape()
                    ->setHeight(40)
                    ->setWidth(500)
                    ->setOffsetX(220)
                    ->setOffsetY(180);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fontStyle = [
                    'style' => 'Heiti SC Light', //字体
                    'color' => '#000', //颜色
                    'size'  => '20', //字的大小
                    'bold'  => false, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, '关注官方微信，学干货领福利', $fontStyle);
                $shape = $slide->createDrawingShape();
                $shape->setName('PHPPresentation logo')
                    ->setDescription('PHPPresentation logo')
                    ->setPath('../cache/17qrcode.jpg')
                    ->setHeight(180)
                    ->setWidth(180)
                    ->setOffsetX(350)
                    ->setOffsetY(220);
                break;
            }
            //ppt的题序展示
            $shape = $slide->createRichTextShape()
                ->setHeight(40)
                ->setWidth(860)
                ->setOffsetX(45)
                ->setOffsetY(100);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $fontStyle = [
                'style' => '思源黑体 CN Medium', //字体
                'color' => '#000', //颜色
                'size'  => '24', //字的大小
                'bold'  => false, //字体是否加粗
            ];
            $this->addPptMsg($slide, $shape, '第' . $i . '题', $fontStyle);

            $question = $questions[$i - 1];
            //1.题型
            if (isset($question['typeInfo']) && !empty($question['typeInfo'])) {
                $shape = $slide->createRichTextShape()
                    ->setHeight(80)
                    ->setWidth(860)
                    ->setOffsetX(45)
                    ->setOffsetY(15);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fontStyle = [
                    'style' => '思源黑体 CN Medium', //字体
                    'color' => 'FF4672A8', //颜色
                    'size'  => '36', //字的大小
                    'bold'  => true, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, $question['typeInfo']['name'], $fontStyle);
            }
            $shape = $slide->createRichTextShape()
                ->setHeight(400)
                ->setWidth(860)
                ->setOffsetX(45)
                ->setOffsetY(140);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $fontStyle = [
                'style' => '思源宋体 CN', //字体
                'color' => '#000', //颜色
                'size'  => '24', //字的大小
                'bold'  => false, //字体是否加粗
            ];
            //题目材料
            if (!empty($question['materialInfo'])) {
                $materialResult = $this->addPptMsg($slide, $shape, $question['materialInfo']['material_img'], $fontStyle);
                $imageArr       = array_merge($imageArr, $materialResult['img']);
            }
            //2.题目名称
            $titleResult = $this->addPptMsg($slide, $shape, $i . '.' . $question['matter_img'], $fontStyle);
            $imageArr    = array_merge($imageArr, $titleResult['img']);
            //3.选项导出
            if (!empty($question['selections'])) {
                foreach ($question['selections'] as $selection) {
                    $selectionResult = $this->addPptMsg($slide, $shape, $selection['mark'] . '. ' . $selection['value_img'], $fontStyle);
                    $imageArr        = array_merge($imageArr, $selectionResult['img']);
                }
            }
            //将知识点、标签、答案、解析写进备注当中
            $note = new Note();
            //6.答案导出
            $answer       = isset($question['answer_img']) && $question['answer_img'] != '' ? $question['answer_img'] : '';
            $answerResult = $this->addPptNote($slide, $note, $i . '.' . $answer);
            $imageArr     = array_merge($imageArr, $answerResult['img']);
            //7.解析导出
            $analyze       = isset($question['analyze_img']) && $question['analyze_img'] != '' ? $question['analyze_img'] : '';
            $analyzeResult = $this->addPptNote($slide, $note, $analyze);
            $imageArr      = array_merge($imageArr, $analyzeResult['img']);
            //设置备注
            $slide->setNote($note);
        }
        $oWriterPPTX = \PhpOffice\PhpPresentation\IOFactory::createWriter($objPHPPowerPoint, 'PowerPoint2007');
        //检查导出路径是否创建
        if (!is_dir($this->frame->conf['fileDir'])) {
            mkdir($this->frame->conf['fileDir'], '0777');
        }
        $fileName = $paperName . '_' . date('YmdH_i') . '.pptx';
        //清除路径'/'信息
        $fileName = str_replace('/', '', $fileName);
        //系统文件路径
        $systemPath = $this->frame->conf['fileDir'] . $fileName;
        //链接访问路径
        $urlPath = $this->frame->conf['urls']['file'] . $fileName;
        $oWriterPPTX->save($systemPath);
        if (!empty($imageArr)) {
            foreach ($imageArr as $img) {
                @unlink($img);
            }
        }
        return ['systemPath' => $systemPath, 'urlPath' => $urlPath];
    }

    /**
     * 题组导出PPT
     * @param $questions
     * @param string $groupName
     * @return array
     * @throws \Exception
     */
    public function doublePpt($questions, $groupName = 'questionGroup')
    {
    	$questions = $this->processQuestions($questions);
        //1.创建ppt对象
        $objPHPPowerPoint = new PhpPresentation();
        //2.设置属性
        $objPHPPowerPoint->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9, true);
        $objPHPPowerPoint->getDocumentProperties()->setCreator('PHPOffice')
            ->setLastModifiedBy('PHPPresentation Team')
            ->setTitle('Sample 02 Title')
            ->setSubject('Sample 02 Subject')
            ->setDescription('Sample 02 Description')
            ->setKeywords('office 2007 openxml libreoffice odt php')
            ->setCategory('Sample Category');

        //3.删除第一页(多页最好删除)
        $objPHPPowerPoint->removeSlideByIndex(0);

        //4.对题目重新进行排序
        $indexArr = array_column($questions, 'index');
        array_multisort($indexArr, SORT_ASC, $questions);
        $questions = array_values($questions);
        //5.创建幻灯片
        $pageSize = count($questions);
        //根据需求 调整for循环
        $imageArr = [];
        for ($i = 1; $i <= $pageSize; $i++) {
            $question = $questions[$i - 1];
            //创建幻灯片并添加到这个演示中
            $slide = $objPHPPowerPoint->createSlide();
            $shape = $slide->createRichTextShape()
                ->setHeight(400)
                ->setWidth(850)
                ->setOffsetX(85)
                ->setOffsetY(90);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            //1.题目材料
            if (!empty($question['materialInfo'])) {
                $materialResult = $this->addPptMsg($slide, $shape, '【材料】 ' . $question['materialInfo']['material_img']);
                $imageArr       = array_merge($imageArr, $materialResult['img']);
            }
            //2.题目名称
            $titleResult = $this->addPptMsg($slide, $shape, $question['index'] . '. ' . $question['matter_img']);
            $imageArr    = array_merge($imageArr, $titleResult['img']);
            //3.选项导出
            if (!empty($question['selections'])) {
                foreach ($question['selections'] as $selection) {
                    $selectionResult = $this->addPptMsg($slide, $shape, $selection['mark'] . '. ' . $selection['value_img']);
                    $imageArr        = array_merge($imageArr, $selectionResult['img']);
                }
            }

            $slide = $objPHPPowerPoint->createSlide();
            $shape = $slide->createRichTextShape()
                ->setHeight(400)
                ->setWidth(850)
                ->setOffsetX(85)
                ->setOffsetY(90);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $this->addPptMsg($slide, $shape, '【第' . $question['index'] . '题解析】 ');
            $this->addPptMsg($slide, $shape, $question['analyze_img'], ['color' => 'FF0000',]);
        }
        $oWriterPPTX = \PhpOffice\PhpPresentation\IOFactory::createWriter($objPHPPowerPoint, 'PowerPoint2007');
        //检查导出路径是否创建
        if (!is_dir($this->frame->conf['fileDir'])) {
            mkdir($this->frame->conf['fileDir'], '0777');
        }
        $fileName = $groupName . '_' . date('YmdH_i') . '.pptx';
        //清除路径'/'信息
        $fileName = str_replace('/', '', $fileName);
        //系统文件路径
        $systemPath = $this->frame->conf['fileDir'] . $fileName;
        //链接访问路径
        $urlPath = $this->frame->conf['urls']['file'] . $fileName;
        $oWriterPPTX->save($systemPath);
        if (!empty($imageArr)) {
            foreach ($imageArr as $img) {
                @unlink($img);
            }
        }
        return ['systemPath' => $systemPath, 'urlPath' => $urlPath];
    }

    /**
     * 题组导出的题目关联真题试卷的年份和地区信息
     * @param $questions
     * @return array
     * @throws \Exception
     */
    public function LinksExamPaper($questions, $paperName)
    {
    	$questions = $this->processQuestions($questions);
        //1.创建ppt对象
        $objPHPPowerPoint = new PhpPresentation();
        //2.设置属性
        $objPHPPowerPoint->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9, true);
        $objPHPPowerPoint->getDocumentProperties()->setCreator('PHPOffice')
            ->setLastModifiedBy('PHPPresentation Team')
            ->setTitle('Sample 02 Title')
            ->setSubject('Sample 02 Subject')
            ->setDescription('Sample 02 Description')
            ->setKeywords('office 2007 openxml libreoffice odt php')
            ->setCategory('Sample Category');

        //3.删除第一页(多页最好删除)
        $objPHPPowerPoint->removeSlideByIndex(0);

        //4.对题目重新进行排序
        $indexArr = array_column($questions, 'index');
        array_multisort($indexArr, SORT_ASC, $questions);
        $questions = array_values($questions);
        //5.创建幻灯片
        $pageSize = count($questions) + 1;
        //根据需求 调整for循环
        $imageArr = [];
        for ($i = 0; $i <= $pageSize; $i++) {
            //创建幻灯片并添加到这个演示中
            $slide = $objPHPPowerPoint->createSlide();
            //处理试卷名称和主讲人
            if ($i == 0) {
                //添加一个图片
                $shape = $slide->createDrawingShape();
                $shape->setName('PHPPresentation logo')
                    ->setDescription('PHPPresentation logo')
                    ->setPath('../cache/gkppt01.png')
                    ->setWidth('960')
                    ->setOffsetX(0)
                    ->setOffsetY(0);
                $shape = $slide->createRichTextShape()
                    ->setHeight(200)
                    ->setWidth(950)
                    ->setOffsetX(40)
                    ->setOffsetY(100);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $fontStyle = [
                    'style' => '黑体 (正文)', //字体
                    'color' => 'FFFFFF', //颜色
                    'size'  => '62', //字的大小
                    'bold'  => false, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, $paperName, $fontStyle);
                $shape = $slide->createRichTextShape()
                    ->setHeight(100)
                    ->setWidth(200)
                    ->setOffsetX(40)
                    ->setOffsetY(400);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $fontStyle = [
                    'style' => '黑体 (正文)', //字体
                    'color' => 'FFFFFF', //颜色
                    'size'  => '30', //字的大小
                    'bold'  => false, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, '主讲：', $fontStyle);
                continue;
            }
            //处理末页内容
            if ($i == $pageSize) {
                $shape = $slide->createDrawingShape();
                $shape->setName('PHPPresentation logo')
                    ->setDescription('PHPPresentation logo')
                    ->setPath('../cache/gkppt02.png')
                    ->setHeight(70)
                    ->setWidth(380)
                    ->setOffsetX(300)
                    ->setOffsetY(180);
                $shape = $slide->createRichTextShape()
                    ->setHeight(60)
                    ->setWidth(500)
                    ->setOffsetX(240)
                    ->setOffsetY(300);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fontStyle = [
                    'style' => '黑体 (正文)', //字体
                    'color' => '#000', //颜色
                    'size'  => '36', //字的大小
                    'bold'  => false, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, '一起公考 一起上岸', $fontStyle);
                break;
            }
            //题目背景图片
            $shape = $slide->createDrawingShape();
            $shape->setName('PHPPresentation logo')
                ->setDescription('PHPPresentation logo')
                ->setPath('../cache/gkppt03.png')
                ->setHeight(47)
                ->setWidth(260)
                ->setOffsetX(700)
                ->setOffsetY(487);
            //ppt的题序展示
            $shape = $slide->createRichTextShape()
                ->setHeight(40)
                ->setWidth(860)
                ->setOffsetX(45)
                ->setOffsetY(20);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $fontStyle     = [
                'style' => '黑体 (正文)', //字体
                'color' => '#000', //颜色
                'size'  => '25', //字的大小
                'bold'  => true, //字体是否加粗
            ];
            $questionIndex = $i - 1;
            $title         = $i >= 10 ? '例题' . $i : '例题0' . $i;
            $this->addPptMsg($slide, $shape, $title, $fontStyle);
            $question = $questions[$questionIndex];

            $shape = $slide->createRichTextShape()
                ->setHeight(400)
                ->setWidth(860)
                ->setOffsetX(25)
                ->setOffsetY(80);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $fontStyle = [
                'style' => 'Franklin Gothic Book (正文)', //字体
                'color' => '#000', //颜色
                'size'  => '16', //字的大小
                'bold'  => false, //字体是否加粗
            ];
            //题目材料
            if (!empty($question['materialInfo'])) {
                $materialResult = $this->addPptMsg($slide, $shape, $question['materialInfo']['material_img'], $fontStyle);
                $imageArr       = array_merge($imageArr, $materialResult['img']);
            }
            //1.题干
            $matter      = !empty($question['examPaperInfo']) ? '（' . $question['examPaperInfo']['year'] . ' ' . $question['examPaperInfo']['area'] . '） ' . $question['matter_img'] : $question['matter_img'];
            $titleResult = $this->addPptMsg($slide, $shape, $matter, $fontStyle);
            $imageArr    = array_merge($imageArr, $titleResult['img']);
            //2.选项
            if (!empty($question['selections'])) {
                foreach ($question['selections'] as $selection) {
                    $selectionResult = $this->addPptMsg($slide, $shape, $selection['mark'] . '. ' . $selection['value_img'], $fontStyle);
                    $imageArr        = array_merge($imageArr, $selectionResult['img']);
                }
            }
            //将答案、解析写进备注当中
            $note = new Note();
            //3.答案导出
            $answer       = isset($question['answer_img']) && $question['answer_img'] != '' ? $question['answer_img'] : '';
            $answerResult = $this->addPptNote($slide, $note, '【答案】：' . $answer);
            $imageArr     = array_merge($imageArr, $answerResult['img']);
            //4.解析导出
            $analyze       = isset($question['analyze_img']) && $question['analyze_img'] != '' ? '【解析】：' . $question['analyze_img'] : '';
            $analyzeResult = $this->addPptNote($slide, $note, $analyze);
            $imageArr      = array_merge($imageArr, $analyzeResult['img']);
            //设置备注
            $slide->setNote($note);
        }
        $oWriterPPTX = \PhpOffice\PhpPresentation\IOFactory::createWriter($objPHPPowerPoint, 'PowerPoint2007');
        //检查导出路径是否创建
        if (!is_dir($this->frame->conf['fileDir'])) {
            mkdir($this->frame->conf['fileDir'], '0777');
        }
        $fileName = $paperName . '_' . date('YmdH_i') . '.pptx';
        //清除路径'/'信息
        $fileName = str_replace('/', '', $fileName);
        //系统文件路径
        $systemPath = $this->frame->conf['fileDir'] . $fileName;
        //链接访问路径
        $urlPath = $this->frame->conf['urls']['file'] . $fileName;
        $oWriterPPTX->save($systemPath);
        if (!empty($imageArr)) {
            foreach ($imageArr as $img) {
                @unlink($img);
            }
        }
        return ['systemPath' => $systemPath, 'urlPath' => $urlPath];
    }

    /**
     * ppt答案标红模板导出
     * @param $questions        题目信息
     * @param string $paperName 试卷名称
     * @return array            返回结果
     * @throws \Exception       异常
     */
    public function exportRedAnswerPpt($questions, $paperName = '')
    {
    	$questions = $this->processQuestions($questions);
        //1.创建ppt对象
        $objPHPPowerPoint = new PhpPresentation();
        //2.设置属性
        $objPHPPowerPoint->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9, true);
        $objPHPPowerPoint->getDocumentProperties()->setCreator('PHPOffice')
            ->setLastModifiedBy('PHPPresentation Team')
            ->setTitle('Sample 02 Title')
            ->setSubject('Sample 02 Subject')
            ->setDescription('Sample 02 Description')
            ->setKeywords('office 2007 openxml libreoffice odt php')
            ->setCategory('Sample Category');

        //3.删除第一页(多页最好删除)
        $objPHPPowerPoint->removeSlideByIndex(0);

        //4.对题目重新进行排序
        $indexArr = array_column($questions, 'index');
        array_multisort($indexArr, SORT_ASC, $questions);
        $questions = array_values($questions);
        //5.创建幻灯片
        $pageSize = count($questions) + 1;
        //根据需求 调整for循环
        $imageArr = [];
        for ($i = 0; $i <= $pageSize; $i++) {
            //创建幻灯片并添加到这个演示中
            $slide = $objPHPPowerPoint->createSlide();
            //log展示
            $shape = $slide->createDrawingShape();
            $shape->setName('PHPPresentation logo')
                ->setDescription('PHPPresentation logo')
                ->setPath('../cache/17logo.png')
                ->setHeight(45)
                ->setOffsetX(30)
                ->setOffsetY(30);
            $shape->getShadow()->setVisible(true)
                ->setDirection(45)
                ->setDistance(10);
            //处理试卷名称和主讲人
            if ($i == 0) {
                //ppt的第一页展示试卷名称和主讲
                $shape = $slide->createRichTextShape()
                    ->setHeight(160)
                    ->setWidth(800)
                    ->setOffsetX(85)
                    ->setOffsetY(90);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fontStyle = [
                    'style' => '思源黑体 CN Medium', //字体
                    'color' => '#000', //颜色
                    'size'  => '49', //字的大小
                    'bold'  => false, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, $paperName, $fontStyle);
                //ppt的第一页展示试卷名称和主讲
                $shape             = $slide->createRichTextShape()
                    ->setHeight(80)
                    ->setWidth(600)
                    ->setOffsetX(85)
                    ->setOffsetY(300);
                $fontStyle['size'] = 14;
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $this->addPptMsg($slide, $shape, '主讲人：', $fontStyle);
                continue;
            }
            //处理末页内容
            if ($i == $pageSize) {
                //ppt的第一页展示试卷名称和主讲
                $shape = $slide->createRichTextShape()
                    ->setHeight(60)
                    ->setWidth(500)
                    ->setOffsetX(220)
                    ->setOffsetY(110);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fontStyle = [
                    'style' => 'Heiti SC Medium', //字体
                    'color' => '#000', //颜色
                    'size'  => '40', //字的大小
                    'bold'  => false, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, '学习就业，17搞定', $fontStyle);
                $shape = $slide->createRichTextShape()
                    ->setHeight(40)
                    ->setWidth(500)
                    ->setOffsetX(220)
                    ->setOffsetY(180);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fontStyle = [
                    'style' => 'Heiti SC Light', //字体
                    'color' => '#000', //颜色
                    'size'  => '20', //字的大小
                    'bold'  => false, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, '关注官方微信，学干货领福利', $fontStyle);
                $shape = $slide->createDrawingShape();
                $shape->setName('PHPPresentation logo')
                    ->setDescription('PHPPresentation logo')
                    ->setPath('../cache/17qrcode.jpg')
                    ->setHeight(180)
                    ->setWidth(180)
                    ->setOffsetX(350)
                    ->setOffsetY(220);
                break;
            }
            $question = $questions[$i - 1];
            //1.题型
            if (isset($question['typeInfo']) && !empty($question['typeInfo'])) {
                $shape = $slide->createRichTextShape()
                    ->setHeight(80)
                    ->setWidth(860)
                    ->setOffsetX(45)
                    ->setOffsetY(80);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fontStyle = [
                    'style' => '思源黑体 CN Medium', //字体
                    'color' => 'FF4672A8', //颜色
                    'size'  => '30', //字的大小
                    'bold'  => true, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, $question['typeInfo']['name'], $fontStyle);
            }
            $shape = $slide->createRichTextShape()
                ->setHeight(400)
                ->setWidth(860)
                ->setOffsetX(45)
                ->setOffsetY(140);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $fontStyle = [
                'style' => '思源宋体 CN', //字体
                'color' => '#000', //颜色
                'size'  => '24', //字的大小
                'bold'  => false, //字体是否加粗
            ];
            //题目材料
            if (!empty($question['materialInfo'])) {
                $materialResult = $this->addPptMsg($slide, $shape, $question['materialInfo']['material_img'], $fontStyle);
                $imageArr       = array_merge($imageArr, $materialResult['img']);
            }
            //2.题目名称
            $titleResult = $this->addPptMsg($slide, $shape, $i . '.' . $question['matter_img'], $fontStyle);
            $imageArr    = array_merge($imageArr, $titleResult['img']);
            //3.选项导出
            if (!empty($question['selections'])) {
                foreach ($question['selections'] as $selection) {
                    $selectionResult = $this->addPptMsg($slide, $shape, $selection['mark'] . '. ' . $selection['value_img'], $fontStyle);
                    $imageArr        = array_merge($imageArr, $selectionResult['img']);
                }
            } else {
                //将知识点、标签、答案、解析写进备注当中
                $note = new Note();
                //6.答案导出
                $answer       = isset($question['answer_img']) && $question['answer_img'] != '' ? $question['answer_img'] : '';
                $answerResult = $this->addPptNote($slide, $note, $i . '.' . $answer);
                $imageArr     = array_merge($imageArr, $answerResult['img']);
                //7.解析导出
                $analyze       = isset($question['analyze_img']) && $question['analyze_img'] != '' ? $question['analyze_img'] : '';
                $analyzeResult = $this->addPptNote($slide, $note, $analyze);
                $imageArr      = array_merge($imageArr, $analyzeResult['img']);
                //设置备注
                $slide->setNote($note);
                continue;
            }
            //创建幻灯片并添加到这个演示中
            $slide = $objPHPPowerPoint->createSlide();
            //log展示
            $shape = $slide->createDrawingShape();
            $shape->setName('PHPPresentation logo')
                ->setDescription('PHPPresentation logo')
                ->setPath('../cache/17logo.png')
                ->setHeight(45)
                ->setOffsetX(30)
                ->setOffsetY(30);
            $shape->getShadow()->setVisible(true)
                ->setDirection(45)
                ->setDistance(10);
            //1.题型
            if (isset($question['typeInfo']) && !empty($question['typeInfo'])) {
                $shape = $slide->createRichTextShape()
                    ->setHeight(80)
                    ->setWidth(860)
                    ->setOffsetX(45)
                    ->setOffsetY(80);
                $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fontStyle = [
                    'style' => '思源黑体 CN Medium', //字体
                    'color' => 'FF4672A8', //颜色
                    'size'  => '30', //字的大小
                    'bold'  => true, //字体是否加粗
                ];
                $this->addPptMsg($slide, $shape, $question['typeInfo']['name'], $fontStyle);
            }
            $shape = $slide->createRichTextShape()
                ->setHeight(400)
                ->setWidth(860)
                ->setOffsetX(45)
                ->setOffsetY(140);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $fontStyle = [
                'style' => '思源宋体 CN', //字体
                'color' => '#000', //颜色
                'size'  => '24', //字的大小
                'bold'  => false, //字体是否加粗
            ];
            //题目材料
            if (!empty($question['materialInfo'])) {
                $materialResult = $this->addPptMsg($slide, $shape, $question['materialInfo']['material_img'], $fontStyle);
                $imageArr       = array_merge($imageArr, $materialResult['img']);
            }
            //2.题目名称
            $titleResult = $this->addPptMsg($slide, $shape, $i . '.' . $question['matter_img'], $fontStyle);
            $imageArr    = array_merge($imageArr, $titleResult['img']);
            //3.选项导出
            if (!empty($question['selections'])) {
                $answer = $question['answer_img'];
                foreach ($question['selections'] as $selection) {
                    if (strpos($answer, $selection['mark']) !== false) {
                        $fontStyle = [
                            'style' => '思源宋体 CN', //字体
                            'color' => 'FF0000', //颜色
                            'size'  => '24', //字的大小
                            'bold'  => false, //字体是否加粗
                        ];
                        $this->addPptMsg($slide, $shape, $selection['mark'] . '. ' . $selection['value_img'], $fontStyle);
                    } else {
                        $fontStyle = [
                            'style' => '思源宋体 CN', //字体
                            'color' => '#000', //颜色
                            'size'  => '24', //字的大小
                            'bold'  => false, //字体是否加粗
                        ];
                        $this->addPptMsg($slide, $shape, $selection['mark'] . '. ' . $selection['value_img'], $fontStyle);
                    }
                }
            }
            //将知识点、标签、答案、解析写进备注当中
            $note = new Note();
            //6.答案导出
            $answer       = isset($question['answer_img']) && $question['answer_img'] != '' ? $question['answer_img'] : '';
            $answerResult = $this->addPptNote($slide, $note, $i . '.' . $answer);
            $imageArr     = array_merge($imageArr, $answerResult['img']);
            //7.解析导出
            $analyze       = isset($question['analyze_img']) && $question['analyze_img'] != '' ? $question['analyze_img'] : '';
            $analyzeResult = $this->addPptNote($slide, $note, $analyze);
            $imageArr      = array_merge($imageArr, $analyzeResult['img']);
            //设置备注
            $slide->setNote($note);
        }
        $oWriterPPTX = \PhpOffice\PhpPresentation\IOFactory::createWriter($objPHPPowerPoint, 'PowerPoint2007');
        //检查导出路径是否创建
        if (!is_dir($this->frame->conf['fileDir'])) {
            mkdir($this->frame->conf['fileDir'], '0777');
        }
        $fileName = $paperName . '_' . date('YmdH_i') . '.pptx';
        //清除路径'/'信息
        $fileName = str_replace('/', '', $fileName);
        //系统文件路径
        $systemPath = $this->frame->conf['fileDir'] . $fileName;
        //链接访问路径
        $urlPath = $this->frame->conf['urls']['file'] . $fileName;
        $oWriterPPTX->save($systemPath);
        if (!empty($imageArr)) {
            foreach ($imageArr as $img) {
                @unlink($img);
            }
        }
        return ['systemPath' => $systemPath, 'urlPath' => $urlPath];
    }

    /**
     * 导出PPT内容转换
     * @param $slide        ppt对象
     * @param $shape        ppt对象
     * @param $content      内容ppt内容
     * @param $fontStyle    字体样式
     * @return array
     * @throws \Exception
     */
    public function addPptMsg($slide, $shape, $content = '', $fontStyle = [])
    {
        if (!is_object($slide) || !is_object($shape)) {
            throw new $this->exception('参数错误！');
        }
        //1.把所有的换行符通过文档形式输出
        $content = str_replace('<br/>', "<br>", $content);
        //2.去掉所有的img标签，添加text文本内容
        $textString = preg_replace('/<\/?img.*?>/', '', $content);
        $textArr    = explode('<br>', $textString);
        $style      = isset($fontStyle['style']) ? $fontStyle['style'] : '黑体'; //字体
        $color      = isset($fontStyle['color']) ? $fontStyle['color'] : '#000'; //字的颜色
        $size       = isset($fontStyle['size']) ? $fontStyle['size'] : '14';
        $bold       = isset($fontStyle['bold']) ? $fontStyle['bold'] : true;
        foreach ($textArr as $value) {
            if (empty($value)) {
                continue;
            }
            $value = $this->stripSpecialCode(strip_tags($value));
            if (strpos($value, 'http://') === 0) {
                $textRun = $shape->createTextRun($value);
                $textRun->setHyperlink(new Hyperlink($value));
                $textRun->getFont()->setBold($bold)
                    ->setSize($size)->setName($style)
                    ->setColor(new Color($color));
            } else {
                $textRun = $shape->createTextRun($value);
                $textRun->getFont()
                    ->setColor(new Color($color)) //颜色
                    ->setBold($bold) //加粗
                    ->setSize($size) //字号
                    ->setName($style); //字体
            }
            $shape->createBreak();
        }
        //3.获取所有的img标签的内容,并且把图片信息进行加载
        $reg = '/<img.*?src="(.*?)".*?>|<img.*?src=\'(.*?)\'.*?>/is';
        preg_match_all($reg, $content, $matches);
        $imgArr = [];
        if (!empty($matches[0])) {
            //匹配内容整合，并且去空
            $imgUrlArr = array_filter(array_merge($matches[1], $matches[2]));
            foreach ($imgUrlArr as $img) {
                //有些图片会带有问号链接，把问号链接以后的内容给去掉(例如:http://tiku.huatu.com/cdn/pandora/img/890450b2-4d52-4cf5-a31f-719044087151..png?imageView2/0/w/441/format/jpg)
                if (substr($img, 0, 4) == 'http' && strpos($img, '?') !== false) {
                    $img = substr($img, 0, strpos($img, '?'));
                }
                @$urlContent = file_get_contents($img);
                if ($urlContent === false) {
                    continue;
                }
                //图片文件存储路径
                if (!is_dir($this->frame->conf['epubTmpDir'])) {
                    mkdir($this->frame->conf['epubTmpDir'], 0777);
                }
                $pathInfo = pathinfo($img);
                $imgPath  = $this->frame->conf['epubTmpDir'] . $pathInfo['filename'] . mt_rand(0, 9999999999) . '.' . $pathInfo['extension'];
                file_put_contents($imgPath, $urlContent);
                $imgSlide = $slide->createDrawingShape()
                    ->setName($imgPath)->setDescription($imgPath)
                    ->setPath($imgPath)->setWidthAndHeight(350, 150);
                $imgSlide->getShadow()->setVisible(false);
                $imgArr[] = $imgPath;
            }
        }
        return ['img' => $imgArr];
    }

    /**
     * ppt添加备注
     * @param $slide
     * @param $note
     * @param string $content
     * @return array
     * @throws \Exception
     */
    public function addPptNote($slide, $note, $content = '')
    {
        if (!is_object($slide) || !is_object($note)) {
            throw new $this->exception('参数错误！');
        }
        //1.把所有的换行符通过文档形式输出
        $content = str_replace('<br/>', "<br>", $content);
        //2.去掉所有的img标签，添加text文本内容
        $textString = preg_replace('/<\/?img.*?>/', '', $content);
        $textArr    = explode('<br>', $textString);
        $textRun    = $note->setParent($slide)->createRichTextShape();
        foreach ($textArr as $value) {
            if (empty($value)) {
                continue;
            }
            $value = $this->stripSpecialCode(strip_tags($value));
            if (strpos($value, 'http://') === 0) {
                $textRun->createText($value);
                $textRun->setHyperlink(new Hyperlink($value));
            } else {

                $textRun->createText($value);
            }
        }
        //3.获取所有的img标签的内容,并且把图片信息进行加载
        $reg = '/<img.*?src="(.*?)".*?>|<img.*?src=\'(.*?)\'.*?>/is';
        preg_match_all($reg, $content, $matches);
        $imgArr = [];
        if (!empty($matches[0])) {
            //匹配内容整合，并且去空
            $imgUrlArr = array_filter(array_merge($matches[1], $matches[2]));
            foreach ($imgUrlArr as $img) {
                //有些图片会带有问号链接，把问号链接以后的内容给去掉(例如:http://tiku.huatu.com/cdn/pandora/img/890450b2-4d52-4cf5-a31f-719044087151..png?imageView2/0/w/441/format/jpg)
                if (substr($img, 0, 4) == 'http' && strpos($img, '?') !== false) {
                    $img = substr($img, 0, strpos($img, '?'));
                }
                @$urlContent = file_get_contents($img);
                if ($urlContent === false) {
                    continue;
                }
                //图片文件存储路径
                if (!is_dir($this->frame->conf['epubTmpDir'])) {
                    mkdir($this->frame->conf['epubTmpDir'], 0777);
                }
                $pathInfo = pathinfo($img);
                $imgPath  = $this->frame->conf['epubTmpDir'] . $pathInfo['filename'] . mt_rand(0, 9999999999) . '.' . $pathInfo['extension'];
                file_put_contents($imgPath, $urlContent);
                $slide->createDrawingShape()
                    ->setName($imgPath)->setDescription($imgPath)
                    ->setPath($imgPath)->setWidthAndHeight(350, 150)
                    ->getShadow()->setVisible(false);
                $imgArr[] = $imgPath;
            }
        }
        return ['img' => $imgArr];
    }

    /**
     * 清除字符串中html字符
     * @param $string
     * @return string
     */
    public function stripSpecialCode($string)
    {
        $string = str_replace(array('<red>', '</red>'), array('', ''), $string);
        $search  = [
            '&emsp;',
            '&ensp;',
            '&nbsp;',
            '&thinsp;',
            '&',
            '<',
            '>',
            "'",
            '"',
        ];
        $replace = [
            '',
            '',
            '',
            '',
            '&amp;',
            '&lt;',
            '&gt;',
            '&quot;',
            '&apos;',
        ];
        return str_replace($search, $replace, $string);
    }

    /**
     * 读取Excel文件的内容
     *
     * @return array
     */
    public function readImportData($filePath)
    {
        // 检查文件是否存在
        if (!is_file($filePath)) {
            return false;
        }
        // 文件数据读取
        $sheetData = IOFactory::load($filePath);
        $sheet     = $sheetData->getActiveSheet();
        
       
        // 获取表头
        $headSheetArr = $sheet->getRowIterator(1, 2);
        $dataHeads    = array(); // 表头
        if (is_iteratable($headSheetArr)) foreach ($headSheetArr as $row) {
            $tmp = array();
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getFormattedValue();
                $value = trim($value);
                if (empty($value)) {
                    continue;
                }
                $tmp[] = $value;
            }
            if (empty($tmp)) {
                return false;
            }
            if ($row->getRowIndex() == 1) {
                $dataHeads = $tmp;
            }
        }
        
        print_r($dataHeads);exit;
        // 数据
        $dataSheetArr = $sheet->getRowIterator(2);
        $dataList     = array();
        if (is_iteratable($dataSheetArr)) foreach ($dataSheetArr as $row) {
            $tmp = array();
            foreach ($row->getCellIterator() as $cell) {
                $tmp[] = $cell->getFormattedValue();
            }
            $data     = array();
            $allEmpty = true;
            // 根据表头获取数据
            foreach ($dataHeads as $index => $name) {
                $cell = isset($tmp[$index]) ? $tmp[$index] : '';
                if (strtolower(trim($cell)) == 'null') {
                    $cell = '';
                }
                if (!empty($cell)) {
                    $allEmpty = false;
                }
                $data[$name] = $cell;
            }
            if (!empty($allEmpty)) {
                continue;
            }
            $dataList[] = $data;
        }
        return array(
            'data' => $dataList,
            'head' => $dataHeads,
        );
    }

    /**
     * EXCEL文件导出
     * @param $filename
     * @param $headList
     * @param $dataList
     * @param bool $fileNameSpliceTime
     * @param bool $download
     * @param null $path
     * @param bool $isMultiSheet
     * @return string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportExcel($filename, $headList, $dataList, $fileNameSpliceTime = true, $download = true, $path = null, $isMultiSheet = false)
    {
        $spreadsheet = new Spreadsheet();
        $headArr     = [array_values($headList)];
        if ($fileNameSpliceTime === true) {
            $date     = date('Ymd_H_i', time());
            $filename .= "_{$date}";
        }
        if (!$isMultiSheet) {
            $fields  = array_keys($headList);
            $dataArr = [];
            foreach ($dataList as $item) {
                $temp = [];
                foreach ($fields as $field) {
                    $temp[$field] = isset($item[$field]) ? $item[$field] : '';
                }
                $dataArr[] = $temp;
            }
            //单个sheet
            $dataArr   = array_map('array_values', $dataArr);
            $arrayData = array_merge($headArr, $dataArr);
            $sheet     = $spreadsheet->getActiveSheet();
            $sheet->fromArray($arrayData, null, 'A1');
        } else {
            $dataArr = $dataList;
            //多个sheet
            foreach ($dataArr as $k => $v) {
                $arrayData = ['暂无内容!'];
                //每个sheet表数据
                if (!empty($v['sheetContent'])) {
                    $questionStats = array_map('array_values', $v['sheetContent']);
                    $arrayData     = array_merge($headArr, $questionStats);
                }
                //每个sheet表名
                $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $v['sheetName']);
                $sheet1      = $spreadsheet->addSheet($myWorkSheet, 0);
                $sheet1->fromArray($arrayData, null, 'A1');
            }
        }

        if ($download === true) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); //告诉浏览器输出07Excel文件
            //header(‘Content-Type:application/vnd.ms-excel‘);//告诉浏览器将要输出Excel03版本文件
            header('Content-Disposition: attachment;filename=' . $filename . '.xls'); //告诉浏览器输出浏览器名称
            header('Cache-Control: max-age=0'); //禁止缓存
            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
            exit;
        } else {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
            if (!isset($path)) {
                $path = $this->frame->conf['fileDir'];
            }
            //清除路径'/'信息
            $filename = str_replace('/', '', $filename);
            $filePath = $path . $filename . '.xls';
            $writer->save($filePath);
            return $filePath;
        }
    }

    /**
     * 阶梯式分多个sheet导出excel
     * @param $dataList array 导出数据
     * @param $filename string 文件名称
     * @return string   文件路径
     */
    public function exportLevelData($dataList = [], $filename = '')
    {
        $spreadsheet = new Spreadsheet();

        $date     = date('Ymd_H_i', time());
        $filename .= "_{$date}";

        $dataArr = $dataList;
        //多个sheet
        //删除默认的sheet
        $spreadsheet->removeSheetByIndex(0);
        foreach ($dataArr as $k => $v) {
            //每个sheet表数据
            if (!empty($v['sheetContent'])) {
                $questionStats = array_map('array_values', $v['sheetContent']);
                $arrayData     = array_merge([$v['header']], $questionStats);
            }
            //每个sheet表名
            $myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $v['name']);
            //添加sheet
            $sheet = $spreadsheet->addSheet($myWorkSheet);
            $sheet->fromArray($v['knowledgeData'], null, 'A1');
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
        if (!isset($path)) {
            $path = $this->frame->conf['fileDir'];
        }
        $filePath = $path . $filename . '.xls';
        $writer->save($filePath);
        return $filePath;
    }

    /**
     * 导出Excel数据表格
     * @param array $dataList 要导出的数组格式的数据
     * @param array $headList 导出的Excel数据第一列表头
     * @param string $fileName 输出Excel表格文件名(只有直接进行导出的时候才需要设置$fileName的值)
     * @param bool $rename 改变文件的名字
     * @param bool $download 是否下载
     * @return string
     */
    public function exportCsv($dataList = array(), $headList = array(), $fileName = '', $rename = true, $download = true)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        if ($download) {
            header("Content-type:application/vnd.ms-excel");
            header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
            $filePath = "php://output";
        } else {
            $fileName = $rename === true ? $fileName . '_' . date('Ymd_H_i') . '_' . mt_rand(0, 1000) . '.csv' : $fileName . '.csv';
            //清除路径'/'信息
            $fileName = str_replace('/', '', $fileName);
            $filePath = $this->frame->conf['fileDir'] . $fileName;
        }
        $header = implode(",", $headList);
        $header = iconv('UTF-8', 'GBK//IGNORE', $header);
        $header = explode(",", $header);
        $fp     = fopen($filePath, 'a+');
        if (!empty($headList) && is_array($headList)) {
            fputcsv($fp, $header);
        }
        foreach ($dataList as $row) {
            $str = implode("@@@@", $row);
            $str = iconv('UTF-8', 'GBK//IGNORE', $str);
            $str = str_replace(",", "|", $str);
            $row = explode("@@@@", $str);
            fputcsv($fp, $row);
        }
        unset($data);
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
        return $filePath;
    }

    /**
     * 导入Excel数据表格
     * @param string $fileName 文件名
     * @param int $start 从第几行开始读，默认从第一行读取
     * @param int $limit 读取几行，默认全部读取
     * @return bool|array
     */
    public function importCsv($fileName, $start = 0, $limit = 0)
    {
        set_time_limit(0); //防止超时
        ini_set("memory_limit", "2048M"); //防止内存溢出
        $handle = fopen($fileName, 'r');
        if (!$handle) {
            return '文件打开失败';
        }
        //由于excel导入的时候第一行永远都是题目的头部信息，因此读取的开始位置要始终加+1
        $start  += 1;
        $i      = 0;
        $j      = 0;
        $arr    = [];
        $header = [];
        while ($data = fgetcsv($handle)) {
            //获取头部信息
            if ($i == 0) {
                foreach ($data as $key => $value) {
                    $content = iconv("gbk", "utf-8//IGNORE", $value); //转化编码
                    if (!empty($content)) {
                        $header[] = $content;
                    }
                }
                $i++;
                continue;
            }
            //小于偏移量则不读取,但$i仍然需要自增
            if ($i < $start && $start) {
                $i++;
                continue;
            }
            //大于读取行数则退出
            foreach ($data as $key => $value) {
                if ($key >= count($header)) {
                    break;
                }
                $content                = iconv("gbk", "utf-8//IGNORE", $value); //转化编码
                $arr[$j][$header[$key]] = $content;
            }
            if ($limit > 0 && count($arr) == $limit) {
                break;
            }
            $i++;
            $j++;
        }
        return array(
            'data' => $arr,
            'head' => $header,
        );
    }
}
