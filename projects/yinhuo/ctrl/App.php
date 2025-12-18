<?php
namespace ctrl;

/**
 * 首页
 *
 * @author
 */
class App extends CtrlBase
{
    /**
     * 获取静态配置
     *
     * @return array
     */
    public function getStaticConfig()
    {
        $params = $this->params;
        $appSv = \service\App::singleton();
        return $appSv->getStaticConfig();
    }
    
}