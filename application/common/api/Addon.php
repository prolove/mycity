<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace app\common\api;

use think\Validate;
use think\Db;
use think\Exception;
/*
 * @title插件开发公共类
 * @Author: 小矮人  82550565@qq.com <www.twothink.cn>
 */
class Addon{
    public $error;//错误信息

    /**
     * 获取插件所需的钩子是否存在，没有则新增
     * @param string $str 钩子名称
     * @param string $addons 插件名称
     * @param string $msg 插件描述
     * @param int 类型
     * @Author: 小矮人  82550565@qq.com <www.twothink.cn>
     */
    public function existHook($str, $addons, $msg = '',$type = 1) {
        $hook_mod = Db::name( 'Hooks' );
        $where ['name'] = $str;
        $gethook = $hook_mod->where ( $where )->find ();
        if (! $gethook || empty ( $gethook ) || ! is_array ( $gethook )) {
            $data ['name'] = $str;
            $data ['description'] = $msg;
            $data ['type'] = $type;
            $data ['update_time'] = time();
            $data ['addons'] = $addons;

            $rule = [
                ['name','require|unique:hooks','钩子名称必须|钩子已存在'],
                ['description','require','钩子描述必须']
            ];
            $validate = new Validate($rule);
            if (!$validate->check($data)) {
                $this->error( $validate->getError() );
                return false;
            }

            if($hook_mod->insert ( $data ))
                cache ( 'hooks', null );
        }
        return true;
    }
    /*
     * @title 查询单条钩子信息
     * @param array $name 钩子名称
     * @Author: 小矮人  82550565@qq.com <www.twothink.cn>
     */
    public function findHooks($name) {
        return $data = Db::name('Hooks')->getByName($name);
    }
    /*
     * @title 删除钩子
     * @param array $name 钩子名称
     * @Author: 小矮人  82550565@qq.com <www.twothink.cn>
     */
    public function delHooks($name) {
        $gd_name = ['pageHeader','pageFooter','documentEditForm','documentDetailAfter','documentDetailBefore','documentSaveComplete','documentEditFormContent','adminArticleEdit','topicComment','app_begin'];
        if(in_array($name,$gd_name)){
            $this->error ( '系统钩子不可删除' );
            return false;
        }
        $obj = Db::name('Hooks');
        if(!$data = $obj->getByName($name)){
            return true;
        }
        $count = count(explode(',',$data['addons']));
        if($count <= 1){
            $obj->delete($data['id']);
        }
        return true;
    }
    protected function error($msg){
        $this->error = $msg;
    }
    public function getError()
    {
        return $this->error;
    }
}