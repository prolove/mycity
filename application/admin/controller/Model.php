<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace app\admin\controller;


class Model extends Admin {
    public $model_info=[
        'default'=>[
            'meta_title' => '配置管理',
            'title_bar'  => '配置管理',
            //表单提交地址
            'url' => 'updates',
            //特殊字符串替换用于列表定义解析
            'replace_string' => [],
            //字段映射
            'int_to_string' => ['status'=>['禁用','正常']],
            //按钮组
            'button'     => [
                ['title'=>'新增','url'=>'add','icon'=>'','class'=>'btn_add btn-success ajax-get iframe','ExtraHTML'=>''],
                ['title'=>'启用','url'=>'setstatus?model=model&status=1','icon'=>'','class'=>'ajax-post btn-info','ExtraHTML'=>'target-form="ids"'],
                ['title'=>'禁用','url'=>'setstatus?model=model&status=0','icon'=>'','class'=>'ajax-post btn-danger','ExtraHTML'=>'target-form="ids"'],
                ['title'=>'生成/复制','url'=>'generate','icon'=>'','class'=>'btn-success ajax-get iframe','ExtraHTML'=>'']
            ],
            //表名
            'name' => 'modelmodel',
            //主键
            'pk' => 'id',
            //列表定义
            'list_grid'  => 'id:ID;name:标识;title:名称:[EDIT];create_time:最后更新;status_text:状态;id:操作:think/listquery?model=[name]|数据,[status]|{0.启用.setStatus?model=model&status=1&ids=[id] 1.禁用.setStatus?model=model&status=0&ids=[id]},[EDIT]|编辑,del?id=[id]|删除',
            //列表头  由系统完成  可不填
            'list_field' => [],
            //验证字段属性信息 由系统完成 在fields设置
            'validate' => [],
            //自由组合的搜索字段  ['字段'=>'标题'] 为空取列表定义的
            'seach_field'=> [],
            //默认搜索条件[]填写查询条件
            'where'      => ['status'=>['gt',-1]],
        ],
        'index'=>['url'=>''],
        'edit'=>[
            //表单显示分组
            'field_group'=>'1:基础,2:设计,3:高级',
            //表单显示排序
            "fields"=>[
                '1'=>[
                    ['name'=>'id','title'=>'UID','type'=>'string','remark'=>'','is_show'=>4],
                    ['name'=>'name','title'=>'模型标识','type'=>'string','remark'=>'请输入文档模型标识','is_show'=>1],
                    ['name'=>'title','title'=>'模型名称','type'=>'string','remark'=>'请输入模型的名称','is_show'=>1],
                    ['name'=>'extend','title'=>'模型类型','type'=>'select','extra'=>':Field_models()','remark'=>'选择继承模型','is_show'=>1],
                ],
                '2'=>[
                    ['name'=>'','title'=>'字段管理','type'=>'widget','extra'=>'Model/fields','remark'=>'只有新增了字段，该表才会真正建立','is_show'=>1],
                    ['name'=>'attribute_alias','title'=>'字段别名定义','type'=>'textarea','remark'=>'用于表单显示的名称','is_show'=>1],
                    ['name'=>'field_group','title'=>'表单显示分组','type'=>'string','remark'=>'用于表单显示的分组，以及设置该模型表单排序的显示[1:基础,2:扩展]','is_show'=>1],
                    ['name'=>'','title'=>'表单显示排序','type'=>'widget','extra'=>'Model/sort','remark'=>'直接拖动进行排序','is_show'=>1],
                    ['name'=>'list_grid','title'=>'列表定义','type'=>'textarea','extra'=>'','remark'=>'默认列表模板的展示规则','is_show'=>1],
                    ['name'=>'search_key','title'=>'默认搜索字段','type'=>'string','extra'=>'','remark'=>'默认列表模板的默认搜索项','is_show'=>1],
                    ['name'=>'search_list','title'=>'高级搜索字段','type'=>'string','extra'=>'','remark'=>'默认列表模板的高级搜索项','is_show'=>1]
                ],
                '3'=>[
                    ['name'=>'template_list','title'=>'列表模板','type'=>'string','remark'=>'自定义的列表模板，放在application\admin\view\default\think下，不写则使用默认模板','is_show'=>1],
                    ['name'=>'template_add','title'=>'新增模板','type'=>'string','remark'=>'自定义的列表模板，放在application\admin\view\default\think下，不写则使用默认模板','is_show'=>1],
                    ['name'=>'template_edit','title'=>'编辑模板','type'=>'string','remark'=>'自定义的列表模板，放在application\admin\view\default\think下，不写则使用默认模板','is_show'=>1],
                    ['name'=>'list_row','title'=>'列表数据大小','type'=>'string','remark'=>'默认列表模板的分页属性','is_show'=>1],
                ]

            ]
        ],
        'add'=>[
            'meta_title' => '新增模型',
            'title_bar'  => '新增模型',
            'field_group'=>'1:基础',
            "fields"=>[
                '1'=>[
                    ['name'=>'id','title'=>'UID','type'=>'string','remark'=>'','is_show'=>4],
                    ['name'=>'name','title'=>'模型标识','type'=>'string','remark'=>'请输入文档模型标识','is_show'=>1],
                    ['name'=>'title','title'=>'模型名称','type'=>'string','remark'=>'请输入模型的名称','is_show'=>1],
                    ['name'=>'extend','title'=>'模型类型','type'=>'select','extra'=>':Field_models()','remark'=>'选择继承模型','is_show'=>1],
                    ['name'=>'engine_type','title'=>'引擎类型','type'=>'select','extra'=>'MyISAM:MyISAM,InnoDB:InnoDB,MEMORY:MEMORY,BLACKHOLE:BLACKHOLE,MRG_MYISAM:MRG_MYISAM,ARCHIVE:ARCHIVE','remark'=>'','is_show'=>1],
                    ['name'=>'need_pk','title'=>'是否需要主键','type'=>'radio','extra'=>'1:是,0:否','remark'=>'选“是”则会新建默认的id字段作为主键','is_show'=>1],
                ],
            ]
        ],
        'generate'=>[
            'meta_title' => '生成模型',
            'title_bar'  => '生成/复制模型',
            'url' =>'model/generate',
            //表单显示分组
            'field_group'=>'1:基础',
            //表单显示排序
            "fields"=>[
                '1'=>[
                    ['name'=>'table','title'=>'数据表','type'=>'select','extra'=>':Field_ModelTables()','remark'=>'当前数据库的所有表','is_show'=>1],
                    ['name'=>'name','title'=>'模型标识','type'=>'string','remark'=>'模型英文标识','is_show'=>1],
                    ['name'=>'title','title'=>'模型名称','type'=>'string','remark'=>'模型名称','is_show'=>1],
                    ['name'=>'copy','title'=>'操作类型','type'=>'radio','extra'=>'1:生成模型;2:复制并生成模型','value'=>1,'remark'=>'','is_show'=>1],
                ]
            ]
        ]
    ];

    /**
     * @title   生成复制模型
     * @author 小矮人 82550565@qq.com
     */
    public function generate(){
        if($this->request->isPost()){
            $data = $this->request->param();
            $table = $data['table'];
            empty($table) && $this->error('请选择要生成的数据表！');
            $validate = validate('Modelmodel');
            if(!$validate->check($this->request->Post())){
                return $this->error($validate->getError());
            }
            $res = model('Modelmodel')->generate($table,$data['name'],$data['title']);
            if($res){
                if($data['copy'] == 2){//复制表
                    db()->query('CREATE TABLE '.config('database.prefix').$data['name'].' LIKE '.$data['table']);
                    $this->success('复制表并生成模型成功！', url('index'));
                }
                $this->success('生成模型成功！', url('index'));
            }else{
                $this->error(model('Modelmodel')->getError());
            }
        }else{
            return parent::_add();
        }
    }
    /**
     * 删除一条数据
     * @author 小矮人  <twothink.cn>
     */
    public function del($Model = false){
        $ids = $this->request->param('id');
        empty($ids) && $this->error('参数不能为空！');
        $ids = explode(',', $ids);
        foreach ($ids as $value){
            $res = model('Modelmodel')->del($value);
            if(!$res){
                break;
            }
        }
        if(!$res){
            $this->error('删除模型失败,只支持删除文档模型和独立模型');
        }else{
            $this->success('删除模型成功！');
        }
    }
}