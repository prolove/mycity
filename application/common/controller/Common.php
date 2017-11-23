<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn>
// +----------------------------------------------------------------------
namespace app\common\controller;
use think\Controller;
/**
 * @title 系统封装基类
 * @author 小矮人 <82550565@qq.com>
 */
class Common extends Controller
{
    public $param;
    protected function _initialize()
    {
        parent::_initialize();
        /*防止跨域*/
        header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authKey, sessionId");
        $param =  $this->request->param();
        $this->param = $param;
    }
    /**
     * 加载模板输出
     */
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        $template = $this->view->fetch($template, $vars, $replace, $config);
        if($this->request->isAjax()){
            $this->success('','',$template);
        }else{
            return $template;
        }
    }
}