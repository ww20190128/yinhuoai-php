<?php
namespace ctrl;

/**
 * 数据采集
 *
 * @author
 */
class Grab extends CtrlBase
{
    /**
     * 心芝-采集数据（第一步）
     *
     * @return array
     */
    public function xz()
    {
        $grabSv = \service\Grab::singleton();
        $grabSv->xz();
    }
    
    /**
     * 心芝-录入测评（第二步）
     *
     * @return array
     */
    public function xz_input()
    {
    	$grabSv = \service\Grab::singleton();
    	$grabSv->xz_input();
    }
    
    /**
     * 心芝-同步分类（第三步）
     *
     * @return array
     */
    public function xz_input_classify()
    {
    	$grabSv = \service\Grab::singleton();
    	$grabSv->xz_input_classify();
    }
    
    /**
     * 心芝-报告制作流程（第四步）
     *
     * @return array
     */
    public function xz_report_process()
    {
    	$grabSv = \service\Grab::singleton();
    	$grabSv->xz_report_process();
    }
    
    /**
     * 心芝-同步报告（第五步）
     *
     * @return array
     */
    public function xz_report()
    {
    	$grabSv = \service\Grab::singleton();
    	$grabSv->xz_report();
    }
    
    /**
     * 心芝-图片
     *
     * @return array
     */
    public function xz_getImgs()
    {
        $grabSv = \service\Grab::singleton();
        $grabSv->xz_getImgs();
    }

    /**
     * 心芝-同步推广数据
     * 
     * 641acea1
     * 65893ef0
     * 65114043
     * 
     * 65a4f081
     *
     *6669a943
     * @return array
     */
    public function xz_promotion()
    {
        $grabSv = \service\Grab::singleton();
        $pid = '6669a943'; // 推广ID
        $grabSv->xz_promotion($pid);
    }
    
    /**
     * 心芝-数据分析
     *
     * @return array
     */
    public function xz_analyse()
    {
    	$grabSv = \service\Grab::singleton();
    	$grabSv->xz_analyse();
    }
 
    /**
     * 心芝-通用报告分析
     *
     * @return array
     */
    public function xz_report_common()
    {
    	$grabSv = \service\Grab::singleton();
    	$grabSv->xz_report_common();
    }
    
    /**
     * 心芝-通用报告分析
     *
     * @return array
     */
    public function xz_report_set_common()
    {
    	$grabReportSv = \service\GrabReport::singleton();
    	$grabReportSv->main();
    }
    
    /**
     * 心芝-同步MBTI报告
     *
     * @return array
     */
    public function xz_report_mbti()
    {
    	$grabSv = \service\Grab::singleton();
    	$grabSv->xz_report_mbti();
    	echo "MBTI报告同步完毕\n";exit;
    }
    
    /**
     * 心芝-同步MBTI报告
     *
     * @return array
     */
    public function xz_question()
    {
    	$grabSv = \service\Grab::singleton();
    	$grabSv->xz_question();
    	echo "MBTI报告同步完毕\n";exit;
    }
    
}