<?php
namespace app\home\controller;

/**
 * 空模块，主要用于显示404页面，请不要删除
 */
class Error extends Home{
    //没有任何方法，直接执行Home的_empty方法
    //请不要删除该控制器

    public function _empty(){
        echo request()->url()."<br/>"; //当前地址
        echo request()->module()."-".request()->controller()."-".request()->action(); //模型、控制器、方法
    }
}