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
use think\Db;
use think\Request;

/*
 * @title模型定义处理类
 * @Author: 艺品网络  82550565@qq.com <www.twothink.cn>
 */
class Modelinfo{
    public $info; //模型定义信息
    public $scene; //应用场景
    public $model_name;//模型名称即表名
    public $replace_string = [['[DELETE]','[EDIT]'],['delete?ids=[id]','edit?id=[id]']]; //特殊字符串替换用于列表定义解析
    protected $options;//['field'=>获取指定字段]
    protected $Queryobj;//实列化查询对象
    /*
     * @title 操作场景(控制器方法)
     * @author 艺品网络 593657688@qq.com
     */
    public function scene($scene = false){
        if($scene)
            $this->scene = $scene;
        else
            $this->scene = 'default';
        return $this;
    }
    /*
     * @title 模型规则解析
     * @$info 模型定义
     * @return obj
     * @author 艺品网络 593657688@qq.com
     */
    public function info($info = false){
        if(!$info && !isset($this->info)){
            $this->error = '模型配置信息不存在';
            return false;
        }
        $scene = $this->scene = $this->scene ?:request()->action();
        //当前操作模型信息
        if(isset($info[$scene]) && isset($info['default'])){
            $info = array_merge($info['default'],$info[$scene]);
        }elseif(isset($info['default'])){
            $info = $info['default'];
        }
        $this->info = $info;
        //param
        $param = request()->param();
        //button
        if(isset($info['button'])){
            foreach ($info['button'] as $key=>$value){
                // 替换数据变量
                $this->info['button'][$key]['url']   =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($param){return isset($param[$match[1]])?$param[$match[1]]:'';}, $value['url']);
            }
        }
        //replace_string
        if(empty($info['replace_string'])){
            $this->info['replace_string'] = $this->replace_string;
        }
        //list_grid
        if(isset($info['list_grid'])){
            $this->list_field();
        }
        //search_list
        if(!isset($info['search_list'])){

        }
        //where_solid
        if(isset($info['where_solid'])){
            $this->info['where_solid'] = $this->where_solid($info['where_solid']);
        }

        //fields
        $this->info['fields'] = isset($info['fields']) ? $info['fields'] : [];
        foreach ($this->info['fields'] as $key => $value) {
            $data_name = array_column($value,'name');
            if(count($data_name) == count(array_filter($data_name)))
                $this->info['fields'][$key] = Array_mapping($this->info['fields'][$key],'name');
        }
        //validate
        $validate_arr = [];
        foreach ($this->info['fields'] as $key=>$value){
            $validate_arr = array_merge_recursive($validate_arr,$value);
        }
        $this->info['validate'] = array_combine(array_column($validate_arr,'name'),$validate_arr);
        $this->info['name'] = !empty($info['name']) ?$info['name']:request()->controller();
        if(isset($info['url']) && $info['url'] !== false){
            $this->info['url'] = $info['url'] !== true?url($info['url']):request()->url();
        }

        $this->model_name = $info['name']?:request()->controller();
        return $this;
    }
    /*
     * @title 查询系统模型信息
     * @$model_id 模型id
     * @return $this
     * @author 艺品网络 593657688@qq.com
     */
    public function modelinfo($model_id = 1){
        // 绑定多个模型 取基础模型的列表定义
        $DanQianmodel = $model = Db::name('Model')->getById($model_id);
        if(!empty($model['extend']) && $model['extend'] > 0){
            unset($model);
            $model = Db::name('Model')->getById($DanQianmodel['extend']);
            $model['list_grid'] = !empty($DanQianmodel['list_grid'])?$DanQianmodel['list_grid']:$model['list_grid'];
            $model['field_group'] = !empty($DanQianmodel['field_group'])?$DanQianmodel['field_group']:$model['field_group'];
            $model['DanQianmodel'] = $DanQianmodel;
        }
        $this->info=$model;
        return $this;
    }
    /*
     * @title 系统模型解析列表定义
     * @$list_grid 列表规则(uid:UID;nickname:昵称;score:积分;login:登录次数;last_login_time|time_format:最后登入时间;)
     * @return $this
     * @author 艺品网络 593657688@qq.com
     */
    public function list_field($list_grid = false){
        if(empty($list_grid) && isset($this->info['list_grid'])){
            $list_grid = $this->info['list_grid'];
        }
        $fields = $grids = [];
        if(!empty($list_grid)){

            $grids  = is_array($list_grid)?$list_grid:preg_split('/[;\r\n]+/s', trim($list_grid));
            foreach ($grids as &$value) {
                // 字段:标题:链接
                $val      = explode(':', $value);
                // 支持多个字段显示
                $field   = explode(',', $val[0]);
                $field_name = explode('|', $field[0]);
                $value    = ['name'=>$field_name['0'],'field' => $field, 'title' => $val[1]];
                if(isset($val[2])){
                    // 链接信息
                    $value['href']  =   $val[2];
                }
                if(strpos($val[1],'|')){
                    // 显示格式定义
                    list($value['title'],$value['format'])    =   explode('|',$val[1]);
                }
                foreach($field as $vals){
                    $array  =   explode('|',$vals);
                    if($val[1] !== '操作')
                        $fields[$array[0]] = $val[1];
                }
                unset($fields[0]);
            }
        }
        // 过滤重复字段信息
        $fields =   array_unique($fields);
        //自由组合的搜索字段
        if(empty($this->info['search_list'])){
            $this->search_list($fields);
        }
        $this->info['field'] = array_keys($fields);
        $this->info['list_field'] = $grids;
        return $this;
    }
    /*
     * @title 自由组合的高级搜索字段  ['字段'=>'标题'] 为空取列表定义的
     * @$field 搜索字段
     * @return $this
     * @author 艺品网络 593657688@qq.com
     */
    public function search_list($field = false){
        if($field)
            $this->info['search_list'] = $field;
        return $this;
    }
    /*
     * @title 系统模型表单显示(字段显示属性信息)
     * @fields array 表单显示排序
     * @return $this
     * @author 艺品网络 593657688@qq.com
     */
    public function fields(){
        if(empty($this->info['fields']) && isset($this->info['id'])){
            $model_id = $this->info['DanQianmodel']?$this->info['DanQianmodel']['id']:$this->info['id'];
            $this->info['fields'] = get_model_attribute($model_id);
            foreach ($this->info['fields'] as $key => $value) {
                $this->info['fields'][$key] = Array_mapping($this->info['fields'][$key],'name');
            }
        }
        return $this;
    }

    /*
     * @title 获取对象值
     * @$param 要获取的参数 支持多级  a.b.c
     * @return array
     * @author 艺品网络 593657688@qq.com
     */
    public function Getparam($param = false){
        if($param){
            if (is_string($param)) {
                if (!strpos($param, '.')) {
                    if($this->options['field'] && $param == 'info')
                        return $this->options['field'];
                    return $this->$param;
                }
                $name = explode('.', $param);
                $arr = $this->toArray();
                foreach ($name as $value){
                    $arr = $arr[$value];
                }
                return $arr;
            }
        }else{
            return $this->toArray();
        }
    }
    //对象转数组
    public function toArray(){
        return (array)$this;
    }

    /**
     * @title        DataTables 拼装搜索条件
     * @$param  [] 请求信息
     * @$where_default [] 默认搜索条件 在所有请求查询条件的为空情况下启用
     * @$where_solid [] 固定搜索条件 在所有条件下都会加上改条件
     * @author 艺品网络 593657688@qq.com
     */
    public function DataTables_where($param=null,$where_default=null,$where_solid=null){
        if (empty($param)){
            $param = request()->param();
        }
        //模型配置信息
        $info = $this->info;
        $pk = isset($info['pk'])?$info['pk']:'id';
        if(empty($where_default)){
            $where_default = isset($info['where'])?$info['where']:'';
        }

        $where = [];
        if(!empty($param['seach_all']) && empty($param['like_seach']) && $filters_arr=json_decode($param['seach_all'],true)){ //and
            $seach_all_name = is_array($filters_arr['seach_all_name'])?$filters_arr['seach_all_name']:[$filters_arr['seach_all_name']];
            $seach_all_value = is_array($filters_arr['seach_all_value']) ?$filters_arr['seach_all_value']:[$filters_arr['seach_all_value']];
            $seach_all_type = is_array($filters_arr['seach_all_type']) ? $filters_arr['seach_all_type']:[$filters_arr['seach_all_type']];
            foreach ($seach_all_type as $key => $value) {
                switch ($value) {//判断查询方式
                    case 'ne':
                        $search_arr=['neq',$seach_all_value[$key]];
                        break;
                    case 'lt':
                        $search_arr=['lt',$seach_all_value[$key]];
                        break;
                    case 'le':
                        $search_arr=['elt',$seach_all_value[$key]];
                        break;
                    case 'gt':
                        $search_arr=['gt',$seach_all_value[$key]];
                        break;
                    case 'ge':
                        $search_arr=['egt',$seach_all_value[$key]];
                        break;
                    case 'cn':
                        $search_arr=['like',"%".$seach_all_value[$key]."%"];
                        break;
                    default:
                        $search_arr=['eq',$seach_all_value[$key]];
                        break;
                }

                if(isset($where[$seach_all_name[$key]])){
                    $where[$seach_all_name[$key]]['0']=$where[$seach_all_name[$key]];
                    $where[$seach_all_name[$key]]['1']=$search_arr;
                }else{
                    $where[$seach_all_name[$key]]=$search_arr;
                }
            }
        }elseif(!empty($param['like_seach'])){ //or
            if($info['list_field']){
                $fields = array_unique(array_column($info['list_field'],'name'));
                $fields = implode('|',$fields);
                $where[$fields] = ['like',"%".$param['like_seach']."%"];
            }else{
                $where[$pk] = ['eq',$param['like_seach']];
            }
        }elseif(!empty($where_default)){//默认搜索条件
            $where = $where_default;
        }else{
            $where[$pk] = ['gt',0];
        }
        //固定搜索
        if($where_solid){
            $where += $where_solid;
        }elseif(isset($info['where_solid'])){
            $where = array_merge($where,$info['where_solid']);
        }
        $this->info['where'] = $where;
        return $this;
    }
    /*
     * @title 解析固定搜索规则
     * @author 小矮人 82550565@qq.com
     */
    protected function where_solid($where_solid){
        $data = request()->param();
        foreach ($where_solid as $key=>$value){
            if(is_array($value)){
                foreach ($value as $k=>$v){
                    $where_solid[$key][$k]  =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $v);
                }
            }else{
                $where_solid[$key]   =   preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $value);
            }
        }
        return $where_solid;
    }
    /**
     * 快速编辑更新(模型和验证器可有可无   没有则使用Base模型和验证器)
     * @author 小矮人 82550565@qq.com
     * $parameter = false, 控制器参数重整数组
     * $save_data = [] 操作的值
     * @return array   当前数据的id
     */
    public function editDataCustom($parameter = false, $save_data = [])
    {
        if (empty($save_data)) {
            if ($parameter && is_array($parameter)) {
                $data = $this->buildParam($parameter);
            } else {
                $data = request()->param();
            }
        } else {
            $data = $save_data;
        }
        if (!$data){
            $this->error = '未提交数据信息';
            return false;
        }
        //模型规则
        $info = $this->info;
        //实列化模型
        $model = Model_hierarchy($info['name']);
        //自动验证及自动完成
        if($info['validate']){//自动验证及自动完成
            if(!$data = $this->checkModelAttr($info['validate'],$data)){
                return false;
            };
        }elseif(Model_hierarchy($info['name'], 'validate', '',false)){
            //自动验证
            $validate_module =  \think\Loader::validate($info['name']);
            if (!$validate_module->check($data)) {
                $this->error = $validate_module->getError();
                return false;
            }
        }

        // 编辑 新增数据
        $res = $model->editData($data);
        if(!$res){
            $this->error = $model->getError();
            return false;
        }
        return $res;
    }
    /**
     * 检测属性的自动验证和自动完成属性 并进行验证
     * 验证场景  insert和update二个个场景，可以分别在新增和编辑
     * @$fields 模型字段属性信息(get_model_attribute($model_id,false))
     * @return boolean  验证通过返回自动完成后的数据 失败(false)
     */
    public function checkModelAttr($fields=false,$data=false){
        if(!$data){
            $data = Request()->param(); //获取数据
        }
        $validate   =   array();
        $auto_data = $data; //自动完成更新接收数据
        $scene = 'auto';//验证场景
        $validate_scene_field = [];//验证字段
        foreach($fields as $key=>$attr){
            if(!isset($attr['validate_time']))
                continue;
            switch ($attr['validate_time']) {
                case '1':
                    if (empty($data['id'])) {//新增数据
                        $scene = 'insert';//验证场景
                        // 自动验证规则
                        if(!empty($attr['validate_rule'])) {
                            if($attr['is_must']){// 必填字段
                                $require = 'require|';
                                $require_msg= $attr['title'].'不能为空|';
                            }
                            $msg = $attr['error_info']?$attr['error_info']:$attr['title'].'验证错误';
                            $validate[]=[$attr['name'], $require.$attr['validate_rule'],$require_msg.$msg];
                            $validate_scene_field[] = $attr['name'];//验证字段
                        }elseif($attr['is_must']){
                            $validate[]=[$attr['name'], 'require', $attr['title'].'不能为空'];
                            $validate_scene_field[] = $attr['name'];//验证字段
                        }

                    }
                    break;
                case '2':
                    if (!empty($data['id'])) {//编辑
                        $scene = 'update';//验证场景
                        // 自动验证规则
                        if(!empty($attr['validate_rule'])) {
                            if($attr['is_must']){// 必填字段
                                $require = 'require|';
                                $require_msg= $attr['title'].'不能为空|';
                            }
                            $msg = $attr['error_info']?$attr['error_info']:$attr['title'].'验证错误';
                            $validate[]=[$attr['name'], $require.$attr['validate_rule'],$require_msg.$msg];
                            $validate_scene_field[] = $attr['name'];//验证字段
                        }elseif($attr['is_must']){
                            $validate[]=[$attr['name'], 'require', $attr['title'].'不能为空'];
                            $validate_scene_field[] = $attr['name'];//验证字段
                        }
                    }
                    break;
                default:
                    $scene = 'auto';//验证场景
                    // 自动验证规则
                    if(!empty($attr['validate_rule'])) {
                        if($attr['is_must']){// 必填字段
                            $require = 'require|';
                            $require_msg= $attr['title'].'不能为空|';
                        }
                        $msg = $attr['error_info']?$attr['error_info']:$attr['title'].'验证错误';
                        $validate[]=[$attr['name'], $require.$attr['validate_rule'],$require_msg.$msg];
                        $validate_scene_field[] = $attr['name'];//验证字段
                    }elseif($attr['is_must']){
                        $validate[]=[$attr['name'], 'require', $attr['title'].'不能为空'];
                        $validate_scene_field[] = $attr['name'];//验证字段
                    }
                    break;
            }

            // 自动完成
            switch ($attr['auto_time']){
                case '1':
                    if(empty($data['id']) && !empty($attr['auto_rule'])){//新增
                        $auto_data[$attr['name']] = $attr['auto_rule']($data[$attr['name']],$data);
                    }
                    break;
                case '2':
                    if (!empty($data['id']) && !empty($attr['auto_rule'])) {//编辑
                        $auto_data[$attr['name']] = $attr['auto_rule']($data[$attr['name']],$data);
                    }
                    break;
                default:
                    if (!empty($attr['auto_rule'])){//始终
                        $auto_data[$attr['name']] = $attr['auto_rule']($data[$attr['name']],$data);
                    }elseif('checkbox'==$attr['type']){ // 多选型
                        $auto_data[$attr['name']] = arr2str($data[$attr['name']]);
                    }elseif('datetime' == $attr['type'] || 'date' == $attr['type']){ // 日期型
                        $auto_data[$attr['name']] = strtotime($data[$attr['name']]);
                    }
                    break;
            }
        }

        //判断验证模型
        $validate_status = Model_hierarchy($this->model_name, 'validate', '',false);
        if($validate_status && $validate_scene_field){//添加验证规则
            $validate_module = \think\Loader::validate($this->model_name);
            $validate_module->Validationrules(['rule'=>$validate,'scene'=>$scene,'scene_fields'=>$validate_scene_field]);
        }elseif($validate_status && !$validate_scene_field){
            $validate_module = \think\Loader::validate($this->model_name);
        }else{
            $validate_module = \think\Validate::make($validate);
            $validate_module->scene($scene);
        }
        if (!$validate_module->check($data)) {
            $this->error = $validate_module->getError();
            return false;
        }
        return $auto_data;
    }
    /**
     * param数据字段转换
     * @author 艺品网络 82550565@qq.com
     * @param $array 要转换的数组
     * @return 返回param请求数据数组
     */
    protected function buildParam($array=[])
    {
        $data = $this->request->param();
        if (is_array($array)&&!empty($array)){
            foreach( $array as $item=>$value ){
                $data[$item] = $data[$value];
            }
        }
        return $data;
    }
    /**
     * @title        DataTables 搜索信息接收初始化
     * @$param  [] 请求信息
     * @author 艺品网络 593657688@qq.com
     */
    public function getDataTableWhere(){
        //获取请求信息
        $param = request()->param();
        $param['draw'] = (int)$param['draw'];
        $param['length'] = (int)$param['length'];
        $listRows = $param['length'] ? $param['length'] : config('list_rows');
        //排序(暂未实现文档动态排序)
        $list_field = $this->info['list_field'];
        $list_field_order = $list_field[$param['order'][0]['column']-1]['field']['0'];
        $list_field_arr =  explode('|',$list_field_order);
        $param['order'] = $list_field_arr['0']. ' ' . $param['order'][0]['dir'];
        $this->info['Tableparam'] = $param;
        //where
        $this->DataTables_where($param);
        return $this;
    }
    /**
     * @title        DataTables 搜索列表
     * @author 艺品网络 593657688@qq.com
     */
    public function getSearchList(){
        $JCmodel_name = $this->info['name'];
        $model_obj = Db::view($JCmodel_name,Db::name($JCmodel_name)->getTableFields());
        $model_obj_two = Db::view($JCmodel_name,Db::name($JCmodel_name)->getTableFields());
        $map = $this->info['where'];
        if(isset($this->info['DanQianmodel'])){
            $DanQianmodel = $this->info['DanQianmodel'];
//            $map['model_id']    =   $DanQianmodel['id'];
            $modelName  =   $JCmodel_name.'_'.$DanQianmodel['name'];
            $model_obj->view($modelName,true,$JCmodel_name.'.id='.$modelName.'.id');
            $model_obj_two->view($modelName,true,$JCmodel_name.'.id='.$modelName.'.id');
        }
        // 分页查询
        $count = $model_obj->where($map)->count();
        $param = $this->info['Tableparam'];
        $listRows = $param['length'] ? $param['length'] : config('list_rows');

        if($param['length'] < 1){
            $listRows = $count;
        }
        $field = isset($this->info['field']) ? $this->info['field'] : true;
        $search_id = array_search("id",$field);
        if($search_id !== false){
            unset($field[$search_id]);
        }
        $Db_list = $model_obj_two->where($map)->order($param['order'])->field($field)->limit($param['start'], $listRows)->select();
        if(isset($this->info['id'])){
            $model_id = isset($Db_list['0']['model_id'])?$Db_list['0']['model_id']:$this->info['id'];
            $parse_list = $this->parseDocumentList($Db_list,$model_id);
        }else{
            $parse_list = $this->parseControllerList($Db_list);
        }
        $Db_list_new = [];
        foreach ($Db_list as $k=>$v){
            foreach ($this->info['list_field'] as $key=>$value){
                $First = $v[$value['name']];//初始值
                $parse_value = $parse_list[$k][$value['name']];//处理后内容
                $instent_value = intent_list_field($v,$value,$this->info['replace_string']);//列表规则解析后
                if($instent_value != $parse_value && $instent_value == $First){
                    $instent_value = $parse_value;
                }
                $Db_list_new[$k][$key+1] = $instent_value;
            }
        }
        $list_arr['data'] = $Db_list_new;
        $list_arr['draw'] = $param['draw'];
        $list_arr['recordsTotal'] = $count;//数据总数
        $list_arr['recordsFiltered'] = $count;//显示数量
        return $list_arr;
    }
    /**
     * 处理控制器列表显示即在控制器里定义的模型
     * @param array $list 列表数据
     * @author 艺品网络 593657688@qq.com
     */
    public function parseControllerList($list){
        $attrList = [];
        foreach ($this->info['fields'] as $key=>$value){
            $attrList = array_merge_recursive($attrList,$value);
        }
        $attrList = array_combine(array_column($attrList,'name'),$attrList);
        return $this->parseList($list,$attrList);
    }
    /**
     * 处理文档列表显示即系统后台定义的模型
     * @param array $list 列表数据
     * @param integer $model_id 模型id
     * @author 艺品网络 593657688@qq.com
     */
    public function parseDocumentList($list,$model_id=null){
        $model_id = $model_id ? $model_id : 1;
        $attrList = get_model_attribute($model_id,false,'id,name,type,extra');
        return $this->parseList($list,$attrList,$model_id);
    }
    /**
     * 对列表数据进行显示处理
     * @param array $list 列表数据
     * @param array $attrList 将name做为key的字段集
     * @param int $model_id 模型id
     * @author 艺品网络 593657688@qq.com
     */
    public function parseList($list,$attrList,$model_id = null){
        if(is_array($list)){
            foreach ($list as $k=>$data){
                foreach($data as $key=>$val){
                    if(isset($attrList[$key])){
                        $extra      =   $attrList[$key]['extra'];
                        $type       =   $attrList[$key]['type'];
                        if('select'== $type || 'checkbox' == $type || 'radio' == $type || 'bool' == $type) {
                            // 枚举/多选/单选/布尔型
                            $options    =   parse_field_attr($extra);
                            if($options && array_key_exists($val,$options)) {
                                $data[$key]    =   $options[$val];
                            }
                        }elseif('date'==$type && is_int($val)){ // 日期型
                            $data[$key]    =   date('Y-m-d',$val);
                        }elseif('datetime' == $type && is_int($val)){ // 时间型
                            $data[$key]    =   date('Y-m-d H:i',$val);
                        }
                    }
                }
                if($model_id)
                    $data['model_id'] = $model_id;
                $list[$k]   =   $data;
            }
        }
        return $list;
    }
    /**
     * 指定info获取字段 支持字段排除和指定数据字段
     * @param mixed   $field
     * @param boolean $except    是否排除
     * @return $this
     * @author 艺品网络 593657688@qq.com
     */
    public function field($field, $except = false)
    {
        if (empty($field)) {
            return $this;
        }
        if (is_string($field)) {
            $field = array_map('trim', explode(',', $field));
            $field = array_flip($field);
        }
        if($except){
            $field  = array_diff_key($this->info, $field);
        }else{
            $field = array_intersect_key($this->info, $field);
        }
        $this->options['field'] = $field;
        return $this;
    }
    /**
     * @title  获取字段配置默认值
     * @author 艺品网络 593657688@qq.com
     * @return $obj
     */
    public function FieldDefaultValue($fields=false){
        if(!$fields)
            $fields = $this->info['fields'];
        $arr = [];
        foreach ($fields as $key=>$value){
            $arr = array_merge_recursive($arr,$value);
        }
        $new_arr = [];
        foreach ($arr as $key=>$value){
            if(isset($value['value']))
                $new_arr[$value['name']] = parse_field_value($value['value']);
        }
        $this->info['field_default_value'] = $new_arr;
        return $this;
    }
    /**
     * @title  拼装View查询对象
     * @author 艺品网络 593657688@qq.com
     * @return $model_obj 查询对象
     */
    public function Viewquery(){
        $JCmodel_name = $this->info['name'];
        $model_obj = Db::view($JCmodel_name,Db::name($JCmodel_name)->getTableFields());
        if(isset($this->info['DanQianmodel'])){
            $DanQianmodel = $this->info['DanQianmodel'];
            $modelName  =   $JCmodel_name.'_'.$DanQianmodel['name'];
            $model_obj->view($modelName,true,$JCmodel_name.'.id='.$modelName.'.id');
        }
        return $model_obj;
    }
    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->error;
    }

    /*
     * @title 设置模型配置信息
     * @$arr array 支持数组[name=>value]
     * @author 艺品网络 593657688@qq.com
     */
    public function setInfo($arr,$value = ''){
        if(is_array($arr)){
            foreach ($arr as $key=>$v){
                $this->info[$key] = $v;
            }
        }else{
            $this->info[$arr] = $value;
        }
        return $this;
    }
}

?>