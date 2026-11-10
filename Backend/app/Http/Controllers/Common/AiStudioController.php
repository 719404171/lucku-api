<?php
/*
 * File: AiStudioController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-30 4:15:36 pm
 * Modified By: WangYuQiang && WangpeiGang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Common;


use App\Http\Controllers\Controller;
use GuzzleHttp\Client;  

class AiStudioController extends Controller
{
    private $ApiEndpoint;
    private $ApiKey;

    public function __construct()
    {
        // 設定API端點和您的API密鑰
        $this->ApiURL = config('services.BaiDu.api_endpoint');
        $this->ApiKey = config('services.BaiDu.api_key');
    }




	/**
	 *百度獲取人工智能
	 */
	public function getAiStudio($info) {
		$result = $this->curlPost($this->ApiEndpoint , $data);
		return $result;
	}




	/**
	 * 通过CURL发送HTTP请求
	 * @param string $url  //请求URL
	 * @param array $postFields //请求参数
	 * @return mixed
	 */
	private function curlPost($url,$data){
		$data = json_encode($data);
		$ch = curl_init ();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json; charset=utf-8; ' ,
            'Authorization: Bearer ' . $this->ApiKey,
			)
		);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt( $ch, CURLOPT_TIMEOUT,10);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec ( $ch );
        // if (false == $response) {
        //     $result = curl_error(  $ch);
        // } else {
        //     $rsp = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
        //     if (200 !== $rsp) {
        //         $result = "请求状态 ". $rsp . " " . curl_error($ch);
        //     } else {
        //         $result = $response;
        //     }
        // }
		curl_close ( $ch );
		return $response;
	}














}
