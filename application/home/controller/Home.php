<?php


namespace app\home\controller;
use app\common\controller\Common;


/**
 * 前台公共控制器
 */
class Home extends Common {
	public function __construct(){
        /* 读取站点配置 */
        $config = cache('db_config_data_home');
        if(!$config){
            $config = api('Config/lists');
            $config ['template'] = config('template');
            $config ['template']['view_path'] = APP_PATH.'home/view/'.$config['home_view_path'].'/';
            cache('db_config_data_home', $config);
        }
        config($config); //添加配置
        parent::__construct();

        $this->news_menu = model('Category')->getTree(1);        // 新闻
        $this->customer_menu = model('Category')->getTree(42);   // 合作客户



        $this->view->news_menu  = $this->news_menu;
        $this->view->customer_menu  = $this->customer_menu;
        // var_dump($this->news_menu);

	}
    protected function _initialize(){
        if(!config('web_site_close')){
            $this->error('站点已经关闭，请稍后访问~');
        }
    }
	/* 空操作，用于输出404页面 */
	public function _empty(){
		$this->redirect('Index/index');
	}
}
