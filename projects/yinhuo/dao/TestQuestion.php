<?php
namespace dao;

/**
 * TestQuestion 数据库类
 * 
 * @author 
 */
class TestQuestion extends DaoBase
{
    /**
     * 单例
     *
     * @var object
     */
    private static $instance;

    /**
     * 单例模式
     *
     * @return TestQuestion
     */
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TestQuestion();
        }
        return self::$instance;
    }

    /**
     * 统计测卷题目数量
     *
     * @return array
     */
    public function getQuestionMap($testPaperIds)
    {
        if (empty($testPaperIds)) {
            return array();
        }
        $sql = "select `testPaperId`, count(*) as `num` from {$this->mainTable} where `version` = 1 and `testPaperId` in (" 
            . implode(',', $testPaperIds) . ') group by `testPaperId`;';
        $dataList = $this->readDataBySql($sql);
        $result = array();
        foreach ($dataList as $data) {
            $result[$data->testPaperId] = intval($data->num);
        }
        return $result;
    }

}