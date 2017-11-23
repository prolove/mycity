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

class Attribute extends Admin {
    public $model_info=[
        'default'=>[
            'meta_title' => '配置管理',
            //表单提交地址
            'url' => 'updates',
            //特殊字符串替换用于列表定义解析
            'replace_string' => [],
            //字段映射
            'int_to_string' => ['status'=>['禁用','正常']],
            //按钮组
            'button'     => [
                ['title'=>'新增','url'=>'add?model_id=[model_id]','icon'=>'','class'=>'ajax-get iframe btn-success','ExtraHTML'=>''],
            ],
            //表名
            'name' => 'Attribute',
            //主键
            'pk' => 'id',
            //列表定义
            'list_grid'  => 'id:ID;name:字段;title:名称:[EDIT];type|get_attribute_type:数据类型;model_id:模型id;id:操作:[EDIT]|编辑,remove?id=[id]|删除',
            'field_group'=>'1:基础;2:高级',
            "fields"=>[
                '1'=>[
                    ['name'=>'id','title'=>'UID','type'=>'string','remark'=>'','is_show'=>4],
                    ['name'=>'model_id','title'=>'模型ID','type'=>'string','extra'=>':[model_id]','value'=>'','remark'=>'','is_show'=>4],
                    ['name'=>'name','title'=>'字段名','type'=>'string','remark'=>'请输入字段名 英文字母开头，长度不超过30','is_show'=>1],
                    ['name'=>'title','title'=>'字段标题','type'=>'string','remark'=>'请输入字段标题，用于表单显示','is_show'=>1],
                    ['name'=>'type','title'=>'字段类型','type'=>'widget','extra'=>'Attribute/type','remark'=>'用于表单中的展示方式','is_show'=>1],
                    ['name'=>'field','title'=>'字段定义','type'=>'string','remark'=>'字段属性的sql表示','is_show'=>1],
                    ['name'=>'extra','title'=>'参数','type'=>'textarea','remark'=>'布尔、枚举、多选字段类型的定义数据','is_show'=>1],
                    ['name'=>'value','title'=>'默认值','type'=>'string','remark'=>'字段的默认值','is_show'=>1],
                    ['name'=>'remark','title'=>'字段备注','type'=>'string','remark'=>'用于表单中的提示','is_show'=>1],
                    ['name'=>'is_show','title'=>'是否显示','type'=>'select','extra'=>'1:始终显示,2:新增显示,3:编辑显示,4:始终隐藏,5:新增隐藏,6:编辑隐藏,0:不显示','remark'=>'是否显示在表单中','is_show'=>1],
                    ['name'=>'is_must','title'=>'是否必填','type'=>'select','extra'=>'0:否,1:是','remark'=>'用于自动验证','is_show'=>1],
                ],
                '2'=>[
                    ['name'=>'validate_rule','title'=>'验证规则','type'=>'string','remark'=>'require|max:25','is_show'=>1],
                    ['name'=>'error_info','title'=>'出错提示','type'=>'string','remark'=>'名称必须|名称最多不能超过25个字符','is_show'=>1],
                    ['name'=>'validate_time','title'=>'验证时间','type'=>'select','extra'=>'3:始终,1:新增,2:编辑','remark'=>'','is_show'=>1],
                    ['name'=>'auto_rule','title'=>'自动完成规则','type'=>'string','remark'=>'填写验证函数或方法','is_show'=>1],
                    ['name'=>'auto_time','title'=>'自动完成时间','type'=>'select','extra'=>'3:始终,1:新增,2:编辑','remark'=>'','is_show'=>1],
                ]
            ]
        ],
        'index'=>[
            'url' => '',
            //固定搜索条件
            'where_solid' => ['model_id'=>['eq','[model_id]']],
        ]
    ];
    public function index(){
        $model_id = $this->request->param('model_id');
        empty($model_id) && $this->error('缺少参数!!!');
        $this->model_info['default']['title_bar'] = '['.get_model_by_id($model_id).'] 属性列表(不含继承属性)';
        return parent::_index();
    }
    public function edit(){
        $this->two_assign['highlight_subnav'] = url('Model/index');
        return parent::_edit();
    }
    public function add()
    {
        $model_id = $this->request->param('model_id');
        empty($model_id) && $this->error('缺少参数!!!');
//        $this->model_info['default']['fields']['1']['model_id']['value'] = $model_id;
        $this->two_assign['highlight_subnav'] = url('Model/index');
        return parent::_add();
    }
    /**
     * 删除一条数据
     * @author 艺品网络  <twothink.cn>
     */
    public function remove(){
        $id = $this->request->param('id');
        empty($id) && $this->error('参数错误！');

        $Model = model('Attribute');

        $info = $Model->getById($id);
        empty($info) && $this->error('该字段不存在！');
        //删除属性数据
        $res = $Model->where(['id'=>$id])->delete();
        //删除表字段
        $Model->deleteField($info);
        if(!$res){
            $this->error(model('Attribute')->getError());
        }else{
            //记录行为
            action_log('update_attribute', 'attribute', $id, UID);
            $this->success('删除成功', url('index','model_id='.$info['model_id']));
        }
    }
}