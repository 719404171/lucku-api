<?php
/*
 * @Description:
 * @Version: 2.0
 * @Autor: wangyuqiang
 * @Date: 2020-12-16 11:18:25
 * @LastEditors: wangyuqiang
 * @LastEditTime: 2021-04-06 11:24:43
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Common\GeTuiController;
use App\Http\Controllers\Common\MonologsController as Logs;//日志类
use App\Http\Controllers\Common\GoogleTranslateController as GoogleTranslate;//谷歌翻译



class TestController extends Controller{


    /**
     * 翻译
     * @param string $text 翻译原文
     * @return string  $target zh-CN:翻译为中文  en:翻译为英文
     * To Chinese
     */
    public function Translate(Request $request)
    {
        $text = $request->post('text');
        $target = $request->post('target');
        $results=GoogleTranslate::translate($text, $target);
        return $results;
    }





    /**
     * 测试
     * @return  $rep;
     */
    public function Test(Request $request) {
        $language = app('global_language') == "en" ? "en_" : ""; 
        return $language;
    }








    


     /**
     * 保存图片到本地
     *
     */
      public function uploadAvatar(){
          try {
            $imageUrl= config('services.basics.base_logo');//图片地址
            $name=config('services.basics.base_name').'.png';//图片名称
            $exists=Storage::exists('public/'.$name);//图片是否存在
            if ($exists) {
                return  '../../../../../storage/app/public/' . $name;
            }
            // 使用file_get_contents读取图片内容
            $imageData = file_get_contents($imageUrl);
            // 转换成base64编码的字符串
            $imgBase64Code = "data:image/jpeg;base64," . base64_encode($imageData);
            if (!empty($imgBase64Code)) {
                // 将文件保存到默认的磁盘，并指定新的文件名
                $info = Storage::putFileAs('public', $imgBase64Code, $name);
                if ($info) {
                   return  '../../../../../storage/app/public/' . $name;
                }
            }else{
                return '../../../../../logo.png';
            }

          } catch (Exception $e) {
              return $this->ApiFailure($e);
          }
    }












































    



}
