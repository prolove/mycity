<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络  82550565@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Cache;
/**
 * @title 清理缓存
 * @Author 小矮人 82550565@qq.com <www.twothink.cn>
 */
class Clear extends Admin{
    /*@title 清空缓存 */
    public function delcache(){
        if(Cache::clear()){
            $path=ROOT_PATH.'/runtime';
            $this->delFile($path);
            return $this->success('清理成功');
        }else{
            return $this->error('清理失败');
        }
    }
    // 递归删除文件夹
    public function delFile($path,$delDir = FALSE) {
        $handle = @opendir($path);
        if ($handle) {
            while (false !== ( $item = readdir($handle) )) {
                if ($item != "." && $item != "..")
                    is_dir("$path/$item") ? $this->delFile("$path/$item", $delDir) : unlink("$path/$item");
            }
            closedir($handle);
            if ($delDir) return rmdir($path);
        }else {
            if (file_exists($path)) {
                return unlink($path);
            } else {
                return FALSE;
            }
        }
    }
}