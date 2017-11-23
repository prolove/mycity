<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn>
// +----------------------------------------------------------------------

namespace app\home\controller;

use think\Controller;
use think\Cache;
use think\Loader;
use think\Db;
/**
 * 插件执行默认控制器
 * @Author 苹果 <593657688@qq.com>
 */
class Addons extends Controller
{
    public $addon_path; // 插件路径

    public function _initialize(){

        /* 读取数据库中的配置 */
        $config = Cache::get('db_config_data_addons');
        if(!$config){
            $config = api('Config/lists');
            Cache::set('db_config_data_addons',$config);
        }
        config($config); //添加配置

        if(!$this->addon_path){
            $param = $this->request->param();
            $this->addon_path = "../addons/{$param['_addons']}/";
        }
        //加载插件函数文件
        if (file_exists($this->addon_path.'common.php')) {
            include_once $this->addon_path.'common.php';
        }
    }
    /**
     * 插件执行
     */
    public function execute($_addons = null, $_controller = null, $_action = null)
    {
        if (!empty($_addons) && !empty($_controller) && !empty($_action)) {
            // 获取类的命名空间
            $class = get_addon_class($_addons, 'controller', $_controller);

            if(class_exists($class)) {
                $model = new $class();
                if ($model === false) {
                    $this->error(lang('addon init fail'));
                }

                // 调用操作
                return  \think\App::invokeMethod([$class, $_action]);
            }else{
                $this->error(lang('控制器不存在'.$class));
            }
        }
        $this->error(lang('没有指定插件名称，控制器或操作！'));
    }
    /**
     * 通用列表查询
     * $model_info 模型定义规则 或者  系统模型ID
     * $pattern 查询模式 true:datatable插件模式 false:thinkphp系统列表模式
     * $isQuery 是否进行查询
     * return  array
     * @author 小矮人 <twothink.cn>
     */
    protected function getList($model_info,$pattern=true,$isQuery = true){
        $param = $this->request->param();
        if($pattern){
            if(is_numeric($model_info)){
                $model_obj = Modelinfo()->scene($param['_action'])->modelinfo($model_info)->list_field()->fields();
            }else{
                $model_obj = Modelinfo()->scene($param['_action'])->info($model_info);
            }
            $model_info = $model_obj->Getparam('info');
            if($this->request->isPost() && $isQuery){
                $list = $model_obj->getDataTableWhere()->getSearchList();
                $data['list'] = $list;
            }else{
                $data['model_info'] = $model_info;
            }
            return $data;
        }else{
            //TODO 后期扩展系统列表模式
        }
    }
    /**
     * 通用模型编辑和更新规则解析
     * $model_def 模型定义规则 或者  系统模型ID
     * return $model_info 解析后的模型规则
     * @author 小矮人 <twothink.cn>
     */
    protected function getEdit($model_def){
        $param = $this->request->param();
        if(is_numeric($model_def)){
            $model_obj = Modelinfo()->scene($param['_action'])->modelinfo($model_def)->list_field()->fields()->FieldDefaultValue();
        }else{
            $model_obj = Modelinfo()->scene($param['_action'])->info($model_def)->FieldDefaultValue();
        }
        $model_info = $model_obj->Getparam('info');
        $arr['model_info'] = $model_info;
        $arr['data'] = $model_info['field_default_value'];
        return $arr;
    }
    /**
     * 设置一条或者多条数据的状态
     * $model 模型名称或模型对象
     * $pk 主键
     */
    protected function setState($model=false,$pk='id'){
        $ids    =   input('ids/a');
        $status =   input('status');

        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        $map[$pk] = ['in',$ids];
        switch ($status){
            case -1 :
                $this->delete($model, $map,['success'=>'删除成功','error'=>'删除失败']);
                break;
            case '0'  :
                $this->forbid($model, $map,['success'=>'禁用成功','error'=>'禁用失败']);
                break;
            case 1  :
                $this->resume($model, $map,['success'=>'启用成功','error'=>'启用失败']);
                break;
            case 'del':
                $this->delRow($model, $map,['success'=>'删除成功','error'=>'删除失败']);
                break;
            default :
                $this->error('参数错误');
                break;
        }
    }
    /**
     * 条目假删除
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     * * @author 艺品网络 <twothink.cn>
     */
    protected function delete ( $model , $where = [] , $msg = ['success'=>'删除成功！', 'error'=>'删除失败！']) {
        $data['status']  =   -1;
        $this->editRow($model , $data, $where, $msg);
    }
    /**
     * 条目真删除
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     * * @author 艺品网络 <twothink.cn>
     */
    final protected function delRow ( $model,$where , $msg=false ){
        $msg   = array_merge( array( 'success'=>'删除成功！', 'error'=>'删除失败！', 'url'=>'' ,'ajax'=>var_export($this->request->isAjax(), true)) , (array)$msg );
        if(empty($model)){
            $param = $this->request->param();
            $model = Loader::parseName($param['_controller'],1);
        }
        if(!is_object($model)){
            $model = Db::name($model);
        }
        if( $model->where($where)->delete()!==false ) {
            $this->success($msg['success']);
        }else{
            $this->error($msg['error']);
        }
    }
    /**
     * 还原条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     * @author 艺品网络  <twothink.cn>
     */
    protected function restore (  $model , $where = array() , $msg = array( 'success'=>'状态还原成功！', 'error'=>'状态还原失败！')){
        $data    = array('status' => 1);
        $where   = array_merge(array('status' => -1),$where);
        $this->editRow(   $model , $data, $where, $msg);
    }
    /**
     * 禁用条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的 where()方法的参数
     * @param array  $msg   执行正确和错误的消息,可以设置四个元素 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     * * @author 艺品网络 <twothink.cn>
     */
    protected function forbid ( $model , $where = [] , $msg = ['success'=>'状态禁用成功！', 'error'=>'状态禁用失败！']){
        $data    =  ['status' => 0];
        $this->editRow( $model , $data, $where, $msg);
    }
    /**
     * 恢复条目
     * @param string $model 模型名称,供D函数使用的参数
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     * * @author 艺品网络 <twothink.cn>
     */
    protected function resume (  $model , $where = [] , $msg = ['success'=>'状态恢复成功！', 'error'=>'状态恢复失败！']){
        $data    =  ['status' => 1];
        $this->editRow(   $model , $data, $where, $msg);
    }
    /**
     * 对数据表中的单行或多行记录执行修改 GET参数id为数字或逗号分隔的数字
     *
     * @param string $model 模型名称
     * @param array  $data  修改的数据
     * @param array  $where 查询时的where()方法的参数
     * @param array  $msg   执行正确和错误的消息 array('success'=>'','error'=>'', 'url'=>'','ajax'=>false)
     *                     url为跳转页面,ajax是否ajax方式(数字则为倒数计时秒数)
     * * @author 艺品网络 <twothink.cn>
     */
    final protected function editRow ( $model ,$data, $where , $msg=false ){
        $msg   = array_merge( array( 'success'=>'操作成功！', 'error'=>'操作失败！', 'url'=>'' ,'ajax'=>var_export($this->request->isAjax(), true)) , (array)$msg );
        if(empty($model)){
            $param = $this->request->param();
            $model = Loader::parseName($param['_controller'],1);
        }
        if(!is_object($model)){
//            $path = 'addons\adver\model\\'.$model;
//            $model = new $path;
            $model = Db::name($model);
        }
        if( $model->where($where)->update($data)!==false ) {
            $this->success($msg['success']);
        }else{
            $this->error($msg['error']);
        }
    }
}
