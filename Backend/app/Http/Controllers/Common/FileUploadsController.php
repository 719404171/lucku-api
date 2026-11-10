<?php
/*
 * File: FileUploadsController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-30 4:15:57 pm
 * Modified By: WangYuQiang && WangpeiGang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Common;


use CoderCat\JWKToPEM\JWKConverter;
use Facebook\Facebook;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use OSS\OssClient;
use OSS\Core\OssException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
/**
 * Class BaseController
 * @package App\Http\Controllers\Api
 * 图片上传 和 内容转为二维码
 */
class FileUploadsController
{

    //上傳圖片接口
    public function uploadImage($filePath)
    {
        $accessKeyId = config('services.aliyun_oss.access_key_id');
        $accessKeySecret = config('services.aliyun_oss.access_key_secret');
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = config('services.aliyun_oss.endpoint');
        // 存储空间名称
        $bucket = config('services.aliyun_oss.bucket');
        // 文件名称
        // $filePath = $request->file("file");
        $object = date("Y-m-d") . "/" . time() . "." . $filePath->getClientOriginalExtension();
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filePath);
            return $this->Success(["file_path" =>config('services.aliyun_oss.url') . '/' . $object]);
        } catch (OssException $e) {
            return $this->Error();
        }
    }


    /**
     * @param $value
     * @return array
     * 獲取二維碼
     */
    public function getQRCode($value)
    {
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
            return $this->success(["file_path" => '']);
        }

    }


    /**
     * @param $filename
     * @return array
     * 上傳二維碼
     */
    public function uploadQRcode($filename)
    {
        $accessKeyId = config('services.aliyun_oss.access_key_id');
        $accessKeySecret = config('services.aliyun_oss.access_key_secret');
        $endpoint = config('services.aliyun_oss.endpoint');
        // 存储空间名称
        $bucket = config('services.aliyun_oss.bucket');
        $object = 'ewm/' . $filename;
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filename);
            return $this->success(["file_path" => config('services.aliyun_oss.url') . '/' . $object]);
        } catch (OssException $e) {
            return $this->Error($e);
        }
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
