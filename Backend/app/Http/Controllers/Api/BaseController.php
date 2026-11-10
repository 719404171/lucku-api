<?php
/*
 * File: BaseController.php
 * Project: NewDemo
 * Created Time: 2024-05-29 03:11:44 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-09-27 10:22:33 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\Clause;
use App\Http\Controllers\Auth\AdminTokenController;
use CoderCat\JWKToPEM\JWKConverter;
use Facebook\Facebook;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use OSS\OssClient;
use OSS\Core\OssException;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\SMSController;

/**
 * Class BaseController
 * @package App\Http\Controllers\Api
 * 一些公共函數
 */
class BaseController extends Controller{

    /**
     * @return array
     * 上傳圖片接口
     */
    public function uploadImage(Request $request){
        $accessKeyId = config('services.aliyun_oss.access_key_id');
        $accessKeySecret = config('services.aliyun_oss.access_key_secret');
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = config('services.aliyun_oss.endpoint');
        // 存储空间名称
        $bucket = config('services.aliyun_oss.bucket');
        //地址
        $htpps = config('services.aliyun_oss.url') . '/';
        // 文件名称
        $filePath = $request->file("file");
        $object = date("Y-m-d") . "/" . time() .rand(111111,999999). "." . $filePath->getClientOriginalExtension();
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filePath);
            $results['file_path']=$htpps.$object;
            return $this->success( __('api.picture_success'), $results);
        } catch (OssException $e) {
            return $this->Error(__('api.picture_error'), $e);
        }
    }


    
    /**
     * @return array
     * 發送短信驗證碼
     */
    public function sendMessage(Request $request){
        if($request->has('user_phone')){
            $areacode = $request->post('area_code');
            $phone = $areacode . $request->post('user_phone');
            $sms = new ChuanglanSmsApi();
            $code = mt_rand(100000, 999999);
            $name=config('services.basics.app_name');
            $result = $sms->sendInternational($phone, '['.$name.']您的驗證碼為：' . $code);
            if (!is_null(json_decode($result))) {
                $output = json_decode($result, true);
                if (isset($output['code']) && $output['code'] == '0') {
                    $results['code']=$code;
                    return $this->success( __('api.list_success'), $results);
                } else {
                    return $this->error(__('api.list_fail'));
                }
            } else {
                return $this->error(__('api.list_fail'));
            }
        }else{
            return $this->parametersError(__('api.param_error'));
        }
    }


    /**
     * @return array
     * 獲取用戶二維碼
     */
    public function getUserCode(){
        $user = auth('api')->user();
        if (empty($user->user_code) || is_null($user->user_code)) {
            $Users = new User();
            $base_name=config('services.basics.app_name');
            $CodeInfo=$base_name.":" . $user->user_id;
            $ewm = $this->getQRCode($CodeInfo);
            if (empty($ewm['results'])) {
                return $this->error(__('api.picture_error'));
            }
            DB::beginTransaction();
            $user_code=$ewm['results'];
            $info=$Users->where('user_id', $user->user_id)->update(['user_code' => $user_code]);
            if ($info) {
                DB::commit();
                $results['user_code']=$user_code;
                return $this->success( __('api.picture_success'), $results);
            } else {
                DB::rollBack();
                return $this->error(__('api.picture_error') );
            }
        } else {
            $results['user_code']=$user->user_code;
            return $this->success( __('api.picture_success'), $results);
        }

    }


    
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
        if (isset($uploadQRcode['status']) && $uploadQRcode['status'] == 200) {
            @unlink($filename);
            return $uploadQRcode;
        } else {
            $file_path='';
            return $this->success( __('api.picture_success'), $file_path);
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
            return $this->success( __('api.picture_success'), $file_path);
        } catch (OssException $e) {
            return $this->error(__('api.picture_error'), $e);
        }
    }



     /**
     * 保存图片到本地
     *
     */
      public function uploadAvatar(){
          try {
            if ( empty( app('global_shop_name') ) || empty( app('global_shop_logo') ) ) {
                return '../../../../../logo.png';
            }
            $imageUrl= app('global_shop_logo');//图片地址
            $name=app('global_shop_name').'.png';//图片名称
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
