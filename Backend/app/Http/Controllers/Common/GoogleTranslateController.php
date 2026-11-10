<?php
/*
 * File: GoogleTranslateController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-31 3:54:58 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Common;

use Illuminate\Http\Request;
/**
 * 谷歌翻译类
 * Class GoogleTranslate
 * @package app\common
 */
class GoogleTranslateController 
{

    /**
     * @param string $text 翻译原文
     * @param string $source 源语言
     * @param string $target 目标语言
     * @return string
     * $target zh-CN:翻译为中文  en:翻译为英文
	 * To English
     */
    public function TranslateApi($text='' , $target = 'en'){
        $result = self::translate($text, $target);
        return $result;
    }



	/**
     * Internal function to make the request to the translator service
     * https://translate.google.com/?sl=auto&tl=en&text=你好&op=translate
     * @param $text
     * @param string $target zh-CN:翻译为中文  en:翻译为英文
     * @return string
     */
    public static function translate($text ,$target='zh-CN'){
        $entext = urlencode($text);
        $url = 'https://translate.google.com/translate_a/single?client=gtx&dt=t&ie=UTF-8&oe=UTF-8&sl=auto&tl='.$target.'&q='.$text;
        set_time_limit(0);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS,20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 40);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if(!empty($result)){
            foreach($result[0] as $k){
                $v[] = $k[0];
            }
            return implode(" ", $v);
        }
    }






}
