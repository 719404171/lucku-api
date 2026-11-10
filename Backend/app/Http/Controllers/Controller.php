<?php
/*
 * File: Controller.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-07-18 1:58:54 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers;

use OSS\OssClient;
use OSS\Core\OssException;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Common\MonologsController as Logs;//日志类
use App\Http\Controllers\Common\GoogleTranslateController as GoogleTranslate;//谷歌翻译

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;



    

/*********************************************************return common**********************************************************************/

    /**
    * 成功返回数据
    * @param $message 信息
    */
    protected function success($msg = "Success" , $results=''){

        // $results=GoogleTranslate::translate($results, 'en');

        return ["status" => 200, "msg" => $msg, "results" => $results];
    }


    /**
    * 失败返回数据
    * @param $message 信息
    */
    protected function error($msg = "Network Error" , $results=''){

        if(empty($results)){
            return ["status" => 500, "msg" => $msg];
        }else{
            return ["status" => 500, "msg" => $msg ,"results" => $results];
        }


    }


    /**
    * 参数错误返回数据
    * @param $message 信息
    */
    protected function parametersError($msg = "No Parameters" , $results=''){

        if(empty($results)){
            return ["status" => 501, "msg" => $msg];
        }else{
            return ["status" => 501, "msg" => $msg ,"results" => $results];
        }

    }



    /**
    * 输出日志信息
    * @param $message 信息
    * @param array $data 数据
    * @param string $filename 文件名称  默认：info
    * @param string $isDate 是否按月份分文件夹 默认：否
    */
    protected function LogInfo($message, $data, $filename, $isDate){

        Logs::info($message, json_encode($data), $filename, $isDate);
    }


/*********************************************************return web**********************************************************************/

    protected static function getLanguageWeb(){
        $header = getallheaders();
        if($header['Language'] == 'en'){
            $language = 'en_';
        }else{
            $language = '';
        }
        return $language;
    }



/*********************************************************return api**********************************************************************/


 
    /**
     * @param $value
     * @return array
     * 獲取二維碼
     */
    public function getQRCode($value){
        $img = new \QRcode();
        $values = $value; //二维码内容
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 6; // 生成图片大小
        $filename = rand(10000, 99999) . time() . '.jpg';
        $img->png($values, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        $uploadQRcode = $this->uploadQRcode($filename);
        if (!empty($uploadQRcode)) {
            @unlink($filename);//刪除生產二維碼
            return $uploadQRcode;
        } else {
            $file_path='';
            return $file_path;
        }

    }


    /**
     * @param $filename
     * @return array
     * 上傳二維碼
     */
    public function uploadQRcode($filename){
        $accessKeyId = config('services.aliyun_oss.access_key_id');
        $accessKeySecret = config('services.aliyun_oss.access_key_secret');
        $endpoint = config('services.aliyun_oss.endpoint');
        // 存储空间名称
        $bucket = config('services.aliyun_oss.bucket');
        $object = 'ewm/' . $filename;
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filename);
            $file_path=config('services.aliyun_oss.url') . '/' . $object;
            return $file_path;
        } catch (OssException $e) {
            return '';
        }
    }






    /**
     * 響應返回後再生成用戶二維碼並上傳 OSS，避免 OSS 上傳阻塞註冊接口
     * 注意：user_code 會有短暫空窗，異步生成完成後才寫入
     * @param int $userId
     * @param string $userNumber
     * @return void
     */
    public function queueUserQrCode($userId, $userNumber){
        $userId = (int)$userId;
        if ($userId <= 0 || empty($userNumber)) {
            return;
        }
        app()->terminating(function () use ($userId, $userNumber) {
            try {
                $userCode = $this->getQRCode($userNumber);
                if (!empty($userCode)) {
                    \App\Models\User::where('user_id', $userId)->update([
                        'user_code'  => $userCode,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error('生成用戶二維碼失敗: ' . $e->getMessage(), ['user_id' => $userId]);
            }
        });
    }



/*********************************************************END************************************************************************/


}
