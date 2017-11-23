<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Controller;
use think\Db;
/**
 * @title 系统配置
 * @author 小矮人  <82550565@qq.com>
 */
class Config extends Admin {
    //自定义模型信息
    public $model_info = [
        'default'=>[
            'meta_title' => '配置管理',
            //表单提交地址
            'url' => 'Config/updates',
            //操作方法(方法不存在的时候起作用)
            'action'=>'',
            //特殊字符串替换用于列表定义解析
            'replace_string' => [['[DELETE]','[EDIT]'],['delete?ids=[id]','edit?id=[id]']],
            //按钮组
            'button'     => [
                ['title'=>'新增','url'=>'add','icon'=>'','class'=>'ajax-get iframe','ExtraHTML'=>''],
                ['title'=>'删除','url'=>'del','icon'=>'','class'=>'btn-danger ajax-post confirm','ExtraHTML'=>'target-form="ids"'],
                ['title'=>'排序','url'=>'sort','icon'=>'','class'=>'btn-info ajax-get iframe','ExtraHTML'=>'']
            ],
            //表名
            'name' => 'config',
            //主键
            'pk' => 'id',
            //列表定义
            'list_grid'  => 'id:ID;name:名称:[EDIT];title:标题;update_time:最后更新;group|get_config_group:分组;type|get_config_type:类型;id:操作:[EDIT]|编辑,del?id=[id]|删除',
            //列表头  由系统完成
            'list_field' => [],
            //验证字段属性信息 由系统完成 在fields设置
            'validate' => [],
            //自由组合的搜索字段  ['字段'=>'标题'] 为空取列表定义的
            'seach_field'=> [],
            //默认搜索条件[]填写查询条件
            'where'      => [],
            //固定搜索条件
            'where_solid' => [],
            //表单显示分组
            'field_group'=>'1:基础',
            //表单显示排序
            "fields"=>[
                '1'=>[
                    ['name'=>'id','title'=>'UID','type'=>'string','remark'=>'','is_show'=>4],
                    ['name'=>'name','title'=>'配置标识','type'=>'string','remark'=>'用于C函数调用，只能使用英文且不能重复','is_show'=>1],
                    ['name'=>'title','title'=>'配置标题','type'=>'string','remark'=>'用于后台显示的配置标题','is_show'=>1],
                    ['name'=>'sort','title'=>'排序','type'=>'string','remark'=>'用于分组显示的顺序','is_show'=>1],
                    ['name'=>'type','title'=>'配置类型','type'=>'select','extra'=>':config_type_list()','value'=>'','remark'=>'系统会根据不同类型解析配置值','is_show'=>1],
                    ['name'=>'group','title'=>'配置分组','type'=>'select','extra'=>':config_group_list()','value'=>'','remark'=>'配置分组 用于批量设置 不分组则不会显示在系统设置中','is_show'=>1],
                    ['name'=>'value','title'=>'配置值','type'=>'textarea','remark'=>'配置值','is_show'=>1],
                    ['name'=>'extra','title'=>'配置项','type'=>'textarea','remark'=>'如果是枚举型 需要配置该项','is_show'=>1],
                    ['name'=>'remark','title'=>'说明','type'=>'textarea','remark'=>'配置详细说明','is_show'=>1],
                ]
            ]
        ],
        'index'=>['url'=>''],
        'group'=>[
            'url' => 'Config/save',
        ],
        'add'=>[
            'meta_title' => '新增配置',
        ],
        'edit'=>[
            'meta_title' => '编辑配置',
        ]
    ];
    public function group(){
        $group   =   config('config_group_list');
        $list   =   Db::name("Config")->where(['status'=>1])->field('id,name,title,extra,value,remark,type,group')->order('sort')->select();
        $new_list = [];
        $field_type = ['num','string','textarea','textarea','select'];
        foreach ($list as $key => $value){
            $value['type'] = $field_type[$value['type']];
            if($value['group'] == 0){
                $new_list[1][] = $value;
            }else{
                $new_list[$value['group']][] = $value;
            }
        }
        $this->two_assign['model_info']['url'] = url('Config/save');
        $this->two_assign['model_info']['fields'] = $new_list;
        $this->two_assign['model_info']['field_group'] = $group;
        $this->two_assign['data'] =  Array_mapping($list,'name','value'); ;
        $this->two_assign['meta_title'] = '网站设置';
        $this->assign( $this->two_assign);
        return $this->fetch('base/edit');
    }
    /**
     * @title 批量保存配置
     * @author 小矮人  <82550565@qq.com>
     */
    public function save(){
        $config = $this->request->param();
        if($config && is_array($config)){
            $Config = Db::name('Config');
            foreach ($config as $name => $value) {
                $map = ['name' => $name];
                $Config->where($map)->setField('value', $value);
            }
        }
        cache('db_config_data',null);
        $this->success('保存成功！');
    }
    /**
     * 配置排序
     * @author 艺品网络  <twothink.cn>
     */
    public function sort(){
        if($this->request->isGet()){
            $ids = $this->request->param('ids');
            //获取排序的数据
            $map['status'] = ['gt',-1];
            if(!empty($ids)){
                $map['id'] = ['in',$ids];
            }elseif($group = $this->request->param('group')){
                $map['group']	=	$group;
            }
            $list = Db::name('Config')->where($map)->field('id,title')->order('sort asc,id asc')->select();

            $this->two_assign['list'] = $list;
            $this->two_assign['meta_title'] = '配置排序';
            $this->assign($this->two_assign);
            return $this->fetch();
        }elseif ($this->request->isPost()){
            $ids = input('ids');
            $ids = explode(',', $ids);
            foreach ($ids as $key=>$value){
                $res = Db::name('Config')->where(array('id'=>$value))->setField('sort', $key+1);
            }
            if($res !== false){
                $this->success('排序成功！',Cookie('__forward__'));
            }else{
                $this->error('排序失败！');
            }
        }else{
            $this->error('非法请求！');
        }
    }
}