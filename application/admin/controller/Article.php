<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Modelinfo;
use think\Db;
use app\admin\model\AuthGroup;

/**
 * 后台内容控制器
 * @author 艺品网络  <twothink.cn>
 */
class Article extends Admin {

    /* 保存允许访问的公共方法 */
    static protected $allow = array( 'draftbox','mydocument');

    private $cate_id        =   null; //文档分类id

    /**
     * 检测需要动态判断的文档类目有关的权限
     *
     * @return boolean|null
     *      返回true则表示当前访问有权限
     *      返回false则表示当前访问无权限
     *      返回null，则会进入checkRule根据节点授权判断权限
     */
    protected function checkDynamic(){
        $cates = AuthGroup::getAuthCategories(UID);
        switch(strtolower($this->request->action())){
            case 'index':   //文档列表
            case 'add':   // 新增
                $cate_id =  input('cate_id');
                break;
            case 'edit':    //编辑
            case 'update':  //更新
                $doc_id  =  input('id');
                $cate_id =  Db::name('Document')->where(array('id'=>$doc_id))->value('category_id');
                break;
            case 'setstatus': //更改状态
            case 'permit':    //回收站
                $doc_id  =  (array)input('ids');
                $cate_id =  Db::name('Document')->where(array('id'=>array('in',$doc_id)))->column('category_id',true);
                $cate_id =  array_unique($cate_id);
                break;
        }
        if(!$cate_id){
            return null;//不明
        }elseif( !is_array($cate_id) && in_array($cate_id,$cates) ) {
            return true;//有权限
        }elseif( is_array($cate_id) && $cate_id==array_intersect($cate_id,$cates) ){
            return true;//有权限
        }else{
            return false;//无权限
        }
    }

    /**
     * 显示左边菜单，进行权限控制
     * @author 艺品网络  <twothink.cn>
     */
    public function getMenu(){
        //获取动态分类
        $cate_auth  =   AuthGroup::getAuthCategories(UID); //获取当前用户所有的内容权限节点
        $cate_auth  =   $cate_auth == null ? array() : $cate_auth;
        $cate       =   Db::name('Category')->where(array('status'=>1))->field('id,title,pid,allow_publish')->order('pid,sort')->select();
        //没有权限的分类则不显示
        if(!IS_ROOT){
            foreach ($cate as $key=>$value){
                if(!in_array($value['id'], $cate_auth)){
                    unset($cate[$key]);
                }
            }
        }

        $cate           =   list_to_tree($cate);    //生成分类树

        //获取分类id
        $cate_id        =   input('param.cate_id');
        $this->cate_id  =   $cate_id;

        //是否展开分类
        $hide_cate = false;
        $action_name = $this->request->action();
        if($action_name != 'recycle' && $action_name != 'draftbox' && $action_name != 'mydocument'){
            $hide_cate  =   true;
        }

        //生成每个分类的url
        foreach ($cate as $key=>&$value){
            $value['url']   =   'Article/index?cate_id='.$value['id'];
            if($cate_id == $value['id'] && $hide_cate){
                $value['current'] = true;
            }else{
                $value['current'] = false;
            }
            if(!empty($value['_child'])){
                $is_child = false;
                foreach ($value['_child'] as $ka=>&$va){
                    $va['url']      =   'Article/index?cate_id='.$va['id'];
                    if(!empty($va['_child'])){
                        foreach ($va['_child'] as $k=>&$v){
                            $v['url']   =   'Article/index?cate_id='.$v['id'];
                            $v['pid']   =   $va['id'];
                            $is_child = $v['id'] == $cate_id ? true : false;
                        }
                    }
                    //展开子分类的父分类
                    if($va['id'] == $cate_id || $is_child){
                        $is_child = false;
                        if($hide_cate){
                            $value['current']   =   true;
                            $va['current']      =   true;
                        }else{
                            $value['current']   =   false;
                            $va['current']      =   false;
                        }
                    }else{
                        $va['current']      =   false;
                    }
                }
            }
        }
        $this->assign('nodes',      $cate);
        $this->assign('cate_id',    $this->cate_id);
        //获取面包屑信息
        $nav = get_parent_category($cate_id);
        $this->assign('rightNav',   $nav);

        //获取回收站权限
        $this->assign('show_recycle', IS_ROOT || $this->checkRule('Admin/article/recycle'));
        //获取草稿箱权限
        $this->assign('show_draftbox', config('open_draftbox'));
        //获取审核列表权限
        $this->assign('show_examine', IS_ROOT || $this->checkRule('Admin/article/examine'));
    }

    /**
     * 分类文档列表页
     * @param integer $cate_id 分类id
     * @param integer $model_id 模型id
     * @param integer $position 推荐标志
     * @param integer $group_id 分组id
     */
    public function index($cate_id = null, $model_id = null, $position = null,$group_id=null){
        $groups =[];
        $model = null;
        $pid = input('pid',0);
        if($cate_id===null)
            $cate_id = $this->cate_id;
        if(!empty($cate_id)){
            // 获取分类绑定的模型
            if ($pid == 0) {
                $models     =   get_category($cate_id, 'model');
                // 获取分组定义
                $groups		=	get_category($cate_id, 'groups');
                if($groups){
                    $groups	=	parse_field_attr($groups);
                }
            }else{ // 子文档列表
                $models     =   get_category($cate_id, 'model_sub');
            }
            if(is_null($model_id) && !is_numeric($models)){
                // 绑定多个模型 取基础模型的列表定义
                $model = Db::name('Model')->getById($models);
                if($model['extend'] !== 0)
                    $model = Db::name('Model')->getById($model['extend']);
            }else{
                $model_id   =   $model_id ? : $models;
                //获取模型信息
                $model = Db::name('Model')->getById($model_id);
                if (empty($model['list_grid']) && !$model['extend'] == 0) {
                    $model['list_grid'] = Db::name('Model')->getFieldById($model['extend'],'list_grid');
                    empty($model['list_grid']) && $this->error('未定义列表定义');
                }
            }
            $this->assign('model', explode(',', $models));
        }else{
            // 获取基础模型信息
            $model = Db::name('Model')->getByName('document');
            $model_id   =   null;
            $cate_id    =   0;
            $this->assign('model', null);
        }
        $model_info = Modelinfo()->info($model)->list_field()->fields()->setInfo(['pk'=>'id'])->Getparam('info');
        if($this->request->isPost()){
            $fields = $model_info['field']? $model_info['field']:false;
            // 文档模型列表始终要获取的数据字段 用于其他用途
            $fields[] = 'category_id';
            $fields[] = 'model_id';
            $fields[] = 'pid';
            // 列表查询
            $list   =   $this->getDocumentList($cate_id,$model_id,$position,$fields,$group_id);
            return $list;
        }
        //获取左边菜单
        $this->getMenu();
        $this->assign('meta_title', '内容管理');
        /*面包屑标题*/
        //获取面包屑信息
        $title_bar = $this->two_assign['rightNav']?'文档列表[':'文档列表';
        if($this->two_assign['rightNav']){
            foreach ($this->two_assign['rightNav'] as $key=>$value){
                $title_bar .= "<a href=".url('index',['cate_id'=>$value['id']]).">{$value['title']}</a>";
                if(count($this->two_assign['rightNav']) > $key+1)
                    $title_bar .= '>';
            }
        }else{
            $title_bar .= empty($position)?' [ 全部':'<a href="'.url('article/index').'">全部</a>';$title_bar .= '&nbsp;';
			foreach(config('document_position') as $key=>$value){
                $title_bar .= $position!= $key?'<a href="'.url('index',array('position'=>$key)).'">'.$value.'</a>':$value;
                $title_bar .= '&nbsp;';
            }
        }
        $title_bar .= ']';
        //检查该分类是否允许发布内容
        $allow_publish  =   get_category($cate_id, 'allow_publish');
        if($allow_publish == 0){
            $title_bar .= '（该分类不允许发布内容）';
        }
        if (count($this->two_assign['model']) > 1){
            $title_bar .= '[ 模型：';
			$title_bar .= empty($model_id) ? '<strong>全部</strong>': '<a href="'.url('index',array('pid'=>$pid,'cate_id'=>$cate_id)).'">全部</a>';
            foreach ($this->two_assign['model'] as $key=>$value){
                $title_bar .= $model_id != $value?'<a href="'.url('index',array('pid'=>$pid,'cate_id'=>$cate_id,'model_id'=>$value)).'">'.get_document_model($value,'title').'</a>':'<strong>'.get_document_model($value,'title').'</strong>';
                $title_bar .= '&nbsp;';
            }
            $title_bar .= ']';
        }
        if(!empty($groups)){
            $title_bar .= '[ 分组：';
            $title_bar .= empty($group_id)?'<strong>全部</strong>':'<a href="'.url('index',array('pid'=>$pid,'cate_id'=>$cate_id)).'">全部</a>';
            foreach ($groups as $key=>$value){
                $title_bar .= $group_id != $key?'<a href="'.url('index',array('pid'=>$pid,'cate_id'=>$cate_id,'model_id'=>$model_id,'group_id'=>$key)).'">'.$value.'</a>':'<strong>'.$value.'</strong>';
            }
            $title_bar .= ']';
        }
        $this->assign('title_bar',$title_bar);
        //模型定义
        $model_info['pk'] = 'id';
        if(count($this->two_assign['model']) > 1){
            foreach ($this->two_assign['model'] as $value){
                $add_button[] = ['title'=>get_document_model($value,'title'),'url'=>'article/add?cate_id='.$cate_id.'&model_id='.$value.'&pid='.$pid.'&group_id='.$group_id];
            }
            $add_button = ['title'=>'新增','type'=>'select','extra'=>$add_button,'class'=>'bg-aqua'];
        }else{
            $add_button = ['title'=>'新增','url'=>'article/add?cate_id='.$cate_id.'&pid='.$pid.'&model_id='.$model_id,'class'=>'bg-aqua ajax-get iframe','icon'=>'','ExtraHTML'=>''];
        }
        $model_info['button']     = [
            $add_button,
            ['title'=>'启用','url'=>'setstatus?status=1&cate_id='.$cate_id,'icon'=>'','class'=>'bg-teal ajax-post','ExtraHTML'=>'target-form="ids"'],
            ['title'=>'禁用','url'=>'setstatus?status=0&cate_id='.$cate_id,'icon'=>'','class'=>'bg-yellow ajax-post confirm','ExtraHTML'=>'target-form="ids"'],
            ['title'=>'删除','url'=>'setstatus?status=-1&cate_id='.$cate_id,'icon'=>'','class'=>'bg-red ajax-post confirm','ExtraHTML'=>'target-form="ids"']
        ];
        $model_info['url'] = $this->request->url();
        $this->assign('model_info',$model_info);
        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $tpl = !empty($model_info['template_list'])?'think/'.$model_info['template_list']:'base/list';
        return $this->fetch($tpl);
    }

    /**
     * 默认文档列表方法
     * @param integer $cate_id 分类id
     * @param integer $model_id 模型id
     * @param integer $position 推荐标志
     * @param mixed $field 字段列表
     * @param integer $group_id 分组id
     */
    protected function getDocumentList($cate_id=0,$model_id=null,$position=null,$field=true,$group_id=null){
        /* 查询条件初始化 */
    	$data=input();
        $map = array();
        if(isset($data['status'])){
            $map['status'] = input('status');
            $status = $map['status'];
        }else{
            $status = null;
            $map['status'] = array('in', '0,1,2');
        }
        if ( isset($data['time-start']) ) {
            $map['update_time'][] = array('egt',strtotime(input('time-start')));
        }
        if ( isset($data['time-end']) ) {
            $map['update_time'][] = array('elt',24*60*60 + strtotime(input('time-end')));
        }
        if ( isset($data['nickname']) ) {
            $map['uid'] = Db::name('Member')->where(array('nickname'=>input('nickname')))->value('uid');
        }

        //获取当前模型形象
        empty($model_id) && $model_id=1;
        $dq_model =   Db::name('Model')->getById($model_id);
        if($dq_model['extend'] != 0){//获取基础模型name
        	 $JCmodel_name = Db::name('Model')->getFieldById($dq_model['extend'],'name');
        }else{
        	$JCmodel_name = $dq_model['name'];
        }

        //列表定义 组合DataTable查询条件
        $model_obj = Modelinfo();
        $model_info = $model_obj->modelinfo($model_id)->list_field()->Getparam('info');
        //获取请求信息
        $param = $this->request->param();
        $param['draw'] = (int)$param['draw'];
        $param['length'] = (int)$param['length'];
        $listRows = $param['length'] ? $param['length'] : config('list_rows');
        //排序(暂未实现文档动态排序)
        $list_field = $model_info['list_field'];
        $list_field_order = $list_field[$param['order'][0]['column']-1]['field']['0'];
        $list_field_arr =  explode('|',$list_field_order);
        $param['order'] = $list_field_arr['0']. ' ' . $param['order'][0]['dir'];
        //where
        $map = $model_obj->DataTables_where($param)->Getparam('info.where');

        // 构建列表数据
        if($cate_id){
            $map['category_id'] =   $cate_id;
        }
        $map['pid']         =   input('pid',0);
        if($map['pid']){ // 子文档列表忽略分类
            unset($map['category_id']);
        }
        if(!is_null($position)){
            $map['position'] = $position;
        }
        if(!is_null($group_id)){
            $map['group_id']	=	$group_id;
        }
        if($dq_model['extend'] != 0){
            $order = 'level desc,id desc';
        }else{
            $order = 'id desc';
        }

        $DocumentNew =  Db::view($JCmodel_name,Db::name($JCmodel_name)->getTableFields());
        $Document = Db::view($JCmodel_name,Db::name($JCmodel_name)->getTableFields());
        if(!is_null($model_id) && $dq_model['extend'] != 0){
            $map['model_id']    =   $model_id;
//            if(is_array($field) && array_diff(Db::name($JCmodel_name)->getTableInfo(false,'fields'),$field)){
                $modelName  =   $JCmodel_name.'_'.$dq_model['name'];
                $DocumentNew->view($modelName,true,$JCmodel_name.'.id='.$modelName.'.id');
                $Document->view($modelName,true,$JCmodel_name.'.id='.$modelName.'.id');
//            }
        }
		// 分页查询
        $count = $Document->where($map)->count();
        if($param['length'] < 1){
            $listRows = $count;
        }
        $search_id = array_search("id",$field);
        if($search_id !== false){
            unset($field[$search_id]);
        }
        $Db_list = $DocumentNew->where($map)->order($order)->field($field)->limit($param['start'], $listRows)->select();
        $Db_list = $this->parseDocumentList($Db_list,$model_id);
        $Db_list_new = [];
        foreach ($Db_list as $k=>$v){
            foreach ($list_field as $key=>$value){
                $Db_list_new[$k][$key+1] = intent_list_field($v,$value);
            }
        }
        $list_arr['data'] = $Db_list_new;
        $list_arr['draw'] = $data['draw'];
        $list_arr['recordsTotal'] = $count;//数据总数
        $list_arr['recordsFiltered'] = $count;//显示数量
        return json($list_arr);
    }

    /**
     * 设置一条或者多条数据的状态
     */
    public function setStatus($cate_id=null,$pk='id'){
    	// 检查支持的文档模型
        if(!empty($cate_id)){
        	$modelList =   Db::name('Category')->getFieldById($cate_id,'model');   // 当前分类支持的文档模型
        }else{
        	$modelList = 1;
        }
    	$model = Db::name('Model')->getById($modelList);
    	if($model['extend'] == 0){
        	$model_name = $model['name'];
        }else{
        	$model_name = Db::name('Model')->getFieldById($model['extend'],'name');
        }
        return parent::setStatus($model_name,$pk);
    }

    /**
     * 文档新增页面初始化
     * @author 艺品网络  <twothink.cn>
     */
    public function add(){
        //获取左边菜单
        $this->getMenu();

        $cate_id    =   input('cate_id',0);
        $model_id   =   input('model_id',0);
		$group_id	=	input('group_id','');

        empty($cate_id) && $this->error('分类参数不能为空！');
        empty($model_id) && $this->error('该分类未绑定模型！');

        //检查该分类是否允许发布
        $allow_publish = check_category($cate_id);
        !$allow_publish && $this->error('该分类不允许发布内容！');

        // 获取当前的模型信息
        $model_info = $model    =   get_document_model($model_id);

        //处理结果
        $info['pid']            =   input('pid')?input('pid'):0;

        if($info['pid']){
            // 获取上级文档
        	$JCmodel_name = Db::name('Model')->getFieldById($model['extend'],'name');
            $article            =   Db::name($JCmodel_name)->field('id,title,type')->find($info['pid']);
            $this->assign('article',$article);
        }
        //获取表单字段排序
        $model_info['fields'] = get_model_attribute($model['id']);
        $model_info['url'] = url('update');

        $this->assign('data',Modelinfo()->FieldDefaultValue($model_info['fields'])->Getparam('info.field_default_value'));
        $this->assign('model_info', $model_info);
        $this->assign('meta_title','新增'.$model['title']);
        $tpl = !empty($model_info['template_add'])?'think/'.$model_info['template_add']:'base/add';
        return $this->fetch($tpl);
    }

    /**
     * 文档编辑页面初始化
     * @author 艺品网络  <twothink.cn>
     */
    public function edit(){
        //获取左边菜单
        $this->getMenu();

        $id     =   input('id','');
        if(empty($id)){
            $this->error('参数不能为空！');
        }
        $model_id = input('param.model',0);
        $cate_id =   input('param.cate_id',0);
        //获取模型信息
        if(empty($model_id) && !empty($cate_id)){
        	$model_id =   Db::name('Category')->getFieldById($cate_id,'model');   // 当前分类支持的文档模型
        }
        $model = Db::name('Model')->getById($model_id);

        //继承模型先实例化基础模型数据
        if($model['extend'] != 0){
        	$model_id = $model['extend'];
        }
        //获取基础模型数据
        if(!$jc_data = logic($model_id)->detail($id)){
            $this->error('数据不存在');
        }
        //获取扩展模型数据
        if($jc_data['model_id']){
            $kz_data  = logic($jc_data['model_id'])->detail($id);
            $kz_data || $this->error('扩展数据不存在');
        }
        if($kz_data){
            $data = array_merge($jc_data, $kz_data);
        }else{
            $data = $jc_data;
        }
        if($data['pid']){
            // 获取上级文档
            $article        =   Db::name(get_table_name($model_id))->field('id,,titletype')->find($data['pid']);
            $this->assign('article',$article);
        }

        // 获取当前的模型信息
        $model_info = Modelinfo()->modelinfo($data['model_id'])->fields()->setInfo('url',url('update'))->Getparam('info');
        $model_info['fields'][1][] = ['name'=>'id','type'=>'num','is_show'=>4];
        $this->assign('data', $data);//dump($data);
        $this->assign('model_id', $data['model_id']);
        //获取当前分类的文档类型
        $this->assign('type_list', get_type_bycate($data['category_id']));
        $this->assign('model_info', $model_info);
        $this->assign('meta_title', '编辑文档');
        $tpl = !empty($model_info['template_edit'])?'think/'.$model_info['template_edit']:'base/edit';
        return $this->fetch($tpl);
    }

    /**
     * 更新一条数据
     * @author 艺品网络  <twothink.cn>
     */
    public function update(){
    	/* 获取数据对象 */
    	$model_id = input('param.model_id',0);
    	$data = input();
    	if(!$model_id)
    		$this->error('模型id不能为空');
    	//获取模型信息
    	$model = Db::name('Model')->getById($model_id);
        if($model['extend']){
            //更新基础模型
            $logic = logic($model['extend']);
            $res_id = $logic->editData();
            $res_id || $this->error($logic->getError());
        }
        $update_id = '';
        if(empty($data['id']) && $model['extend'] != 0){
            $update_id = $res_id;
        }
        //更新当前模型
        $logic = logic($model['id']);
        $res = $logic->editData($update_id);
        $res || $this->error($logic->getError());
        $this->success(!empty($data['id'])?'更新成功':'新增成功', Cookie('__forward__'));
    }

    /**
     * 待审核列表
     */
    public function examine(){
        //获取左边菜单
        $this->getMenu();

        $map['status']  =   2;
        if ( !IS_ROOT ) {
            $cate_auth  =   AuthGroup::getAuthCategories(UID);
            if($cate_auth){
                $map['category_id']    =   array('IN',$cate_auth);
            }else{
                $map['category_id']    =   -1;
            }
        }
        $list = $this->lists(db('Document'),$map,'update_time desc');
        //处理列表数据
        if(is_array($list)){
            foreach ($list as $k=>&$v){
                $v['username']      =   get_nickname($v['uid']);
            }
        }

        $this->assign('list', $list);
        $this->assign('meta_title','待审核');
        return $this->fetch();
    }

    /**
     * 回收站列表
     * @param int $model_id 模型ID
     * @author 艺品网络  <twothink.cn>
     */
    public function recycle($model_id = 1){
        //获取模型信息
        $model = get_document_model($model_id);
        $this->model_info = $model_info = Modelinfo()->modelinfo($model['id'])->list_field()->Getparam('info');
        if($this->request->isPost()){
            $map['status']  =   -1;
            if ( !IS_ROOT ) {
                $cate_auth  =   AuthGroup::getAuthCategories(UID);
                if($cate_auth){
                    $map['category_id']    =   array('IN',$cate_auth);
                }else{
                    $map['category_id']    =   -1;
                }
            }
            $this->model_info['where_solid'] = $map;
            return parent::_index();
        }else{
            //获取左边菜单
            $this->getMenu();
            $this->assign('meta_title','回收站');
            //模型定义
            $model_info['pk'] = 'id';
            $model_info['button'] = [
                ['title'=>'清空','url'=>'clear?model_id='.$model_id,'icon'=>'','class'=>'btn-success ajax-post confirm','ExtraHTML'=>'target-form="ids"'],
                ['title'=>'还原','url'=>'permit?model_id='.$model_id,'icon'=>'','class'=>'btn-danger ajax-post confirm','ExtraHTML'=>'target-form="ids"']
            ];
            $this->assign('model_info',$model_info);
            return $this->fetch('base/list');
        }
    }

    /**
     * 写文章时自动保存至草稿箱
     * @author 艺品网络  <twothink.cn>
     */
    public function autoSave(){
        $res = model('Document')->autoSave();
        if($res !== false){
            $return['data']     =   $res;
            $return['info']     =   '保存草稿成功';
            $return['status']   =   1;
            return json($return);
        }else{
            $this->error('保存草稿失败：'.Db::name('Document')->getError());
        }
    }

    /**
     * 草稿箱
     * @author 艺品网络  <twothink.cn>
     */
    public function draftBox(){
        //获取左边菜单
        $this->getMenu();

        $Document   =   \think\Loader::model('Document','logic');
        $map        =   array('status'=>3,'uid'=>UID);
        $list       =   $this->lists($Document,$map);
        //获取状态文字
        //int_to_string($list);

        $this->assign('list', $list);
        $this->assign('meta_title','草稿箱');
        return $this->fetch();
    }

    /**
     * 我的文档
     * @author 艺品网络  <twothink.cn>
     */
    public function mydocument($status = null, $title = null){
        //获取左边菜单
        $this->getMenu();

        $Document   =   \think\Loader::model('Document','logic');
        /* 查询条件初始化 */
        $map['uid'] = UID;
        if(isset($title)){
            $map['title']   =   array('like', '%'.$title.'%');
        }
        if(isset($status)){
            $map['status']  =   $status;
        }else{
            $map['status']  =   array('in', '0,1,2');
        }
        $get_data = input();
        if ( isset($get_data['time-start']) ) {
            $map['update_time'][] = array('egt',strtotime(I('time-start')));
        }
        if ( isset($get_data['time-end']) ) {
            $map['update_time'][] = array('elt',24*60*60 + strtotime(I('time-end')));
        }
        //只查询pid为0的文章
        $map['pid'] = 0;
        $list = $this->lists($Document,$map,'update_time desc');
        int_to_string($list);
//         $list = $this->parseDocumentList($list,1);

        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('meta_title','我的文档');
        return $this->fetch();
    }

    /**
     * 还原被删除的数据
     * @author 艺品网络  <twothink.cn>
     */
    public function permit(){
        /*参数过滤*/
        $data = $this->request->param();
        $ids = $data['id'];
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        //获取模型信息
        $model = get_document_model($data['model_id']);
        if($model['extend'] > 0){
        	$model_name =get_document_model($model['extend'],'name');
        }else{
        	$model_name = $model['name'];
        }
        /*拼接参数并修改状态*/
        $Model  =   $model_name;
        $map    =   array();
        if(is_array($ids)){
            $map['id'] = array('in', $ids);
        }elseif (is_numeric($ids)){
            $map['id'] = $ids;
        }
        $this->restore($Model,$map);
    }

    /**
     * 清空回收站
     * @author 艺品网络  <twothink.cn>
     */
    public function clear($model_id){

    	//获取模型信息
    	$model = get_document_model($model_id);
    	if($model['extend'] > 0){
    		$model_id =get_document_model($model['extend'],'id');
    	}
    	$model = logic($model_id,'Documentbase');
        $res = $model->remove();
        if($res !== false){
            $this->success('清空回收站成功！');
        }else{
            $this->error('清空回收站失败！');
        }
    }

    /**
     * 移动文档
     * @author 艺品网络  <twothink.cn>
     */
    public function move() {
    	$data= input('ids/a');
        if(empty($data)) {
            $this->error('请选择要移动的文档！');
        }
        session('moveArticle', $data);
        session('copyArticle', null);
        $this->success('请选择要移动到的分类！');
    }

    /**
     * 拷贝文档
     * @author 艺品网络  <twothink.cn>
     */
    public function copy() {
    	$data= input('ids/a');
        if(empty($data)) {
            $this->error('请选择要复制的文档！');
        }
        session('copyArticle', $data);
        session('moveArticle', null);
        $this->success('请选择要复制到的分类！');
    }

    /**
     * 粘贴文档
     * @author 艺品网络  <twothink.cn>
     */
    public function paste() {
        $moveList = session('moveArticle');
        $copyList = session('copyArticle');
        if(empty($moveList) && empty($copyList)) {
            $this->error('没有选择文档！');
        }
        $post_data = input('param.');
        if(!isset($post_data['cate_id'])) {
            $this->error('请选择要粘贴到的分类！');
        }
        $cate_id = $post_data['cate_id'];   //当前分类
        $pid = input('post.pid',0);        //当前父类数据id
        //检查所选择的数据是否符合粘贴要求
        $check = $this->checkPaste(empty($moveList) ? $copyList : $moveList, $cate_id, $pid);
        if(!$check['status']){
            $this->error($check['info']);
        }

        if(!empty($moveList)) {// 移动    TODO:检查name重复
            foreach ($moveList as $key=>$value){
                $Model              =   db('Document');
                $map['id']          =   $value;
                $data['category_id']=   $cate_id;
                $data['pid']        =   $pid;
                //获取root
                if($pid == 0){
                    $data['root'] = 0;
                }else{
                    $p_root = $Model->getFieldById($pid, 'root');
                    $data['root'] = $p_root == 0 ? $pid : $p_root;
                }
                $res = $Model->where($map)->save($data);
            }
            session('moveArticle', null);
            if(false !== $res){
                $this->success('文档移动成功！');
            }else{
                $this->error('文档移动失败！');
            }
        }elseif(!empty($copyList)){ // 复制
            foreach ($copyList as $key=>$value){
            	// 检查支持的文档模型
            	if($pid){
            		$modelList =   Db::name('Category')->getFieldById($cate_id,'model_sub');   // 当前分类支持的文档模型
            	}else{
            		$modelList =   Db::name('Category')->getFieldById($cate_id,'model');   // 当前分类支持的文档模型
            	}
            	$model = Db::name('Model')->getById($modelList);
            	if($model['extend'] == 0){
            		$Model = logic($model['id'],'Documentbase');
            	}else{
            		$Model = logic($model['extend'],'Documentbase');
            	}
                $Model  =   Db::name('Document');
                $data   =   $Model->find($value);
                unset($data['id']);
                unset($data['name']);
                $data['category_id']    =   $cate_id;
                $data['pid']            =   $pid;
                $data['create_time']    =   time();
                $data['update_time']    =   time();
                //获取root
                if($pid == 0){
                    $data['root'] = 0;
                }else{
                    $p_root = $Model->getFieldById($pid, 'root');
                    $data['root'] = $p_root == 0 ? $pid : $p_root;
                }
                $result   =  $Model->insertGetId($data);
                if($result){
                    $logic      =   logic($data['model_id']);
                    $data       =   $logic->detail($value); //获取指定ID的扩展数据

                    $data['id'] =   $result;
                    $res        =   $logic->insert($data);
                }
            }
            session('copyArticle', null);
            if($res){
                $this->success('文档复制成功！');
            }else{
                $this->error('文档复制失败！');
            }
        }
    }

    /**
     * 检查数据是否符合粘贴的要求
     * @author 艺品网络  <twothink.cn>
     */
    protected function checkPaste($list, $cate_id, $pid){
        $return     =   array('status'=>1);
        // 检查支持的文档模型
        if($pid){
            $modelList =   Db::name('Category')->getFieldById($cate_id,'model_sub');   // 当前分类支持的文档模型
        }else{
            $modelList =   Db::name('Category')->getFieldById($cate_id,'model');   // 当前分类支持的文档模型
        }
        $model = Db::name('Model')->getById($modelList);
        if($model['extend'] == 0){
        	$Document = logic($model['id'],'Documentbase');
        }else{
        	$Document = logic($model['extend'],'Documentbase');
        }
        foreach ($list as $key => $value){
            //不能将自己粘贴为自己的子内容
            if($value == $pid){
                $return['status'] = 0;
                $return['info'] = '不能将编号为 '.$value.' 的数据粘贴为他的子内容！';
                return $return;
            }
            // 移动文档的所属文档模型
            $modelType  =   $Document->getFieldById($value,'model_id');
            if(!in_array($modelType,explode(',',$modelList))) {
                $return['status'] = 0;
                $return['info'] = '当前分类的文档模型不支持编号为 '.$value.' 的数据！';
                return $return;
            }
        }

        // 检查支持的文档类型和层级规则
        $typeList =   Db::name('Category')->getFieldById($cate_id,'type'); // 当前分类支持的文档模型
        foreach ($list as $key=>$value){
            // 移动文档的所属文档模型
            $modelType  =   $Document->getFieldById($value,'type');
            if(!in_array($modelType,explode(',',$typeList))) {
                $return['status'] = 0;
                $return['info'] = '当前分类的文档类型不支持编号为 '.$value.' 的数据！';
                return $return;
            }
            $res = $Document->checkDocumentType($modelType, $pid);
            if(!$res['status']){
                $return['status'] = 0;
                $return['info'] = $res['info'].'。错误数据编号：'.$value;
                return $return;
            }
        }

        return $return;
    }
}
